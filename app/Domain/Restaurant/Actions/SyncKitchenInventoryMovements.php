<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Inventory\Integrations\GoogleSheetsInventoryClient;
use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Domain\Restaurant\Models\KitchenInventoryMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SyncKitchenInventoryMovements
{
    public function __construct(private readonly GoogleSheetsInventoryClient $client) {}

    /**
     * @return Collection<int, KitchenInventoryMovement>
     */
    public function handle(Carbon|string $from, Carbon|string|null $to = null): Collection
    {
        $fromDate = $from instanceof Carbon ? $from->toDateString() : (string) $from;
        $toDate = $to instanceof Carbon ? $to->toDateString() : ($to ? (string) $to : $fromDate);
        $stockItems = KitchenDailyStockItem::query()->get()->keyBy(
            fn (KitchenDailyStockItem $item): string => NormalizeKitchenInventoryText::handle($item->product_name)
        );

        return collect($this->client->movements($fromDate, $toDate))
            ->map(fn (array $row): ?KitchenInventoryMovement => $this->persist($row, $stockItems))
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<string, KitchenDailyStockItem>  $stockItems
     */
    private function persist(array $row, Collection $stockItems): ?KitchenInventoryMovement
    {
        $productName = $this->string($row, ['product_name', 'producto', 'nombre_producto', 'item', 'insumo']);

        if ($productName === '') {
            return null;
        }

        $date = $this->string($row, ['movement_date', 'fecha', 'date', 'created_at']);
        $movementDate = $date !== '' ? Carbon::parse($date)->toDateString() : now('America/Guayaquil')->toDateString();
        $normalized = NormalizeKitchenInventoryText::handle($productName);
        $stockItem = $stockItems->get($normalized);
        $sourceId = $this->string($row, ['id', 'source_id', 'movimiento_id', 'documento', 'referencia']);

        if ($sourceId === '') {
            $sourceId = sha1(json_encode([$movementDate, $normalized, $row], JSON_THROW_ON_ERROR));
        }

        return KitchenInventoryMovement::updateOrCreate(
            ['source_id' => $sourceId],
            [
                'movement_date' => $movementDate,
                'kitchen_daily_stock_item_id' => $stockItem?->id,
                'product_name' => $productName,
                'normalized_product_name' => $normalized,
                'type' => $this->string($row, ['type', 'tipo', 'movimiento']),
                'area' => $this->string($row, ['area', 'departamento']),
                'location' => $this->string($row, ['location', 'ubicacion', 'bodega']),
                'from_location' => $this->string($row, ['from_location', 'origen', 'desde']),
                'to_location' => $this->string($row, ['to_location', 'destino', 'hacia']),
                'quantity' => $this->number($row, ['quantity', 'cantidad', 'egreso', 'salida']),
                'unit' => $this->string($row, ['unit', 'unidad']),
                'value' => $this->number($row, ['value', 'valor', 'costo']),
                'raw' => $row,
                'synced_at' => now(),
            ],
        );
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function string(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (filled($row[$key] ?? null)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function number(array $row, array $keys): float
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return round((float) str_replace(',', '.', (string) $row[$key]), 4);
            }
        }

        return 0.0;
    }
}
