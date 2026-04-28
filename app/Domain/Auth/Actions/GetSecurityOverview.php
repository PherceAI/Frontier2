<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Shared\Data\ModuleMetricData;
use App\Domain\Shared\Data\ModuleOverviewData;
use Spatie\LaravelData\DataCollection;

class GetSecurityOverview
{
    public function handle(): ModuleOverviewData
    {
        return new ModuleOverviewData(
            module: 'auth',
            title: 'Seguridad y usuarios',
            description: 'Usuarios, roles, permisos, autenticacion headless con Fortify y auditoria operativa.',
            status: 'Scaffold listo',
            metrics: ModuleMetricData::collect([
                new ModuleMetricData('Usuarios', '0', 'Pendiente de seed inicial'),
                new ModuleMetricData('Roles base', '0', 'Pendiente de seed RBAC'),
                new ModuleMetricData('Eventos auditados', '0', 'Activitylog instalado'),
            ], DataCollection::class),
            nextSteps: [
                'Crear roles de administracion, recepcion, limpieza y caja.',
                'Sembrar permisos por modulo del ERP.',
                'Activar politica de 2FA para cuentas administrativas.',
            ],
        );
    }
}
