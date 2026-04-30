<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalTask;
use App\Models\User;

class CanOperateOnTask
{
    public function complete(User $user, OperationalTask $task): bool
    {
        if ($task->assigned_user_id) {
            return $task->assigned_user_id === $user->id || $user->hasManagementAccess();
        }

        if (! $task->assigned_area_id) {
            return $user->hasManagementAccess();
        }

        return $user->activeAreas()->whereKey($task->assigned_area_id)->exists();
    }

    public function validate(User $user, OperationalTask $task): bool
    {
        if ($user->hasManagementAccess()) {
            return true;
        }

        if (! $task->assigned_area_id) {
            return false;
        }

        return $user->leadsArea($task->assigned_area_id);
    }
}
