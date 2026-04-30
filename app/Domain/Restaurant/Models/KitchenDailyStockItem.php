<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenDailyStockItem extends Model
{
    protected $fillable = [
        'category',
        'product_name',
        'target_stock',
        'unit',
        'unit_detail',
        'is_active',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'target_stock' => 'decimal:4',
            'is_active' => 'boolean',
            'imported_at' => 'datetime',
        ];
    }
}
