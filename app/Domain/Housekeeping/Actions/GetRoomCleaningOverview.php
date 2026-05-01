<?php

namespace App\Domain\Housekeeping\Actions;

use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Housekeeping\Models\RoomCleaningSetting;
use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Support\Carbon;

class GetRoomCleaningOverview
{
    /**
     * @return array<string, mixed>
     */
    public function handle(?Carbon $date = null): array
    {
        $date ??= today('America/Guayaquil');
        $settings = RoomCleaningSetting::current();
        $tasks = HousekeepingTask::query()
            ->with(['room.type', 'assignee:id,name', 'taskNotes.user:id,name'])
            ->whereDate('scheduled_date', $date->toDateString())
            ->orderByRaw("case cleaning_type when 'checkout' then 0 else 1 end")
            ->join('rooms', 'housekeeping_tasks.room_id', '=', 'rooms.id')
            ->orderBy('rooms.floor')
            ->orderBy('rooms.number')
            ->select('housekeeping_tasks.*')
            ->get();

        return [
            'settings' => [
                'autoAssignmentEnabled' => $settings->auto_assignment_enabled,
                'workingDays' => $settings->working_days,
                'assignmentTime' => substr($settings->assignment_time, 0, 5),
                'assignmentStrategy' => $settings->assignment_strategy,
            ],
            'summary' => [
                'pending' => $tasks->whereIn('status', [HousekeepingTask::STATUS_PENDING, HousekeepingTask::STATUS_IN_PROGRESS])->count(),
                'completed' => $tasks->where('status', HousekeepingTask::STATUS_COMPLETED)->count(),
                'checkout' => $tasks->where('cleaning_type', HousekeepingTask::CLEANING_TYPE_CHECKOUT)->count(),
                'stay' => $tasks->where('cleaning_type', HousekeepingTask::CLEANING_TYPE_STAY)->count(),
                'unassigned' => $tasks->whereNull('assigned_to')->count(),
            ],
            'employees' => $this->employees(),
            'tasks' => $tasks->map(fn (HousekeepingTask $task): array => $this->taskData($task))->values(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function employees(): array
    {
        $roomsArea = Area::query()->where('slug', 'rooms')->first();

        if (! $roomsArea) {
            return [];
        }

        return $roomsArea->employees()
            ->wherePivot('is_active', true)
            ->where('users.operational_status', 'active')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function taskData(HousekeepingTask $task): array
    {
        return [
            'id' => $task->id,
            'roomNumber' => $task->room->number,
            'floor' => $task->room->floor,
            'roomType' => $task->room->type?->name,
            'cleaningType' => $task->cleaning_type,
            'status' => $task->status,
            'assignmentSource' => $task->assignment_source,
            'assignedTo' => $task->assigned_to,
            'assigneeName' => $task->assignee?->name,
            'guestName' => $task->metadata['guest_name'] ?? null,
            'companyName' => $task->metadata['company_name'] ?? null,
            'reservationCode' => $task->metadata['reservation_code'] ?? null,
            'checkInDate' => $task->metadata['check_in_date'] ?? null,
            'checkOutDate' => $task->metadata['check_out_date'] ?? null,
            'generatedForDate' => $task->generated_for_date?->toDateString(),
            'scheduledAt' => $task->scheduled_at?->format('H:i'),
            'completedAt' => $task->completed_at?->format('H:i'),
            'notes' => $task->notes,
            'novelties' => $task->taskNotes
                ->map(fn ($note): array => [
                    'id' => $note->id,
                    'severity' => $note->severity,
                    'body' => $note->body,
                    'userName' => $note->user?->name,
                    'createdAt' => $note->created_at?->format('H:i'),
                ])
                ->values(),
        ];
    }
}
