<?php

namespace Tests\Feature;

use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_employee_can_visit_mobile_operational_portal(): void
    {
        $user = User::factory()->create([
            'name' => 'Cocinero',
            'operational_status' => 'active',
        ]);
        $area = $this->createArea('Cocina / Restaurante', 'restaurant');
        $event = OperationalEvent::create([
            'area_id' => $area->id,
            'created_by' => $user->id,
            'type' => 'restaurant_service',
            'source' => 'test',
            'title' => 'Almuerzo corporativo',
            'description' => '20 pax - Menu ejecutivo',
            'status' => 'pending',
            'severity' => 'normal',
            'starts_at' => today()->setTime(12, 0),
            'payload' => [
                'critical_supplies' => [
                    ['name' => 'Proteina', 'quantity' => '20 porciones', 'status' => 'Pendiente'],
                ],
            ],
        ]);
        OperationalTask::create([
            'operational_event_id' => $event->id,
            'assigned_area_id' => $area->id,
            'created_by' => $user->id,
            'title' => 'Validar mise en place',
            'description' => 'Estacion caliente',
            'status' => OperationalTask::STATUS_PENDING,
            'priority' => 'normal',
            'due_at' => today()->setTime(11, 0),
        ]);
        OperationalForm::create([
            'area_id' => $area->id,
            'slug' => 'kitchen-supply-shortage',
            'name' => 'Reportar faltante de insumo',
            'context' => 'shortage',
            'status' => 'active',
            'schema' => ['fields' => [['name' => 'supply', 'type' => 'text']]],
        ]);

        $user->areas()->attach($area->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->get('/operativo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employee/operations')
                ->where('employee.name', 'Cocinero')
                ->where('employee.areas.0.slug', 'restaurant')
                ->where('activeArea.slug', 'restaurant')
                ->has('events', 1)
                ->has('tasks', 1)
                ->has('forms', 1)
                ->where('summary.pending', 1));
    }

    public function test_area_assigned_task_can_be_completed_by_any_employee_in_that_area(): void
    {
        [$user, $area] = $this->createKitchenEmployee();
        $task = OperationalTask::create([
            'assigned_area_id' => $area->id,
            'created_by' => $user->id,
            'title' => 'Confirmar menu',
            'status' => OperationalTask::STATUS_PENDING,
            'priority' => 'normal',
        ]);

        $this->actingAs($user);

        $this->patch(route('employee.tasks.complete', $task), [
            'notes' => 'Listo',
        ])->assertRedirect();

        $task->refresh();

        $this->assertSame(OperationalTask::STATUS_COMPLETED, $task->status);
        $this->assertSame($user->id, $task->completed_by);
        $this->assertNotNull($task->completed_at);
    }

    public function test_critical_task_moves_to_pending_validation_when_completed(): void
    {
        [$user, $area] = $this->createKitchenEmployee();
        $task = OperationalTask::create([
            'assigned_area_id' => $area->id,
            'created_by' => $user->id,
            'title' => 'Revisar insumos criticos',
            'status' => OperationalTask::STATUS_PENDING,
            'priority' => 'high',
            'requires_validation' => true,
        ]);

        $this->actingAs($user);

        $this->patch(route('employee.tasks.complete', $task))->assertRedirect();

        $this->assertSame(OperationalTask::STATUS_PENDING_VALIDATION, $task->refresh()->status);
    }

    public function test_area_lead_can_validate_pending_task_for_their_area(): void
    {
        [$lead, $area] = $this->createKitchenEmployee(isLead: true);
        $task = OperationalTask::create([
            'assigned_area_id' => $area->id,
            'created_by' => $lead->id,
            'title' => 'Validar mise en place',
            'status' => OperationalTask::STATUS_PENDING_VALIDATION,
            'priority' => 'high',
            'requires_validation' => true,
        ]);

        $this->actingAs($lead);

        $this->patch(route('employee.tasks.validate', $task), [
            'decision' => 'validated',
            'notes' => 'Correcto',
        ])->assertRedirect();

        $task->refresh();

        $this->assertSame(OperationalTask::STATUS_VALIDATED, $task->status);
        $this->assertSame($lead->id, $task->validated_by);
        $this->assertNotNull($task->validated_at);
    }

    public function test_non_lead_employee_cannot_validate_area_task(): void
    {
        [$user, $area] = $this->createKitchenEmployee();
        $task = OperationalTask::create([
            'assigned_area_id' => $area->id,
            'created_by' => $user->id,
            'title' => 'Validacion restringida',
            'status' => OperationalTask::STATUS_PENDING_VALIDATION,
            'priority' => 'high',
            'requires_validation' => true,
        ]);

        $this->actingAs($user);

        $this->patch(route('employee.tasks.validate', $task), [
            'decision' => 'validated',
        ])->assertForbidden();

        $this->assertSame(OperationalTask::STATUS_PENDING_VALIDATION, $task->refresh()->status);
    }

    public function test_employee_can_submit_active_form_for_their_area(): void
    {
        [$user, $area] = $this->createKitchenEmployee();
        $form = OperationalForm::create([
            'area_id' => $area->id,
            'slug' => 'daily-check',
            'name' => 'Chequeo diario',
            'context' => 'daily',
            'status' => 'active',
            'schema' => ['fields' => [['name' => 'temperature', 'type' => 'number']]],
        ]);

        $this->actingAs($user);

        $this->post(route('employee.forms.entries.store', $form), [
            'fields' => ['temperature' => '4'],
            'notes' => 'Sin novedades',
        ])->assertRedirect();

        $this->assertDatabaseHas('operational_entries', [
            'operational_form_id' => $form->id,
            'area_id' => $area->id,
            'user_id' => $user->id,
            'status' => 'submitted',
        ]);
    }

    public function test_employee_cannot_complete_task_assigned_to_another_employee(): void
    {
        [$user, $area] = $this->createKitchenEmployee();
        $otherUser = User::factory()->create(['operational_status' => 'active']);
        $otherUser->areas()->attach($area->id, ['assigned_at' => now(), 'is_active' => true]);
        $task = OperationalTask::create([
            'assigned_area_id' => $area->id,
            'assigned_user_id' => $otherUser->id,
            'created_by' => $user->id,
            'title' => 'Tarea personal',
            'status' => OperationalTask::STATUS_PENDING,
            'priority' => 'normal',
        ]);

        $this->actingAs($user);

        $this->patch(route('employee.tasks.complete', $task))->assertForbidden();

        $this->assertSame(OperationalTask::STATUS_PENDING, $task->refresh()->status);
    }

    public function test_reporting_supply_shortage_creates_event_inventory_task_notification_and_entry(): void
    {
        [$user, $restaurant] = $this->createKitchenEmployee();
        $inventory = $this->createArea('Inventario / Bodega', 'inventory');
        $this->createArea('Gerencia', 'management');
        OperationalForm::create([
            'area_id' => $restaurant->id,
            'slug' => 'kitchen-supply-shortage',
            'name' => 'Reportar faltante de insumo',
            'context' => 'shortage',
            'status' => 'active',
            'schema' => ['fields' => [['name' => 'supply', 'type' => 'text']]],
        ]);

        $this->actingAs($user);

        $this->post(route('employee.kitchen.shortages.store'), [
            'supply' => 'Arroz',
            'quantity' => '10 kg',
            'notes' => 'Necesario para evento',
        ])->assertRedirect();

        $this->assertDatabaseHas('operational_events', [
            'type' => 'supply_shortage',
            'area_id' => $restaurant->id,
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('operational_tasks', [
            'assigned_area_id' => $inventory->id,
            'type' => 'supply_restock',
            'requires_validation' => true,
        ]);
        $this->assertDatabaseHas('operational_notifications', [
            'area_id' => $inventory->id,
            'type' => 'urgent',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('operational_entries', [
            'area_id' => $restaurant->id,
            'user_id' => $user->id,
            'status' => 'submitted',
        ]);
    }

    public function test_kitchen_stock_catalog_is_visible_without_target_stock_for_blind_count(): void
    {
        [$user] = $this->createKitchenEmployee();
        KitchenDailyStockItem::create([
            'category' => 'OTROS',
            'product_name' => 'Arroz',
            'target_stock' => 14,
            'unit' => 'LB',
            'unit_detail' => null,
            'is_active' => true,
            'imported_at' => now(),
        ]);

        $this->actingAs($user);

        $this->get('/operativo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employee/operations')
                ->where('kitchenStockCatalog.0.productName', 'Arroz')
                ->where('kitchenStockCatalog.0.unit', 'LB')
                ->missing('kitchenStockCatalog.0.target_stock')
                ->missing('kitchenStockCatalog.0.targetStock'));
    }

    /**
     * @return array{0: User, 1: Area}
     */
    private function createKitchenEmployee(bool $isLead = false): array
    {
        $user = User::factory()->create(['operational_status' => 'active']);
        $area = $this->createArea('Cocina / Restaurante', 'restaurant');

        $user->areas()->attach($area->id, [
            'assigned_at' => now(),
            'is_active' => true,
            'is_lead' => $isLead,
        ]);

        return [$user, $area];
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
