<?php

namespace App\Domain\Restaurant\Models;

use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenInventoryClosing extends Model
{
    public const STATUS_PENDING_COUNT = 'pending_count';

    public const STATUS_COUNT_SUBMITTED = 'count_submitted';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'operational_task_id',
        'assigned_area_id',
        'counted_by',
        'replenished_by',
        'operating_date',
        'status',
        'count_submitted_at',
        'replenishment_confirmed_at',
        'has_negative_discrepancy',
        'has_replenishment_alert',
        'pending_mappings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'operating_date' => 'date',
            'count_submitted_at' => 'datetime',
            'replenishment_confirmed_at' => 'datetime',
            'has_negative_discrepancy' => 'boolean',
            'has_replenishment_alert' => 'boolean',
            'pending_mappings' => 'array',
        ];
    }

    /**
     * @return HasMany<KitchenInventoryClosingItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KitchenInventoryClosingItem::class);
    }

    /**
     * @return BelongsTo<OperationalTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(OperationalTask::class, 'operational_task_id');
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
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function replenishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replenished_by');
    }
}
