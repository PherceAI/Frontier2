<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Shared\Data\ModuleMetricData;
use App\Domain\Shared\Data\ModuleOverviewData;
use Spatie\LaravelData\DataCollection;

class GetBillingOverview
{
    public function handle(): ModuleOverviewData
    {
        return new ModuleOverviewData(
            module: 'billing',
            title: 'Facturacion',
            description: 'Cargos de hospedaje, consumos, pagos, comprobantes y cierre de cuenta.',
            status: 'Scaffold listo',
            metrics: ModuleMetricData::collect([
                new ModuleMetricData('Cuentas abiertas', '0', 'Sin reservas facturables'),
                new ModuleMetricData('Pagos registrados', '0', 'Sin caja inicial'),
                new ModuleMetricData('Pendiente de cobro', '$0.00', 'Sin saldos'),
            ], DataCollection::class),
            nextSteps: [
                'Definir cargos por noche y consumos adicionales.',
                'Crear flujo de pagos y anulaciones auditadas.',
                'Conectar cierre de cuenta con check-out.',
            ],
        );
    }
}
