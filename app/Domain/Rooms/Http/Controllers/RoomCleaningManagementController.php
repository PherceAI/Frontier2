<?php

namespace App\Domain\Rooms\Http\Controllers;

use App\Domain\Housekeeping\Actions\AssignDailyRoomCleanings;
use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Housekeeping\Models\RoomCleaningSetting;
use App\Domain\Organization\Models\Area;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class RoomCleaningManagementController extends Controller
{
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_assignment_enabled' => ['required', 'boolean'],
        ]);

        RoomCleaningSetting::current()->forceFill([
            'auto_assignment_enabled' => $validated['auto_assignment_enabled'],
            'working_days' => [1, 2, 3, 4, 5, 6],
            'assignment_time' => '07:00:00',
            'assignment_strategy' => RoomCleaningSetting::STRATEGY_FLOOR_ZONE,
            'updated_by' => $request->user()->id,
        ])->save();

        return back()->with('status', 'room-cleaning-settings-updated');
    }

    public function generate(Request $request, AssignDailyRoomCleanings $assignCleanings): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $assignCleanings->handle(Carbon::parse($validated['date'], 'America/Guayaquil'), true, $request->user());

        return back()->with('status', 'room-cleaning-generated');
    }

    public function updateTask(Request $request, HousekeepingTask $task): RedirectResponse
    {
        if ($request->input('assigned_to') === 'unassigned') {
            $request->merge(['assigned_to' => null]);
        }

        $roomsArea = Area::query()->where('slug', 'rooms')->first();
        $employeeIds = $roomsArea
            ? $roomsArea->employees()->wherePivot('is_active', true)->pluck('users.id')->all()
            : [];

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::in($employeeIds)],
            'status' => ['required', 'string', Rule::in([
                HousekeepingTask::STATUS_PENDING,
                HousekeepingTask::STATUS_IN_PROGRESS,
                HousekeepingTask::STATUS_COMPLETED,
            ])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $metadata = $task->metadata ?? [];
        $metadata['management_updates'][] = [
            'updated_by' => $request->user()->id,
            'updated_at' => now()->toISOString(),
            'assigned_to' => $validated['assigned_to'] ?? null,
            'status' => $validated['status'],
        ];

        $task->forceFill([
            'assigned_to' => $validated['assigned_to'] ?? null,
            'assigned_by' => $request->user()->id,
            'assignment_source' => HousekeepingTask::ASSIGNMENT_SOURCE_MANUAL,
            'status' => $validated['status'],
            'completed_by' => $validated['status'] === HousekeepingTask::STATUS_COMPLETED ? $request->user()->id : $task->completed_by,
            'completed_at' => $validated['status'] === HousekeepingTask::STATUS_COMPLETED ? ($task->completed_at ?? now()) : null,
            'notes' => $validated['notes'] ?? null,
            'metadata' => $metadata,
        ])->save();

        return back()->with('status', 'room-cleaning-task-updated');
    }
}
