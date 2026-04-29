<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;

class ContificoProduct extends Model
{
    protected $fillable = [
        'external_id',
        'nombre',
        'raw',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
