# Frontier: Gerencia, Areas y Accesos Operativos

## Objetivo

Definir la base funcional para que **gerencia** controle Frontier desde el panel principal y para que los empleados no puedan operar hasta que tengan una o varias areas asignadas.

## Panel gerencial

El panel de gerencia es la superficie principal para ver, controlar, asignar y cruzar informacion. Los modulos base son:

- **Dashboard**: pulso gerencial, indicadores, alertas y acciones clave.
- **Habitaciones**: inventario, estados, disponibilidad y readiness operacional.
- **Bitacora**: registro de lo que pasa en cada area, eventos, acciones, validaciones y cambios.
- **Eventos**: grupos, restaurante, habitaciones, personal, menus, insumos y coordinacion interarea.
- **Analisis**: cruces de informacion, discrepancias, reportes y reglas inteligentes.
- **Empleados**: usuarios conectados, estado operativo, areas asignadas y responsabilidades.

## Regla de acceso principal

Cuando una persona se registra o inicia sesion:

1. Si es administrador, puede entrar al panel gerencial.
2. Si tiene acceso de gerencia, puede entrar al panel gerencial.
3. Si tiene al menos un area operativa activa, pero no es gerencia, entra solamente al portal operativo mobile.
3. Si no tiene areas asignadas, queda en estado `pending_area_assignment`.
4. Mientras este pendiente, solo ve una pantalla de espera: gerencia debe asignarle una o varias areas.

Esta regla evita que un empleado recien creado entre al sistema sin contexto operativo ni responsabilidades.

Los empleados **nunca deben entrar al panel gerencial**. El panel queda reservado para `administrator`, `management` o usuarios con area activa `management`.

## Modelo de areas

Las areas son entidades propias del dominio operacional. Un empleado puede pertenecer a varias areas y cada area puede tener varios empleados.

Relacion:

- `users` muchos a muchos `areas`.
- Tabla pivote: `area_user`.

Campos clave:

- `area_user.area_id`
- `area_user.user_id`
- `area_user.assigned_by`
- `area_user.assigned_at`
- `area_user.is_active`

Esto permite trazabilidad sobre quien asigno el area, cuando lo hizo y si la asignacion sigue activa.

## Estado operativo del usuario

La tabla `users` incluye `operational_status`.

Estados iniciales:

- `pending_area_assignment`: usuario autenticado, pero sin area activa.
- `active`: usuario con acceso operacional.
- `suspended`: usuario bloqueado operacionalmente.

La regla efectiva de acceso no depende solo del texto del estado: tambien valida si el usuario tiene areas activas, excepto administradores.

## Areas base

Las areas iniciales de Frontier son:

- Gerencia.
- Recepcion.
- Habitaciones.
- Limpieza.
- Lavanderia.
- Mantenimiento.
- Inventario / Bodega.
- Contabilidad.
- Eventos.
- Cocina / Restaurante.
- Camareras.
- Nochero.

Estas areas representan el mapa operacional inicial del hotel-restaurante.

## Roles y permisos base

Frontier usa Spatie Permission.

Roles iniciales:

- `administrator`: acceso total.
- `management`: acceso gerencial para ver, asignar y controlar.
- `employee`: acceso operativo despues de asignacion.
- `pending_assignment`: cuenta creada pero esperando area.

Permisos gerenciales iniciales:

- `dashboard.view`
- `rooms.view`
- `rooms.manage`
- `logbook.view`
- `logbook.manage`
- `events.view`
- `events.manage`
- `analytics.view`
- `employees.view`
- `employees.manage`
- `areas.manage`
- `security.view`
- `security.manage`

## Frontend

La navegacion gerencial debe mostrar:

- Panel.
- Habitaciones.
- Bitacora.
- Eventos.
- Analisis.
- Empleados.

La interfaz gerencial esta orientada a computadora y telefono, pero prioriza control, escaneo, indicadores y accion.

La interfaz operativa de empleados es distinta: mobile-first, simple, enfocada en tareas, carga de datos y validaciones. Su entrada inicial es `/operativo`.

Primer portal operativo creado:

- Area `restaurant` / Cocina-Restaurante.
- Ruta: `/operativo`.
- Pagina Inertia: `employee/kitchen`.
- Enfoque: eventos del dia, estado del servicio y checklist de pendientes.

## Backend

La base implementada incluye:

- Modelo `Area`.
- Relacion `User::areas()`.
- Relacion `User::activeAreas()`.
- Metodo `User::hasOperationalAccess()`.
- Metodo `User::isAwaitingAreaAssignment()`.
- Middleware `area.assigned`.
- Middleware `management.access`.
- Ruta operativa `/operativo`.
- Pantalla `pending-assignment` para usuarios sin area.
- Seeders de areas, roles y permisos iniciales.

## Base de datos

Tablas nuevas:

- `areas`
- `area_user`

Campo nuevo:

- `users.operational_status`

## Siguiente paso funcional

Construir el modulo **Empleados** para que gerencia pueda:

- Ver usuarios conectados.
- Ver usuarios pendientes de area.
- Asignar una o varias areas a un empleado.
- Activar o desactivar asignaciones.
- Cambiar estado operativo.
- Auditar quien asigno cada area.
