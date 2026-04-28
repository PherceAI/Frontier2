<?php

namespace App\Domain\Management\Http\Controllers;

use App\Domain\Organization\Models\Area;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class EmployeesController extends Controller
{
    public function index(): Response
    {
        $onlineUserIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $employees = User::query()
            ->with(['areas' => fn ($query) => $query->orderBy('name'), 'roles'])
            ->orderByRaw("case when operational_status = 'pending_area_assignment' then 0 else 1 end")
            ->latest()
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'operational_status' => $user->operational_status,
                'is_online' => $onlineUserIds->contains($user->id),
                'roles' => $user->roles->pluck('name')->values(),
                'areas' => $user->areas->map(fn (Area $area): array => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'slug' => $area->slug,
                    'is_active' => (bool) $area->pivot?->is_active,
                ])->values(),
                'created_at' => $user->created_at?->toISOString(),
            ]);

        $areas = Area::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'areas' => $areas,
            'summary' => [
                'total' => $employees->count(),
                'pending' => $employees->where('operational_status', 'pending_area_assignment')->count(),
                'online' => $employees->where('is_online', true)->count(),
                'areas' => $areas->count(),
            ],
        ]);
    }

    public function updateAreas(Request $request, User $employee): RedirectResponse
    {
        $validated = $request->validate([
            'area_ids' => ['array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
        ]);

        $areaIds = collect($validated['area_ids'] ?? [])
            ->unique()
            ->values();

        $employee->areas()->sync(
            $areaIds->mapWithKeys(fn (int $areaId): array => [
                $areaId => [
                    'assigned_by' => $request->user()?->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ],
            ])->all(),
        );

        if ($areaIds->isEmpty()) {
            $employee->forceFill(['operational_status' => 'pending_area_assignment'])->save();

            if (! $employee->hasRole('administrator')) {
                Role::firstOrCreate(['name' => 'pending_assignment']);
                $employee->syncRoles(['pending_assignment']);
            }
        } else {
            $employee->forceFill(['operational_status' => 'active'])->save();

            if (! $employee->hasRole('administrator')) {
                Role::firstOrCreate(['name' => 'employee']);
                $employee->syncRoles(['employee']);
            }
        }

        return back()->with('status', 'areas-updated');
    }
}
