import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BedDouble, Building2, CheckCircle2, Clock3, DoorOpen, UsersRound } from 'lucide-react';

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

const typeLabel = (type: string) =>
    ({
        standard: 'Estandar',
        executive: 'Ejecutiva',
        premium: 'Premium',
    })[type] ?? type;

export default function RoomsDashboardPage({ dashboard }: { dashboard: RoomsDashboard }) {
    const occupiedRooms = dashboard.rooms.filter((room) => room.status === 'occupied');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Habitaciones" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-sm text-neutral-500 dark:text-zinc-400">
                            <Building2 className="size-4" />
                            Operacion hotelera del dia
                        </div>
                        <h1 className="mt-3 text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Habitaciones</h1>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-zinc-400">
                            Disponibilidad diaria conectada al ERP legacy via Supabase, con lectura horaria de ocupacion y catalogo fisico del hotel.
                        </p>
                    </div>
                    <div className="rounded-lg border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                        <div className="flex items-center gap-2">
                            <Clock3 className="size-4" />
                            {formatSync(dashboard.lastSyncedAt)}
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-4">
                    <MetricCard icon={BedDouble} label="Total" value={dashboard.summary.total} detail={formatDate(dashboard.date)} />
                    <MetricCard icon={DoorOpen} label="Libres" value={dashboard.summary.available} detail="Disponibles para vender" />
                    <MetricCard icon={UsersRound} label="Ocupadas" value={dashboard.summary.occupied} detail="Con huesped hoy" />
                    <MetricCard icon={CheckCircle2} label="Ocupacion" value={`${dashboard.summary.occupancyRate}%`} detail="Sobre inventario real" />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1.4fr]">
                    <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardContent className="grid gap-4 p-5">
                            <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Resumen por tipo</h2>
                            <div className="grid gap-3">
                                {dashboard.byType.map((group) => (
                                    <div key={group.type} className="grid gap-2 rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{group.label}</span>
                                            <span className="text-sm text-neutral-500 dark:text-zinc-400">{group.total} hab.</span>
                                        </div>
                                        <div className="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-zinc-800">
                                            <div
                                                className="h-full bg-emerald-500"
                                                style={{ width: `${group.total ? (group.available / group.total) * 100 : 0}%` }}
                                            />
                                        </div>
                                        <div className="flex items-center justify-between text-xs text-neutral-500 dark:text-zinc-400">
                                            <span>{group.available} libres</span>
                                            <span>{group.occupied} ocupadas</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardContent className="grid gap-4 p-5">
                            <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Ocupadas hoy</h2>
                            <div className="grid max-h-[440px] gap-2 overflow-auto pr-1">
                                {occupiedRooms.length === 0 ? (
                                    <div className="rounded-lg border border-dashed border-neutral-200 p-5 text-sm text-neutral-500 dark:border-zinc-800 dark:text-zinc-400">
                                        No hay ocupacion sincronizada para hoy.
                                    </div>
                                ) : (
                                    occupiedRooms.map((room) => (
                                        <div
                                            key={room.number}
                                            className="grid gap-3 rounded-lg border border-neutral-200 p-4 md:grid-cols-[96px_1fr_auto] dark:border-zinc-800"
                                        >
                                            <div>
                                                <div className="text-lg font-semibold text-neutral-900 dark:text-zinc-50">{room.number}</div>
                                                <div className="text-xs text-neutral-500 dark:text-zinc-400">{typeLabel(room.type)}</div>
                                            </div>
                                            <div className="min-w-0">
                                                <div className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">
                                                    {room.guestName ?? 'Huesped sin nombre'}
                                                </div>
                                                <div className="mt-1 text-xs text-neutral-500 dark:text-zinc-400">
                                                    {formatDate(room.checkInDate)} a {formatDate(room.checkOutDate)}
                                                </div>
                                                {room.companyName ? (
                                                    <div className="mt-1 truncate text-xs text-neutral-500 dark:text-zinc-400">{room.companyName}</div>
                                                ) : null}
                                            </div>
                                            <Badge variant="secondary" className="h-fit justify-self-start rounded-md">
                                                {room.reservationCode ?? 'Sin folio'}
                                            </Badge>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4">
                    <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Mapa por piso</h2>
                    {dashboard.byFloor.map((floor) => (
                        <div key={floor.floor} className="grid gap-3">
                            <div className="flex items-center justify-between text-sm">
                                <span className="font-medium text-neutral-900 dark:text-zinc-50">Piso {floor.floor}</span>
                                <span className="text-neutral-500 dark:text-zinc-400">
                                    {floor.available} libres / {floor.occupied} ocupadas
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8">
                                {dashboard.rooms
                                    .filter((room) => room.floor === floor.floor)
                                    .map((room) => (
                                        <div
                                            key={room.number}
                                            className={[
                                                'min-h-24 rounded-lg border p-3 transition-colors',
                                                room.status === 'occupied'
                                                    ? 'border-rose-200 bg-rose-50 dark:border-rose-900/70 dark:bg-rose-950/30'
                                                    : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/70 dark:bg-emerald-950/30',
                                            ].join(' ')}
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-base font-semibold text-neutral-900 dark:text-zinc-50">{room.number}</span>
                                                <span
                                                    className={[
                                                        'size-2 rounded-full',
                                                        room.status === 'occupied' ? 'bg-rose-500' : 'bg-emerald-500',
                                                    ].join(' ')}
                                                />
                                            </div>
                                            <div className="mt-2 text-xs text-neutral-500 dark:text-zinc-400">{typeLabel(room.type)}</div>
                                            <div className="mt-1 truncate text-xs font-medium text-neutral-700 dark:text-zinc-200">
                                                {room.status === 'occupied' ? (room.guestName ?? 'Ocupada') : 'Libre'}
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </div>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}

function MetricCard({
    icon: Icon,
    label,
    value,
    detail,
}: {
    icon: typeof BedDouble;
    label: string;
    value: number | string;
    detail: string;
}) {
    return (
        <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="grid gap-3 p-5">
                <div className="flex items-center justify-between gap-3">
                    <span className="text-sm text-neutral-500 dark:text-zinc-400">{label}</span>
                    <Icon className="size-4 text-neutral-500 dark:text-zinc-400" />
                </div>
                <div className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">{value}</div>
                <div className="text-xs text-neutral-500 dark:text-zinc-400">{detail}</div>
            </CardContent>
        </Card>
    );
}
