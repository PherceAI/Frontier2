<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Domain\Restaurant\Models\KitchenInventoryClosing;
use Illuminate\Support\Carbon;

class CreateKitchenInventoryClosingTask
{
    public function handle(?Carbon $at = null): KitchenInventoryClosing
    {
        $operatingDate = KitchenInventoryOperatingDate::resolve($at);
        $area = Area::query()->where('slug', 'restaurant')->first();

        $closing = KitchenInventoryClosing::query()
            ->whereDate('operating_date', $operatingDate->toDateString())
            ->firstOr(fn () => KitchenInventoryClosing::create([
                'operating_date' => $operatingDate->toDateString(),
                'assigned_area_id' => $area?->id,
                'status' => KitchenInventoryClosing::STATUS_PENDING_COUNT,
            ]));

        $this->seedItems($closing);

        $task = OperationalTask::firstOrCreate(
            [
                'type' => 'kitchen_inventory_closing',
                'due_at' => $operatingDate->copy()->addDay()->setTime(1, 0),
            ],
            [
                'assigned_area_id' => $area?->id,
                'title' => 'Cierre y Reposición',
                'description' => 'Conteo ciego de cocina, calculo de discrepancias y reposicion desde bodega.',
                'status' => OperationalTask::STATUS_PENDING,
                'priority' => 'high',
                'requires_validation' => false,
                'metadata' => [
                    'closing_id' => $closing->id,
                    'operating_date' => $operatingDate->toDateString(),
                ],
            ],
        );

        if (! $closing->operational_task_id) {
            $closing->forceFill(['operational_task_id' => $task->id])->save();
        }

        return $closing->refresh()->load('items');
    }

    private function seedItems(KitchenInventoryClosing $closing): void
    {
        $items = KitchenDailyStockItem::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('product_name')
            ->get();

        foreach ($items as $item) {
            $closing->items()->firstOrCreate(
                ['kitchen_daily_stock_item_id' => $item->id],
                [
                    'category_snapshot' => $item->category,
                    'product_name_snapshot' => $item->product_name,
                    'unit_snapshot' => $item->unit,
                    'unit_detail_snapshot' => $item->unit_detail,
                    'target_stock_snapshot' => $item->target_stock,
                ],
            );
        }
    }
}
