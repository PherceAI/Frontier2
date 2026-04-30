# Contexto Maestro del Proyecto Frontier

## Nombre del sistema

El sistema se llamara **Frontier**.

Frontier es una plataforma de operaciones hoteleras concebida como el **Segundo Cerebro del hotel**. Su proposito es crear una capa inteligente sobre la operativa real del hotel y restaurante, centralizando datos, eventos, acciones, trazabilidad, validacion y toma de decisiones.

## Vision general

Frontier no es solamente un ERP ni un dashboard. Es una **capa inteligente de operacion** que se conecta con distintas fuentes de datos y con las personas que trabajan dentro del hotel.

La plataforma debe integrarse con:

- El ERP actual del hotel.
- El sistema contable.
- Fuentes internas de datos operativos.
- Datos cargados directamente por empleados.
- Eventos y acciones generadas por la operativa diaria.

El objetivo es que el dueno, gerentes y responsables puedan ver la operativa real del hotel, cruzar informacion, detectar discrepancias, tomar accion y monitorear el negocio en tiempo real.

Al mismo tiempo, los empleados tendran un espacio simple, principalmente mobile, donde puedan cargar datos, ver tareas pendientes, validar actividades realizadas y alimentar el sistema con informacion operativa que hoy se pierde o queda dispersa en formularios, mensajes o procesos manuales.

## Principios fundacionales

Frontier parte de varias ideas clave:

- Lo que no se puede medir, no se puede mejorar.
- Toda operacion importante debe tener trazabilidad.
- La informacion debe validarse, cruzarse y gobernarse.
- El sistema debe ser proactivo, no solo reactivo.
- Cada area debe funcionar de forma independiente, pero conectada al resto.
- La plataforma debe integrar pensamiento sistemico: cada parte tiene logica propia, pero todo forma un sistema operativo centralizado.

## Concepto operativo

Frontier debe funcionar como un **centro operativo completo** para hotel y restaurante.

El hotel tiene muchas areas y muchos procesos simultaneos. La plataforma debe permitir centralizar la informacion, entender que esta pasando, anticipar problemas y activar acciones entre areas.

Ejemplo conceptual:

1. Llega un grupo o se registra un evento.
2. Recepcion carga la informacion en Frontier.
3. El sistema analiza ese evento.
4. Se activan acciones relacionadas:
   - Verificar habitaciones necesarias.
   - Revisar disponibilidad y estado de habitaciones.
   - Consultar bodega/inventario para validar insumos.
   - Alertar si faltan recursos.
   - Notificar a lavanderia, camareras, cocina, mantenimiento o eventos segun aplique.
5. Cada area recibe tareas o informacion accionable.
6. Los empleados validan si realizaron o no las actividades.
7. La gerencia ve trazabilidad, cumplimiento, discrepancias y reportes.

## Areas clave a integrar

Frontier debe considerar, entre otras, las siguientes areas:

- Inventario / bodega.
- Recepcion.
- Contabilidad.
- Limpieza.
- Habitaciones.
- Lavanderia.
- Mantenimiento.
- Eventos.
- Camareras.
- Cocina / restaurante.
- Gerencia.
- Seguridad y roles.
- Otras areas operativas que puedan agregarse modularmente.

Cada area debe tener logica unica, pero tambien debe poder cruzarse con otras areas mediante datos, eventos, acciones y reglas de negocio.

## Arquitectura conceptual

Frontier debe trabajar de forma modular.

Cada modulo debe poder funcionar de forma independiente, con su propia logica, pantallas, datos y reglas. Sin embargo, los modulos no deben vivir aislados: deben conectarse mediante eventos, acciones, estados y cruces de informacion.

La logica de negocio debe ser fuerte. El sistema debe permitir:

- Registrar eventos operativos.
- Disparar acciones entre areas.
- Validar datos cargados por empleados.
- Detectar discrepancias.
- Generar alertas.
- Cruzar informacion entre fuentes.
- Producir reportes gerenciales.
- Mantener trazabilidad de quien hizo que, cuando y por que.
- Gobernar permisos, responsabilidades y accesos.

## Usuarios principales

### Dueno, gerencia y usuarios administrativos

Estos usuarios usaran principalmente computadora, aunque tambien deben poder acceder desde telefono.

Necesitan un panel gerencial o dashboard donde puedan:

- Ver datos globales del hotel.
- Monitorear la operativa en tiempo real.
- Cruzar informacion entre areas.
- Revisar alertas y discrepancias.
- Tomar accion.
- Ver reportes.
- Auditar trazabilidad.
- Entender que esta pasando en habitaciones, reservas, eventos, inventario, limpieza, lavanderia, mantenimiento, contabilidad y restaurante.

### Empleados y usuarios operativos

Estos usuarios usaran principalmente telefonos.

Necesitan una interfaz mobile-first, clara y facil de entender, donde puedan:

- Cargar datos.
- Ver pendientes.
- Confirmar tareas realizadas.
- Reportar incidencias.
- Validar actividades.
- Consultar informacion necesaria para su trabajo.
- Recibir alertas o instrucciones.
- Alimentar la trazabilidad del sistema.

La experiencia de empleado debe ser muy simple, rapida y enfocada en accion. No debe parecer un dashboard gerencial reducido, sino una interfaz pensada para trabajo operativo desde celular.

## Division de interfaces

Frontier debe separarse conceptualmente en dos grandes experiencias:

### 1. Panel gerencial / administrativo

Orientado a dueno, gerencia, administracion y responsables de area.

Debe priorizar:

- Dashboards.
- Indicadores.
- Cruces de datos.
- Reportes.
- Alertas.
- Auditoria.
- Acciones de gestion.
- Vision completa del hotel.

### 2. Portal operativo mobile

Orientado a empleados.

Debe priorizar:

- Tareas pendientes.
- Formularios simples.
- Validaciones.
- Estados claros.
- Carga rapida de datos.
- Eventos del dia.
- Acciones concretas.
- Uso comodo en telefono.

## Casos de uso esperados

### Recepcion

Recepcion puede cargar reservas, grupos, eventos, llegadas, salidas y cambios operativos.

Esos datos pueden activar acciones hacia habitaciones, limpieza, lavanderia, cocina, eventos, inventario o mantenimiento.

### Eventos

Cuando hay un evento, el sistema debe centralizar la informacion:

- Fecha y hora.
- Cantidad de personas.
- Habitaciones relacionadas si aplica.
- Menu o requerimientos de restaurante.
- Personal necesario.
- Insumos necesarios.
- Tareas para camareras, cocina, mantenimiento o limpieza.

Cada area debe poder ver lo que le corresponde y marcar avances.

### Lavanderia

Lavanderia debe poder cargar datos de ciclos, ropa lavada, cantidades, tiempos y estados.

Esa informacion puede cruzarse con habitaciones, eventos, ocupacion y necesidades futuras.

### Camareras

Camareras deben poder ver eventos, habitaciones asignadas, tareas pendientes y detalles operativos. Deben poder indicar si una tarea fue realizada, reportar incidencias o marcar validaciones.

### Cocina / restaurante

El chef o responsables de cocina deben poder ver eventos, menus, cantidades y requerimientos. El sistema debe ayudar a anticipar necesidades de insumos y coordinar con bodega.

### Inventario / bodega

Bodega debe centralizar insumos y disponibilidad. Debe cruzarse con eventos, restaurante, habitaciones, lavanderia y mantenimiento para alertar faltantes o necesidades.

### Gerencia

Gerencia debe ver todo:

- Reportes.
- Alertas.
- Discrepancias.
- Cumplimiento de tareas.
- Datos de areas.
- Cruces de informacion.
- Trazabilidad.
- Proactividad del sistema.

## Capacidades inteligentes esperadas

Frontier debe evolucionar hacia una capa inteligente capaz de:

- Analizar eventos operativos.
- Detectar relaciones entre areas.
- Recomendar acciones.
- Alertar problemas antes de que escalen.
- Validar consistencia de datos.
- Cruzar datos del ERP, contabilidad y registros internos.
- Generar reportes accionables.
- Mostrar la operativa real, no solo datos aislados.

## Requisitos de producto

- Debe funcionar perfectamente en computadoras y telefonos.
- Debe ser mobile-first para empleados.
- Debe ser claro, rapido y accionable.
- Debe mantener trazabilidad completa.
- Debe tener validacion de datos.
- Debe permitir gobernanza de roles y permisos.
- Debe estar preparado para crecer por modulos.
- Debe centralizar hotel y restaurante.
- Debe permitir cruce de datos entre areas.
- Debe evitar que la informacion quede dispersa.
- Debe convertir operaciones diarias en datos utiles.

## Direccion de diseno y experiencia

Frontier debe sentirse como una herramienta premium, precisa, minimalista y operativa.

La interfaz gerencial debe transmitir control, claridad y confianza. Debe permitir escanear informacion, comparar datos y tomar decisiones sin ruido visual.

La interfaz de empleados debe ser aun mas simple: mobile-first, directa, con tareas claras, estados visibles y pocos pasos para cargar informacion.

El sistema no debe parecer una landing page ni una app decorativa. Debe sentirse como una herramienta de trabajo real para operar un hotel y restaurante.

## Resumen ejecutivo para futuras IA

Frontier es una capa inteligente de operaciones hoteleras para un hotel-restaurante. Su objetivo es centralizar datos, eventos, tareas, validaciones y trazabilidad entre areas como recepcion, habitaciones, limpieza, lavanderia, mantenimiento, eventos, bodega, contabilidad, cocina y gerencia.

El sistema debe tener dos experiencias principales: un panel gerencial para ver datos, cruces, reportes, alertas y acciones; y un portal operativo mobile para empleados que cargan datos, ven pendientes y validan tareas.

La arquitectura debe ser modular, con pensamiento sistemico: cada area tiene logica propia, pero todo se conecta mediante eventos, acciones y reglas de negocio fuertes. Frontier debe funcionar como el Segundo Cerebro del hotel, permitiendo medir, mejorar, anticipar problemas y operar mejor.

## Decision agregada: gerencia, modulos y asignacion de areas

El panel gerencial inicial de Frontier contiene los modulos:

- Dashboard / Panel.
- Habitaciones.
- Bitacora.
- Eventos.
- Analisis.
- Empleados.

Gerencia usa este panel para ver, controlar, asignar, cruzar informacion y monitorear la operativa. El modulo Empleados debe permitir ver usuarios conectados, asignar areas y administrar responsabilidades.

Regla central de acceso: cuando un empleado se autentica, si aun no tiene area operativa asignada por gerencia, debe quedar en espera. Un empleado puede pertenecer a varias areas y cada area puede tener varios empleados. La relacion entre usuarios y areas debe tener trazabilidad: quien asigno, cuando asigno y si la asignacion esta activa.

Areas operativas base:

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

Esta decision queda detallada en `documentacion/gerencia-areas-accesos.dm`.

## Sincronizaciones externas

- Supabase legado: `frontier:sync-legacy-room-occupancy` corre cada hora en `America/Guayaquil` para mantener ocupacion/habitaciones actualizadas.
- Google Sheets inventario: `frontier:sync-google-inventory` y `frontier:sync-kitchen-inventory-movements` corren cada hora para resumen de inventario y movimientos de cocina/bodega.
- Contifico restaurante: `frontier:sync-contifico-restaurant --period=month` corre cada 3 horas en `America/Guayaquil` para mantener ventas, compras, cuentas por pagar y productos disponibles en el modulo Restaurante.
- El cierre de cocina `frontier:create-kitchen-inventory-closing` corre diario a la 1am y pertenece al dia operativo anterior.
