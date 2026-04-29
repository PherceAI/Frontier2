<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\GoogleInventorySnapshot;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GetInventoryDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $snapshot = GoogleInventorySnapshot::query()->latest('generated_at')->first();
        $data = $snapshot?->payload['data'] ?? [];

        return [
            'lastSyncedAt' => $snapshot?->synced_at?->toIso8601String(),
            'generatedAt' => $snapshot?->generated_at?->toIso8601String(),
            'summary' => [
                'totalProducts' => (int) ($data['total_productos'] ?? 0),
                'inventoryValue' => $this->money($data['valor_total_inventario'] ?? 0),
                'payablesTotal' => $this->money(Arr::get($data, 'cuentas_por_pagar.total', 0)),
                'payablesOverdue' => $this->money(Arr::get($data, 'cuentas_por_pagar.vencido', 0)),
                'pendingDocuments' => (int) Arr::get($data, 'cuentas_por_pagar.documentos_pendientes', 0),
            ],
            'locations' => $this->locations(Arr::get($data, 'valor_inventario_por_ubicacion', [])),
            'incomeAreas' => $this->areas(Arr::get($data, 'valor_por_area.ingresos', [])),
            'expenseAreas' => $this->areas(Arr::get($data, 'valor_por_area.egresos', [])),
            'movementTotals' => [
                'income' => [
                    'records' => (int) Arr::get($data, 'ingresos.total_registros', 0),
                    'quantity' => (float) Arr::get($data, 'ingresos.cantidad_total', 0),
                    'value' => $this->money(Arr::get($data, 'ingresos.valor_total', 0)),
                ],
                'expenses' => [
                    'records' => (int) Arr::get($data, 'egresos.total_registros', 0),
                    'quantity' => (float) Arr::get($data, 'egresos.cantidad_total', 0),
                    'value' => $this->money(Arr::get($data, 'egresos.valor_total', 0)),
                ],
            ],
            'signals' => $this->signals($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $locations
     * @return array<int, array<string, mixed>>
     */
    private function locations(array $locations): array
    {
        return collect($locations)
            ->reject(fn (mixed $value, string $key): bool => $key === 'total' || ! is_array($value))
            ->map(fn (array $item, string $key): array => [
                'key' => $key,
                'label' => Str::of($key)->replace('_', ' ')->title()->toString(),
                'quantity' => (float) ($item['cantidad'] ?? 0),
                'value' => $this->money($item['valor'] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $areas
     * @return array<int, array<string, mixed>>
     */
    private function areas(array $areas): array
    {
        return collect($areas)
            ->map(fn (array $item, string $key): array => [
                'key' => $key,
                'label' => Str::of($key)->replace('_', ' ')->title()->toString(),
                'quantity' => (float) ($item['cantidad'] ?? 0),
                'value' => $this->money($item['valor'] ?? 0),
                'records' => (int) ($item['registros'] ?? 0),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, string>>
     */
    private function signals(array $data): array
    {
        $overdue = $this->money(Arr::get($data, 'cuentas_por_pagar.vencido', 0));
        $payables = $this->money(Arr::get($data, 'cuentas_por_pagar.total', 0));
        $roomsExpense = $this->money(Arr::get($data, 'valor_por_area.egresos.HABITACIONES.valor', 0));
        $restaurantExpense = $this->money(Arr::get($data, 'valor_por_area.egresos.COCINA.valor', 0))
            + $this->money(Arr::get($data, 'valor_por_area.egresos.ALMUERZOS_CENAS.valor', 0))
            + $this->money(Arr::get($data, 'valor_por_area.egresos.DESAYUNOS.valor', 0));

        return collect([
            [
                'title' => 'Cuentas vencidas',
                'value' => '$'.number_format($overdue, 2),
                'detail' => $payables > 0 ? round(($overdue / $payables) * 100, 1).'% del pendiente total' : 'Sin pendientes registrados',
            ],
            [
                'title' => 'Consumo Habitaciones',
                'value' => '$'.number_format($roomsExpense, 2),
                'detail' => 'Base para cruzar con ocupacion, limpieza y amenities',
            ],
            [
                'title' => 'Consumo Restaurante',
                'value' => '$'.number_format($restaurantExpense, 2),
                'detail' => 'Cocina, desayunos y almuerzos/cenas',
            ],
        ])->all();
    }

    private function money(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
