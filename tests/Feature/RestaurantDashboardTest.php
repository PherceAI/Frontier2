<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Area;
use App\Domain\Restaurant\Actions\GetRestaurantDashboard;
use App\Domain\Restaurant\Actions\ImportStandardRecipesFromCsv;
use App\Domain\Restaurant\Integrations\ContificoClient;
use App\Domain\Restaurant\Models\ContificoDocument;
use App\Domain\Restaurant\Models\StandardRecipe;
use App\Domain\Restaurant\Models\StandardRecipeItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RestaurantDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-28 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_restaurant_dashboard_aggregates_contifico_documents_without_tips(): void
    {
        $this->createDocument([
            'external_id' => 'cli-1',
            'tipo_registro' => 'CLI',
            'total' => 110,
            'vendedor_id' => 'ml',
            'vendedor_nombre' => 'Maria Lopez',
            'detalles' => [
                ['producto_id' => 'p1', 'producto_nombre' => 'Lomo al jugo', 'cantidad' => 2, 'precio' => 50],
            ],
            'cobros' => [
                ['forma_cobro' => 'TC', 'monto' => 110, 'monto_propina' => 10],
            ],
        ]);
        $this->createDocument([
            'external_id' => 'pro-1',
            'tipo_registro' => 'PRO',
            'total' => 40,
            'estado' => 'P',
            'saldo' => 40,
            'persona_nombre' => 'Distribuidora Andina',
            'fecha_vencimiento' => '2026-04-30',
        ]);

        $dashboard = app(GetRestaurantDashboard::class)->handle('today');

        $this->assertSame(100.0, $dashboard['summary']['salesTotal']);
        $this->assertSame(100.0, $dashboard['summary']['averageTicket']);
        $this->assertSame(60.0, $dashboard['summary']['grossMargin']);
        $this->assertSame(60.0, $dashboard['summary']['grossMarginPercent']);
        $this->assertSame(100.0, $dashboard['paymentDistribution'][0]['amount']);
        $this->assertSame('Lomo al jugo', $dashboard['topDishes'][0]['name']);
        $this->assertSame(40.0, $dashboard['supplierPurchases']['dueSoonTotal']);
        $this->assertSame(1, $dashboard['supplierPurchases']['dueSoonCount']);
        $this->assertSame(40.0, $dashboard['accountsPayable']['total']);
        $this->assertSame(1, $dashboard['accountsPayable']['dueNext7Count']);
        $this->assertSame('Distribuidora Andina', $dashboard['accountsPayable']['items'][0]['supplier']);
    }

    public function test_restaurant_dashboard_page_renders_for_management_users(): void
    {
        $this->withoutVite();

        $manager = $this->createAssignedManager();
        $this->actingAs($manager);

        $this->get('/restaurant?period=month')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('restaurant/dashboard')
                ->where('dashboard.period', 'month'));
    }

    public function test_contifico_client_paginates_until_last_partial_page(): void
    {
        Config::set('services.contifico.api_key', 'secret');
        Config::set('services.contifico.base_url', 'https://api.contifico.com/sistema/api/v1');

        Http::fake([
            'api.contifico.com/*' => Http::sequence()
                ->push(array_fill(0, 2, ['id' => 'doc']), 200)
                ->push([['id' => 'last']], 200),
        ]);

        $documents = app(ContificoClient::class)->documents([
            'tipo_registro' => 'CLI',
            'result_size' => 2,
        ]);

        $this->assertCount(3, $documents);
        Http::assertSentCount(2);
    }

    public function test_standard_recipes_can_be_imported_rendered_and_edited(): void
    {
        $this->withoutVite();

        $csv = implode("\n", [
            'ID_PLATO,NOMBRE_PLATO,CATEGORIA,SUBCATEGORIA,ID_PRODUCTO_INVENTARIO,NOMBRE_PRODUCTO_INVENTARIO,CANTIDAD_USADA,UNIDAD_MEDIDA,EQUIVALENCIA',
            'MP,MENU ALMUERZO A,MENÚ,CARNE BLANCA,,ARROZ,85,GRAMOS,',
            'MP,MENU ALMUERZO A,MENÚ,CARNE BLANCA,,POLLO,0.5,PORCIÓN,110g',
        ]);
        $path = storage_path('framework/testing/standard-recipes.csv');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $csv);

        $summary = app(ImportStandardRecipesFromCsv::class)->handle($path);

        $this->assertSame(['recipes' => 1, 'items' => 2], $summary);
        $this->assertDatabaseHas('restaurant_standard_recipes', [
            'dish_code' => 'MP',
            'dish_name' => 'MENU ALMUERZO A',
        ]);
        $this->assertDatabaseHas('restaurant_standard_recipe_items', [
            'inventory_product_name' => 'ARROZ',
            'quantity_used' => '85.0000',
            'unit' => 'GRAMOS',
        ]);

        $manager = $this->createAssignedManager();
        $this->actingAs($manager);

        $recipe = StandardRecipe::query()->firstOrFail();
        $item = StandardRecipeItem::query()->where('inventory_product_name', 'ARROZ')->firstOrFail();

        $this->get('/recipes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('restaurant/recipes')
                ->where('summary.recipes', 1)
                ->where('summary.items', 2)
                ->where('recipes.0.dish_name', 'MENU ALMUERZO A'));

        $this->patch(route('recipes.update', $recipe), [
            'dish_code' => 'MP',
            'dish_name' => 'MENU ALMUERZO EDITADO',
            'category' => 'MENÚ',
            'subcategory' => 'CARNE BLANCA',
            'is_active' => true,
        ])->assertRedirect();

        $this->patch(route('recipe-items.update', $item), [
            'inventory_product_id' => null,
            'inventory_product_name' => 'ARROZ FLOR',
            'quantity_used' => 90,
            'unit' => 'GRAMOS',
            'equivalence' => null,
            'notes' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('restaurant_standard_recipes', [
            'dish_name' => 'MENU ALMUERZO EDITADO',
        ]);
        $this->assertDatabaseHas('restaurant_standard_recipe_items', [
            'inventory_product_name' => 'ARROZ FLOR',
            'quantity_used' => '90.0000',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createDocument(array $attributes): ContificoDocument
    {
        return ContificoDocument::create([
            'external_id' => $attributes['external_id'],
            'tipo_registro' => $attributes['tipo_registro'],
            'tipo_documento' => $attributes['tipo_documento'] ?? 'FAC',
            'documento' => $attributes['documento'] ?? $attributes['external_id'],
            'estado' => $attributes['estado'] ?? 'C',
            'anulado' => $attributes['anulado'] ?? false,
            'fecha_emision' => $attributes['fecha_emision'] ?? '2026-04-28',
            'fecha_vencimiento' => $attributes['fecha_vencimiento'] ?? null,
            'total' => $attributes['total'] ?? 0,
            'saldo' => $attributes['saldo'] ?? 0,
            'servicio' => $attributes['servicio'] ?? 0,
            'vendedor_id' => $attributes['vendedor_id'] ?? null,
            'vendedor_nombre' => $attributes['vendedor_nombre'] ?? null,
            'persona_nombre' => $attributes['persona_nombre'] ?? null,
            'detalles' => $attributes['detalles'] ?? [],
            'cobros' => $attributes['cobros'] ?? [],
            'raw' => $attributes['raw'] ?? [],
            'synced_at' => now(),
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
