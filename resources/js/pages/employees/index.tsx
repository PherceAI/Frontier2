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
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardHeader className="gap-4 border-b border-border/60 p-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div className="flex items-start gap-4">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium text-foreground">
                            {employee.name.slice(0, 1).toUpperCase()}
                        </div>
                        <div className="grid gap-1">
                            <CardTitle className="text-base font-semibold text-foreground">
                                {employee.name}
                            </CardTitle>
                            <p className="text-sm font-normal tracking-[-0.01em] text-muted-foreground">{employee.email}</p>
                            <div className="mt-2 flex flex-wrap items-center gap-3 text-sm font-medium text-muted-foreground">
                                <span className="flex items-center gap-2">
                                    <span className={`size-2 rounded-full ${status.color}`} />
                                    {status.label}
                                </span>
                                <span className="flex items-center gap-2">
                                    <span
                                        className={`size-2 rounded-full ${employee.is_online ? 'bg-emerald-500' : 'bg-border'}`}
                                    />
                                    {employee.is_online ? 'Conectado' : 'Sin sesion activa'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {employee.roles.map((role) => (
                            <span key={role} className="flex items-center gap-2 text-sm font-normal text-muted-foreground">
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
                                    className="flex cursor-pointer items-start gap-3 rounded-lg border border-border/60 p-4 text-sm font-normal transition-colors duration-150 hover:bg-muted/50"
                                >
                                    <Checkbox
                                        id={inputId}
                                        checked={data.area_ids.includes(area.id)}
                                        onCheckedChange={(checked) => toggleArea(area.id, checked === true)}
                                        className="mt-0.5 rounded-lg"
                                    />
                                    <span className="grid gap-1">
                                        <span className="text-sm font-medium tracking-[-0.02em] text-foreground">{area.name}</span>
                                        <span className="text-sm leading-5 font-normal text-muted-foreground">{area.description}</span>
                                    </span>
                                </Label>
                            );
                        })}
                    </div>
                    <div className="flex flex-col gap-3 border-t border-border/60 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <span className="text-sm font-normal text-muted-foreground">
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
            <div className="flex h-full flex-1 flex-col gap-8 p-5 md:p-8">
                <section className="flex flex-col gap-4">
                    <span className="flex w-fit items-center gap-2 rounded-full border border-border/60 bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Control gerencial
                    </span>
                    <div className="grid gap-2">
                        <h1 className="text-2xl font-semibold text-foreground">Empleados</h1>
                        <p className="max-w-3xl text-sm leading-relaxed font-normal tracking-[-0.01em] text-muted-foreground">
                            Gestiona usuarios registrados, estado operativo y asignacion de una o varias areas por empleado.
                        </p>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {metrics.map((metric) => (
                        <Card key={metric.label} className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
                            <CardContent className="grid gap-3 p-6">
                                <p className="text-sm font-medium tracking-[-0.01em] text-muted-foreground">{metric.label}</p>
                                <p className="tabular-nums text-2xl font-semibold text-foreground">{metric.value}</p>
                                <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
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
                        <Card className="border-border/60 bg-card shadow-none">
                            <CardContent className="flex items-center gap-4 p-6">
                                <UsersRound className="size-5 text-muted-foreground" />
                                <p className="text-sm font-normal text-muted-foreground">Todavia no hay empleados registrados.</p>
                            </CardContent>
                        </Card>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
