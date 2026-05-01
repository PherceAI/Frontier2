import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { BedDouble, Check, CheckCircle2, DoorOpen, LoaderCircle, Play, RefreshCw, Save, Sparkles, UsersRound } from 'lucide-react';
import { FormEventHandler } from 'react';

type Room = {
    number: string;
    floor: number;
    type: 'standard' | 'executive' | 'premium';
    status: 'available' | 'occupied';
    label: string;
    guestName: string | null;
    companyName: string | null;
    reservationCode: string | null;
    checkInDate: string | null;
    checkOutDate: string | null;
    adults: number | null;
    children: number | null;
    balance: number | null;
    syncedAt: string | null;
};

type RoomsDashboard = {
    date: string;
    lastSyncedAt: string | null;
    summary: {
        total: number;
        occupied: number;
        available: number;
        occupancyRate: number;
    };
    byType: {
        type: string;
        label: string;
        total: number;
        occupied: number;
        available: number;
    }[];
    byFloor: {
        floor: number;
        total: number;
        occupied: number;
        available: number;
    }[];
    rooms: Room[];
};

type CleaningTask = {
    id: number;
    roomNumber: string;
    floor: number;
    roomType: string | null;
    cleaningType: 'checkout' | 'stay' | string;
    status: 'pending' | 'in_progress' | 'completed' | string;
    assignmentSource: string;
    assignedTo: number | null;
    assigneeName: string | null;
    guestName: string | null;
    companyName: string | null;
    reservationCode: string | null;
    checkInDate: string | null;
    checkOutDate: string | null;
    generatedForDate: string | null;
    scheduledAt: string | null;
    completedAt: string | null;
    notes: string | null;
    novelties: {
        id: number;
        severity: string;
        body: string;
        userName: string | null;
        createdAt: string | null;
    }[];
};

type Cleaning = {
    settings: {
        autoAssignmentEnabled: boolean;
        workingDays: number[];
        assignmentTime: string;
        assignmentStrategy: string;
    };
    summary: {
        pending: number;
        completed: number;
        checkout: number;
        stay: number;
        unassigned: number;
    };
    employees: {
        id: number;
        name: string;
    }[];
    tasks: CleaningTask[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Habitaciones', href: '/rooms' }];

const formatDate = (value: string | null) => {
    if (!value) return 'Sin dato';

    const [year, month, day] = value.split('-').map(Number);

    return new Intl.DateTimeFormat('es-EC', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(year, month - 1, day));
};

const formatSync = (value: string | null) => {
    if (!value) return 'Sin sincronizacion';

    return new Intl.DateTimeFormat('es-EC', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const typeLabel = (type: string | null) =>
    ({
        standard: 'Estandar',
        executive: 'Ejecutiva',
        premium: 'Premium',
    })[type ?? ''] ??
    (type || 'Sin tipo');

const statusCopy: Record<string, { label: string; dot: string }> = {
    pending: { label: 'Pendiente', dot: 'bg-amber-500' },
    in_progress: { label: 'En progreso', dot: 'bg-sky-500' },
    completed: { label: 'Completada', dot: 'bg-emerald-500' },
};

export default function RoomsDashboardPage({ dashboard, cleaning }: { dashboard: RoomsDashboard; cleaning: Cleaning }) {
    const occupiedRooms = dashboard.rooms.filter((room) => room.status === 'occupied');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Habitaciones" />
            <div className="flex h-full flex-1 flex-col gap-8 p-5 md:p-8">
                <header className="grid gap-4 xl:grid-cols-[1fr_auto] xl:items-start">
                    <div className="grid gap-3">
                        <div className="flex w-fit items-center gap-2 rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-medium text-neutral-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                            <span className="size-2 rounded-full bg-emerald-500" />
                            Operacion hotelera del dia
                        </div>
                        <div className="grid gap-2">
                            <h1 className="text-2xl font-semibold text-foreground">Habitaciones</h1>
                            <p className="max-w-3xl text-sm leading-relaxed font-normal tracking-[-0.01em] text-muted-foreground">
                                Disponibilidad, ocupacion y limpieza diaria en un solo tablero de control gerencial.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 rounded-xl border border-border/60 bg-card px-4 py-3 text-sm font-normal text-muted-foreground">
                        <RefreshCw className="size-4" />
                        <span>{formatSync(dashboard.lastSyncedAt)}</span>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard icon={BedDouble} label="Inventario" value={dashboard.summary.total} detail={formatDate(dashboard.date)} />
                    <MetricCard icon={DoorOpen} label="Libres" value={dashboard.summary.available} detail="Disponibles para venta" />
                    <MetricCard icon={UsersRound} label="Ocupadas" value={dashboard.summary.occupied} detail="Con huesped hoy" />
                    <MetricCard icon={CheckCircle2} label="Ocupacion" value={`${dashboard.summary.occupancyRate}%`} detail="Sobre inventario real" />
                </section>

                <RoomCleaningSection cleaning={cleaning} date={dashboard.date} />

                <section className="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
                    <InventoryMix groups={dashboard.byType} />
                    <OccupiedRooms rooms={occupiedRooms} />
                </section>

                <FloorMap dashboard={dashboard} />
            </div>
        </AppLayout>
    );
}

function RoomCleaningSection({ cleaning, date }: { cleaning: Cleaning; date: string }) {
    const settingsForm = useForm({
        auto_assignment_enabled: cleaning.settings.autoAssignmentEnabled,
    });
    const generationForm = useForm({ date });
    const totalTasks = cleaning.summary.pending + cleaning.summary.completed;
    const progress = totalTasks > 0 ? Math.round((cleaning.summary.completed / totalTasks) * 100) : 0;

    const updateSettings: FormEventHandler = (event) => {
        event.preventDefault();
        settingsForm.patch(route('rooms.cleaning.settings.update'), { preserveScroll: true });
    };

    const generateAssignments: FormEventHandler = (event) => {
        event.preventDefault();
        generationForm.post(route('rooms.cleaning.assignments.store'), { preserveScroll: true });
    };

    return (
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-6 p-6">
                <div className="grid gap-4 xl:grid-cols-[1fr_auto] xl:items-start">
                    <div className="grid gap-3">
                        <div className="flex w-fit items-center gap-2 text-sm font-normal text-muted-foreground">
                            <Sparkles className="size-4" />
                            Limpieza operativa
                        </div>
                        <div className="grid gap-2">
                            <h2 className="text-xl font-semibold text-foreground">Limpiezas de hoy</h2>
                            <p className="max-w-2xl text-sm leading-relaxed font-normal tracking-[-0.01em] text-muted-foreground">
                                Reparte, corrige y audita las habitaciones asignadas al equipo de limpieza.
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-[auto_auto]">
                        <form
                            onSubmit={updateSettings}
                            className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 px-4 py-3 dark:border-zinc-800"
                        >
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="auto-assignment"
                                    checked={settingsForm.data.auto_assignment_enabled}
                                    onCheckedChange={(checked) => settingsForm.setData('auto_assignment_enabled', checked === true)}
                                />
                                <Label htmlFor="auto-assignment" className="text-sm font-medium text-foreground">
                                    Auto 07:00
                                </Label>
                            </div>
                            <Button type="submit" size="sm" variant="outline" className="rounded-lg" disabled={settingsForm.processing}>
                                Guardar
                            </Button>
                        </form>

                        <form onSubmit={generateAssignments} className="grid grid-cols-[1fr_auto] gap-2">
                            <Input
                                type="date"
                                value={generationForm.data.date}
                                onChange={(event) => generationForm.setData('date', event.target.value)}
                                className="rounded-lg"
                            />
                            <Button type="submit" className="rounded-lg" disabled={generationForm.processing}>
                                {generationForm.processing ? <LoaderCircle className="size-4 animate-spin" /> : <Play className="size-4" />}
                                Generar
                            </Button>
                        </form>
                    </div>
                </div>

                <div className="grid gap-4 border-y border-neutral-200 py-4 md:grid-cols-3 lg:grid-cols-6 dark:border-zinc-800">
                    <div className="flex items-center gap-4 border-r border-neutral-200 pr-4 last:border-0 dark:border-zinc-800">
                        <div className="flex-1">
                            <div className="flex items-end justify-between gap-2">
                                <p className="text-sm font-normal text-muted-foreground">Progreso</p>
                                <p className="text-xl font-semibold tracking-[-0.02em] text-foreground">{progress}%</p>
                            </div>
                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                <div className="h-full rounded-full bg-primary" style={{ width: `${progress}%` }} />
                            </div>
                        </div>
                    </div>
                    <MiniStat label="Pendientes" value={cleaning.summary.pending} />
                    <MiniStat label="Completadas" value={cleaning.summary.completed} />
                    <MiniStat label="Salidas" value={cleaning.summary.checkout} />
                    <MiniStat label="Estancias" value={cleaning.summary.stay} />
                    <MiniStat label="Sin asignar" value={cleaning.summary.unassigned} />
                </div>

                <div className="min-w-0 rounded-xl border border-border/60">
                    <div className="hidden grid-cols-[80px_1fr_200px_150px_1fr_48px] gap-3 border-b border-border/60 bg-muted/40 px-4 py-3 text-xs font-medium uppercase text-muted-foreground xl:grid">
                        <span>Hab.</span>
                        <span>Reserva</span>
                        <span>Responsable</span>
                        <span>Estado</span>
                        <span>Nota interna</span>
                        <span />
                    </div>
                    <div className="divide-y divide-neutral-200 dark:divide-zinc-800">
                        {cleaning.tasks.length > 0 ? (
                            cleaning.tasks.map((task) => <CleaningTaskRow key={task.id} task={task} employees={cleaning.employees} />)
                        ) : (
                            <div className="p-6 text-sm font-normal text-muted-foreground">
                                Todavia no hay limpiezas generadas para esta fecha.
                            </div>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function CleaningTaskRow({ task, employees }: { task: CleaningTask; employees: Cleaning['employees'] }) {
    const { data, setData, patch, processing, isDirty, recentlySuccessful } = useForm({
        assigned_to: task.assignedTo ? String(task.assignedTo) : 'unassigned',
        status: task.status,
        notes: task.notes ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('rooms.cleaning.tasks.update', task.id), { preserveScroll: true, preserveState: true });
    };

    return (
        <form
            onSubmit={submit}
            className="group grid gap-4 p-4 transition-colors hover:bg-muted/40 xl:grid-cols-[80px_1fr_200px_150px_1fr_48px] xl:items-center hover:bg-muted/40"
        >
            <div className="flex items-center justify-between gap-3 xl:block">
                <div className="text-xl font-semibold tracking-[-0.02em] text-foreground">{task.roomNumber}</div>
                <div className="text-xs font-medium tracking-[-0.01em] text-muted-foreground">Piso {task.floor}</div>
            </div>

            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="text-sm font-medium text-foreground">{task.guestName ?? 'Huesped sin nombre'}</span>
                    <CleaningType type={task.cleaningType} />
                    {task.novelties.length > 0 && (
                        <span className="text-xs font-normal text-amber-600 dark:text-amber-300">{task.novelties.length} novedad(es)</span>
                    )}
                </div>
                <p className="mt-1 truncate text-xs font-normal text-muted-foreground">
                    {formatDate(task.checkInDate)} a {formatDate(task.checkOutDate)}
                    {task.companyName ? ` / ${task.companyName}` : ''}
                </p>
            </div>

            <Select value={data.assigned_to} onValueChange={(value) => setData('assigned_to', value)}>
                <SelectTrigger className="rounded-lg bg-transparent hover:bg-white dark:hover:bg-zinc-900">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="unassigned">Sin asignar</SelectItem>
                    {employees.map((employee) => (
                        <SelectItem key={employee.id} value={String(employee.id)}>
                            {employee.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                <SelectTrigger className="rounded-lg bg-transparent hover:bg-white dark:hover:bg-zinc-900">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="pending">Pendiente</SelectItem>
                    <SelectItem value="in_progress">En progreso</SelectItem>
                    <SelectItem value="completed">Completada</SelectItem>
                </SelectContent>
            </Select>

            <Input 
                value={data.notes} 
                onChange={(event) => setData('notes', event.target.value)} 
                placeholder="Anadir nota..." 
                className="rounded-lg bg-transparent hover:bg-white dark:hover:bg-zinc-900" 
            />

            <Button 
                type="submit" 
                size="icon"
                variant={isDirty ? "default" : "ghost"}
                className={`rounded-lg transition-all ${!isDirty && !recentlySuccessful ? 'opacity-0 focus:opacity-100 group-hover:opacity-100 xl:opacity-50' : ''}`} 
                disabled={processing || (!isDirty && !recentlySuccessful)}
                title="Guardar cambios"
            >
                {processing ? <LoaderCircle className="size-4 animate-spin" /> : recentlySuccessful ? <Check className="size-4" /> : <Save className="size-4" />}
            </Button>
        </form>
    );
}

function InventoryMix({ groups }: { groups: RoomsDashboard['byType'] }) {
    return (
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-4 p-6">
                <SectionTitle title="Inventario por tipo" detail="Lectura por categoria comercial" />
                <div className="grid gap-3">
                    {groups.map((group) => {
                        const occupiedRate = group.total > 0 ? Math.round((group.occupied / group.total) * 100) : 0;

                        return (
                            <div key={group.type} className="grid gap-3 rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-sm font-medium text-foreground">{group.label}</span>
                                    <span className="text-sm font-normal text-muted-foreground">{occupiedRate}% ocupado</span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                    <div className="h-full rounded-full bg-neutral-900 dark:bg-zinc-50" style={{ width: `${occupiedRate}%` }} />
                                </div>
                                <div className="flex items-center justify-between gap-3 text-xs font-normal text-muted-foreground">
                                    <span>{group.available} libres</span>
                                    <span>{group.occupied} ocupadas</span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}

function OccupiedRooms({ rooms }: { rooms: Room[] }) {
    return (
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-4 p-6">
                <SectionTitle title="Ocupadas hoy" detail={`${rooms.length} habitaciones con huesped`} />
                <div className="grid max-h-[440px] gap-0 overflow-auto rounded-xl border border-border/60">
                    {rooms.length === 0 ? (
                        <div className="p-6 text-sm font-normal text-muted-foreground">No hay ocupacion sincronizada para hoy.</div>
                    ) : (
                        rooms.map((room) => (
                            <div
                                key={room.number}
                                className="grid gap-3 border-b border-neutral-200 p-4 last:border-b-0 hover:bg-neutral-100 md:grid-cols-[72px_1fr_auto] md:items-center dark:border-zinc-800 dark:hover:bg-zinc-800/50"
                            >
                                <div>
                                    <p className="text-base font-semibold text-foreground">{room.number}</p>
                                    <p className="text-xs font-normal text-muted-foreground">{typeLabel(room.type)}</p>
                                </div>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium text-foreground">
                                        {room.guestName ?? 'Huesped sin nombre'}
                                    </p>
                                    <p className="mt-1 truncate text-xs font-normal text-muted-foreground">
                                        {formatDate(room.checkInDate)} a {formatDate(room.checkOutDate)}
                                        {room.companyName ? ` / ${room.companyName}` : ''}
                                    </p>
                                </div>
                                <span className="text-xs font-normal text-muted-foreground">{room.reservationCode ?? 'Sin folio'}</span>
                            </div>
                        ))
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function FloorMap({ dashboard }: { dashboard: RoomsDashboard }) {
    return (
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-6 p-6">
                <SectionTitle title="Mapa por piso" detail="Estado de inventario fisico" />
                <div className="grid gap-5">
                    {dashboard.byFloor.map((floor) => (
                        <div key={floor.floor} className="grid gap-3">
                            <div className="flex items-center justify-between gap-3 text-sm">
                                <span className="font-medium text-foreground">Piso {floor.floor}</span>
                                <span className="font-normal text-muted-foreground">
                                    {floor.available} libres / {floor.occupied} ocupadas
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8 xl:grid-cols-10">
                                {dashboard.rooms
                                    .filter((room) => room.floor === floor.floor)
                                    .map((room) => (
                                        <div
                                            key={room.number}
                                            className="min-h-24 rounded-lg border border-neutral-200 p-3 transition-colors hover:bg-neutral-100 dark:border-zinc-800 dark:hover:bg-zinc-800/50"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-base font-semibold text-foreground">{room.number}</span>
                                                <span
                                                    className={`size-2 rounded-full ${room.status === 'occupied' ? 'bg-rose-500' : 'bg-emerald-500'}`}
                                                />
                                            </div>
                                            <p className="mt-2 text-xs font-normal text-muted-foreground">{typeLabel(room.type)}</p>
                                            <p className="mt-1 truncate text-xs font-medium text-foreground">
                                                {room.status === 'occupied' ? (room.guestName ?? 'Ocupada') : 'Libre'}
                                            </p>
                                        </div>
                                    ))}
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

function MetricCard({ icon: Icon, label, value, detail }: { icon: typeof BedDouble; label: string; value: number | string; detail: string }) {
    return (
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-3 p-6">
                <div className="flex items-center justify-between gap-3">
                    <span className="text-sm font-medium tracking-[-0.01em] text-muted-foreground">{label}</span>
                    <Icon className="size-4 text-muted-foreground" />
                </div>
                <div className="grid gap-1">
                    <p className="tabular-nums text-2xl font-semibold text-foreground">{value}</p>
                    <p className="text-xs font-medium text-muted-foreground">{detail}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function MiniStat({ label, value }: { label: string; value: number }) {
    return (
        <div className="border-r border-border/60 px-4 last:border-0">
            <p className="text-xs font-medium tracking-[-0.01em] text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold tracking-[-0.02em] text-foreground">{value}</p>
        </div>
    );
}

function SectionTitle({ title, detail }: { title: string; detail: string }) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <h2 className="text-base font-semibold text-foreground">{title}</h2>
            <p className="text-sm font-normal tracking-[-0.01em] text-muted-foreground">{detail}</p>
        </div>
    );
}

function StatusText({ status }: { status: string }) {
    const copy = statusCopy[status] ?? { label: status, dot: 'bg-neutral-400' };

    return (
        <span className="flex items-center gap-2 text-sm font-normal text-muted-foreground">
            <span className={`size-2 rounded-full ${copy.dot}`} />
            {copy.label}
        </span>
    );
}

function CleaningType({ type }: { type: string }) {
    const isCheckout = type === 'checkout';

    return (
        <span className="flex items-center gap-2 text-xs font-normal text-muted-foreground">
            <span className={`size-2 rounded-full ${isCheckout ? 'bg-rose-500' : 'bg-sky-500'}`} />
            {isCheckout ? 'Salida' : 'Estancia'}
        </span>
    );
}
