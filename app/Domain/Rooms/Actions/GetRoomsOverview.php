<?php

namespace App\Domain\Rooms\Actions;

use App\Domain\Shared\Data\ModuleMetricData;
use App\Domain\Shared\Data\ModuleOverviewData;
use Spatie\LaravelData\DataCollection;

class GetRoomsOverview
{
    public function handle(): ModuleOverviewData
    {
        return new ModuleOverviewData(
            module: 'rooms',
            title: 'Habitaciones',
            description: 'Inventario operativo de habitaciones, tipos, tarifas base y estado de disponibilidad.',
            status: 'Scaffold listo',
            metrics: ModuleMetricData::collect([
                new ModuleMetricData('Habitaciones', '0', 'Pendiente de carga inicial'),
                new ModuleMetricData('Disponibles', '0', 'Sin datos operativos'),
                new ModuleMetricData('Fuera de servicio', '0', 'Sin incidencias registradas'),
            ], DataCollection::class),
            nextSteps: [
                'Definir tipos de habitacion y capacidad.',
                'Cargar inventario de habitaciones fisicas.',
                'Conectar disponibilidad con Reservas y Limpieza.',
            ],
        );
    }
}
