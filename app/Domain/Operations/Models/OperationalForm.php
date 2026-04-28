<?php

namespace App\Domain\Operations\Models;

use App\Domain\Organization\Models\Area;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalForm extends Model
{
    protected $fillable = [
        'area_id',
        'slug',
        'name',
        'context',
        'status',
        'schema',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
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
     * @return HasMany<OperationalEntry>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(OperationalEntry::class);
    }
}
