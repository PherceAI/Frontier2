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
            <div className="flex h-full flex-1 flex-col gap-8 p-5 md:p-8">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="flex w-fit items-center gap-2 rounded-full border border-border/60 bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                            <PackageSearch className="size-4 text-emerald-500" />
                            Bodega conectada a Google Sheets
                        </div>
                        <h1 className="mt-3 text-2xl font-semibold text-foreground">Inventario</h1>
                        <p className="mt-2 max-w-3xl text-sm leading-relaxed font-normal tracking-[-0.01em] text-muted-foreground">
                            Lectura gerencial del sistema actual de inventario: valor en bodega, cuentas por pagar y consumos por area para cruzar con habitaciones, restaurante y mantenimiento.
                        </p>
                    </div>
                    <div className="rounded-lg border border-border/60 bg-card px-4 py-3 text-sm text-muted-foreground">
                        <div className="flex items-center gap-2">
                            <Clock3 className="size-4" />
                            {formatDateTime(dashboard.generatedAt ?? dashboard.lastSyncedAt)}
                        </div>
                    </div>
                </section>

                <section className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                    <MetricCard icon={Boxes} label="Productos" value={number(dashboard.summary.totalProducts)} detail="Catalogo activo" />
                    <MetricCard icon={DollarSign} label="Inventario" value={money(dashboard.summary.inventoryValue)} detail="Valor total" />
                    <MetricCard icon={FileWarning} label="CxP total" value={money(dashboard.summary.payablesTotal)} detail={`${dashboard.summary.pendingDocuments} documentos`} />
                    <MetricCard icon={AlertTriangle} label="Vencido" value={money(dashboard.summary.payablesOverdue)} detail={`${overdueRate.toFixed(1)}% del pendiente`} />
                    <MetricCard icon={TrendingDown} label="Egresos" value={money(dashboard.movementTotals.expenses.value)} detail={`${dashboard.movementTotals.expenses.records} registros`} />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1.2fr]">
                    <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
                        <CardContent className="grid gap-6 p-6">
                            <div className="flex items-center justify-between gap-3">
                <h2 className="text-base font-semibold text-foreground">Inventario por ubicacion</h2>
                                <Badge variant="secondary" className="rounded-md">
                                    {money(dashboard.summary.inventoryValue)}
                                </Badge>
                            </div>
                            <div className="grid gap-3">
                                {dashboard.locations.map((location) => (
                        <div key={location.key} className="grid gap-2 rounded-lg border border-border/60 p-4 transition-colors duration-150 hover:bg-muted/50">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-medium text-foreground">{location.label}</span>
                                            <span className="text-sm text-muted-foreground">{money(location.value)}</span>
                                        </div>
                                        <div className="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full bg-emerald-500"
                                                style={{ width: `${dashboard.summary.inventoryValue ? (location.value / dashboard.summary.inventoryValue) * 100 : 0}%` }}
                                            />
                                        </div>
                                        <div className="mt-2 text-xs text-muted-foreground">{number(location.quantity)} unidades registradas</div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
                        <CardContent className="grid gap-6 p-6">
                            <h2 className="text-base font-semibold text-foreground">Señales para gerencia</h2>
                            <div className="grid gap-3 md:grid-cols-3">
                                {dashboard.signals.map((signal) => (
                                    <div key={signal.title} className="rounded-lg border border-border/60 p-4">
                                        <div className="text-sm text-muted-foreground">{signal.title}</div>
                                        <div className="mt-2 text-2xl font-semibold text-foreground">{signal.value}</div>
                                        <div className="mt-2 text-xs leading-5 text-muted-foreground">{signal.detail}</div>
                                    </div>
                                ))}
                            </div>
                            <div className="grid gap-3 rounded-lg border border-border/60 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-foreground">
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
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-2 p-4">
                <div className="flex items-center justify-between gap-2">
                    <span className="text-xs font-medium text-muted-foreground">{label}</span>
                    <Icon className="size-3.5 shrink-0 text-muted-foreground" />
                </div>
                <div className="grid gap-0.5">
                    <p className="tabular-nums text-xl font-semibold text-foreground">{value}</p>
                    <p className="text-[11px] text-muted-foreground">{detail}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function Movement({ label, value, records }: { label: string; value: number; records: number }) {
    return (
        <div className="rounded-lg bg-muted/40 p-3">
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="mt-1 text-lg font-semibold text-foreground">{money(value)}</div>
            <div className="text-xs text-muted-foreground">{records} registros</div>
        </div>
    );
}

function AreaList({ title, areas, maxValue, tone }: { title: string; areas: AreaMetric[]; maxValue: number; tone: 'emerald' | 'rose' }) {
    return (
        <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
            <CardContent className="grid gap-6 p-6">
                <h2 className="text-base font-semibold text-foreground">{title}</h2>
                <div className="grid gap-3">
                    {areas.map((area) => (
                        <div key={area.key} className="grid gap-2 rounded-lg border border-border/60 p-4 transition-colors duration-150 hover:bg-muted/50">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm font-medium text-foreground">{area.label}</span>
                                <span className="text-sm text-muted-foreground">{money(area.value)}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                <div className={tone === 'emerald' ? 'h-full bg-emerald-500' : 'h-full bg-rose-500'} style={{ width: `${(area.value / maxValue) * 100}%` }} />
                            </div>
                            <div className="flex justify-between text-xs text-muted-foreground">
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
