<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleInventorySnapshot extends Model
{
    protected $fillable = [
        'generated_at',
        'timezone',
        'total_products',
        'inventory_value',
        'payables_total',
        'payables_overdue',
        'pending_documents',
        'hotel_inventory_value',
        'restaurant_inventory_value',
        'payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'total_products' => 'integer',
            'inventory_value' => 'decimal:2',
            'payables_total' => 'decimal:2',
            'payables_overdue' => 'decimal:2',
            'pending_documents' => 'integer',
            'hotel_inventory_value' => 'decimal:2',
            'restaurant_inventory_value' => 'decimal:2',
            'payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
