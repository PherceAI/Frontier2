import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, BedDouble, ClipboardCheck, FilePenLine, Home, ListChecks, LoaderCircle, LogOut, Send } from 'lucide-react';
import { FormEventHandler, ReactNode, useMemo, useState } from 'react';

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

type KitchenClosingItem = {
    id: number;
    stockItemId: number;
    category: string;
    productName: string;
    unit: string;
    unitDetail: string | null;
    physicalCount: string | null;
    wasteQuantity: string | null;
    notes: string | null;
    replenishmentRequired?: string | null;
    replenishmentActual?: string | null;
    hasReplenishmentAlert?: boolean;
};

type KitchenClosing = {
    id: number;
    status: 'pending_count' | 'count_submitted' | 'closed' | string;
    operatingDate: string;
    hasNegativeDiscrepancy: boolean;
    hasReplenishmentAlert: boolean;
    items: KitchenClosingItem[];
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
    assignedArea: string | null;
    canComplete: boolean;
    canValidate: boolean;
    kitchenClosing: KitchenClosing | null;
};

type RoomCleaning = {
    id: number;
    roomNumber: string;
    floor: number;
    roomType: string | null;
    cleaningType: 'checkout' | 'stay' | string;
    status: string;
    rawStatus: string;
    guestName: string | null;
    companyName: string | null;
    reservationCode: string | null;
    checkInDate: string | null;
    checkOutDate: string | null;
    scheduledAt: string | null;
    completedAt: string | null;
    novelties: {
        id: number;
        severity: string;
        body: string;
        userName: string | null;
        createdAt: string | null;
    }[];
};

type FormField = {
    name: string;
    label?: string;
    type: 'text' | 'textarea' | 'number' | 'date' | 'checkbox' | string;
    required?: boolean;
};

type OperationalForm = {
    id: number;
    slug: string;
    name: string;
    context: string;
    schema: {
        fields?: FormField[];
    };
};

type NotificationItem = {
    id: number;
    type: string;
    title: string;
    body: string | null;
};

type PortalProps = {
    employee: {
        name: string;
        areas: Area[];
    };
    activeArea: Area | null;
    summary: {
        dateLabel: string;
        pending: number;
        completed: number;
        pendingValidation: number;
        alerts: number;
    };
    events: EventItem[];
    tasks: TaskItem[];
    forms: OperationalForm[];
    notifications: NotificationItem[];
    roomCleanings: RoomCleaning[];
};

type Tab = 'home' | 'load' | 'tasks';

function statusDot(status: string) {
    if (status === 'urgent' || status === 'high' || status === 'rejected') {
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

export default function Operations({ employee, activeArea, summary, events, tasks, forms, notifications, roomCleanings }: PortalProps) {
    const [tab, setTab] = useState<Tab>('home');
    const activeTasks = tasks.filter((task) => !['completed', 'validated', 'cancelled'].includes(task.rawStatus));
    const completedTasks = tasks.filter((task) => ['completed', 'validated'].includes(task.rawStatus));

    return (
        <>
            <Head title="Operativo" />
            <main className="min-h-screen bg-neutral-50 pb-24 text-neutral-900 dark:bg-zinc-950 dark:text-zinc-50">
                <div className="mx-auto flex min-h-screen w-full max-w-md flex-col gap-5 px-4 py-4">
                    <header className="flex items-center justify-between gap-4">
                        <div className="grid gap-1">
                            <p className="text-sm text-neutral-500 dark:text-zinc-400">Frontier operativo</p>
                            <h1 className="text-2xl font-semibold text-neutral-900 dark:text-zinc-50">{activeArea?.name ?? 'Mi operacion'}</h1>
                        </div>
                        <Button asChild variant="ghost" size="icon" className="rounded-lg">
                            <Link href={route('logout')} method="post" aria-label="Cerrar sesion">
                                <LogOut className="size-5 text-neutral-500 dark:text-zinc-400" />
                            </Link>
                        </Button>
                    </header>

                    {employee.areas.length > 1 && (
                        <div className="flex gap-2 overflow-x-auto pb-1">
                            {employee.areas.map((area) => (
                                <Button
                                    key={area.id}
                                    asChild
                                    variant={activeArea?.slug === area.slug ? 'default' : 'outline'}
                                    className="h-9 shrink-0 rounded-lg"
                                >
                                    <Link href={`/operativo?area=${area.slug}`}>{area.name}</Link>
                                </Button>
                            ))}
                        </div>
                    )}

                    {tab === 'home' && (
                        <HomeTab
                            employeeName={employee.name}
                            summary={summary}
                            events={events}
                            notifications={notifications}
                            activeTasks={activeTasks}
                            completedTasks={completedTasks}
                            roomCleanings={roomCleanings}
                        />
                    )}

                    {tab === 'load' && <LoadTab forms={forms} />}
                    {tab === 'tasks' && <TasksTab tasks={tasks} roomCleanings={roomCleanings} />}
                </div>

                <nav className="fixed inset-x-0 bottom-0 border-t border-neutral-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
                    <div className="mx-auto grid max-w-md grid-cols-3 gap-2 px-4 py-3">
                        <TabButton tab="home" current={tab} setTab={setTab} icon={Home} label="Inicio" />
                        <TabButton tab="load" current={tab} setTab={setTab} icon={FilePenLine} label="Cargar" />
                        <TabButton tab="tasks" current={tab} setTab={setTab} icon={ListChecks} label="Tareas" />
                    </div>
                </nav>
            </main>
        </>
    );
}

function HomeTab({
    employeeName,
    summary,
    events,
    notifications,
    activeTasks,
    completedTasks,
    roomCleanings,
}: {
    employeeName: string;
    summary: PortalProps['summary'];
    events: EventItem[];
    notifications: NotificationItem[];
    activeTasks: TaskItem[];
    completedTasks: TaskItem[];
    roomCleanings: RoomCleaning[];
}) {
    return (
        <>
            <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                <CardContent className="grid gap-4 p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm text-neutral-500 dark:text-zinc-400">{summary.dateLabel}</p>
                            <h2 className="mt-1 text-lg font-medium text-neutral-900 dark:text-zinc-50">Hola, {employeeName}</h2>
                        </div>
                        <span className="flex items-center gap-2 text-sm text-neutral-500 dark:text-zinc-400">
                            <span className="size-2 rounded-full bg-emerald-500" />
                            Operativo
                        </span>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <MiniMetric label="Pendientes" value={summary.pending} />
                        <MiniMetric label="Completadas" value={summary.completed} />
                        <MiniMetric label="Validacion" value={summary.pendingValidation} />
                        <MiniMetric label="Alertas" value={summary.alerts} />
                    </div>
                </CardContent>
            </Card>

            <Section title="Alertas" count={notifications.length} icon={AlertTriangle}>
                {notifications.length > 0 ? (
                    notifications.map((notification) => (
                        <ListItem key={notification.id} title={notification.title} detail={notification.body} status={notification.type} />
                    ))
                ) : (
                    <EmptyState text="Sin alertas activas." />
                )}
            </Section>

            <Section title="Eventos de hoy" count={events.length} icon={ClipboardCheck}>
                {events.length > 0 ? (
                    events.map((event) => (
                        <ListItem key={event.id} title={`${event.time} / ${event.title}`} detail={event.detail} status={event.severity} />
                    ))
                ) : (
                    <EmptyState text="Sin eventos para esta area." />
                )}
            </Section>

            <Section title="Pulso de tareas" count={activeTasks.length + completedTasks.length} icon={ListChecks}>
                {roomCleanings.length > 0 && (
                    <ListItem
                        title="Habitaciones asignadas"
                        detail={`${roomCleanings.filter((task) => task.rawStatus !== 'completed').length} pendientes de limpieza`}
                        status="pending"
                    />
                )}
                <ListItem title="Pendientes activas" detail={`${activeTasks.length} por realizar o validar`} status="pending" />
                <ListItem title="Completadas" detail={`${completedTasks.length} cerradas o validadas`} status="completed" />
            </Section>
        </>
    );
}

function LoadTab({ forms }: { forms: OperationalForm[] }) {
    return (
        <section className="grid gap-4">
            <div className="grid gap-1">
                <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Reportes rapidos</h2>
                <p className="text-sm leading-5 text-neutral-500 dark:text-zinc-400">
                    Registra novedades del turno que no pertenecen a una tarea asignada.
                </p>
            </div>
            {forms.length > 0 ? (
                forms.map((form) => <DynamicForm key={form.id} form={form} />)
            ) : (
                <EmptyState text="No hay reportes activos para esta area." />
            )}
        </section>
    );
}

function DynamicForm({ form }: { form: OperationalForm }) {
    const fields = form.schema.fields ?? [];
    const initialFields = useMemo(
        () =>
            fields.reduce<Record<string, string | boolean>>((carry, field) => {
                carry[field.name] = field.type === 'checkbox' ? false : '';
                return carry;
            }, {}),
        [fields],
    );
    const { data, setData, post, processing, reset } = useForm<{ fields: Record<string, string | boolean>; notes: string }>({
        fields: initialFields,
        notes: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('employee.forms.entries.store', form.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardHeader className="border-b border-neutral-200 p-4 dark:border-zinc-800">
                <CardTitle className="text-base font-medium text-neutral-900 dark:text-zinc-50">{form.name}</CardTitle>
            </CardHeader>
            <CardContent className="p-4">
                <form onSubmit={submit} className="grid gap-4">
                    {fields.map((field) => (
                        <div key={field.name} className="grid gap-2">
                            <Label htmlFor={`${form.slug}-${field.name}`} className="text-sm font-medium">
                                {field.label ?? field.name}
                            </Label>
                            {field.type === 'textarea' ? (
                                <textarea
                                    id={`${form.slug}-${field.name}`}
                                    value={(data.fields[field.name] as string) ?? ''}
                                    onChange={(event) => setData('fields', { ...data.fields, [field.name]: event.target.value })}
                                    required={field.required}
                                    className="border-input bg-background min-h-24 rounded-lg border px-3 py-2 text-sm"
                                />
                            ) : field.type === 'checkbox' ? (
                                <Checkbox
                                    id={`${form.slug}-${field.name}`}
                                    checked={data.fields[field.name] === true}
                                    onCheckedChange={(checked) => setData('fields', { ...data.fields, [field.name]: checked === true })}
                                />
                            ) : (
                                <Input
                                    id={`${form.slug}-${field.name}`}
                                    type={['number', 'date'].includes(field.type) ? field.type : 'text'}
                                    value={(data.fields[field.name] as string) ?? ''}
                                    onChange={(event) => setData('fields', { ...data.fields, [field.name]: event.target.value })}
                                    required={field.required}
                                    className="rounded-lg"
                                />
                            )}
                        </div>
                    ))}
                    <div className="grid gap-2">
                        <Label htmlFor={`${form.slug}-notes`} className="text-sm font-medium">
                            Notas
                        </Label>
                        <Input
                            id={`${form.slug}-notes`}
                            value={data.notes}
                            onChange={(event) => setData('notes', event.target.value)}
                            className="rounded-lg"
                        />
                    </div>
                    <Button type="submit" disabled={processing} className="rounded-lg">
                        {processing ? <LoaderCircle className="size-4 animate-spin" /> : <Send className="size-4" />}
                        Enviar
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

function TasksTab({ tasks, roomCleanings }: { tasks: TaskItem[]; roomCleanings: RoomCleaning[] }) {
    const kitchenClosingTasks = tasks.filter((task) => task.kitchenClosing);
    const regularTasks = tasks.filter((task) => !task.kitchenClosing);

    return (
        <section className="grid gap-4">
            <div className="grid gap-1">
                <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Trabajo asignado</h2>
                <p className="text-sm leading-5 text-neutral-500 dark:text-zinc-400">
                    Completa primero las tareas del turno. El cierre de cocina se hace aqui.
                </p>
            </div>
            {roomCleanings.length > 0 && (
                <section className="grid gap-3">
                    <div className="flex items-center justify-between gap-3">
                        <h3 className="text-sm font-medium text-neutral-500 dark:text-zinc-400">Mis habitaciones</h3>
                        <span className="text-xs text-neutral-500 dark:text-zinc-400">
                            {roomCleanings.filter((task) => task.rawStatus === 'completed').length}/{roomCleanings.length}
                        </span>
                    </div>
                    {roomCleanings.map((task) => (
                        <RoomCleaningCard key={task.id} task={task} />
                    ))}
                </section>
            )}
            {kitchenClosingTasks.map((task) => (
                <TaskCard key={task.id} task={task} />
            ))}
            {regularTasks.length > 0 && (
                <section className="grid gap-3">
                    {kitchenClosingTasks.length > 0 && <h3 className="text-sm font-medium text-neutral-500 dark:text-zinc-400">Otras tareas</h3>}
                    {regularTasks.map((task) => (
                        <TaskCard key={task.id} task={task} />
                    ))}
                </section>
            )}
            {tasks.length === 0 && roomCleanings.length === 0 && <EmptyState text="Sin tareas para esta area." />}
        </section>
    );
}

function RoomCleaningCard({ task }: { task: RoomCleaning }) {
    const { data, setData, post, processing, reset } = useForm({ severity: 'normal', body: '' });
    const completeForm = useForm({ notes: '' });
    const isCompleted = task.rawStatus === 'completed';
    const startTask = () => router.patch(route('employee.room-cleaning.start', task.id), {}, { preserveScroll: true });
    const completeTask = () => completeForm.patch(route('employee.room-cleaning.complete', task.id), { preserveScroll: true });
    const submitNote: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('employee.room-cleaning.notes.store', task.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="grid gap-4 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2">
                            <BedDouble className="size-4 text-neutral-500 dark:text-zinc-400" />
                            <h3 className="text-xl font-semibold text-neutral-900 dark:text-zinc-50">{task.roomNumber}</h3>
                        </div>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-zinc-400">
                            Piso {task.floor} / {task.cleaningType === 'checkout' ? 'Salida' : 'Estancia'}
                        </p>
                    </div>
                    <span
                        className={`rounded-lg px-2.5 py-1 text-xs font-medium ${isCompleted ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200'}`}
                    >
                        {task.status}
                    </span>
                </div>

                <div className="grid gap-1 text-sm text-neutral-500 dark:text-zinc-400">
                    <p className="truncate text-neutral-900 dark:text-zinc-50">{task.guestName ?? 'Huesped sin nombre'}</p>
                    {task.companyName && <p className="truncate">{task.companyName}</p>}
                    <p>
                        {task.checkInDate ?? 'Sin llegada'} a {task.checkOutDate ?? 'Sin salida'}
                    </p>
                    {task.scheduledAt && <p>Asignada {task.scheduledAt}</p>}
                </div>

                {!isCompleted && (
                    <div className="grid grid-cols-2 gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-lg"
                            onClick={startTask}
                            disabled={task.rawStatus === 'in_progress'}
                        >
                            En progreso
                        </Button>
                        <Button type="button" className="rounded-lg" onClick={completeTask} disabled={completeForm.processing}>
                            {completeForm.processing ? <LoaderCircle className="size-4 animate-spin" /> : <ClipboardCheck className="size-4" />}
                            Limpia
                        </Button>
                    </div>
                )}

                <form onSubmit={submitNote} className="grid gap-3 border-t border-neutral-200 pt-4 dark:border-zinc-800">
                    <div className="grid grid-cols-[1fr_auto] gap-2">
                        <Input
                            value={data.body}
                            onChange={(event) => setData('body', event.target.value)}
                            placeholder="Novedad de la habitacion"
                            className="rounded-lg"
                        />
                        <Button
                            type="button"
                            variant={data.severity === 'urgent' ? 'default' : 'outline'}
                            className="rounded-lg"
                            onClick={() => setData('severity', data.severity === 'urgent' ? 'normal' : 'urgent')}
                        >
                            Urgente
                        </Button>
                    </div>
                    <Button type="submit" disabled={processing || !data.body.trim()} variant="outline" className="rounded-lg">
                        {processing ? <LoaderCircle className="size-4 animate-spin" /> : <Send className="size-4" />}
                        Registrar novedad
                    </Button>
                </form>

                {task.novelties.length > 0 && (
                    <div className="grid gap-2">
                        {task.novelties.map((novelty) => (
                            <ListItem key={novelty.id} title={novelty.body} detail={novelty.createdAt} status={novelty.severity} />
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function TaskCard({ task }: { task: TaskItem }) {
    if (task.kitchenClosing) {
        return <KitchenClosingTaskCard task={task} closing={task.kitchenClosing} />;
    }

    const completeTask = () => {
        router.patch(
            route('employee.tasks.complete', task.id),
            { notes: `Completado desde portal operativo: ${task.title}` },
            { preserveScroll: true },
        );
    };
    const validateTask = (decision: 'validated' | 'rejected') => {
        router.patch(route('employee.tasks.validate', task.id), { decision }, { preserveScroll: true });
    };

    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="grid gap-3 p-4">
                <div className="flex items-start gap-3">
                    <Checkbox
                        className="mt-1 rounded-lg"
                        checked={['completed', 'pending_validation', 'validated'].includes(task.rawStatus)}
                        disabled={!task.canComplete || ['completed', 'pending_validation', 'validated'].includes(task.rawStatus)}
                        onCheckedChange={(checked) => {
                            if (checked === true && task.canComplete) {
                                completeTask();
                            }
                        }}
                    />
                    <div className="min-w-0 flex-1">
                        <h3 className="text-base font-medium text-neutral-900 dark:text-zinc-50">{task.title}</h3>
                        {task.detail && <p className="mt-1 text-sm leading-5 text-neutral-500 dark:text-zinc-400">{task.detail}</p>}
                        <div className="mt-2 flex flex-wrap gap-2 text-xs text-neutral-500 dark:text-zinc-400">
                            <span className="flex items-center gap-2">
                                <span className={`size-2 rounded-full ${statusDot(task.rawStatus)}`} />
                                {task.status}
                            </span>
                            {task.dueAt && <span>{task.dueAt}</span>}
                            {task.assignedArea && <span>{task.assignedArea}</span>}
                        </div>
                    </div>
                </div>
                {task.canComplete && !['completed', 'pending_validation', 'validated'].includes(task.rawStatus) && (
                    <Button type="button" variant="outline" className="rounded-lg" onClick={completeTask}>
                        Marcar como hecho
                    </Button>
                )}
                {task.canValidate && (
                    <div className="grid grid-cols-2 gap-2">
                        <Button type="button" className="rounded-lg" onClick={() => validateTask('validated')}>
                            Validar
                        </Button>
                        <Button type="button" variant="outline" className="rounded-lg" onClick={() => validateTask('rejected')}>
                            Rechazar
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function KitchenClosingTaskCard({ task, closing }: { task: TaskItem; closing: KitchenClosing }) {
    const categories = useMemo(() => Array.from(new Set(closing.items.map((item) => item.category))), [closing.items]);
    const [activeCategory, setActiveCategory] = useState(categories[0] ?? '');
    const initialItems = useMemo(
        () =>
            closing.items.map((item) => ({
                stock_item_id: item.stockItemId,
                physical_count: item.physicalCount ?? '',
                waste_quantity: item.wasteQuantity ?? '',
                notes: item.notes ?? '',
            })),
        [closing.items],
    );
    const { data, setData, post, processing } = useForm<{
        items: { stock_item_id: number; physical_count: string; waste_quantity: string; notes: string }[];
    }>({ items: initialItems });
    const completedCount = data.items.filter((item) => String(item.physical_count).trim() !== '').length;
    const visibleItems = closing.items.filter((item) => item.category === activeCategory);
    const requiredReplenishments = closing.items.filter((item) => Number(item.replenishmentRequired ?? 0) > 0);

    const submitCount: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('employee.kitchen-closings.count', closing.id), { preserveScroll: true });
    };

    const confirmReplenishment = () => {
        router.post(route('employee.kitchen-closings.replenishment', closing.id), {}, { preserveScroll: true });
    };

    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardHeader className="grid gap-4 border-b border-neutral-200 p-4 dark:border-zinc-800">
                <div className="flex items-start justify-between gap-3">
                    <div className="grid gap-1">
                        <CardTitle className="text-base font-medium text-neutral-900 dark:text-zinc-50">{task.title}</CardTitle>
                        <p className="text-sm text-neutral-500 dark:text-zinc-400">Dia operativo {closing.operatingDate}</p>
                    </div>
                    <span className="rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-950 dark:text-sky-200">
                        {closing.status === 'pending_count' ? 'Paso 1' : closing.status === 'count_submitted' ? 'Paso 2' : 'Cerrado'}
                    </span>
                </div>
                {closing.status === 'pending_count' && (
                    <div className="grid gap-2">
                        <div className="flex items-center justify-between text-xs text-neutral-500 dark:text-zinc-400">
                            <span>Conteo fisico</span>
                            <span>
                                {completedCount}/{closing.items.length}
                            </span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-zinc-800">
                            <div
                                className="h-full rounded-full bg-emerald-500"
                                style={{ width: `${closing.items.length > 0 ? (completedCount / closing.items.length) * 100 : 0}%` }}
                            />
                        </div>
                    </div>
                )}
            </CardHeader>
            <CardContent className="grid gap-4 p-4">
                {closing.status === 'pending_count' && (
                    <form onSubmit={submitCount} className="grid gap-4">
                        <div className="flex gap-2 overflow-x-auto pb-1">
                            {categories.map((category) => (
                                <Button
                                    key={category}
                                    type="button"
                                    variant={activeCategory === category ? 'default' : 'outline'}
                                    className="h-9 shrink-0 rounded-lg"
                                    onClick={() => setActiveCategory(category)}
                                >
                                    {category}
                                </Button>
                            ))}
                        </div>
                        <div className="grid gap-3">
                            {visibleItems.map((item) => {
                                const index = data.items.findIndex((row) => row.stock_item_id === item.stockItemId);

                                return (
                                    <div key={item.id} className="grid gap-3 rounded-lg border border-neutral-200 p-3 dark:border-zinc-800">
                                        <div>
                                            <p className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{item.productName}</p>
                                            <p className="mt-1 text-xs text-neutral-500 dark:text-zinc-400">
                                                {item.unit}
                                                {item.unitDetail ? ` / ${item.unitDetail}` : ''}
                                            </p>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <div className="grid gap-2">
                                                <Label htmlFor={`count-${item.id}`} className="text-xs">
                                                    Conteo fisico
                                                </Label>
                                                <Input
                                                    id={`count-${item.id}`}
                                                    inputMode="decimal"
                                                    value={data.items[index]?.physical_count ?? ''}
                                                    onChange={(event) => {
                                                        const items = [...data.items];
                                                        items[index] = { ...items[index], physical_count: event.target.value };
                                                        setData('items', items);
                                                    }}
                                                    required
                                                    className="rounded-lg"
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor={`waste-${item.id}`} className="text-xs">
                                                    Merma
                                                </Label>
                                                <Input
                                                    id={`waste-${item.id}`}
                                                    inputMode="decimal"
                                                    value={data.items[index]?.waste_quantity ?? ''}
                                                    onChange={(event) => {
                                                        const items = [...data.items];
                                                        items[index] = { ...items[index], waste_quantity: event.target.value };
                                                        setData('items', items);
                                                    }}
                                                    className="rounded-lg"
                                                />
                                            </div>
                                        </div>
                                        <Input
                                            value={data.items[index]?.notes ?? ''}
                                            onChange={(event) => {
                                                const items = [...data.items];
                                                items[index] = { ...items[index], notes: event.target.value };
                                                setData('items', items);
                                            }}
                                            placeholder="Nota opcional"
                                            className="rounded-lg"
                                        />
                                    </div>
                                );
                            })}
                        </div>
                        <Button type="submit" disabled={processing} className="rounded-lg">
                            {processing ? <LoaderCircle className="size-4 animate-spin" /> : <Send className="size-4" />}
                            Enviar conteo
                        </Button>
                    </form>
                )}

                {closing.status === 'count_submitted' && (
                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <p className="text-sm font-medium text-neutral-900 dark:text-zinc-50">Reposicion desde bodega</p>
                            <p className="text-sm leading-5 text-neutral-500 dark:text-zinc-400">
                                Saca exactamente las cantidades indicadas y confirma al terminar.
                            </p>
                        </div>
                        <div className="grid gap-0 overflow-hidden rounded-lg border border-neutral-200 dark:border-zinc-800">
                            {requiredReplenishments.length > 0 ? (
                                requiredReplenishments.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between gap-3 border-b border-neutral-200 p-3 last:border-b-0 dark:border-zinc-800"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">{item.productName}</p>
                                            <p className="text-xs text-neutral-500 dark:text-zinc-400">{item.unit}</p>
                                        </div>
                                        <span className="text-sm font-semibold text-neutral-900 dark:text-zinc-50">{item.replenishmentRequired}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="p-4 text-sm text-neutral-500 dark:text-zinc-400">No hay productos para reponer.</p>
                            )}
                        </div>
                        <Button type="button" onClick={confirmReplenishment} className="rounded-lg">
                            Confirmar reposicion
                        </Button>
                    </div>
                )}

                {closing.status === 'closed' && (
                    <div className="grid gap-2">
                        <ListItem
                            title="Cierre completado"
                            detail="La reposicion fue verificada y el inventario inicial del nuevo dia quedo registrado."
                            status="completed"
                        />
                        {closing.hasReplenishmentAlert && (
                            <ListItem
                                title="Alerta de reposicion"
                                detail="Lo real egresado no coincide con lo requerido. Gerencia puede auditar el detalle."
                                status="high"
                            />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function Section({ title, count, icon: Icon, children }: { title: string; count: number; icon: typeof Home; children: ReactNode }) {
    return (
        <section className="grid gap-3">
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">{title}</h2>
                <span className="flex items-center gap-2 text-sm text-neutral-500 dark:text-zinc-400">
                    <Icon className="size-4" />
                    {count}
                </span>
            </div>
            <Card className="overflow-hidden rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                <CardContent className="grid gap-0 p-0">{children}</CardContent>
            </Card>
        </section>
    );
}

function ListItem({ title, detail, status }: { title: string; detail?: string | null; status: string }) {
    return (
        <div className="border-b border-neutral-200 p-4 last:border-b-0 dark:border-zinc-800">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{title}</p>
                    {detail && <p className="mt-1 text-sm leading-5 text-neutral-500 dark:text-zinc-400">{detail}</p>}
                </div>
                <span className={`mt-1 size-2 shrink-0 rounded-full ${statusDot(status)}`} />
            </div>
        </div>
    );
}

function MiniMetric({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
            <p className="text-sm text-neutral-500 dark:text-zinc-400">{label}</p>
            <p className="mt-1 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">{value}</p>
        </div>
    );
}

function EmptyState({ text }: { text: string }) {
    return (
        <p className="rounded-xl border border-dashed border-neutral-200 p-4 text-sm text-neutral-500 dark:border-zinc-800 dark:text-zinc-400">
            {text}
        </p>
    );
}

function TabButton({
    tab,
    current,
    setTab,
    icon: Icon,
    label,
}: {
    tab: Tab;
    current: Tab;
    setTab: (tab: Tab) => void;
    icon: typeof Home;
    label: string;
}) {
    return (
        <Button type="button" variant={current === tab ? 'default' : 'ghost'} className="h-12 rounded-lg" onClick={() => setTab(tab)}>
            <Icon className="size-4" />
            {label}
        </Button>
    );
}
