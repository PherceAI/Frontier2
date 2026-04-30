<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardRecipeItem extends Model
{
    protected $table = 'restaurant_standard_recipe_items';

    protected $fillable = [
        'restaurant_standard_recipe_id',
        'sort_order',
        'inventory_product_id',
        'inventory_product_name',
        'quantity_used',
        'unit',
        'equivalence',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'quantity_used' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<StandardRecipe, StandardRecipeItem>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(StandardRecipe::class, 'restaurant_standard_recipe_id');
    }
}
