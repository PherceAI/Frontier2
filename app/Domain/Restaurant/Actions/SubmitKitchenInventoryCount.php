<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Models\KitchenInventoryClosing;
use App\Domain\Restaurant\Models\KitchenInventoryDailyStart;
use App\Domain\Restaurant\Models\KitchenInventoryMovement;
use App\Models\User;
use Illuminate\Support\Collection;

class SubmitKitchenInventoryCount
{
    public function __construct(private readonly CalculateKitchenTheoreticalConsumption $consumption) {}

    /**
     * @param  array<int, array<string, mixed>>  $payloadItems
     */
    public function handle(User $user, KitchenInventoryClosing $closing, array $payloadItems): KitchenInventoryClosing
    {
        $closing->loadMissing('items');
        $input = collect($payloadItems)->keyBy(fn (array $row): int => (int) $row['stock_item_id']);
        $calculatedConsumption = $this->consumption->handle($closing->operating_date);

        foreach ($closing->items as $item) {
            $row = $input->get($item->kitchen_daily_stock_item_id, []);
            $physical = round((float) ($row['physical_count'] ?? 0), 4);
            $waste = round((float) ($row['waste_quantity'] ?? 0), 4);
            $initial = $this->initialQuantity($item->kitchen_daily_stock_item_id, $closing);
            $transfers = $this->transferQuantity($item->kitchen_daily_stock_item_id, $closing);
            $theoreticalConsumption = (float) ($calculatedConsumption['consumption'][$item->kitchen_daily_stock_item_id] ?? 0);
            $theoreticalFinal = round($initial + $transfers - $theoreticalConsumption, 4);
            $discrepancy = round($physical - $theoreticalFinal, 4);
            $replenishmentRequired = round(max((float) $item->target_stock_snapshot - $physical, 0), 4);

            $item->update([
                'initial_quantity' => $initial,
                'transfer_quantity' => $transfers,
                'theoretical_consumption' => $theoreticalConsumption,
                'theoretical_final' => $theoreticalFinal,
                'physical_count' => $physical,
                'waste_quantity' => $waste,
                'discrepancy' => $discrepancy,
                'replenishment_required' => $replenishmentRequired,
                'notes' => $row['notes'] ?? null,
                'has_negative_discrepancy' => $discrepancy < 0,
            ]);
        }

        $closing->forceFill([
            'counted_by' => $user->id,
            'status' => KitchenInventoryClosing::STATUS_COUNT_SUBMITTED,
            'count_submitted_at' => now(),
            'has_negative_discrepancy' => $closing->items()->where('has_negative_discrepancy', true)->exists(),
            'pending_mappings' => $calculatedConsumption['pendingMappings'],
        ])->save();

        return $closing->refresh()->load('items');
    }

    private function initialQuantity(int $stockItemId, KitchenInventoryClosing $closing): float
    {
        return round((float) KitchenInventoryDailyStart::query()
            ->where('kitchen_daily_stock_item_id', $stockItemId)
            ->whereDate('inventory_date', $closing->operating_date->toDateString())
            ->value('quantity'), 4);
    }

    private function transferQuantity(int $stockItemId, KitchenInventoryClosing $closing): float
    {
        return round((float) KitchenInventoryMovement::query()
            ->where('kitchen_daily_stock_item_id', $stockItemId)
            ->whereDate('movement_date', $closing->operating_date->toDateString())
            ->where(function ($query) {
                $query->whereRaw("lower(coalesce(type, '')) like '%egreso%'")
                    ->orWhereRaw("lower(coalesce(type, '')) like '%salida%'")
                    ->orWhereRaw("lower(coalesce(from_location, '')) like '%bodega%'");
            })
            ->where(function ($query) {
                $query->whereRaw("lower(coalesce(area, '')) like '%cocina%'")
                    ->orWhereRaw("lower(coalesce(area, '')) like '%restaurante%'")
                    ->orWhereRaw("lower(coalesce(to_location, '')) like '%cocina%'")
                    ->orWhereRaw("lower(coalesce(to_location, '')) like '%restaurante%'");
            })
            ->sum('quantity'), 4);
    }
}
