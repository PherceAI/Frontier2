<?php

namespace Tests\Feature;

use App\Domain\Operations\Actions\CreateOperationalEvent;
use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_event_creates_related_tasks_and_notifications(): void
    {
        $manager = User::factory()->create(['operational_status' => 'active']);
        $restaurant = $this->createArea('Cocina / Restaurante', 'restaurant');
        $inventory = $this->createArea('Inventario / Bodega', 'inventory');

        $event = app(CreateOperationalEvent::class)->handle($manager, [
            'area_id' => $restaurant->id,
            'type' => 'event_menu_confirmation',
            'source' => 'management',
            'title' => 'Confirmar menu evento',
            'status' => 'pending',
            'severity' => 'normal',
            'starts_at' => now(),
        ], [
            [
                'assigned_area_id' => $inventory->id,
                'type' => 'supply_check',
                'title' => 'Revisar insumos para evento',
                'priority' => 'high',
                'requires_validation' => true,
            ],
        ], [
            [
                'area_id' => $inventory->id,
                'type' => 'event_task',
                'channel' => 'in_app',
                'title' => 'Nueva tarea de evento',
            ],
        ]);

        $this->assertDatabaseHas('operational_events', [
            'id' => $event->id,
            'type' => 'event_menu_confirmation',
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('operational_tasks', [
            'operational_event_id' => $event->id,
            'assigned_area_id' => $inventory->id,
            'status' => OperationalTask::STATUS_PENDING,
            'requires_validation' => true,
        ]);
        $this->assertDatabaseHas('operational_notifications', [
            'operational_event_id' => $event->id,
            'area_id' => $inventory->id,
            'status' => 'pending',
        ]);
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
