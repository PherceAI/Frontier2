import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BarChart3, BedDouble, BookOpenText, CalendarDays, LayoutDashboard, UsersRound } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel',
        href: '/dashboard',
    },
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
        title: 'Bitacora',
        href: '/logbook',
        description: 'Eventos, acciones, validaciones y cambios por area.',
        icon: BookOpenText,
    },
    {
        title: 'Eventos',
        href: '/events',
        description: 'Grupos, restaurante, habitaciones, personal e insumos.',
        icon: CalendarDays,
    },
    {
        title: 'Analisis',
        href: '/analytics',
        description: 'Cruces de informacion, discrepancias y reportes.',
        icon: BarChart3,
    },
    {
        title: 'Empleados',
        href: '/employees',
        description: 'Usuarios, areas asignadas, presencia y responsabilidades.',
        icon: UsersRound,
    },
];

const operatingMetrics = [
    { label: 'Areas operativas', value: '11', state: 'Modelo base' },
    { label: 'Empleados pendientes', value: '0', state: 'Asignacion gerencial' },
    { label: 'Eventos abiertos', value: '0', state: 'Por conectar' },
    { label: 'Alertas activas', value: '0', state: 'Reglas pendientes' },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 tracking-[-0.02em] dark:bg-zinc-950">
                <section className="flex flex-col gap-4">
                    <span className="flex w-fit items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Capa inteligente
                    </span>
                    <div className="grid gap-2">
                        <h1 className="text-3xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Frontier</h1>
                        <p className="max-w-3xl text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">
                            Segundo Cerebro del hotel: una capa inteligente para que gerencia vea, controle, asigne y cruce informacion entre
                            habitaciones, eventos, empleados, bitacora y analisis operativo.
                        </p>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {operatingMetrics.map((metric) => (
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

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {modules.map((module) => (
                        <Card key={module.href} className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <Link href={module.href} className="block rounded-xl transition-colors hover:bg-neutral-100 dark:hover:bg-zinc-800/50">
                                <CardHeader className="flex flex-row items-start justify-between gap-4 p-6">
                                    <div className="grid gap-4">
                                        <module.icon className="size-5 text-neutral-500 dark:text-zinc-400" />
                                        <div className="grid gap-2">
                                            <CardTitle className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                                {module.title}
                                            </CardTitle>
                                            <p className="text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">{module.description}</p>
                                        </div>
                                    </div>
                                    <ArrowRight className="size-4 text-neutral-500 dark:text-zinc-400" />
                                </CardHeader>
                            </Link>
                        </Card>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}
