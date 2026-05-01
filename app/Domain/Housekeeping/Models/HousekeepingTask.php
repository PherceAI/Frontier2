<?php

namespace App\Domain\Housekeeping\Models;

use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomOccupancySnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingTask extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const CLEANING_TYPE_CHECKOUT = 'checkout';

    public const CLEANING_TYPE_STAY = 'stay';

    public const ASSIGNMENT_SOURCE_AUTO = 'auto';

    public const ASSIGNMENT_SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'room_id',
        'occupancy_snapshot_id',
        'assigned_to',
        'assigned_by',
        'assignment_source',
        'type',
        'cleaning_type',
        'status',
        'scheduled_date',
        'generated_for_date',
        'scheduled_at',
        'started_at',
        'completed_at',
        'completed_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'generated_for_date' => 'date',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return BelongsTo<RoomOccupancySnapshot, $this>
     */
    public function occupancySnapshot(): BelongsTo
    {
        return $this->belongsTo(RoomOccupancySnapshot::class);
    }

    /**
     * @return HasMany<HousekeepingTaskNote, $this>
     */
    public function taskNotes(): HasMany
    {
        return $this->hasMany(HousekeepingTaskNote::class);
    }
}
