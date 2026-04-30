<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenInventoryMovement extends Model
{
    protected $fillable = [
        'source_id',
        'movement_date',
        'kitchen_daily_stock_item_id',
        'product_name',
        'normalized_product_name',
        'type',
        'area',
        'location',
        'from_location',
        'to_location',
        'quantity',
        'unit',
        'value',
        'raw',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'quantity' => 'decimal:4',
            'value' => 'decimal:2',
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<KitchenDailyStockItem, $this>
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(KitchenDailyStockItem::class, 'kitchen_daily_stock_item_id');
    }
}
