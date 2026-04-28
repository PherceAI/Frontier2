<?php

namespace App\Domain\EmployeePortal\Http\Controllers;

use App\Domain\Operations\Actions\CompleteOperationalTask;
use App\Domain\Operations\Actions\GetKitchenOperationalPortal;
use App\Domain\Operations\Actions\ReportKitchenSupplyShortage;
use App\Domain\Operations\Models\OperationalTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeHomeController extends Controller
{
    public function __invoke(Request $request, GetKitchenOperationalPortal $kitchenPortal): Response
    {
        $user = $request->user();
        $areas = $user->activeAreas()->orderBy('name')->get(['areas.id', 'areas.name', 'areas.slug']);

        if ($areas->contains('slug', 'restaurant')) {
            return Inertia::render('employee/kitchen', $kitchenPortal->handle($user));
        }

        return Inertia::render('employee/home', [
            'employee' => [
                'name' => $user->name,
                'areas' => $areas->map(fn ($area): array => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'slug' => $area->slug,
                ])->values(),
            ],
        ]);
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
