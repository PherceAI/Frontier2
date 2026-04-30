<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenInventoryDailyStart extends Model
{
    protected $fillable = [
        'kitchen_daily_stock_item_id',
        'kitchen_inventory_closing_id',
        'inventory_date',
        'quantity',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'inventory_date' => 'date',
            'quantity' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<KitchenDailyStockItem, $this>
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(KitchenDailyStockItem::class, 'kitchen_daily_stock_item_id');
    }

    /**
     * @return BelongsTo<KitchenInventoryClosing, $this>
     */
    public function closing(): BelongsTo
    {
        return $this->belongsTo(KitchenInventoryClosing::class, 'kitchen_inventory_closing_id');
    }
}
