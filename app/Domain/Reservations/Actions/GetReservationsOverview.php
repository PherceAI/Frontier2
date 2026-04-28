<?php

namespace App\Domain\Reservations\Actions;

use App\Domain\Shared\Data\ModuleMetricData;
use App\Domain\Shared\Data\ModuleOverviewData;
use Spatie\LaravelData\DataCollection;

class GetReservationsOverview
{
    public function handle(): ModuleOverviewData
    {
        return new ModuleOverviewData(
            module: 'reservations',
            title: 'Reservas',
            description: 'Flujo de reservas, check-in, check-out, huespedes y calendario de ocupacion.',
            status: 'Scaffold listo',
            metrics: ModuleMetricData::collect([
                new ModuleMetricData('Reservas activas', '0', 'Sin reservas cargadas'),
                new ModuleMetricData('Check-ins hoy', '0', 'Sin agenda del dia'),
                new ModuleMetricData('Ocupacion', '0%', 'Pendiente de habitaciones'),
            ], DataCollection::class),
            nextSteps: [
                'Crear el calendario de disponibilidad.',
                'Modelar huespedes y acompanantes.',
                'Emitir eventos de reserva para Billing y Housekeeping.',
            ],
        );
    }
}
