# Estado Del Core Operativo Frontier

Actualizado: 2026-04-29

## Resumen

Frontier ya tiene una base modular para operar hotel y restaurante con dos experiencias separadas:

- Panel gerencial: dashboards, cruces, empleados, habitaciones, restaurante, inventario y analisis.
- Portal operativo mobile: experiencia comun para empleados por area.

La decision central queda fijada: el sistema opera con el flujo `Eventos -> Tareas -> Formularios -> Validaciones -> Alertas`.

## Arquitectura Actual

- Backend Laravel 13 + Inertia React.
- Dominio modular en `app/Domain`.
- No hay API publica externa; las pantallas usan Inertia.
- Los datos externos se cachean localmente antes de mostrarse.
- `main` es la rama estable para despliegue en Coolify.

Modulos implementados o conectados:

- Habitaciones: ocupacion diaria desde Supabase legacy, cache local y dashboard gerencial.
- Restaurante: Contifico, ventas, proveedores, cuentas por pagar y dashboard gerencial.
- Inventario: Google Sheets Apps Script, valor de inventario, cuentas por pagar, movimientos por area.
- Empleados: asignacion de areas, estado operativo y control de acceso.
- Operaciones: eventos, tareas, formularios, entradas y notificaciones.
- Portal empleado: mobile-first, comun para todas las areas.

## Flujo Operativo Oficial

1. Algo importante ocurre o se registra como `OperationalEvent`.
2. El evento puede generar una o varias `OperationalTask`.
3. Cada tarea puede asignarse a un usuario especifico o a un area completa.
4. El empleado completa la tarea desde `/operativo`.
5. Si la tarea requiere validacion, queda en `pending_validation`.
6. Gerencia o un lider de area puede validar o rechazar.
7. Los formularios dinamicos generan `OperationalEntry`.
8. Las alertas se guardan como `OperationalNotification`, preparadas para in-app y webpush.

## Portal Operativo Mobile

Ruta: `/operativo`.

La pantalla comun tiene 3 vistas:

- Inicio: resumen del area activa, pendientes, completadas, validaciones y alertas.
- Cargar: formularios activos del area seleccionada.
- Tareas: tareas asignadas al usuario o al area.

Si un empleado pertenece a varias areas, puede cambiar el area activa desde la parte superior del portal. La URL usa `?area=slug`.

## Validaciones

Una tarea puede completarse directamente o quedar pendiente de validacion.

Pueden validar:

- Usuarios con acceso gerencial.
- Lideres del area de la tarea.

El liderazgo de area se guarda en `area_user.is_lead`.

Campos nuevos en tareas:

- `validated_by`
- `validated_at`
- `validation_notes`

## Fuentes Externas Conectadas

Supabase legacy:

- Tabla: `ocupacion_historico`.
- Comando: `php artisan frontier:sync-legacy-room-occupancy`.
- Uso: ocupacion diaria de habitaciones.

Contifico:

- Comando: `php artisan frontier:sync-contifico-restaurant`.
- Uso: ventas, documentos, proveedores y restaurante.

Google Sheets inventario:

- Variable: `INVENTORY_GOOGLE_SHEETS_URL`.
- Comando: `php artisan frontier:sync-google-inventory`.
- Uso: inventario, cuentas por pagar y movimientos por area.

Scheduler:

- Sincroniza habitaciones e inventario cada hora.

## Preparacion Para Coolify

Produccion debe ejecutar:

- App Laravel.
- PostgreSQL.
- Redis.
- Scheduler.
- Queue worker o Horizon.
- Variables `.env` en Coolify.
- Build frontend con Vite.

No subir secretos al repositorio. Las claves de Supabase, Contifico, Reverb/webpush y produccion viven en variables de entorno.

Antes de desplegar:

```bash
php artisan test
npm run build
php artisan schedule:list
```

## Estado De Verificacion

Ultima verificacion local:

- `php artisan test`: suite verde.
- `npm run build`: compila.
- `php artisan schedule:list`: muestra sync horario de habitaciones e inventario.

## Proximos Pasos Por Area

- Gerencia: crear pantallas para crear eventos, delegar tareas y validar pendientes.
- Recepcion: carga de llegadas, salidas, grupos y eventos.
- Habitaciones: cruzar ocupacion con limpieza, mantenimiento y amenities.
- Limpieza: formularios de readiness, incidencias y validacion de habitaciones.
- Lavanderia: ciclos, cantidades, entregas y cruces con ocupacion.
- Mantenimiento: reportes, prioridades, validaciones y cierre tecnico.
- Inventario: convertir faltantes y consumos altos en tareas accionables.
- Cocina / Restaurante: evolucionar desde faltantes hacia produccion, eventos y requerimientos.
- Camareras: tareas derivadas de eventos, habitaciones y restaurante.
- Analisis: reglas de discrepancias y alertas gerenciales.
