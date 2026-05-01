<?php

namespace App\Domain\EmployeePortal\Http\Controllers;

use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Housekeeping\Models\HousekeepingTaskNote;
use App\Domain\Operations\Actions\CanOperateOnTask;
use App\Domain\Operations\Actions\CompleteOperationalTask;
use App\Domain\Operations\Actions\GetEmployeeOperationalPortal;
use App\Domain\Operations\Actions\ReportKitchenSupplyShortage;
use App\Domain\Operations\Actions\SubmitOperationalForm;
use App\Domain\Operations\Actions\ValidateOperationalTask;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Restaurant\Actions\ConfirmKitchenInventoryReplenishment;
use App\Domain\Restaurant\Actions\SubmitKitchenInventoryCount;
use App\Domain\Restaurant\Models\KitchenInventoryClosing;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeHomeController extends Controller
{
    public function __invoke(Request $request, GetEmployeeOperationalPortal $portal): Response
    {
        return Inertia::render('employee/operations', $portal->handle(
            $request->user(),
            $request->string('area')->toString() ?: null,
        ));
    }

    public function completeTask(Request $request, OperationalTask $task, CompleteOperationalTask $completeTask): RedirectResponse
    {
        $validated = $request->validate([
            'form_id' => ['nullable', 'integer', 'exists:operational_forms,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'checklist' => ['nullable', 'array'],
        ]);

        $completeTask->handle($request->user(), $task, $validated);

        return back()->with('status', 'task-completed');
    }

    public function validateTask(Request $request, OperationalTask $task, ValidateOperationalTask $validateTask): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:validated,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validateTask->handle($request->user(), $task, $validated['decision'], $validated['notes'] ?? null);

        return back()->with('status', 'task-validated');
    }

    public function submitForm(Request $request, OperationalForm $form, SubmitOperationalForm $submitForm): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => ['array'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submitForm->handle($request->user(), $form, $validated);

        return back()->with('status', 'form-submitted');
    }

    public function reportSupplyShortage(Request $request, ReportKitchenSupplyShortage $reportShortage): RedirectResponse
    {
        $validated = $request->validate([
            'supply' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reportShortage->handle($request->user(), $validated);

        return back()->with('status', 'shortage-reported');
    }

    public function startRoomCleaning(Request $request, HousekeepingTask $task): RedirectResponse
    {
        abort_unless($this->canOperateOnRoomCleaning($request, $task), 403);

        if ($task->status === HousekeepingTask::STATUS_PENDING) {
            $task->forceFill([
                'status' => HousekeepingTask::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ])->save();
        }

        return back()->with('status', 'room-cleaning-started');
    }

    public function completeRoomCleaning(Request $request, HousekeepingTask $task): RedirectResponse
    {
        abort_unless($this->canOperateOnRoomCleaning($request, $task), 403);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $task->forceFill([
            'status' => HousekeepingTask::STATUS_COMPLETED,
            'started_at' => $task->started_at ?? now(),
            'completed_by' => $request->user()->id,
            'completed_at' => now(),
            'notes' => $validated['notes'] ?? $task->notes,
        ])->save();

        return back()->with('status', 'room-cleaning-completed');
    }

    public function addRoomCleaningNote(Request $request, HousekeepingTask $task): RedirectResponse
    {
        abort_unless($this->canOperateOnRoomCleaning($request, $task), 403);

        $validated = $request->validate([
            'severity' => ['required', 'string', Rule::in([HousekeepingTaskNote::SEVERITY_NORMAL, HousekeepingTaskNote::SEVERITY_URGENT])],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        HousekeepingTaskNote::create([
            'housekeeping_task_id' => $task->id,
            'user_id' => $request->user()->id,
            'severity' => $validated['severity'],
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'room-cleaning-note-added');
    }

    public function submitKitchenClosingCount(
        Request $request,
        KitchenInventoryClosing $closing,
        CanOperateOnTask $permissions,
        SubmitKitchenInventoryCount $submitCount,
    ): RedirectResponse {
        abort_unless($closing->task && $permissions->complete($request->user(), $closing->task), 403);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_item_id' => ['required', 'integer', 'exists:kitchen_daily_stock_items,id'],
            'items.*.physical_count' => ['required', 'numeric', 'min:0'],
            'items.*.waste_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $submitCount->handle($request->user(), $closing, $validated['items']);

        return back()->with('status', 'kitchen-count-submitted');
    }

    public function confirmKitchenClosingReplenishment(
        Request $request,
        KitchenInventoryClosing $closing,
        CanOperateOnTask $permissions,
        ConfirmKitchenInventoryReplenishment $confirmReplenishment,
    ): RedirectResponse {
        abort_unless($closing->task && $permissions->complete($request->user(), $closing->task), 403);

        $confirmReplenishment->handle($request->user(), $closing);

        return back()->with('status', 'kitchen-replenishment-confirmed');
    }

    private function canOperateOnRoomCleaning(Request $request, HousekeepingTask $task): bool
    {
        return $task->assigned_to === $request->user()->id
            && $request->user()->activeAreas()->where('areas.slug', 'rooms')->exists();
    }
}
