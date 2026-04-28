<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalTask;
use App\Models\User;
use Illuminate\Support\Collection;

class GetKitchenOperationalPortal
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        $areas = $user->activeAreas()->orderBy('name')->get(['areas.id', 'areas.name', 'areas.slug']);
        $restaurantArea = $areas->firstWhere('slug', 'restaurant');

        $events = OperationalEvent::query()
            ->where(function ($query) use ($restaurantArea) {
                $query->whereNull('area_id');

                if ($restaurantArea) {
                    $query->orWhere('area_id', $restaurantArea->id);
                }
            })
            ->whereDate('starts_at', today())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('starts_at')
            ->get();

        $tasks = OperationalTask::query()
            ->with('event')
            ->where(function ($query) use ($user, $restaurantArea) {
                $query->where('assigned_user_id', $user->id);

                if ($restaurantArea) {
                    $query->orWhere('assigned_area_id', $restaurantArea->id);
                }
            })
            ->whereNotIn('status', [
                OperationalTask::STATUS_COMPLETED,
                OperationalTask::STATUS_VALIDATED,
                OperationalTask::STATUS_CANCELLED,
            ])
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->orderBy('due_at')
            ->get();

        $forms = OperationalForm::query()
            ->where('status', 'active')
            ->where(function ($query) use ($restaurantArea) {
                $query->whereNull('area_id');

                if ($restaurantArea) {
                    $query->orWhere('area_id', $restaurantArea->id);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'employee' => [
                'name' => $user->name,
                'areas' => $areas->map(fn ($area): array => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'slug' => $area->slug,
                ])->values(),
            ],
            'service' => [
                'dateLabel' => now()->locale('es')->translatedFormat('l d M'),
                'shift' => 'Turno operativo',
                'status' => $tasks->where('priority', 'urgent')->isNotEmpty() ? 'Requiere atencion' : 'Listo para servicio',
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
                'canComplete' => $this->canCompleteTask($user, $task),
            ])->values(),
            'forms' => $forms->map(fn (OperationalForm $form): array => [
                'id' => $form->id,
                'slug' => $form->slug,
                'name' => $form->name,
                'context' => $form->context,
                'schema' => $form->schema,
            ])->values(),
            'criticalSupplies' => $this->criticalSuppliesFromEvents($events),
        ];
    }

    private function canCompleteTask(User $user, OperationalTask $task): bool
    {
        if ($task->assigned_user_id) {
            return $task->assigned_user_id === $user->id || $user->hasManagementAccess();
        }

        if (! $task->assigned_area_id) {
            return $user->hasManagementAccess();
        }

        return $user->activeAreas()->whereKey($task->assigned_area_id)->exists();
    }

    /**
     * @param Collection<int, OperationalEvent> $events
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

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En preparacion',
            'completed' => 'Completado',
            'pending_validation' => 'Requiere validacion',
            'validated' => 'Validado',
            'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
            default => str($status)->replace('_', ' ')->headline()->toString(),
        };
    }
}
