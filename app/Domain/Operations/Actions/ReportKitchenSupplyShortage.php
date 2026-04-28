<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEntry;
use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalNotification;
use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Models\User;

class ReportKitchenSupplyShortage
{
    /**
     * @param array{supply: string, quantity?: string|null, notes?: string|null} $payload
     */
    public function handle(User $user, array $payload): OperationalEvent
    {
        $restaurant = Area::where('slug', 'restaurant')->firstOrFail();
        $inventory = Area::where('slug', 'inventory')->first();
        $management = Area::where('slug', 'management')->first();
        $form = OperationalForm::where('slug', 'kitchen-supply-shortage')->firstOrFail();

        $event = OperationalEvent::create([
            'area_id' => $restaurant->id,
            'created_by' => $user->id,
            'type' => 'supply_shortage',
            'source' => 'employee',
            'title' => 'Faltante de insumo en cocina',
            'description' => trim(($payload['supply'] ?? 'Insumo').' '.($payload['quantity'] ?? '')),
            'status' => 'pending',
            'severity' => 'high',
            'starts_at' => now(),
            'payload' => [
                'supply' => $payload['supply'],
                'quantity' => $payload['quantity'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ],
        ]);

        if ($inventory) {
            $task = OperationalTask::create([
                'operational_event_id' => $event->id,
                'assigned_area_id' => $inventory->id,
                'created_by' => $user->id,
                'type' => 'supply_restock',
                'title' => 'Validar faltante reportado por Cocina',
                'description' => $event->description,
                'status' => OperationalTask::STATUS_PENDING,
                'priority' => 'high',
                'requires_validation' => true,
                'due_at' => now()->addHour(),
                'metadata' => [
                    'origin_area' => 'restaurant',
                    'generated_from' => 'kitchen_supply_shortage',
                ],
            ]);

            OperationalNotification::create([
                'area_id' => $inventory->id,
                'operational_event_id' => $event->id,
                'operational_task_id' => $task->id,
                'type' => 'urgent',
                'channel' => 'webpush',
                'status' => 'pending',
                'title' => 'Faltante reportado por Cocina',
                'body' => $event->description,
                'scheduled_at' => now(),
            ]);
        }

        if ($management) {
            OperationalNotification::create([
                'area_id' => $management->id,
                'operational_event_id' => $event->id,
                'type' => 'urgent',
                'channel' => 'webpush',
                'status' => 'pending',
                'title' => 'Cocina reporto un faltante',
                'body' => $event->description,
                'scheduled_at' => now(),
            ]);
        }

        OperationalEntry::create([
            'operational_form_id' => $form->id,
            'operational_event_id' => $event->id,
            'area_id' => $restaurant->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'payload' => $payload,
        ]);

        return $event;
    }
}
