<?php

namespace Tests\Feature;

use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Domain\Restaurant\Actions\CreateKitchenInventoryClosingTask;
use App\Domain\Restaurant\Models\ContificoDocument;
use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Domain\Restaurant\Models\KitchenInventoryDailyStart;
use App\Domain\Restaurant\Models\KitchenInventoryMovement;
use App\Domain\Restaurant\Models\KitchenInventoryProductMapping;
use App\Domain\Restaurant\Models\StandardRecipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KitchenInventoryReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_action_creates_one_closing_for_previous_operating_day_at_one_am(): void
    {
        $this->createArea('Cocina / Restaurante', 'restaurant');
        $item = $this->createStockItem();

        $first = app(CreateKitchenInventoryClosingTask::class)->handle(Carbon::parse('2026-04-28 01:00:00', 'America/Guayaquil'));
        $second = app(CreateKitchenInventoryClosingTask::class)->handle(Carbon::parse('2026-04-28 01:15:00', 'America/Guayaquil'));

        $this->assertSame($first->id, $second->id);
        $this->assertSame('2026-04-27', $first->operating_date->toDateString());
        $this->assertDatabaseCount('kitchen_inventory_closings', 1);
        $this->assertDatabaseHas('operational_tasks', [
            'type' => 'kitchen_inventory_closing',
            'title' => 'Cierre y Reposición',
        ]);
        $this->assertDatabaseHas('kitchen_inventory_closing_items', [
            'kitchen_daily_stock_item_id' => $item->id,
            'product_name_snapshot' => 'Arroz',
        ]);
    }

    public function test_nochero_with_kitchen_area_gets_blind_count_payload(): void
    {
        [$user] = $this->createNightKitchenEmployee();
        $this->createStockItem();
        $closing = app(CreateKitchenInventoryClosingTask::class)->handle(Carbon::parse('2026-04-28 01:00:00', 'America/Guayaquil'));

        $this->actingAs($user);

        $this->get('/operativo?area=restaurant')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employee/operations')
                ->where('tasks.0.kitchenClosing.id', $closing->id)
                ->where('tasks.0.kitchenClosing.items.0.productName', 'Arroz')
                ->missing('tasks.0.kitchenClosing.items.0.targetStock')
                ->missing('tasks.0.kitchenClosing.items.0.theoreticalFinal')
                ->missing('tasks.0.kitchenClosing.items.0.discrepancy')
                ->missing('tasks.0.kitchenClosing.items.0.replenishmentRequired'));
    }

    public function test_count_and_replenishment_flow_calculates_discrepancy_alerts_and_next_initial(): void
    {
        [$user] = $this->createNightKitchenEmployee();
        $stockItem = $this->createStockItem(target: 14);
        $recipeItem = $this->createRecipeForStockConsumption();
        KitchenInventoryProductMapping::create([
            'restaurant_standard_recipe_item_id' => $recipeItem->id,
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);
        KitchenInventoryDailyStart::create([
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'inventory_date' => '2026-04-27',
            'quantity' => 10,
            'source' => 'test',
        ]);
        KitchenInventoryMovement::create([
            'source_id' => 'transfer-day',
            'movement_date' => '2026-04-27',
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'product_name' => 'Arroz',
            'normalized_product_name' => 'arroz',
            'type' => 'egreso',
            'area' => 'COCINA',
            'from_location' => 'BODEGA',
            'to_location' => 'COCINA',
            'quantity' => 4,
        ]);
        KitchenInventoryMovement::create([
            'source_id' => 'replenishment-next',
            'movement_date' => '2026-04-28',
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'product_name' => 'Arroz',
            'normalized_product_name' => 'arroz',
            'type' => 'egreso',
            'area' => 'COCINA',
            'from_location' => 'BODEGA',
            'to_location' => 'COCINA',
            'quantity' => 3,
        ]);
        $this->createSale();
        $closing = app(CreateKitchenInventoryClosingTask::class)->handle(Carbon::parse('2026-04-28 01:00:00', 'America/Guayaquil'));

        $this->actingAs($user);

        $this->post(route('employee.kitchen-closings.count', $closing), [
            'items' => [
                [
                    'stock_item_id' => $stockItem->id,
                    'physical_count' => 9,
                    'waste_quantity' => 1,
                    'notes' => 'Conteo real',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('kitchen_inventory_closing_items', [
            'kitchen_inventory_closing_id' => $closing->id,
            'initial_quantity' => '10.0000',
            'transfer_quantity' => '4.0000',
            'theoretical_consumption' => '3.0000',
            'theoretical_final' => '11.0000',
            'physical_count' => '9.0000',
            'discrepancy' => '-2.0000',
            'replenishment_required' => '5.0000',
            'has_negative_discrepancy' => true,
        ]);

        $this->post(route('employee.kitchen-closings.replenishment', $closing))->assertRedirect();

        $this->assertDatabaseHas('kitchen_inventory_closing_items', [
            'kitchen_inventory_closing_id' => $closing->id,
            'replenishment_actual' => '3.0000',
            'next_initial_quantity' => '12.0000',
            'has_replenishment_alert' => true,
        ]);
        $this->assertDatabaseHas('kitchen_inventory_daily_starts', [
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'inventory_date' => '2026-04-28 00:00:00',
            'quantity' => 12,
        ]);
        $this->assertSame(OperationalTask::STATUS_COMPLETED, $closing->task->refresh()->status);
    }

    public function test_management_analysis_renders_weekly_cycle_and_mapping_update(): void
    {
        $manager = $this->createAssignedManager();
        $stockItem = $this->createStockItem();
        $recipeItem = $this->createRecipeForStockConsumption();
        app(CreateKitchenInventoryClosingTask::class)->handle(Carbon::parse('2026-04-28 01:00:00', 'America/Guayaquil'));

        $this->actingAs($manager);

        $this->get('/restaurant/analysis?week=2026-04-27')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('restaurant/inventory-analysis')
                ->where('week.start', '2026-04-27')
                ->has('days', 7)
                ->where('mappings.stockItems.0.id', $stockItem->id)
                ->where('mappings.recipeItems.0.id', $recipeItem->id));

        $this->patch(route('kitchen-inventory-mappings.update', $recipeItem), [
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'conversion_factor' => 0.5,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('kitchen_inventory_product_mappings', [
            'restaurant_standard_recipe_item_id' => $recipeItem->id,
            'kitchen_daily_stock_item_id' => $stockItem->id,
            'conversion_factor' => '0.500000',
            'is_active' => true,
        ]);
    }

    private function createStockItem(float $target = 14): KitchenDailyStockItem
    {
        return KitchenDailyStockItem::create([
            'category' => 'OTROS',
            'product_name' => 'Arroz',
            'target_stock' => $target,
            'unit' => 'LB',
            'unit_detail' => null,
            'is_active' => true,
            'imported_at' => now(),
        ]);
    }

    private function createRecipeForStockConsumption()
    {
        $recipe = StandardRecipe::create([
            'dish_code' => 'p1',
            'dish_name' => 'Menu arroz',
            'is_active' => true,
            'imported_at' => now(),
        ]);

        return $recipe->items()->create([
            'sort_order' => 1,
            'inventory_product_name' => 'Arroz',
            'quantity_used' => 1.5,
            'unit' => 'LB',
        ]);
    }

    private function createSale(): ContificoDocument
    {
        return ContificoDocument::create([
            'external_id' => 'sale-1',
            'tipo_registro' => 'CLI',
            'tipo_documento' => 'FAC',
            'documento' => '001-001',
            'estado' => 'C',
            'anulado' => false,
            'fecha_emision' => '2026-04-27',
            'total' => 20,
            'saldo' => 0,
            'servicio' => 0,
            'detalles' => [
                ['producto_id' => 'p1', 'producto_nombre' => 'Menu arroz', 'cantidad' => 2, 'precio' => 10],
            ],
            'cobros' => [],
            'raw' => [],
            'synced_at' => now(),
        ]);
    }

    /**
     * @return array{0: User, 1: Area, 2: Area}
     */
    private function createNightKitchenEmployee(): array
    {
        $user = User::factory()->create(['operational_status' => 'active']);
        $restaurant = $this->createArea('Cocina / Restaurante', 'restaurant');
        $night = $this->createArea('Nochero', 'night_auditor');

        foreach ([$restaurant, $night] as $area) {
            $user->areas()->attach($area->id, [
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        }

        return [$user, $restaurant, $night];
    }

    private function createAssignedManager(): User
    {
        $user = User::factory()->create(['operational_status' => 'active']);
        $area = $this->createArea('Gerencia', 'management');

        $user->areas()->attach($area->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function createArea(string $name, string $slug): Area
    {
        return Area::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $name,
                'is_active' => true,
            ],
        );
    }
}
