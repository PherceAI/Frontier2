<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_without_area_are_redirected_to_pending_assignment()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertRedirect(route('assignment.pending', absolute: false));
    }

    public function test_authenticated_users_with_area_can_visit_the_dashboard()
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

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
    }

    public function test_non_management_employees_are_redirected_away_from_the_dashboard()
    {
        $user = User::factory()->create(['operational_status' => 'active']);
        $area = Area::create([
            'name' => 'Cocina / Restaurante',
            'slug' => 'restaurant',
            'description' => 'Menus y produccion.',
            'is_active' => true,
        ]);

        $user->areas()->attach($area->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->get('/dashboard')->assertRedirect(route('employee.home', absolute: false));
    }
}
