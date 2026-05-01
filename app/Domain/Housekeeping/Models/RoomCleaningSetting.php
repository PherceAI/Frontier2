<?php

namespace App\Domain\Housekeeping\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomCleaningSetting extends Model
{
    public const STRATEGY_FLOOR_ZONE = 'floor_zone';

    protected $fillable = [
        'auto_assignment_enabled',
        'working_days',
        'assignment_time',
        'assignment_strategy',
        'updated_by',
    ];

    protected $attributes = [
        'auto_assignment_enabled' => true,
        'assignment_time' => '07:00:00',
        'assignment_strategy' => self::STRATEGY_FLOOR_ZONE,
    ];

    protected function casts(): array
    {
        return [
            'auto_assignment_enabled' => 'boolean',
            'working_days' => 'array',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'auto_assignment_enabled' => true,
            'working_days' => [1, 2, 3, 4, 5, 6],
            'assignment_time' => '07:00:00',
            'assignment_strategy' => self::STRATEGY_FLOOR_ZONE,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
