import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, ClipboardCheck, FilePenLine, Home, ListChecks, LoaderCircle, LogOut, Send } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';
import { type ReactNode } from 'react';

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
    assignedArea: string | null;
    canComplete: boolean;
    canValidate: boolean;
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

export default function Operations({ employee, activeArea, summary, events, tasks, forms, notifications }: PortalProps) {
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
                        />
                    )}

                    {tab === 'load' && <LoadTab forms={forms} />}

                    {tab === 'tasks' && <TasksTab tasks={tasks} />}
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
}: {
    employeeName: string;
    summary: PortalProps['summary'];
    events: EventItem[];
    notifications: NotificationItem[];
    activeTasks: TaskItem[];
    completedTasks: TaskItem[];
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
                    events.map((event) => <ListItem key={event.id} title={`${event.time} · ${event.title}`} detail={event.detail} status={event.severity} />)
                ) : (
                    <EmptyState text="Sin eventos para esta area." />
                )}
            </Section>

            <Section title="Pulso de tareas" count={activeTasks.length + completedTasks.length} icon={ListChecks}>
                <ListItem title="Pendientes activas" detail={`${activeTasks.length} por realizar o validar`} status="pending" />
                <ListItem title="Completadas" detail={`${completedTasks.length} cerradas o validadas`} status="completed" />
            </Section>
        </>
    );
}

function LoadTab({ forms }: { forms: OperationalForm[] }) {
    return (
        <section className="grid gap-4">
            <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Cargar datos</h2>
            {forms.length > 0 ? forms.map((form) => <DynamicForm key={form.id} form={form} />) : <EmptyState text="No hay formularios activos para esta area." />}
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
                                    className="min-h-24 rounded-lg border border-input bg-background px-3 py-2 text-sm"
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
                        <Input id={`${form.slug}-notes`} value={data.notes} onChange={(event) => setData('notes', event.target.value)} className="rounded-lg" />
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

function TasksTab({ tasks }: { tasks: TaskItem[] }) {
    return (
        <section className="grid gap-4">
            <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Tareas</h2>
            {tasks.length > 0 ? tasks.map((task) => <TaskCard key={task.id} task={task} />) : <EmptyState text="Sin tareas para esta area." />}
        </section>
    );
}

function TaskCard({ task }: { task: TaskItem }) {
    const completeTask = () => {
        router.patch(route('employee.tasks.complete', task.id), { notes: `Completado desde portal operativo: ${task.title}` }, { preserveScroll: true });
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
    return <p className="rounded-xl border border-dashed border-neutral-200 p-4 text-sm text-neutral-500 dark:border-zinc-800 dark:text-zinc-400">{text}</p>;
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
