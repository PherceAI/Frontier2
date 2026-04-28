<?php

namespace App\Models;

use App\Domain\Organization\Models\Area;
use App\Domain\Operations\Models\OperationalTask;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasPushSubscriptions, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'operational_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Area, $this>
     */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class)
            ->withPivot(['assigned_by', 'assigned_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Area, $this>
     */
    public function activeAreas(): BelongsToMany
    {
        return $this->areas()->wherePivot('is_active', true)->where('areas.is_active', true);
    }

    public function hasOperationalAccess(): bool
    {
        return $this->hasRole('administrator') || $this->activeAreas()->exists();
    }

    public function hasManagementAccess(): bool
    {
        return $this->hasRole('administrator')
            || $this->hasRole('management')
            || $this->activeAreas()->where('areas.slug', 'management')->exists();
    }

    public function operationalHomeRoute(): string
    {
        if ($this->isAwaitingAreaAssignment()) {
            return 'assignment.pending';
        }

        if ($this->hasManagementAccess()) {
            return 'dashboard';
        }

        return 'employee.home';
    }

    /**
     * @return HasMany<OperationalTask>
     */
    public function assignedOperationalTasks(): HasMany
    {
        return $this->hasMany(OperationalTask::class, 'assigned_user_id');
    }

    public function isAwaitingAreaAssignment(): bool
    {
        return $this->operational_status === 'pending_area_assignment' || ! $this->hasOperationalAccess();
    }
}
