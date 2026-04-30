<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Restaurant\Models\KitchenInventoryClosing;
use App\Domain\Restaurant\Models\KitchenInventoryDailyStart;
use App\Domain\Restaurant\Models\KitchenInventoryMovement;
use App\Models\User;

class ConfirmKitchenInventoryReplenishment
{
    public function __construct(private readonly SyncKitchenInventoryMovements $syncMovements) {}

    public function handle(User $user, KitchenInventoryClosing $closing): KitchenInventoryClosing
    {
        $nextDate = $closing->operating_date->copy()->addDay();

        try {
            $this->syncMovements->handle($closing->operating_date, $nextDate);
        } catch (\Throwable) {
            // The closing must keep moving even if the live sheet is temporarily unavailable.
        }

        $closing->loadMissing('items');

        foreach ($closing->items as $item) {
            $actual = $this->actualReplenishment($item->kitchen_daily_stock_item_id, $closing);
            $nextInitial = round((float) $item->physical_count + $actual, 4);
            $required = (float) ($item->replenishment_required ?? 0);
            $hasAlert = abs($actual - $required) > 0.0001;

            $item->update([
                'replenishment_actual' => $actual,
                'next_initial_quantity' => $nextInitial,
                'has_replenishment_alert' => $hasAlert,
            ]);

            KitchenInventoryDailyStart::updateOrCreate(
                [
                    'kitchen_daily_stock_item_id' => $item->kitchen_daily_stock_item_id,
                    'inventory_date' => $nextDate->toDateString(),
                ],
                [
                    'kitchen_inventory_closing_id' => $closing->id,
                    'quantity' => $nextInitial,
                    'source' => 'closing',
                ],
            );
        }

        $hasReplenishmentAlert = $closing->items()->where('has_replenishment_alert', true)->exists();

        $closing->forceFill([
            'replenished_by' => $user->id,
            'status' => KitchenInventoryClosing::STATUS_CLOSED,
            'replenishment_confirmed_at' => now(),
            'has_replenishment_alert' => $hasReplenishmentAlert,
        ])->save();

        if ($closing->task) {
            $closing->task->forceFill([
                'status' => OperationalTask::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ])->save();
        }

        return $closing->refresh()->load('items');
    }

    private function actualReplenishment(int $stockItemId, KitchenInventoryClosing $closing): float
    {
        $nextDate = $closing->operating_date->copy()->addDay()->toDateString();

        return round((float) KitchenInventoryMovement::query()
            ->where('kitchen_daily_stock_item_id', $stockItemId)
            ->whereDate('movement_date', $nextDate)
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
