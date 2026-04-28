<?php

namespace App\Domain\Operations\Models;

use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalEntry extends Model
{
    protected $fillable = [
        'operational_form_id',
        'operational_event_id',
        'operational_task_id',
        'area_id',
        'user_id',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<OperationalForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(OperationalForm::class, 'operational_form_id');
    }

    /**
     * @return BelongsTo<OperationalEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(OperationalEvent::class, 'operational_event_id');
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
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
