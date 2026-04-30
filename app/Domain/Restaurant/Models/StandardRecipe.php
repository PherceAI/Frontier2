<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandardRecipe extends Model
{
    protected $table = 'restaurant_standard_recipes';

    protected $fillable = [
        'dish_code',
        'dish_name',
        'category',
        'subcategory',
        'is_active',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'imported_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<StandardRecipeItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StandardRecipeItem::class, 'restaurant_standard_recipe_id')
            ->orderBy('sort_order');
    }
}
