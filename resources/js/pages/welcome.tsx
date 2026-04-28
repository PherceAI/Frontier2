import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BedDouble, CalendarCheck2, CreditCard, Sparkles } from 'lucide-react';

const metrics = [
    { label: 'Ocupacion', value: '82%', status: 'En ritmo' },
    { label: 'Check-ins', value: '24', status: 'Hoy' },
    { label: 'Habitaciones listas', value: '118', status: 'Operativo' },
];

const modules = [
    { title: 'Reservas', description: 'Llegadas, salidas y cambios de estancia.', icon: CalendarCheck2 },
    { title: 'Habitaciones', description: 'Inventario vivo por estado operativo.', icon: BedDouble },
    { title: 'Limpieza', description: 'Tareas, inspecciones y readiness.', icon: Sparkles },
    { title: 'Facturacion', description: 'Cargos, pagos y cierre de cuenta.', icon: CreditCard },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Frontier">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
            </Head>
            <main className="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-zinc-950 dark:text-zinc-50">
                <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-6 px-6 py-6 tracking-[-0.02em] lg:px-8">
                    <header className="flex items-center justify-between border-b border-neutral-200 pb-4 dark:border-zinc-800">
                        <Link href={route('home')} className="flex items-center gap-2 text-sm font-medium text-neutral-900 dark:text-zinc-50">
                            <span className="flex size-8 items-center justify-center rounded-lg border border-neutral-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                                <BedDouble className="size-4 text-neutral-900 dark:text-zinc-50" />
                            </span>
                            Frontier
                        </Link>
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild className="rounded-lg tracking-[-0.02em]">
                                    <Link href={route('dashboard')}>
                                        Panel
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" className="rounded-lg tracking-[-0.02em]">
                                        <Link href={route('login')}>Ingresar</Link>
                                    </Button>
                                    <Button asChild className="rounded-lg tracking-[-0.02em]">
                                        <Link href={route('register')}>Crear cuenta</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </header>

                    <section className="grid flex-1 items-center gap-6 py-6 lg:grid-cols-[1fr_0.86fr]">
                        <div className="flex max-w-3xl flex-col gap-6">
                            <div className="flex flex-col gap-4">
                                <div className="inline-flex w-fit items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm font-normal text-neutral-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                                    <span className="size-2 rounded-full bg-emerald-500" />
                                    Operacion hotelera unificada
                                </div>
                                <h1 className="max-w-2xl text-3xl leading-tight font-semibold tracking-[-0.02em] text-neutral-900 sm:text-5xl dark:text-zinc-50">
                                    Precision operativa para cada habitacion, reserva y cuenta.
                                </h1>
                                <p className="max-w-xl text-base leading-7 font-normal text-neutral-500 dark:text-zinc-400">
                                    Un ERP modular para hoteles de una sola sede, construido con Laravel, Inertia y React para operar reservas,
                                    housekeeping, facturacion y seguridad desde una misma superficie.
                                </p>
                            </div>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Button asChild size="lg" className="rounded-lg tracking-[-0.02em]">
                                    <Link href={auth.user ? route('dashboard') : route('login')}>
                                        Abrir operacion
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                {!auth.user && (
                                    <Button asChild size="lg" variant="outline" className="rounded-lg tracking-[-0.02em]">
                                        <Link href={route('register')}>Configurar acceso</Link>
                                    </Button>
                                )}
                            </div>
                        </div>

                        <Card className="overflow-hidden rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardHeader className="border-b border-neutral-200 p-6 dark:border-zinc-800">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="grid gap-1">
                                        <CardTitle className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                            Pulso de hoy
                                        </CardTitle>
                                        <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Vista compacta de recepcion.</p>
                                    </div>
                                    <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                        <span className="size-2 rounded-full bg-emerald-500" />
                                        Activo
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-6 p-6">
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {metrics.map((metric) => (
                                        <div key={metric.label} className="grid gap-2 rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                            <span className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{metric.label}</span>
                                            <strong className="text-3xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                                {metric.value}
                                            </strong>
                                            <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                                <span className="size-2 rounded-full bg-sky-500" />
                                                {metric.status}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                                <div className="grid gap-3">
                                    {modules.map((module) => (
                                        <div
                                            key={module.title}
                                            className="flex items-center gap-4 rounded-lg p-4 transition-colors hover:bg-neutral-100 dark:hover:bg-zinc-800/50"
                                        >
                                            <module.icon className="size-5 text-neutral-500 dark:text-zinc-400" />
                                            <div className="grid gap-1">
                                                <h2 className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                                    {module.title}
                                                </h2>
                                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{module.description}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </main>
        </>
    );
}
