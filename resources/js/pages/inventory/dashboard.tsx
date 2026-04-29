import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertTriangle, Boxes, Clock3, DollarSign, FileWarning, PackageSearch, TrendingDown, TrendingUp } from 'lucide-react';

type AreaMetric = {
    key: string;
    label: string;
    quantity: number;
    value: number;
    records?: number;
};

type Signal = {
    title: string;
    value: string;
    detail: string;
};

type InventoryDashboard = {
    lastSyncedAt: string | null;
    generatedAt: string | null;
    summary: {
        totalProducts: number;
        inventoryValue: number;
        payablesTotal: number;
        payablesOverdue: number;
        pendingDocuments: number;
    };
    locations: AreaMetric[];
    incomeAreas: AreaMetric[];
    expenseAreas: AreaMetric[];
    movementTotals: {
        income: { records: number; quantity: number; value: number };
        expenses: { records: number; quantity: number; value: number };
    };
    signals: Signal[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Inventario', href: '/inventory' }];

const money = (value: number) =>
    new Intl.NumberFormat('es-EC', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 2,
    }).format(value);

const number = (value: number) => new Intl.NumberFormat('es-EC', { maximumFractionDigits: 2 }).format(value);

const formatDateTime = (value: string | null) => {
    if (!value) return 'Sin sincronizacion';

    return new Intl.DateTimeFormat('es-EC', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

export default function InventoryDashboardPage({ dashboard }: { dashboard: InventoryDashboard }) {
    const maxExpense = Math.max(...dashboard.expenseAreas.map((area) => area.value), 1);
    const overdueRate = dashboard.summary.payablesTotal > 0 ? (dashboard.summary.payablesOverdue / dashboard.summary.payablesTotal) * 100 : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventario" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-sm text-neutral-500 dark:text-zinc-400">
                            <PackageSearch className="size-4" />
                            Bodega conectada a Google Sheets
                        </div>
                        <h1 className="mt-3 text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Inventario</h1>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-zinc-400">
                            Lectura gerencial del sistema actual de inventario: valor en bodega, cuentas por pagar y consumos por area para cruzar con habitaciones, restaurante y mantenimiento.
                        </p>
                    </div>
                    <div className="rounded-lg border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                        <div className="flex items-center gap-2">
                            <Clock3 className="size-4" />
                            {formatDateTime(dashboard.generatedAt ?? dashboard.lastSyncedAt)}
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-5">
                    <MetricCard icon={Boxes} label="Productos" value={number(dashboard.summary.totalProducts)} detail="Catalogo activo" />
                    <MetricCard icon={DollarSign} label="Inventario" value={money(dashboard.summary.inventoryValue)} detail="Valor total" />
                    <MetricCard icon={FileWarning} label="CxP total" value={money(dashboard.summary.payablesTotal)} detail={`${dashboard.summary.pendingDocuments} documentos`} />
                    <MetricCard icon={AlertTriangle} label="Vencido" value={money(dashboard.summary.payablesOverdue)} detail={`${overdueRate.toFixed(1)}% del pendiente`} />
                    <MetricCard icon={TrendingDown} label="Egresos" value={money(dashboard.movementTotals.expenses.value)} detail={`${dashboard.movementTotals.expenses.records} registros`} />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1.2fr]">
                    <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardContent className="grid gap-4 p-5">
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Inventario por ubicacion</h2>
                                <Badge variant="secondary" className="rounded-md">
                                    {money(dashboard.summary.inventoryValue)}
                                </Badge>
                            </div>
                            <div className="grid gap-3">
                                {dashboard.locations.map((location) => (
                                    <div key={location.key} className="rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{location.label}</span>
                                            <span className="text-sm text-neutral-500 dark:text-zinc-400">{money(location.value)}</span>
                                        </div>
                                        <div className="mt-2 h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-zinc-800">
                                            <div
                                                className="h-full bg-emerald-500"
                                                style={{ width: `${dashboard.summary.inventoryValue ? (location.value / dashboard.summary.inventoryValue) * 100 : 0}%` }}
                                            />
                                        </div>
                                        <div className="mt-2 text-xs text-neutral-500 dark:text-zinc-400">{number(location.quantity)} unidades registradas</div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardContent className="grid gap-4 p-5">
                            <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Senales para gerencia</h2>
                            <div className="grid gap-3 md:grid-cols-3">
                                {dashboard.signals.map((signal) => (
                                    <div key={signal.title} className="rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                        <div className="text-sm text-neutral-500 dark:text-zinc-400">{signal.title}</div>
                                        <div className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">{signal.value}</div>
                                        <div className="mt-2 text-xs leading-5 text-neutral-500 dark:text-zinc-400">{signal.detail}</div>
                                    </div>
                                ))}
                            </div>
                            <div className="grid gap-3 rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                                <div className="flex items-center gap-2 text-sm font-medium text-neutral-900 dark:text-zinc-50">
                                    <TrendingUp className="size-4 text-emerald-600" />
                                    Ingresos vs egresos
                                </div>
                                <div className="grid gap-3 md:grid-cols-2">
                                    <Movement label="Ingresos" value={dashboard.movementTotals.income.value} records={dashboard.movementTotals.income.records} />
                                    <Movement label="Egresos" value={dashboard.movementTotals.expenses.value} records={dashboard.movementTotals.expenses.records} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <AreaList title="Ingresos por area" areas={dashboard.incomeAreas} maxValue={Math.max(...dashboard.incomeAreas.map((area) => area.value), 1)} tone="emerald" />
                    <AreaList title="Egresos por area" areas={dashboard.expenseAreas} maxValue={maxExpense} tone="rose" />
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
    icon: typeof Boxes;
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="grid gap-3 p-5">
                <div className="flex items-center justify-between gap-3">
                    <span className="text-sm text-neutral-500 dark:text-zinc-400">{label}</span>
                    <Icon className="size-4 text-neutral-500 dark:text-zinc-400" />
                </div>
                <div className="text-2xl font-semibold text-neutral-900 dark:text-zinc-50">{value}</div>
                <div className="text-xs text-neutral-500 dark:text-zinc-400">{detail}</div>
            </CardContent>
        </Card>
    );
}

function Movement({ label, value, records }: { label: string; value: number; records: number }) {
    return (
        <div className="rounded-lg bg-neutral-50 p-3 dark:bg-zinc-950">
            <div className="text-xs text-neutral-500 dark:text-zinc-400">{label}</div>
            <div className="mt-1 text-lg font-semibold text-neutral-900 dark:text-zinc-50">{money(value)}</div>
            <div className="text-xs text-neutral-500 dark:text-zinc-400">{records} registros</div>
        </div>
    );
}

function AreaList({ title, areas, maxValue, tone }: { title: string; areas: AreaMetric[]; maxValue: number; tone: 'emerald' | 'rose' }) {
    return (
        <Card className="rounded-lg border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="grid gap-4 p-5">
                <h2 className="text-lg font-medium text-neutral-900 dark:text-zinc-50">{title}</h2>
                <div className="grid gap-3">
                    {areas.map((area) => (
                        <div key={area.key} className="grid gap-2 rounded-lg border border-neutral-200 p-4 dark:border-zinc-800">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{area.label}</span>
                                <span className="text-sm text-neutral-500 dark:text-zinc-400">{money(area.value)}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-zinc-800">
                                <div className={tone === 'emerald' ? 'h-full bg-emerald-500' : 'h-full bg-rose-500'} style={{ width: `${(area.value / maxValue) * 100}%` }} />
                            </div>
                            <div className="flex justify-between text-xs text-neutral-500 dark:text-zinc-400">
                                <span>{number(area.quantity)} unidades</span>
                                <span>{area.records ?? 0} registros</span>
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
