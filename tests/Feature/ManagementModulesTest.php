<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManagementModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_assigned_users_can_visit_management_modules(): void
    {
        $user = $this->createAssignedManager();

        $this->actingAs($user);

        collect(['/logbook', '/events', '/analytics', '/restaurant', '/employees'])
            ->each(fn (string $path) => $this->get($path)->assertOk());
    }

    public function test_employees_module_lists_pending_registered_users(): void
    {
        $manager = $this->createAssignedManager();
        User::factory()->create([
            'name' => 'Nuevo Empleado',
            'email' => 'nuevo@example.com',
            'operational_status' => 'pending_area_assignment',
        ]);

        $this->actingAs($manager);

        $this->get('/employees')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/index')
                ->where('summary.total', 2)
                ->where('summary.pending', 1)
                ->has('employees', 2)
                ->where('employees.0.email', 'nuevo@example.com'));
    }

    public function test_management_can_assign_areas_to_pending_employee(): void
    {
        $manager = $this->createAssignedManager();
        $employee = User::factory()->create(['operational_status' => 'pending_area_assignment']);
        $reception = Area::create([
            'name' => 'Recepcion',
            'slug' => 'reception',
            'description' => 'Llegadas y salidas.',
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        $this->patch(route('employees.areas.update', $employee), [
            'area_ids' => [$reception->id],
        ])->assertRedirect();

        $employee->refresh();

        $this->assertSame('active', $employee->operational_status);
        $this->assertTrue($employee->areas()->whereKey($reception->id)->exists());
        $this->assertTrue($employee->hasRole('employee'));
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
