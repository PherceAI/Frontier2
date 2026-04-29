<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Area;
use App\Domain\Rooms\Actions\GetRoomsOverview;
use App\Domain\Rooms\Actions\SyncLegacyRoomOccupancy;
use App\Domain\Rooms\Integrations\LegacySupabaseOccupancyClient;
use App\Domain\Rooms\Models\RoomOccupancySnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoomsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-29 08:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_rooms_dashboard_counts_catalog_and_occupancy(): void
    {
        RoomOccupancySnapshot::create([
            'occupancy_date' => today(),
            'room_number' => '101',
            'room_type' => 'standard',
            'floor' => 1,
            'status' => 'Ocupada',
            'is_occupied' => true,
            'guest_name' => 'Ana Perez',
            'company_name' => 'Empresa Andina',
            'synced_at' => now(),
        ]);

        $dashboard = app(GetRoomsOverview::class)->handle();

        $this->assertSame(81, $dashboard['summary']['total']);
        $this->assertSame(1, $dashboard['summary']['occupied']);
        $this->assertSame(80, $dashboard['summary']['available']);
        $this->assertSame('101', $dashboard['rooms'][0]['number']);
        $this->assertSame('occupied', $dashboard['rooms'][0]['status']);
    }

    public function test_rooms_page_renders_for_management_users(): void
    {
        $this->withoutVite();

        $manager = $this->createAssignedManager();
        $this->actingAs($manager);

        $this->get('/rooms')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('rooms/dashboard')
                ->where('dashboard.summary.total', 81));
    }

    public function test_legacy_occupancy_sync_normalizes_supabase_rows(): void
    {
        $this->app->bind(LegacySupabaseOccupancyClient::class, fn () => new class extends LegacySupabaseOccupancyClient
        {
            public function occupancyFor(Carbon $date): Collection
            {
                return collect([
                    [
                        'habitacion' => '101',
                        'estado' => 'Ocupada',
                        'huesped' => 'Ana Perez',
                        'empresa' => 'Empresa Andina',
                        'reserva' => 'R-100',
                        'fecha_llegada' => '29/04/2026',
                        'fecha_salida' => '30/04/2026',
                        'adultos' => '2',
                    ],
                    [
                        'habitacion' => '999',
                        'estado' => 'Ocupada',
                    ],
                ]);
            }
        });

        $result = app(SyncLegacyRoomOccupancy::class)->handle(today());

        $this->assertSame(1, $result['rows']);
        $this->assertSame(1, $result['occupied']);
        $this->assertDatabaseHas('room_occupancy_snapshots', [
            'room_number' => '101',
            'guest_name' => 'Ana Perez',
            'company_name' => 'Empresa Andina',
            'reservation_code' => 'R-100',
            'is_occupied' => true,
        ]);
    }

    private function createAssignedManager(): User
    {
        $user = User::factory()->create(['operational_status' => 'active']);
        $area = Area::create([
            'name' => 'Gerencia',
            'slug' => 'management',
            'description' => 'Control operacional.',
            'is_active' => true,
        ]);

        $user->areas()->attach($area->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        return $user;
    }
}
