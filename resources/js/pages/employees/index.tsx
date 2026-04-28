import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Check, LoaderCircle, UsersRound } from 'lucide-react';
import { FormEventHandler } from 'react';

type Area = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
};

type EmployeeArea = Area & {
    is_active: boolean;
};

type Employee = {
    id: number;
    name: string;
    email: string;
    operational_status: 'active' | 'pending_area_assignment' | 'suspended' | string;
    is_online: boolean;
    roles: string[];
    areas: EmployeeArea[];
    created_at: string | null;
};

type Summary = {
    total: number;
    pending: number;
    online: number;
    areas: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Empleados',
        href: '/employees',
    },
];

const statusCopy: Record<string, { label: string; color: string }> = {
    active: { label: 'Activo', color: 'bg-emerald-500' },
    pending_area_assignment: { label: 'Pendiente de area', color: 'bg-amber-500' },
    suspended: { label: 'Suspendido', color: 'bg-red-500' },
};

function EmployeeAreaForm({ employee, areas }: { employee: Employee; areas: Area[] }) {
    const { data, setData, patch, processing, recentlySuccessful } = useForm<{ area_ids: number[] }>({
        area_ids: employee.areas.filter((area) => area.is_active).map((area) => area.id),
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        patch(route('employees.areas.update', employee.id), {
            preserveScroll: true,
        });
    };

    const toggleArea = (areaId: number, checked: boolean) => {
        setData('area_ids', checked ? [...data.area_ids, areaId] : data.area_ids.filter((selectedAreaId) => selectedAreaId !== areaId));
    };

    const status = statusCopy[employee.operational_status] ?? { label: employee.operational_status, color: 'bg-neutral-400' };

    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardHeader className="gap-4 border-b border-neutral-200 p-6 dark:border-zinc-800">
                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div className="flex items-start gap-4">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-sm font-medium text-neutral-900 dark:bg-zinc-800 dark:text-zinc-50">
                            {employee.name.slice(0, 1).toUpperCase()}
                        </div>
                        <div className="grid gap-1">
                            <CardTitle className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                {employee.name}
                            </CardTitle>
                            <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{employee.email}</p>
                            <div className="mt-2 flex flex-wrap items-center gap-3 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                <span className="flex items-center gap-2">
                                    <span className={`size-2 rounded-full ${status.color}`} />
                                    {status.label}
                                </span>
                                <span className="flex items-center gap-2">
                                    <span
                                        className={`size-2 rounded-full ${employee.is_online ? 'bg-emerald-500' : 'bg-neutral-300 dark:bg-zinc-700'}`}
                                    />
                                    {employee.is_online ? 'Conectado' : 'Sin sesion activa'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {employee.roles.map((role) => (
                            <span key={role} className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                <span className="size-2 rounded-full bg-sky-500" />
                                {role}
                            </span>
                        ))}
                    </div>
                </div>
            </CardHeader>
            <CardContent className="p-6">
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {areas.map((area) => {
                            const inputId = `employee-${employee.id}-area-${area.id}`;

                            return (
                                <Label
                                    key={area.id}
                                    htmlFor={inputId}
                                    className="flex cursor-pointer items-start gap-3 rounded-lg border border-neutral-200 p-4 text-sm font-normal transition-colors hover:bg-neutral-100 dark:border-zinc-800 dark:hover:bg-zinc-800/50"
                                >
                                    <Checkbox
                                        id={inputId}
                                        checked={data.area_ids.includes(area.id)}
                                        onCheckedChange={(checked) => toggleArea(area.id, checked === true)}
                                        className="mt-0.5 rounded-lg"
                                    />
                                    <span className="grid gap-1">
                                        <span className="text-sm font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">{area.name}</span>
                                        <span className="text-sm leading-5 font-normal text-neutral-500 dark:text-zinc-400">{area.description}</span>
                                    </span>
                                </Label>
                            );
                        })}
                    </div>
                    <div className="flex flex-col gap-3 border-t border-neutral-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                        <span className="text-sm font-normal text-neutral-500 dark:text-zinc-400">
                            {data.area_ids.length === 0 ? 'Sin areas asignadas' : `${data.area_ids.length} area(s) asignada(s)`}
                        </span>
                        <Button type="submit" disabled={processing} className="rounded-lg tracking-[-0.02em]">
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            {recentlySuccessful && !processing ? <Check className="size-4" /> : null}
                            Guardar asignacion
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

export default function EmployeesIndex({ employees, areas, summary }: { employees: Employee[]; areas: Area[]; summary: Summary }) {
    const metrics = [
        { label: 'Empleados', value: summary.total, state: 'Registrados' },
        { label: 'Pendientes', value: summary.pending, state: 'Esperan area' },
        { label: 'Conectados', value: summary.online, state: 'Ultimos 5 min' },
        { label: 'Areas', value: summary.areas, state: 'Operativas' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empleados" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 tracking-[-0.02em] dark:bg-zinc-950">
                <section className="flex flex-col gap-4">
                    <span className="flex w-fit items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Control gerencial
                    </span>
                    <div className="grid gap-2">
                        <h1 className="text-3xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Empleados</h1>
                        <p className="max-w-3xl text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">
                            Gestiona usuarios registrados, estado operativo y asignacion de una o varias areas por empleado.
                        </p>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {metrics.map((metric) => (
                        <Card key={metric.label} className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="grid gap-3 p-6">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{metric.label}</p>
                                <p className="text-3xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">{metric.value}</p>
                                <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    <span className="size-2 rounded-full bg-sky-500" />
                                    {metric.state}
                                </span>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-4">
                    {employees.length > 0 ? (
                        employees.map((employee) => <EmployeeAreaForm key={employee.id} employee={employee} areas={areas} />)
                    ) : (
                        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="flex items-center gap-4 p-6">
                                <UsersRound className="size-5 text-neutral-500 dark:text-zinc-400" />
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Todavia no hay empleados registrados.</p>
                            </CardContent>
                        </Card>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
