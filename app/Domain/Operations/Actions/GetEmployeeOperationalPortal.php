<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalNotification;
use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Models\User;
use Illuminate\Support\Collection;

class GetEmployeeOperationalPortal
{
    public function __construct(private readonly CanOperateOnTask $permissions) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, ?string $activeAreaSlug = null): array
    {
        $areas = $user->activeAreas()->orderBy('name')->get(['areas.id', 'areas.name', 'areas.slug']);
        $activeArea = $this->activeArea($areas, $activeAreaSlug);
        $tasks = $this->tasks($user, $activeArea);
        $events = $this->events($activeArea);
        $forms = $this->forms($activeArea);
        $notifications = $this->notifications($user, $activeArea);

        return [
            'employee' => [
                'name' => $user->name,
                'areas' => $areas->map(fn (Area $area): array => $this->areaData($area))->values(),
            ],
            'activeArea' => $activeArea ? $this->areaData($activeArea) : null,
            'summary' => [
                'dateLabel' => now()->locale('es')->translatedFormat('l d M'),
                'pending' => $tasks->whereIn('status', [OperationalTask::STATUS_PENDING, OperationalTask::STATUS_IN_PROGRESS])->count(),
                'completed' => $tasks->whereIn('status', [OperationalTask::STATUS_COMPLETED, OperationalTask::STATUS_VALIDATED])->count(),
                'pendingValidation' => $tasks->where('status', OperationalTask::STATUS_PENDING_VALIDATION)->count(),
                'alerts' => $notifications->count(),
            ],
            'events' => $events->map(fn (OperationalEvent $event): array => [
                'id' => $event->id,
                'time' => $event->starts_at?->format('H:i') ?? '--:--',
                'title' => $event->title,
                'detail' => $event->description,
                'status' => $this->statusLabel($event->status),
                'severity' => $event->severity,
                'payload' => $event->payload ?? [],
            ])->values(),
            'tasks' => $tasks->map(fn (OperationalTask $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'detail' => $task->description,
                'status' => $this->statusLabel($task->status),
                'rawStatus' => $task->status,
                'priority' => $task->priority,
                'requiresValidation' => $task->requires_validation,
                'eventTitle' => $task->event?->title,
                'dueAt' => $task->due_at?->format('H:i'),
                'assignedArea' => $task->assignedArea?->name,
                'canComplete' => $this->permissions->complete($user, $task),
                'canValidate' => $task->status === OperationalTask::STATUS_PENDING_VALIDATION && $this->permissions->validate($user, $task),
            ])->values(),
            'forms' => $forms->map(fn (OperationalForm $form): array => [
                'id' => $form->id,
                'slug' => $form->slug,
                'name' => $form->name,
                'context' => $form->context,
                'schema' => $form->schema,
            ])->values(),
            'notifications' => $notifications->map(fn (OperationalNotification $notification): array => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'scheduledAt' => $notification->scheduled_at?->toIso8601String(),
            ])->values(),
            'criticalSupplies' => $this->criticalSuppliesFromEvents($events),
        ];
    }

    /**
     * @param  Collection<int, Area>  $areas
     */
    private function activeArea(Collection $areas, ?string $slug): ?Area
    {
        if ($slug) {
            return $areas->firstWhere('slug', $slug) ?? $areas->first();
        }

        return $areas->first();
    }

    /**
     * @return Collection<int, OperationalTask>
     */
    private function tasks(User $user, ?Area $area): Collection
    {
        return OperationalTask::query()
            ->with(['event', 'assignedArea'])
            ->where(function ($query) use ($user, $area) {
                $query->where('assigned_user_id', $user->id);

                if ($area) {
                    $query->orWhere('assigned_area_id', $area->id);
                }
            })
            ->whereNotIn('status', [OperationalTask::STATUS_CANCELLED])
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->orderBy('due_at')
            ->get();
    }

    /**
     * @return Collection<int, OperationalEvent>
     */
    private function events(?Area $area): Collection
    {
        return OperationalEvent::query()
            ->where(function ($query) use ($area) {
                $query->whereNull('area_id');

                if ($area) {
                    $query->orWhere('area_id', $area->id);
                }
            })
            ->whereDate('starts_at', today())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, OperationalForm>
     */
    private function forms(?Area $area): Collection
    {
        return OperationalForm::query()
            ->where('status', 'active')
            ->where(function ($query) use ($area) {
                $query->whereNull('area_id');

                if ($area) {
                    $query->orWhere('area_id', $area->id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, OperationalNotification>
     */
    private function notifications(User $user, ?Area $area): Collection
    {
        return OperationalNotification::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($user, $area) {
                $query->where('user_id', $user->id);

                if ($area) {
                    $query->orWhere('area_id', $area->id);
                }
            })
            ->latest('scheduled_at')
            ->limit(6)
            ->get();
    }

    /**
     * @param  Collection<int, OperationalEvent>  $events
     * @return array<int, array{name: string, quantity: string, status: string}>
     */
    private function criticalSuppliesFromEvents(Collection $events): array
    {
        return $events
            ->flatMap(fn (OperationalEvent $event): array => $event->payload['critical_supplies'] ?? [])
            ->map(fn (array $supply): array => [
                'name' => (string) ($supply['name'] ?? 'Insumo'),
                'quantity' => (string) ($supply['quantity'] ?? 'Por validar'),
                'status' => (string) ($supply['status'] ?? 'Pendiente'),
            ])
            ->values()
            ->all();
    }

    private function areaData(Area $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->name,
            'slug' => $area->slug,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En progreso',
            'completed' => 'Completado',
            'pending_validation' => 'Requiere validacion',
            'validated' => 'Validado',
            'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
            default => str($status)->replace('_', ' ')->headline()->toString(),
        };
    }
}
