<?php

namespace App\Domain\Housekeeping\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTaskNote extends Model
{
    public const SEVERITY_NORMAL = 'normal';

    public const SEVERITY_URGENT = 'urgent';

    protected $fillable = [
        'housekeeping_task_id',
        'user_id',
        'severity',
        'body',
    ];

    /**
     * @return BelongsTo<HousekeepingTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(HousekeepingTask::class, 'housekeeping_task_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
