import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ClipboardCheck, CookingPot, LoaderCircle, LogOut, PackageCheck, Send, Timer, TriangleAlert } from 'lucide-react';
import { FormEventHandler } from 'react';

type Area = {
    id: number;
    name: string;
    slug: string;
};

type EventItem = {
    id: number;
    time: string;
    title: string;
    detail: string | null;
    status: string;
    severity: string;
};

type TaskItem = {
    id: number;
    title: string;
    detail: string | null;
    status: string;
    rawStatus: string;
    priority: string;
    requiresValidation: boolean;
    eventTitle: string | null;
    dueAt: string | null;
    canComplete: boolean;
};

type OperationalForm = {
    id: number;
    slug: string;
    name: string;
    context: string;
    schema: {
        fields: Array<{
            name: string;
            label: string;
            type: string;
            required?: boolean;
        }>;
    };
};

type CriticalSupply = {
    name: string;
    quantity: string;
    status: string;
};

type KitchenProps = {
    employee: {
        name: string;
        areas: Area[];
    };
    service: {
        dateLabel: string;
        shift: string;
        status: string;
    };
    events: EventItem[];
    tasks: TaskItem[];
    forms: OperationalForm[];
    criticalSupplies: CriticalSupply[];
};

function statusDot(status: string) {
    if (status === 'urgent' || status === 'high') {
        return 'bg-red-500';
    }

    if (status === 'pending_validation') {
        return 'bg-amber-500';
    }

    if (status === 'completed' || status === 'validated') {
        return 'bg-emerald-500';
    }

    return 'bg-sky-500';
}

export default function Kitchen({ employee, service, events, tasks, forms, criticalSupplies }: KitchenProps) {
    const shortageForm = forms.find((form) => form.slug === 'kitchen-supply-shortage');
    const {
        data: shortage,
        setData: setShortage,
        post,
        processing: reporting,
        reset,
    } = useForm({
        supply: '',
        quantity: '',
        notes: '',
    });

    const reportShortage: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('employee.kitchen.shortages.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const completeTask = (task: TaskItem) => {
        router.patch(
            route('employee.tasks.complete', task.id),
            {
                notes: `Completado desde portal Cocina: ${task.title}`,
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title="Cocina" />
            <main className="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-zinc-950 dark:text-zinc-50">
                <div className="mx-auto flex min-h-screen w-full max-w-md flex-col gap-6 px-4 py-4 tracking-[-0.02em]">
                    <header className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl border border-neutral-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                                <CookingPot className="size-5 text-neutral-900 dark:text-zinc-50" />
                            </div>
                            <div className="grid gap-1">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Frontier operativo</p>
                                <h1 className="text-2xl font-semibold tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Cocina</h1>
                            </div>
                        </div>
                        <Button asChild variant="ghost" size="icon" className="rounded-lg">
                            <Link href={route('logout')} method="post" aria-label="Cerrar sesion">
                                <LogOut className="size-5 text-neutral-500 dark:text-zinc-400" />
                            </Link>
                        </Button>
                    </header>

                    <section className="grid gap-3">
                        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="grid gap-4 p-6">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="grid gap-1">
                                        <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{service.dateLabel}</p>
                                        <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                            Hola, {employee.name}
                                        </h2>
                                    </div>
                                    <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                        <span className="size-2 rounded-full bg-emerald-500" />
                                        {service.status}
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                        <Timer className="mb-3 size-5 text-neutral-500 dark:text-zinc-400" />
                                        <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Turno</p>
                                        <p className="mt-1 text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                            {service.shift}
                                        </p>
                                    </div>
                                    <div className="rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                        <PackageCheck className="mb-3 size-5 text-neutral-500 dark:text-zinc-400" />
                                        <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Pendientes</p>
                                        <p className="mt-1 text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                            {tasks.length}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <section className="grid gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Eventos de hoy</h2>
                            <span className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{events.length}</span>
                        </div>
                        <div className="grid gap-3">
                            {events.map((event) => (
                                <Card
                                    key={event.id}
                                    className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900"
                                >
                                    <CardContent className="grid gap-3 p-4">
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="grid gap-1">
                                                <p className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{event.time}</p>
                                                <h3 className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                                    {event.title}
                                                </h3>
                                            </div>
                                            <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                                <span className={`size-2 rounded-full ${statusDot(event.severity)}`} />
                                                {event.status}
                                            </span>
                                        </div>
                                        {event.detail && (
                                            <p className="text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">{event.detail}</p>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>

                    <section className="grid gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Insumos criticos</h2>
                            <TriangleAlert className="size-5 text-neutral-500 dark:text-zinc-400" />
                        </div>
                        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="grid gap-0 p-0">
                                {criticalSupplies.length > 0 ? (
                                    criticalSupplies.map((supply) => (
                                        <div
                                            key={`${supply.name}-${supply.quantity}`}
                                            className="border-b border-neutral-200 p-4 last:border-b-0 dark:border-zinc-800"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="grid gap-1">
                                                    <p className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                                        {supply.name}
                                                    </p>
                                                    <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{supply.quantity}</p>
                                                </div>
                                                <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                                    <span className="size-2 rounded-full bg-amber-500" />
                                                    {supply.status}
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="p-4 text-sm font-normal text-neutral-500 dark:text-zinc-400">Sin insumos criticos para hoy.</p>
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    <section className="grid gap-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">Pendientes</h2>
                            <ClipboardCheck className="size-5 text-neutral-500 dark:text-zinc-400" />
                        </div>
                        <Card className="overflow-hidden rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardHeader className="border-b border-neutral-200 p-4 dark:border-zinc-800">
                                <CardTitle className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                    Checklist del servicio
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-0 p-0">
                                {tasks.length > 0 ? (
                                    tasks.map((task) => (
                                        <div
                                            key={task.id}
                                            className="grid gap-3 border-b border-neutral-200 p-4 last:border-b-0 dark:border-zinc-800"
                                        >
                                            <div className="flex items-start gap-3">
                                                <Checkbox
                                                    className="mt-1 rounded-lg"
                                                    checked={task.rawStatus === 'completed' || task.rawStatus === 'pending_validation'}
                                                    disabled={!task.canComplete}
                                                    onCheckedChange={(checked) => {
                                                        if (checked === true && task.canComplete) {
                                                            completeTask(task);
                                                        }
                                                    }}
                                                />
                                                <span className="grid gap-1">
                                                    <span className="text-base font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">
                                                        {task.title}
                                                    </span>
                                                    {task.detail && (
                                                        <span className="text-sm leading-5 font-normal text-neutral-500 dark:text-zinc-400">
                                                            {task.detail}
                                                        </span>
                                                    )}
                                                    <span className="mt-1 flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                                        <span className={`size-2 rounded-full ${statusDot(task.rawStatus)}`} />
                                                        {task.status}
                                                    </span>
                                                    {task.requiresValidation && (
                                                        <span className="text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                                            Requiere validacion
                                                        </span>
                                                    )}
                                                </span>
                                            </div>
                                            {task.canComplete && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="rounded-lg tracking-[-0.02em]"
                                                    onClick={() => completeTask(task)}
                                                >
                                                    Marcar como hecho
                                                </Button>
                                            )}
                                        </div>
                                    ))
                                ) : (
                                    <p className="p-4 text-sm font-normal text-neutral-500 dark:text-zinc-400">Sin pendientes activos.</p>
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    {shortageForm && (
                        <section className="grid gap-3 pb-6">
                            <h2 className="text-lg font-medium tracking-[-0.02em] text-neutral-900 dark:text-zinc-50">{shortageForm.name}</h2>
                            <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                                <CardContent className="p-4">
                                    <form onSubmit={reportShortage} className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="supply" className="text-sm font-medium tracking-[-0.02em]">
                                                Insumo
                                            </Label>
                                            <Input
                                                id="supply"
                                                value={shortage.supply}
                                                onChange={(event) => setShortage('supply', event.target.value)}
                                                placeholder="Ej. Proteina principal"
                                                className="rounded-lg"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="quantity" className="text-sm font-medium tracking-[-0.02em]">
                                                Cantidad estimada
                                            </Label>
                                            <Input
                                                id="quantity"
                                                value={shortage.quantity}
                                                onChange={(event) => setShortage('quantity', event.target.value)}
                                                placeholder="Ej. 12 porciones"
                                                className="rounded-lg"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="notes" className="text-sm font-medium tracking-[-0.02em]">
                                                Notas
                                            </Label>
                                            <Input
                                                id="notes"
                                                value={shortage.notes}
                                                onChange={(event) => setShortage('notes', event.target.value)}
                                                placeholder="Contexto o alternativa"
                                                className="rounded-lg"
                                            />
                                        </div>
                                        <Button type="submit" disabled={reporting} className="rounded-lg tracking-[-0.02em]">
                                            {reporting ? <LoaderCircle className="size-4 animate-spin" /> : <Send className="size-4" />}
                                            Reportar faltante
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        </section>
                    )}
                </div>
            </main>
        </>
    );
}
