<?php

namespace Tests\Feature;

use App\Domain\Inventory\Actions\GetInventoryDashboard;
use App\Domain\Inventory\Actions\SyncGoogleInventorySnapshot;
use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_inventory_sync_stores_snapshot(): void
    {
        Config::set('services.inventory.google_sheets_url', 'https://script.google.com/inventory');

        Http::fake([
            'script.google.com/*' => Http::response($this->payload(), 200),
        ]);

        $snapshot = app(SyncGoogleInventorySnapshot::class)->handle();

        $this->assertSame(500, $snapshot->total_products);
        $this->assertSame('4375.55', (string) $snapshot->inventory_value);
        $this->assertSame('4923.16', (string) $snapshot->payables_overdue);
        $this->assertDatabaseHas('google_inventory_snapshots', [
            'total_products' => 500,
            'pending_documents' => 97,
        ]);
    }

    public function test_inventory_dashboard_builds_operational_signals(): void
    {
        Config::set('services.inventory.google_sheets_url', 'https://script.google.com/inventory');
        Http::fake(['script.google.com/*' => Http::response($this->payload(), 200)]);
        app(SyncGoogleInventorySnapshot::class)->handle();

        $dashboard = app(GetInventoryDashboard::class)->handle();

        $this->assertSame(500, $dashboard['summary']['totalProducts']);
        $this->assertSame(2, count($dashboard['locations']));
        $this->assertSame('Habitaciones', collect($dashboard['expenseAreas'])->firstWhere('key', 'HABITACIONES')['label']);
        $this->assertSame('Consumo Restaurante', $dashboard['signals'][2]['title']);
    }

    public function test_inventory_page_renders_for_management_users(): void
    {
        $this->withoutVite();

        $manager = $this->createAssignedManager();
        $this->actingAs($manager);

        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('inventory/dashboard')
                ->where('dashboard.summary.totalProducts', 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'status' => 'success',
            'meta' => [
                'generated_at' => '2026-04-29T09:31:48',
                'timezone' => 'America/Guayaquil',
            ],
            'data' => [
                'total_productos' => 500,
                'valor_total_inventario' => 4375.55,
                'cuentas_por_pagar' => [
                    'total' => 6735.99,
                    'vencido' => 4923.16,
                    'documentos_pendientes' => 97,
                ],
                'valor_inventario_por_ubicacion' => [
                    'hotel' => ['cantidad' => 4919, 'valor' => 4031.32],
                    'restaurante' => ['cantidad' => 85, 'valor' => 344.23],
                    'total' => ['cantidad' => 5004, 'valor' => 4375.55],
                ],
                'valor_por_area' => [
                    'ingresos' => [
                        'RESTAURANTE' => ['cantidad' => 4605, 'valor' => 2810.39, 'registros' => 177],
                        'HOTEL' => ['cantidad' => 1929, 'valor' => 3925.59, 'registros' => 245],
                    ],
                    'egresos' => [
                        'HABITACIONES' => ['cantidad' => 2098, 'valor' => 1809.46, 'registros' => 270],
                        'COCINA' => ['cantidad' => 1380, 'valor' => 1446.44, 'registros' => 365],
                        'DESAYUNOS' => ['cantidad' => 6730, 'valor' => 1829.88, 'registros' => 340],
                        'ALMUERZOS_CENAS' => ['cantidad' => 1075, 'valor' => 2077.01, 'registros' => 550],
                    ],
                ],
                'ingresos' => ['total_registros' => 422, 'cantidad_total' => 6534, 'valor_total' => 6735.99],
                'egresos' => ['total_registros' => 1594, 'cantidad_total' => 11389, 'valor_total' => 7462.82],
            ],
        ];
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
