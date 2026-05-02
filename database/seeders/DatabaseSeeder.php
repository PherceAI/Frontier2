<?php

namespace Database\Seeders;

use App\Domain\Operations\Models\OperationalEvent;
use App\Domain\Operations\Models\OperationalForm;
use App\Domain\Operations\Models\OperationalNotification;
use App\Domain\Operations\Models\OperationalTask;
use App\Domain\Organization\Models\Area;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect([
            'dashboard.view',
            'rooms.view',
            'rooms.manage',
            'logbook.view',
            'logbook.manage',
            'events.view',
            'events.manage',
            'analytics.view',
            'employees.view',
            'employees.manage',
            'areas.manage',
            'security.view',
            'security.manage',
        ])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]));

        $administrator = Role::firstOrCreate(['name' => 'administrator']);
        $management = Role::firstOrCreate(['name' => 'management']);
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $pending = Role::firstOrCreate(['name' => 'pending_assignment']);

        $administrator->syncPermissions($permissions);
        $management->syncPermissions([
            'dashboard.view',
            'rooms.view',
            'logbook.view',
            'events.view',
            'analytics.view',
            'employees.view',
            'employees.manage',
            'areas.manage',
        ]);
        $employee->syncPermissions(['dashboard.view']);
        $pending->syncPermissions([]);

        $areas = collect([
            ['name' => 'Gerencia', 'slug' => 'management', 'description' => 'Control, asignacion, supervision y toma de decisiones.'],
            ['name' => 'Recepcion', 'slug' => 'reception', 'description' => 'Llegadas, salidas, reservas, grupos y coordinacion inicial.'],
            ['name' => 'Habitaciones', 'slug' => 'rooms', 'description' => 'Inventario, disponibilidad y estado operativo de habitaciones.'],
            ['name' => 'Limpieza', 'slug' => 'housekeeping', 'description' => 'Tareas, inspecciones, readiness e incidencias.'],
            ['name' => 'Lavanderia', 'slug' => 'laundry', 'description' => 'Ciclos, prendas, tiempos y disponibilidad textil.'],
            ['name' => 'Mantenimiento', 'slug' => 'maintenance', 'description' => 'Incidencias tecnicas, reparaciones y verificaciones.'],
            ['name' => 'Inventario / Bodega', 'slug' => 'inventory', 'description' => 'Insumos, faltantes, movimientos y necesidades por evento.'],
            ['name' => 'Contabilidad', 'slug' => 'accounting', 'description' => 'Cruce contable, pagos, cierres y conciliaciones.'],
            ['name' => 'Eventos', 'slug' => 'events', 'description' => 'Grupos, agenda, requerimientos y coordinacion interarea.'],
            ['name' => 'Cocina / Restaurante', 'slug' => 'restaurant', 'description' => 'Menus, produccion, requerimientos e insumos.'],
            ['name' => 'Camareras', 'slug' => 'waitstaff', 'description' => 'Atencion operativa de eventos y restaurante.'],
            ['name' => 'Nochero', 'slug' => 'night_auditor', 'description' => 'Turno nocturno, rondas, novedades, cierres y soporte operativo fuera de horario.'],
        ])->map(fn (array $area) => Area::firstOrCreate(
            ['slug' => $area['slug']],
            ['name' => $area['name'], 'description' => $area['description'], 'is_active' => true],
        ));

        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('password'),
            'operational_status' => 'active',
        ]);

        $admin->assignRole($administrator);
        $admin->forceFill(['operational_status' => 'active'])->save();
        $admin->areas()->syncWithoutDetaching(
            $areas->mapWithKeys(fn (Area $area) => [
                $area->id => [
                    'assigned_by' => $admin->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ],
            ])->all(),
        );

        $restaurant = Area::where('slug', 'restaurant')->firstOrFail();

        $lunchEvent = OperationalEvent::firstOrCreate([
            'type' => 'restaurant_service',
            'title' => 'Almuerzo grupo corporativo',
            'starts_at' => today()->setTime(12, 30),
        ], [
            'area_id' => $restaurant->id,
            'created_by' => $admin->id,
            'source' => 'seed',
            'description' => '28 pax - Menu ejecutivo - Salon principal',
            'status' => 'in_progress',
            'severity' => 'normal',
            'ends_at' => today()->setTime(14, 0),
            'payload' => [
                'pax' => 28,
                'menu' => 'Menu ejecutivo',
                'location' => 'Salon principal',
                'critical_supplies' => [
                    ['name' => 'Proteina principal', 'quantity' => '28 porciones', 'status' => 'Pendiente'],
                    ['name' => 'Vegetales frescos', 'quantity' => '8 kg', 'status' => 'Pendiente'],
                ],
            ],
        ]);

        OperationalEvent::firstOrCreate([
            'type' => 'restaurant_service',
            'title' => 'Cena huespedes internos',
            'starts_at' => today()->setTime(19, 0),
        ], [
            'area_id' => $restaurant->id,
            'created_by' => $admin->id,
            'source' => 'seed',
            'description' => 'Servicio restaurante - Carta controlada',
            'status' => 'pending',
            'severity' => 'normal',
            'ends_at' => today()->setTime(21, 30),
            'payload' => [
                'service' => 'Carta controlada',
                'critical_supplies' => [
                    ['name' => 'Bebidas base', 'quantity' => 'Por validar', 'status' => 'Pendiente'],
                ],
            ],
        ]);

        collect([
            [
                'title' => 'Validar mise en place',
                'description' => 'Estacion caliente y fria',
                'type' => 'mise_en_place',
                'priority' => 'normal',
                'requires_validation' => false,
                'due_at' => today()->setTime(11, 30),
            ],
            [
                'title' => 'Revisar insumos criticos',
                'description' => 'Proteina, vegetales, bebidas y paneria',
                'type' => 'supply_check',
                'priority' => 'high',
                'requires_validation' => true,
                'due_at' => today()->setTime(10, 30),
            ],
            [
                'title' => 'Confirmar menu del evento',
                'description' => 'Cruzar con recepcion y eventos',
                'type' => 'event_menu_confirmation',
                'priority' => 'normal',
                'requires_validation' => false,
                'due_at' => today()->setTime(10, 0),
            ],
        ])->each(fn (array $task) => OperationalTask::firstOrCreate([
            'operational_event_id' => $lunchEvent->id,
            'assigned_area_id' => $restaurant->id,
            'title' => $task['title'],
        ], [
            'created_by' => $admin->id,
            'type' => $task['type'],
            'description' => $task['description'],
            'status' => OperationalTask::STATUS_PENDING,
            'priority' => $task['priority'],
            'requires_validation' => $task['requires_validation'],
            'due_at' => $task['due_at'],
            'metadata' => ['seeded' => true],
        ]));

        OperationalForm::firstOrCreate([
            'slug' => 'kitchen-supply-shortage',
        ], [
            'area_id' => $restaurant->id,
            'name' => 'Reportar faltante de insumo',
            'context' => 'shortage',
            'status' => 'active',
            'schema' => [
                'fields' => [
                    ['name' => 'supply', 'label' => 'Insumo', 'type' => 'text', 'required' => true],
                    ['name' => 'quantity', 'label' => 'Cantidad estimada', 'type' => 'text', 'required' => false],
                    ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'required' => false],
                ],
            ],
        ]);

        OperationalNotification::firstOrCreate([
            'area_id' => $restaurant->id,
            'operational_event_id' => $lunchEvent->id,
            'type' => 'daily_summary',
            'title' => 'Resumen diario de Cocina',
            'scheduled_at' => today()->setTime(7, 0),
        ], [
            'channel' => 'webpush',
            'status' => 'pending',
            'body' => 'Tienes eventos, tareas e insumos criticos por revisar.',
        ]);

        $this->call(RestaurantCatalogSeeder::class);
    }
}
