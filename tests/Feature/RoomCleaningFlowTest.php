<?php

namespace Tests\Feature;

use App\Domain\Housekeeping\Actions\AssignDailyRoomCleanings;
use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Housekeeping\Models\HousekeepingTaskNote;
use App\Domain\Housekeeping\Models\RoomCleaningSetting;
use App\Domain\Organization\Models\Area;
use App\Domain\Rooms\Models\RoomOccupancySnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoomCleaningFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-30 07:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_generates_checkout_stay_and_monday_carryover_without_duplicates(): void
    {
        $roomsArea = $this->createArea('Habitaciones', 'rooms');
        $employee = $this->createEmployee($roomsArea, 'Ana');
        $this->createEmployee($roomsArea, 'Bea');
        $monday = Carbon::parse('2026-05-04', 'America/Guayaquil');

        $this->snapshot('101', '2026-05-04', '2026-05-01', '2026-05-04');
        $this->snapshot('201', '2026-05-04', '2026-05-02', '2026-05-06');
        $this->snapshot('301', '2026-05-04', '2026-05-03', '2026-05-06');
        $this->snapshot('401', '2026-05-03', '2026-05-01', '2026-05-03');

        $first = app(AssignDailyRoomCleanings::class)->handle($monday);
        $second = app(AssignDailyRoomCleanings::class)->handle($monday);

        $this->assertSame(3, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertTrue(HousekeepingTask::query()
            ->where('cleaning_type', HousekeepingTask::CLEANING_TYPE_CHECKOUT)
            ->whereDate('generated_for_date', '2026-05-03')
            ->exists());
        $this->assertDatabaseHas('housekeeping_tasks', [
            'cleaning_type' => HousekeepingTask::CLEANING_TYPE_STAY,
            'status' => HousekeepingTask::STATUS_PENDING,
        ]);
        $this->assertSame(3, HousekeepingTask::query()->count());
        $this->assertTrue(HousekeepingTask::query()->where('assigned_to', $employee->id)->exists());
    }

    public function test_disabled_automatic_assignment_skips_unforced_generation(): void
    {
        RoomCleaningSetting::current()->forceFill(['auto_assignment_enabled' => false])->save();
        $this->snapshot('101', '2026-04-30', '2026-04-28', '2026-04-30');

        $result = app(AssignDailyRoomCleanings::class)->handle(Carbon::parse('2026-04-30', 'America/Guayaquil'));

        $this->assertTrue($result['skipped']);
        $this->assertSame('auto_assignment_disabled', $result['reason']);
        $this->assertDatabaseCount('housekeeping_tasks', 0);
    }

    public function test_floor_zone_assignment_uses_only_active_rooms_area_employees(): void
    {
        $roomsArea = $this->createArea('Habitaciones', 'rooms');
        $inactive = $this->createEmployee($roomsArea, 'Inactivo', activePivot: false);
        $active = $this->createEmployee($roomsArea, 'Activo');
        $restaurant = $this->createArea('Cocina / Restaurante', 'restaurant');
        $otherAreaEmployee = $this->createEmployee($restaurant, 'Cocina');

        $this->snapshot('101', '2026-04-30', '2026-04-28', '2026-04-30');
        $this->snapshot('201', '2026-04-30', '2026-04-28', '2026-04-30');

        app(AssignDailyRoomCleanings::class)->handle(Carbon::parse('2026-04-30', 'America/Guayaquil'));

        $assignedIds = HousekeepingTask::query()->pluck('assigned_to')->unique()->values()->all();

        $this->assertSame([$active->id], $assignedIds);
        $this->assertFalse(HousekeepingTask::query()->where('assigned_to', $inactive->id)->exists());
        $this->assertFalse(HousekeepingTask::query()->where('assigned_to', $otherAreaEmployee->id)->exists());
    }

    public function test_employee_sees_assigned_rooms_and_can_complete_with_novelty(): void
    {
        $roomsArea = $this->createArea('Habitaciones', 'rooms');
        $employee = $this->createEmployee($roomsArea, 'Camarero');
        $this->snapshot('101', '2026-04-30', '2026-04-28', '2026-04-30');
        app(AssignDailyRoomCleanings::class)->handle(Carbon::parse('2026-04-30', 'America/Guayaquil'));
        $task = HousekeepingTask::query()->firstOrFail();

        $this->actingAs($employee);

        $this->get('/operativo?area=rooms')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employee/operations')
                ->where('activeArea.slug', 'rooms')
                ->where('roomCleanings.0.roomNumber', '101'));

        $this->patch(route('employee.room-cleaning.complete', $task), [
            'notes' => 'Habitacion lista',
        ])->assertRedirect();

        $this->post(route('employee.room-cleaning.notes.store', $task), [
            'severity' => HousekeepingTaskNote::SEVERITY_URGENT,
            'body' => 'Falta revisar aire acondicionado',
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame(HousekeepingTask::STATUS_COMPLETED, $task->status);
        $this->assertSame($employee->id, $task->completed_by);
        $this->assertDatabaseHas('housekeeping_task_notes', [
            'housekeeping_task_id' => $task->id,
            'severity' => HousekeepingTaskNote::SEVERITY_URGENT,
            'body' => 'Falta revisar aire acondicionado',
        ]);
    }

    public function test_management_can_reassign_and_employee_cannot_manage_assignments(): void
    {
        $management = $this->createArea('Gerencia', 'management');
        $roomsArea = $this->createArea('Habitaciones', 'rooms');
        $manager = $this->createEmployee($management, 'Gerente');
        $employee = $this->createEmployee($roomsArea, 'Camarero');
        $this->snapshot('101', '2026-04-30', '2026-04-28', '2026-04-30');
        app(AssignDailyRoomCleanings::class)->handle(Carbon::parse('2026-04-30', 'America/Guayaquil'));
        $task = HousekeepingTask::query()->firstOrFail();

        $this->actingAs($manager);
        $this->patch(route('rooms.cleaning.tasks.update', $task), [
            'assigned_to' => $employee->id,
            'status' => HousekeepingTask::STATUS_IN_PROGRESS,
            'notes' => 'Prioridad alta',
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame($employee->id, $task->assigned_to);
        $this->assertSame(HousekeepingTask::STATUS_IN_PROGRESS, $task->status);
        $this->assertSame('Prioridad alta', $task->notes);

        $this->actingAs($employee);
        $this->patch(route('rooms.cleaning.tasks.update', $task), [
            'assigned_to' => null,
            'status' => HousekeepingTask::STATUS_PENDING,
        ])->assertRedirect(route('employee.home'));
    }

    private function snapshot(string $roomNumber, string $occupancyDate, string $checkIn, string $checkOut): RoomOccupancySnapshot
    {
        return RoomOccupancySnapshot::create([
            'occupancy_date' => $occupancyDate,
            'room_number' => $roomNumber,
            'room_type' => 'standard',
            'floor' => (int) substr($roomNumber, 0, 1),
            'status' => 'Ocupada',
            'is_occupied' => true,
            'guest_name' => "Huesped {$roomNumber}",
            'reservation_code' => "R-{$roomNumber}",
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'synced_at' => now(),
        ]);
    }

    private function createEmployee(Area $area, string $name, bool $activePivot = true): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'operational_status' => 'active',
        ]);

        $user->areas()->attach($area->id, [
            'assigned_at' => now(),
            'is_active' => $activePivot,
        ]);

        return $user;
    }

    private function createArea(string $name, string $slug): Area
    {
        return Area::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $name,
            'is_active' => true,
        ]);
    }
}
