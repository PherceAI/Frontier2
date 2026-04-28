# Hotel ERP domain

This directory owns hotel business behavior for a single-property modular monolith.

Initial modules:

- `Rooms`: room inventory, room types, availability states.
- `Reservations`: guests, stays, check-in and check-out flow.
- `Housekeeping`: cleaning tasks, inspections and room readiness.
- `Billing`: invoices, charges, payments and account closeout.
- `Auth`: users, roles, permissions and security workflows.

Controllers inside a module are HTTP/Inertia adapters only. Business rules belong in module `Actions`, `Data`, `Models`, `Policies`, `Queries` or `Services`.

Intentional constraints:

- No tenant, branch, property or office scopes.
- No `branch_id` columns.
- No public REST API routes; browser flows use Inertia.
- No raw, staging or ETL database schemas.
