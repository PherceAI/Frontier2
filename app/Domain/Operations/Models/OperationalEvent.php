<?php

namespace App\Domain\Operations\Models;

use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalEvent extends Model
{
    protected $fillable = [
        'area_id',
        'created_by',
        'type',
        'source',
        'title',
        'description',
        'status',
        'severity',
        'starts_at',
        'ends_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payload' => 'array',
        ];
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<OperationalTask>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(OperationalTask::class);
    }
}
