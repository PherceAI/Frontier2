<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEntry;
use App\Domain\Operations\Models\OperationalTask;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class ValidateOperationalTask
{
    public function __construct(private readonly CanOperateOnTask $permissions) {}

    public function handle(User $user, OperationalTask $task, string $decision, ?string $notes = null): OperationalTask
    {
        if (! $this->permissions->validate($user, $task)) {
            throw new AuthorizationException('No puedes validar esta tarea.');
        }

        if (! in_array($decision, ['validated', 'rejected'], true)) {
            throw new InvalidArgumentException('Decision de validacion invalida.');
        }

        $previousStatus = $task->status;
        $nextStatus = $decision === 'validated'
            ? OperationalTask::STATUS_VALIDATED
            : OperationalTask::STATUS_REJECTED;

        $task->forceFill([
            'status' => $nextStatus,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
            'metadata' => [
                ...($task->metadata ?? []),
                'validation' => [
                    'previous_status' => $previousStatus,
                    'new_status' => $nextStatus,
                    'validated_by' => $user->id,
                    'validated_at' => now()->toISOString(),
                    'notes' => $notes,
                ],
            ],
        ])->save();

        OperationalEntry::where('operational_task_id', $task->id)
            ->where('status', 'pending_validation')
            ->update(['status' => $nextStatus]);

        return $task->refresh();
    }
}
