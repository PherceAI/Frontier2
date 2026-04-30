<?php

namespace App\Domain\EmployeePortal\Http\Controllers;

use App\Domain\Operations\Actions\CompleteOperationalTask;
use App\Domain\Operations\Actions\GetEmployeeOperationalPortal;
use App\Domain\Operations\Actions\ReportKitchenSupplyShortage;
use App\Domain\Operations\Actions\SubmitOperationalForm;
use App\Domain\Operations\Actions\ValidateOperationalTask;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
