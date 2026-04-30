<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEntry;
use App\Domain\Operations\Models\OperationalTask;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CompleteOperationalTask
{
    public function __construct(private readonly CanOperateOnTask $permissions) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(User $user, OperationalTask $task, array $payload = []): OperationalTask
    {
        if (! $this->permissions->complete($user, $task)) {
            throw new AuthorizationException('No puedes completar esta tarea.');
        }

        $previousStatus = $task->status;
        $nextStatus = $task->requires_validation
            ? OperationalTask::STATUS_PENDING_VALIDATION
            : OperationalTask::STATUS_COMPLETED;

        $task->forceFill([
            'status' => $nextStatus,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'metadata' => [
                ...($task->metadata ?? []),
                'completion' => [
                    'previous_status' => $previousStatus,
                    'new_status' => $nextStatus,
                    'completed_by' => $user->id,
                    'completed_at' => now()->toISOString(),
                    'payload' => $payload,
                ],
            ],
        ])->save();

        if ($formId = ($payload['form_id'] ?? null)) {
            OperationalEntry::create([
                'operational_form_id' => $formId,
                'operational_event_id' => $task->operational_event_id,
                'operational_task_id' => $task->id,
                'area_id' => $task->assigned_area_id,
                'user_id' => $user->id,
                'status' => $task->requires_validation ? 'pending_validation' : 'submitted',
                'payload' => $payload,
            ]);
        }

        return $task->refresh();
    }
}
