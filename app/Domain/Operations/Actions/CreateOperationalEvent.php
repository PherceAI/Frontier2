<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalNotification;
use App\Domain\Operations\Models\OperationalTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOperationalEvent
{
    /**
     * @param  array<string, mixed>  $eventData
     * @param  array<int, array<string, mixed>>  $tasks
     * @param  array<int, array<string, mixed>>  $notifications
     */
    public function handle(User $actor, array $eventData, array $tasks = [], array $notifications = []): OperationalEvent
    {
        return DB::transaction(function () use ($actor, $eventData, $tasks, $notifications): OperationalEvent {
            $event = OperationalEvent::create([
                ...$eventData,
                'created_by' => $eventData['created_by'] ?? $actor->id,
            ]);

            foreach ($tasks as $taskData) {
                OperationalTask::create([
                    ...$taskData,
                    'operational_event_id' => $event->id,
                    'created_by' => $taskData['created_by'] ?? $actor->id,
                    'status' => $taskData['status'] ?? OperationalTask::STATUS_PENDING,
                ]);
            }

            foreach ($notifications as $notificationData) {
                OperationalNotification::create([
                    ...$notificationData,
                    'operational_event_id' => $event->id,
                    'status' => $notificationData['status'] ?? 'pending',
                    'scheduled_at' => $notificationData['scheduled_at'] ?? now(),
                ]);
            }

            return $event->refresh();
        });
    }
}
