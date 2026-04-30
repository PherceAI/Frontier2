<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use Illuminate\Support\Carbon;

class UpsertKitchenDailyStockItem
{
    /**
     * @param  array{category: string, product_name: string, target_stock: float|int|string, unit: string, unit_detail?: string|null, is_active?: bool|int|string|null}  $row
     */
    public function handle(array $row, ?Carbon $importedAt = null): KitchenDailyStockItem
    {
        $category = $this->clean($row['category']);
        $productName = $this->clean($row['product_name']);

        return KitchenDailyStockItem::updateOrCreate(
            [
                'category' => $category,
                'product_name' => $productName,
            ],
            [
                'target_stock' => $this->decimal($row['target_stock']),
                'unit' => $this->clean($row['unit']),
                'unit_detail' => $this->nullableClean($row['unit_detail'] ?? null),
                'is_active' => $this->boolean($row['is_active'] ?? true),
                'imported_at' => $importedAt ?? now(),
            ],
        );
    }

    private function clean(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableClean(mixed $value): ?string
    {
        $clean = $this->clean($value);

        return $clean !== '' ? $clean : null;
    }

    private function decimal(mixed $value): float
    {
        $normalized = str_replace(',', '.', $this->clean($value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($this->clean($value)), ['1', 'true', 'si', 'yes', 'activo', 'active'], true);
    }
}
