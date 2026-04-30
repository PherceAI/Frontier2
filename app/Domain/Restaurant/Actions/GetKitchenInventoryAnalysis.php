<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Domain\Restaurant\Models\KitchenInventoryClosing;
use App\Domain\Restaurant\Models\KitchenInventoryProductMapping;
use App\Domain\Restaurant\Models\StandardRecipeItem;
use Illuminate\Support\Carbon;

class GetKitchenInventoryAnalysis
{
    /**
     * @return array<string, mixed>
     */
    public function handle(?string $week = null): array
    {
        $start = $week
            ? Carbon::parse($week, 'America/Guayaquil')->startOfWeek()
            : now('America/Guayaquil')->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $closings = KitchenInventoryClosing::query()
            ->with(['items', 'countedBy', 'replenishedBy'])
            ->whereDate('operating_date', '>=', $start->toDateString())
            ->whereDate('operating_date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (KitchenInventoryClosing $closing): string => $closing->operating_date->toDateString());

        $days = collect(range(0, 6))->map(function (int $offset) use ($start, $closings): array {
            $date = $start->copy()->addDays($offset);
            $closing = $closings->get($date->toDateString());

            return $closing ? $this->closingDay($closing, $date) : [
                'date' => $date->toDateString(),
                'label' => $date->locale('es')->translatedFormat('l d M'),
                'status' => 'sin cierre',
                'wasteTotal' => 0,
                'negativeDiscrepancyTotal' => 0,
                'replenishmentRequiredTotal' => 0,
                'replenishmentActualTotal' => 0,
                'hasAlert' => false,
                'items' => [],
            ];
        })->values();

        return [
            'week' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $start->format('d/m').' - '.$end->format('d/m/Y'),
            ],
            'days' => $days,
            'summary' => [
                'closedDays' => $days->where('status', 'closed')->count(),
                'alerts' => $days->where('hasAlert', true)->count(),
                'wasteTotal' => round((float) $days->sum('wasteTotal'), 4),
                'negativeDiscrepancyTotal' => round((float) $days->sum('negativeDiscrepancyTotal'), 4),
            ],
            'mappings' => $this->mappings(),
        ];
    }

    private function closingDay(KitchenInventoryClosing $closing, Carbon $date): array
    {
        $items = $closing->items
            ->sortByDesc(fn ($item): float => abs((float) $item->discrepancy))
            ->map(fn ($item): array => [
                'id' => $item->id,
                'productName' => $item->product_name_snapshot,
                'category' => $item->category_snapshot,
                'unit' => $item->unit_snapshot,
                'physicalCount' => (float) $item->physical_count,
                'wasteQuantity' => (float) $item->waste_quantity,
                'theoreticalFinal' => (float) $item->theoretical_final,
                'discrepancy' => (float) $item->discrepancy,
                'replenishmentRequired' => (float) $item->replenishment_required,
                'replenishmentActual' => (float) $item->replenishment_actual,
                'hasNegativeDiscrepancy' => $item->has_negative_discrepancy,
                'hasReplenishmentAlert' => $item->has_replenishment_alert,
            ])
            ->values();

        return [
            'id' => $closing->id,
            'date' => $date->toDateString(),
            'label' => $date->locale('es')->translatedFormat('l d M'),
            'status' => $closing->status,
            'countedBy' => $closing->countedBy?->name,
            'replenishedBy' => $closing->replenishedBy?->name,
            'wasteTotal' => round((float) $closing->items->sum('waste_quantity'), 4),
            'negativeDiscrepancyTotal' => round((float) $closing->items->where('has_negative_discrepancy', true)->sum('discrepancy'), 4),
            'replenishmentRequiredTotal' => round((float) $closing->items->sum('replenishment_required'), 4),
            'replenishmentActualTotal' => round((float) $closing->items->sum('replenishment_actual'), 4),
            'hasAlert' => $closing->has_negative_discrepancy || $closing->has_replenishment_alert || filled($closing->pending_mappings),
            'pendingMappings' => $closing->pending_mappings ?? [],
            'items' => $items->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mappings(): array
    {
        $stockItems = KitchenDailyStockItem::query()
            ->orderBy('category')
            ->orderBy('product_name')
            ->get(['id', 'category', 'product_name', 'unit'])
            ->map(fn (KitchenDailyStockItem $item): array => [
                'id' => $item->id,
                'label' => "{$item->category} · {$item->product_name} ({$item->unit})",
            ])
            ->values()
            ->all();

        $mappingByRecipeItem = KitchenInventoryProductMapping::query()
            ->get()
            ->keyBy('restaurant_standard_recipe_item_id');

        $recipeItems = StandardRecipeItem::query()
            ->with('recipe')
            ->orderBy('inventory_product_name')
            ->limit(120)
            ->get()
            ->map(function (StandardRecipeItem $item) use ($mappingByRecipeItem): array {
                $mapping = $mappingByRecipeItem->get($item->id);

                return [
                    'id' => $item->id,
                    'recipe' => $item->recipe?->dish_name ?? 'Receta',
                    'ingredient' => $item->inventory_product_name ?: ('Ingrediente '.$item->id),
                    'quantityUsed' => (float) $item->quantity_used,
                    'unit' => $item->unit,
                    'stockItemId' => $mapping?->kitchen_daily_stock_item_id,
                    'conversionFactor' => $mapping ? (float) $mapping->conversion_factor : 1,
                    'isActive' => $mapping?->is_active ?? false,
                ];
            })
            ->values()
            ->all();

        return [
            'stockItems' => $stockItems,
            'recipeItems' => $recipeItems,
        ];
    }
}
