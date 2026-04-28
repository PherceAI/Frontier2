# Frontier Operational Core

## Decision arquitectonica

Frontier usa una arquitectura hibrida:

- Un motor operativo comun para eventos, tareas, formularios simples, entradas, notificaciones y trazabilidad.
- Modulos especificos por area cuando la logica del dominio lo necesita.

El piloto especifico es Cocina / Restaurante, construido sobre el motor comun.

## Tablas base

- `operational_events`: hechos operativos creados por gerencia, empleados, integraciones, cron jobs o reglas del sistema.
- `operational_tasks`: tareas asignables a area, empleado o evento.
- `operational_forms`: definicion simple de formularios por area/contexto.
- `operational_entries`: respuestas o cargas realizadas por empleados.
- `operational_notifications`: reglas y bitacora de notificaciones por area, usuario, evento o tarea.

## Tareas

Una tarea puede estar relacionada con:

- `assigned_area_id`
- `assigned_user_id`
- `operational_event_id`

Regla de cierre:

- Si tiene `assigned_user_id`, solo ese empleado o gerencia puede completarla.
- Si solo tiene `assigned_area_id`, cualquier empleado activo de esa area puede completarla.
- Si es critica (`requires_validation`), al completarse pasa a `pending_validation`.
- Si no es critica, pasa a `completed`.

Estados base:

- `pending`
- `in_progress`
- `completed`
- `pending_validation`
- `validated`
- `rejected`
- `cancelled`

## Formularios simples

V1 soporta formularios configurables simples:

- `text`
- `number`
- `date`
- `time`
- `select`
- `textarea`
- `checkbox`
- `checklist`
- `photo` mas adelante

Las respuestas se guardan en `operational_entries` con usuario, area, evento/tarea relacionada y payload JSON.

## Cocina piloto

Ruta:

- `/operativo`

Si el usuario tiene area `restaurant`, se renderiza `employee/kitchen`.

La pantalla muestra:

- Eventos del dia.
- Tareas pendientes.
- Insumos criticos derivados de eventos.
- Formulario para reportar faltantes.

Acciones iniciales:

- Completar tarea.
- Reportar faltante de insumo.

Reportar un faltante genera:

- Evento `supply_shortage` en Cocina.
- Tarea para Bodega si existe el area `inventory`.
- Notificacion urgente para Bodega.
- Notificacion urgente para Gerencia.
- Entrada operacional asociada al formulario `kitchen-supply-shortage`.

## PWA

El manifest inicia en `/operativo`.

El service worker cachea `/operativo` para lectura offline. Las acciones mutantes siguen requiriendo conexion en v1.
