# Uso de Laravel Boost en Frontier

## Estado actual

Laravel Boost ya esta instalado en el proyecto y `boost.json` tiene MCP habilitado.

Configuracion actual:

- `guidelines`: activo.
- `mcp`: activo.
- Skills instaladas:
  - `fortify-development`
  - `laravel-best-practices`
  - `configuring-horizon`
  - `pulse-development`
  - `inertia-react-development`
  - `tailwindcss-development`

Tambien existe configuracion local para Codex:

```toml
[mcp_servers.laravel-boost]
command = "php"
args = ["artisan", "boost:mcp"]
cwd = "C:\\Users\\Keybe\\Pictures\\2026\\hotel-erp"
```

## Para que sirve

Laravel Boost permite que la IA trabaje con contexto real del proyecto Laravel. Segun la documentacion oficial, el MCP de Boost puede exponer herramientas para:

- Leer informacion de la aplicacion.
- Inspeccionar esquema de base de datos.
- Ejecutar queries.
- Revisar rutas.
- Ejecutar comandos Artisan.
- Ejecutar codigo con Tinker.
- Leer configuracion.
- Consultar documentacion actual de Laravel y paquetes instalados.
- Revisar logs y ultimos errores.

## Regla de trabajo para Frontier

Cuando Boost este disponible como MCP en la sesion, se debe usar antes de adivinar:

- Para revisar rutas: usar inspector de rutas.
- Para revisar tablas: usar database schema.
- Para validar datos reales: usar database query.
- Para diagnosticar errores: usar logs / last error.
- Para dudas de Laravel, Inertia, Tailwind o Fortify: usar documentation search.

Si Boost no aparece como herramienta MCP en la sesion, usar comandos equivalentes:

- `php artisan route:list`
- `php artisan migrate:status`
- `php artisan tinker`
- `php artisan test`
- `php artisan optimize:clear`
- lectura directa de logs en `storage/logs`

## Nota importante

Si el servidor local corre con `php artisan serve --no-reload`, los cambios backend no se reflejan hasta reiniciar el proceso de Laravel. Despues de cambios en rutas, controladores, middleware o `.env`, reiniciar Laravel antes de validar en navegador.
