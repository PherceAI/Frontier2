<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenInventoryClosingItem extends Model
{
    protected $fillable = [
        'kitchen_inventory_closing_id',
        'kitchen_daily_stock_item_id',
        'category_snapshot',
        'product_name_snapshot',
        'unit_snapshot',
        'unit_detail_snapshot',
        'target_stock_snapshot',
        'initial_quantity',
        'transfer_quantity',
        'theoretical_consumption',
        'theoretical_final',
        'physical_count',
        'waste_quantity',
        'discrepancy',
        'replenishment_required',
        'replenishment_actual',
        'next_initial_quantity',
        'notes',
        'has_negative_discrepancy',
        'has_replenishment_alert',
    ];

    protected function casts(): array
    {
        return [
            'target_stock_snapshot' => 'decimal:4',
            'initial_quantity' => 'decimal:4',
            'transfer_quantity' => 'decimal:4',
            'theoretical_consumption' => 'decimal:4',
            'theoretical_final' => 'decimal:4',
            'physical_count' => 'decimal:4',
            'waste_quantity' => 'decimal:4',
            'discrepancy' => 'decimal:4',
            'replenishment_required' => 'decimal:4',
            'replenishment_actual' => 'decimal:4',
            'next_initial_quantity' => 'decimal:4',
            'has_negative_discrepancy' => 'boolean',
            'has_replenishment_alert' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KitchenInventoryClosing, $this>
     */
    public function closing(): BelongsTo
    {
        return $this->belongsTo(KitchenInventoryClosing::class, 'kitchen_inventory_closing_id');
    }

    /**
     * @return BelongsTo<KitchenDailyStockItem, $this>
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(KitchenDailyStockItem::class, 'kitchen_daily_stock_item_id');
    }
}
