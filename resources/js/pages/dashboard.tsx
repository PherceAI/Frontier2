import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BarChart3, BedDouble, BookOpenText, CalendarDays, LayoutDashboard, UsersRound, UtensilsCrossed } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
];

const modules = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        description: 'Pulso gerencial, alertas, indicadores y acciones clave.',
        icon: LayoutDashboard,
    },
    {
        title: 'Habitaciones',
        href: '/rooms',
        description: 'Inventario, estados y readiness operacional.',
        icon: BedDouble,
    },
    {
        title: 'Bitácora',
        href: '/logbook',
        description: 'Eventos, acciones, validaciones y cambios por área.',
        icon: BookOpenText,
    },
    {
        title: 'Eventos',
        href: '/events',
        description: 'Grupos, restaurante, habitaciones, personal e insumos.',
        icon: CalendarDays,
    },
    {
        title: 'Restaurante',
        href: '/restaurant',
        description: 'Ventas, cobros, proveedores, margen estimado y desempeño desde Contifico.',
        icon: UtensilsCrossed,
    },
    {
        title: 'Análisis',
        href: '/analytics',
        description: 'Cruces de información, discrepancias y reportes.',
        icon: BarChart3,
    },
    {
        title: 'Empleados',
        href: '/employees',
        description: 'Usuarios, áreas asignadas, presencia y responsabilidades.',
        icon: UsersRound,
    },
];

const operatingMetrics = [
    { label: 'Áreas operativas', value: '11', state: 'Modelo base' },
    { label: 'Empleados pendientes', value: '0', state: 'Asignación gerencial' },
    { label: 'Eventos abiertos', value: '0', state: 'Por conectar' },
    { label: 'Alertas activas', value: '0', state: 'Reglas pendientes' },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel" />
            <div className="flex h-full flex-1 flex-col gap-8 p-5 md:p-8">

                {/* Hero */}
                <section className="flex flex-col gap-3">
                    <div className="flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 dark:border-emerald-900 dark:bg-emerald-950/40">
                        <span className="size-1.5 rounded-full bg-emerald-500" />
                        <span className="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">Sistema activo</span>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <h1 className="text-2xl font-semibold text-foreground">
                            Frontier
                        </h1>
                        <p className="max-w-2xl text-sm leading-relaxed text-muted-foreground">
                            Segunda capa de control del hotel: gerencia ve, asigna y cruza información entre habitaciones, eventos, empleados, bitácora y análisis operativo.
                        </p>
                    </div>
                </section>

                {/* KPIs */}
                <section className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    {operatingMetrics.map((metric) => (
                        <Card key={metric.label} className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
                            <CardContent className="flex flex-col gap-3 p-5">
                                <p className="text-xs font-medium text-muted-foreground">{metric.label}</p>
                                <p className="tabular-nums text-2xl font-semibold text-foreground">{metric.value}</p>
                                <div className="flex items-center gap-1.5">
                                    <span className="size-1.5 rounded-full bg-sky-400" />
                                    <span className="text-[11px] text-muted-foreground">{metric.state}</span>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                {/* Modules */}
                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    {modules.map((module) => (
                        <Card
                            key={module.href}
                            className="group border-border/60 bg-card shadow-none transition-all duration-150 hover:border-border hover:shadow-sm"
                        >
                            <Link
                                href={module.href}
                                className="block h-full rounded-[inherit] outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <CardHeader className="flex h-full flex-col items-start justify-between gap-6 p-5">
                                    <div className="flex w-full items-start justify-between gap-4">
                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border/60 bg-muted text-muted-foreground transition-colors duration-150 group-hover:bg-primary/8 group-hover:text-primary group-hover:border-primary/20">
                                            <module.icon className="size-4" />
                                        </div>
                                        <ArrowRight className="size-3.5 text-muted-foreground/40 transition-all duration-150 group-hover:translate-x-0.5 group-hover:text-foreground" />
                                    </div>
                                    <div className="flex flex-col gap-1">
                                        <CardTitle className="text-sm font-semibold text-foreground">
                                            {module.title}
                                        </CardTitle>
                                        <p className="text-xs leading-relaxed text-muted-foreground">
                                            {module.description}
                                        </p>
                                    </div>
                                </CardHeader>
                            </Link>
                        </Card>
                    ))}
                </section>

            </div>
        </AppLayout>
    );
}
