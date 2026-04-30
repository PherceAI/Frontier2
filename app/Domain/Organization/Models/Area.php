<?php

namespace App\Domain\Organization\Models;

use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['assigned_by', 'assigned_at', 'is_active', 'is_lead'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<OperationalEvent>
     */
    public function operationalEvents(): HasMany
    {
        return $this->hasMany(OperationalEvent::class);
    }

    /**
     * @return HasMany<OperationalTask>
     */
    public function operationalTasks(): HasMany
    {
        return $this->hasMany(OperationalTask::class, 'assigned_area_id');
    }

    /**
     * @return HasMany<OperationalForm>
     */
    public function operationalForms(): HasMany
    {
        return $this->hasMany(OperationalForm::class);
    }
}
