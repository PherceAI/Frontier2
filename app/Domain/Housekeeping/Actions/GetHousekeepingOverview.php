<?php

namespace App\Domain\Housekeeping\Actions;

use App\Domain\Shared\Data\ModuleMetricData;
use App\Domain\Shared\Data\ModuleOverviewData;
use Spatie\LaravelData\DataCollection;

class GetHousekeepingOverview
{
    public function handle(): ModuleOverviewData
    {
        return new ModuleOverviewData(
            module: 'housekeeping',
            title: 'Limpieza',
            description: 'Planificacion de limpieza, inspecciones, incidencias y preparacion de habitaciones.',
            status: 'Scaffold listo',
            metrics: ModuleMetricData::collect([
                new ModuleMetricData('Tareas abiertas', '0', 'Sin tareas programadas'),
                new ModuleMetricData('Habitaciones listas', '0', 'Pendiente de inventario'),
                new ModuleMetricData('Incidencias', '0', 'Sin reportes'),
            ], DataCollection::class),
            nextSteps: [
                'Crear colas de tareas por salida y estancia.',
                'Asignar responsables con roles operativos.',
                'Notificar cambios en tiempo real con Reverb.',
            ],
        );
    }
}
