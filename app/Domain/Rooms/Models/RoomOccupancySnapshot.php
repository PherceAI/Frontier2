<?php

namespace App\Domain\Rooms\Models;

use Illuminate\Database\Eloquent\Model;

class RoomOccupancySnapshot extends Model
{
    protected $fillable = [
        'occupancy_date',
        'room_number',
        'room_type',
        'floor',
        'status',
        'is_occupied',
        'guest_name',
        'company_name',
        'reservation_code',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'balance',
        'raw',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'occupancy_date' => 'date',
            'is_occupied' => 'boolean',
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'adults' => 'integer',
            'children' => 'integer',
            'balance' => 'decimal:2',
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
