<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenInventoryProductMapping extends Model
{
    protected $fillable = [
        'restaurant_standard_recipe_item_id',
        'kitchen_daily_stock_item_id',
        'conversion_factor',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<StandardRecipeItem, $this>
     */
    public function recipeItem(): BelongsTo
    {
        return $this->belongsTo(StandardRecipeItem::class, 'restaurant_standard_recipe_item_id');
    }

    /**
     * @return BelongsTo<KitchenDailyStockItem, $this>
     */
    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(KitchenDailyStockItem::class, 'kitchen_daily_stock_item_id');
    }
}
