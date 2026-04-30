<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\OperationalEntry;
use App\Domain\Operations\Models\OperationalForm;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class SubmitOperationalForm
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(User $user, OperationalForm $form, array $payload): OperationalEntry
    {
        if (! $this->canSubmit($user, $form)) {
            throw new AuthorizationException('No puedes cargar datos en este formulario.');
        }

        return OperationalEntry::create([
            'operational_form_id' => $form->id,
            'area_id' => $form->area_id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'payload' => [
                'fields' => $payload['fields'] ?? [],
                'notes' => $payload['notes'] ?? null,
                'submitted_from' => 'employee_portal',
            ],
        ]);
    }

    private function canSubmit(User $user, OperationalForm $form): bool
    {
        if ($form->status !== 'active') {
            return false;
        }

        if (! $form->area_id) {
            return true;
        }

        return $user->activeAreas()->whereKey($form->area_id)->exists();
    }
}
