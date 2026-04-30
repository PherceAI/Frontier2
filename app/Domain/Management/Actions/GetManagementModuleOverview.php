<?php

namespace App\Domain\Management\Actions;

use App\Domain\Shared\Data\ModuleMetricData;
use App\Domain\Shared\Data\ModuleOverviewData;
use Spatie\LaravelData\DataCollection;

class GetManagementModuleOverview
{
    public function handle(string $module): ModuleOverviewData
    {
        return match ($module) {
            'logbook' => new ModuleOverviewData(
                module: 'logbook',
                title: 'Bitacora operacional',
                description: 'Registro central de eventos, acciones, validaciones y cambios por area.',
                status: 'Definicion inicial',
                metrics: ModuleMetricData::collect([
                    new ModuleMetricData('Eventos registrados', '0', 'Pendiente de ingestion'),
                    new ModuleMetricData('Areas conectadas', '0', 'Base operacional lista'),
                    new ModuleMetricData('Discrepancias', '0', 'Sin reglas activas'),
                ], DataCollection::class),
                nextSteps: [
                    'Definir tipos de eventos operativos por area.',
                    'Registrar actor, area, origen, severidad y entidad relacionada.',
                    'Activar bus de eventos para cruzar recepcion, habitaciones, limpieza, bodega y restaurante.',
                ],
            ),
            'events' => new ModuleOverviewData(
                module: 'events',
                title: 'Eventos',
                description: 'Centro de coordinacion para grupos, menus, habitaciones, insumos y tareas interarea.',
                status: 'Definicion inicial',
                metrics: ModuleMetricData::collect([
                    new ModuleMetricData('Eventos activos', '0', 'Pendiente de agenda'),
                    new ModuleMetricData('Tareas generadas', '0', 'Sin automatizaciones'),
                    new ModuleMetricData('Areas involucradas', '0', 'Por configurar'),
                ], DataCollection::class),
                nextSteps: [
                    'Crear entidad evento con fecha, responsable, asistentes y requerimientos.',
                    'Conectar eventos con habitaciones, cocina, camareras, bodega y mantenimiento.',
                    'Disparar tareas automaticas al confirmar un evento.',
                ],
            ),
            'analytics' => new ModuleOverviewData(
                module: 'analytics',
                title: 'Analisis',
                description: 'Cruce inteligente de datos operativos, contables y humanos para detectar patrones y alertas.',
                status: 'Definicion inicial',
                metrics: ModuleMetricData::collect([
                    new ModuleMetricData('Fuentes conectadas', '0', 'ERP y contabilidad pendientes'),
                    new ModuleMetricData('Alertas activas', '0', 'Sin reglas publicadas'),
                    new ModuleMetricData('Reportes gerenciales', '0', 'Pendiente de modelos'),
                ], DataCollection::class),
                nextSteps: [
                    'Definir indicadores gerenciales de ocupacion, costos, cumplimiento y discrepancias.',
                    'Conectar ERP actual y sistema contable como fuentes externas.',
                    'Crear reglas de cruce para detectar faltantes, retrasos y desviaciones.',
                ],
            ),
            'employees' => new ModuleOverviewData(
                module: 'employees',
                title: 'Empleados',
                description: 'Gestion de usuarios, areas asignadas, estados de acceso y responsabilidades operativas.',
                status: 'Base RBAC y areas lista',
                metrics: ModuleMetricData::collect([
                    new ModuleMetricData('Empleados activos', '0', 'Pendiente de carga'),
                    new ModuleMetricData('Pendientes de area', '0', 'Esperando asignacion'),
                    new ModuleMetricData('Areas operativas', '12', 'Seed inicial definido'),
                ], DataCollection::class),
                nextSteps: [
                    'Crear pantalla gerencial para asignar una o varias areas por empleado.',
                    'Mostrar usuarios conectados, ultimo acceso y estado operativo.',
                    'Restringir portal operativo hasta que el empleado tenga area activa.',
                ],
            ),
            default => abort(404),
        };
    }
}
