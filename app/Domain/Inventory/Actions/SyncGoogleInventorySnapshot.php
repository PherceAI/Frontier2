<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Integrations\GoogleSheetsInventoryClient;
use App\Domain\Inventory\Models\GoogleInventorySnapshot;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SyncGoogleInventorySnapshot
{
    public function __construct(private readonly GoogleSheetsInventoryClient $client) {}

    public function handle(): GoogleInventorySnapshot
    {
        $payload = $this->client->summary();
        $data = $payload['data'];
        $meta = $payload['meta'] ?? [];
        $generatedAt = filled($meta['generated_at'] ?? null)
            ? Carbon::parse((string) $meta['generated_at'], (string) ($meta['timezone'] ?? config('app.timezone')))
            : now();

        return GoogleInventorySnapshot::updateOrCreate(
            ['generated_at' => $generatedAt],
            [
                'timezone' => $meta['timezone'] ?? null,
                'total_products' => (int) ($data['total_productos'] ?? 0),
                'inventory_value' => $this->money($data['valor_total_inventario'] ?? 0),
                'payables_total' => $this->money(Arr::get($data, 'cuentas_por_pagar.total', 0)),
                'payables_overdue' => $this->money(Arr::get($data, 'cuentas_por_pagar.vencido', 0)),
                'pending_documents' => (int) Arr::get($data, 'cuentas_por_pagar.documentos_pendientes', 0),
                'hotel_inventory_value' => $this->money(Arr::get($data, 'valor_inventario_por_ubicacion.hotel.valor', 0)),
                'restaurant_inventory_value' => $this->money(Arr::get($data, 'valor_inventario_por_ubicacion.restaurante.valor', 0)),
                'payload' => $payload,
                'synced_at' => now(),
            ],
        );
    }

    private function money(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
