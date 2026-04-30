<?php

namespace App\Domain\Operations\Models;

use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalTask extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING_VALIDATION = 'pending_validation';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'operational_event_id',
        'assigned_area_id',
        'assigned_user_id',
        'created_by',
        'completed_by',
        'validated_by',
        'type',
        'title',
        'description',
        'status',
        'priority',
        'requires_validation',
        'due_at',
        'completed_at',
        'validated_at',
        'validation_notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requires_validation' => 'boolean',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'validated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<OperationalEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(OperationalEvent::class, 'operational_event_id');
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function assignedArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'assigned_area_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
