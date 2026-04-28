import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CalendarDays, CircleDollarSign, Clock, CreditCard, ReceiptText, ShoppingBasket, TrendingUp, UsersRound } from 'lucide-react';
import type { ComponentType } from 'react';

type MoneyMetric = {
    amount: number;
    count?: number;
};

type Dashboard = {
    period: 'today' | 'week' | 'month';
    dateLabel: string;
    rangeLabel: string;
    lastSyncedAt: string | null;
    summary: {
        salesTotal: number;
        salesInvoices: number;
        averageTicket: number;
        grossMargin: number;
        grossMarginPercent: number;
        purchaseTotal: number;
        purchaseInvoices: number;
    };
    topDishes: Array<{ name: string; amount: number; quantity: number; percent: number }>;
    waiterPerformance: Array<{ name: string; initials: string; amount: number }>;
    paymentDistribution: Array<{ code: string; label: string; amount: number; percent: number }>;
    documentStatus: {
        charged: MoneyMetric;
        pending: MoneyMetric;
        voided: MoneyMetric;
        creditNotes: MoneyMetric;
    };
    supplierPurchases: {
        total: number;
        count: number;
        dueSoonTotal: number;
        dueSoonCount: number;
        alerts: Array<{ supplier: string; document: string; amount: number; daysRemaining: number }>;
    };
    accountsPayable: {
        total: number;
        count: number;
        overdueTotal: number;
        overdueCount: number;
        dueTodayTotal: number;
        dueTodayCount: number;
        dueNext7Total: number;
        dueNext7Count: number;
        items: Array<{ supplier: string; document: string; amount: number; dueDate: string | null; daysRemaining: number | null }>;
    };
    isEmpty: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Restaurante', href: '/restaurant' }];
const periods = [
    { label: 'Hoy', value: 'today' },
    { label: 'Semana', value: 'week' },
    { label: 'Mes', value: 'month' },
] as const;
const paymentColors = ['bg-sky-500', 'bg-emerald-500', 'bg-amber-500', 'bg-neutral-400', 'bg-violet-500'];

function money(value: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: value % 1 === 0 ? 0 : 2,
    }).format(value);
}

function percent(value: number) {
    return `${new Intl.NumberFormat('es-EC', { maximumFractionDigits: 1 }).format(value)}%`;
}

const paymentChartColors = ['#0ea5e9', '#10b981', '#f59e0b', '#a3a3a3', '#8b5cf6'];

function paymentChart(paymentDistribution: Dashboard['paymentDistribution']) {
    let offset = 0;
    const segments = paymentDistribution.map((payment, index) => {
        const start = offset;
        offset += payment.percent;

        return `${paymentChartColors[index % paymentChartColors.length]} ${start}% ${offset}%`;
    });

    return segments.length > 0 ? `conic-gradient(${segments.join(', ')})` : undefined;
}

function KpiCard({
    title,
    value,
    detail,
    icon: Icon,
}: {
    title: string;
    value: string;
    detail: string;
    icon: ComponentType<{ className?: string }>;
}) {
    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="grid gap-4 p-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="grid gap-2">
                        <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{title}</p>
                        <p className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">{value}</p>
                    </div>
                    <Icon className="size-5 text-neutral-500 dark:text-zinc-400" />
                </div>
                <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                    <span className="size-2 rounded-full bg-emerald-500" />
                    {detail}
                </span>
            </CardContent>
        </Card>
    );
}

function EmptyState({ isEmpty }: { isEmpty: boolean }) {
    if (!isEmpty) {
        return null;
    }

    return (
        <Card className="rounded-xl border-amber-200 bg-amber-50 shadow-none dark:border-amber-900/60 dark:bg-amber-950/30">
            <CardContent className="flex flex-col gap-3 p-6 md:flex-row md:items-center md:justify-between">
                <div className="flex items-start gap-3">
                    <AlertTriangle className="mt-0.5 size-5 text-amber-600 dark:text-amber-400" />
                    <div className="grid gap-1">
                        <p className="text-base font-medium text-amber-950 dark:text-amber-100">Sin documentos sincronizados</p>
                        <p className="text-sm leading-6 font-normal text-amber-800 dark:text-amber-200">
                            Ejecuta el sincronizador de Contifico para poblar este periodo.
                        </p>
                    </div>
                </div>
                <code className="rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm text-amber-900 dark:border-amber-900/70 dark:bg-zinc-950 dark:text-amber-100">
                    php artisan frontier:sync-contifico-restaurant
                </code>
            </CardContent>
        </Card>
    );
}

export default function RestaurantDashboard({ dashboard }: { dashboard: Dashboard }) {
    const paymentTotal = dashboard.paymentDistribution.reduce((sum, item) => sum + item.amount, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Restaurante" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col justify-between gap-4 xl:flex-row xl:items-start">
                    <div className="grid gap-3">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Restaurante Hotel Zeus</h1>
                            <span className="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm font-normal text-neutral-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                                <span className="size-2 rounded-full bg-sky-500" />
                                Contifico
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-4 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                            <span className="flex items-center gap-2">
                                <CalendarDays className="size-4" />
                                {dashboard.dateLabel}
                            </span>
                            <span className="flex items-center gap-2">
                                <Clock className="size-4" />
                                {dashboard.lastSyncedAt
                                    ? `Sincronizado ${new Date(dashboard.lastSyncedAt).toLocaleString('es-EC')}`
                                    : 'Pendiente de sincronizacion'}
                            </span>
                        </div>
                    </div>
                    <div className="flex w-full rounded-xl border border-neutral-200 bg-white p-1 sm:w-fit dark:border-zinc-800 dark:bg-zinc-900">
                        {periods.map((period) => (
                            <Button
                                key={period.value}
                                asChild
                                variant={dashboard.period === period.value ? 'default' : 'ghost'}
                                className="flex-1 rounded-lg px-5 sm:flex-none"
                            >
                                <Link href={`/restaurant?period=${period.value}`} preserveScroll>
                                    {period.label}
                                </Link>
                            </Button>
                        ))}
                    </div>
                </section>

                <EmptyState isEmpty={dashboard.isEmpty} />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <KpiCard
                        title={`Ventas del periodo (${dashboard.rangeLabel})`}
                        value={money(dashboard.summary.salesTotal)}
                        detail={`${dashboard.summary.salesInvoices} facturas emitidas`}
                        icon={CircleDollarSign}
                    />
                    <KpiCard
                        title="Ticket promedio"
                        value={money(dashboard.summary.averageTicket)}
                        detail="Ventas sin propinas / facturas"
                        icon={ReceiptText}
                    />
                    <KpiCard
                        title="Margen bruto estimado"
                        value={money(dashboard.summary.grossMargin)}
                        detail={`${percent(dashboard.summary.grossMarginPercent)} ventas menos compras`}
                        icon={TrendingUp}
                    />
                    <KpiCard
                        title="Compras a proveedores"
                        value={money(dashboard.summary.purchaseTotal)}
                        detail={`${dashboard.summary.purchaseInvoices} facturas PRO`}
                        icon={ShoppingBasket}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="p-6">
                            <CardTitle className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Platos mas vendidos</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 p-6 pt-0">
                            {dashboard.topDishes.length > 0 ? (
                                dashboard.topDishes.map((dish) => (
                                    <div key={dish.name} className="grid gap-2">
                                        <div className="flex items-center justify-between gap-4">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">{dish.name}</p>
                                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{dish.quantity} unidades</p>
                                            </div>
                                            <p className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{money(dish.amount)}</p>
                                        </div>
                                        <div className="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-zinc-800">
                                            <div className="h-full rounded-full bg-sky-500" style={{ width: `${Math.min(dish.percent, 100)}%` }} />
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Sin detalle de productos en este periodo.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="flex flex-row items-center justify-between p-6">
                            <CardTitle className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Desempeno por mesero</CardTitle>
                            <UsersRound className="size-5 text-neutral-500 dark:text-zinc-400" />
                        </CardHeader>
                        <CardContent className="grid gap-0 p-0">
                            {dashboard.waiterPerformance.length > 0 ? (
                                dashboard.waiterPerformance.map((waiter) => (
                                    <div
                                        key={waiter.name}
                                        className="flex items-center justify-between gap-4 border-t border-neutral-200 px-6 py-4 dark:border-zinc-800"
                                    >
                                        <div className="flex min-w-0 items-center gap-3">
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-medium text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                                {waiter.initials}
                                            </span>
                                            <span className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">{waiter.name}</span>
                                        </div>
                                        <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{money(waiter.amount)}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="border-t border-neutral-200 p-6 text-sm font-normal text-neutral-500 dark:border-zinc-800 dark:text-zinc-400">
                                    Sin vendedor asignado en las facturas del periodo.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.1fr_1fr]">
                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="flex flex-row items-center justify-between p-6">
                            <CardTitle className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Distribucion de cobros</CardTitle>
                            <CreditCard className="size-5 text-neutral-500 dark:text-zinc-400" />
                        </CardHeader>
                        <CardContent className="grid gap-6 p-6 pt-0 md:grid-cols-[180px_1fr] md:items-center">
                            <div
                                className="relative mx-auto flex size-40 items-center justify-center rounded-full bg-neutral-100 dark:bg-zinc-800"
                                style={{ background: paymentChart(dashboard.paymentDistribution) }}
                            >
                                <div className="flex size-24 items-center justify-center rounded-full bg-white dark:bg-zinc-900">
                                    <span className="text-xl font-semibold text-neutral-900 dark:text-zinc-50">{money(paymentTotal)}</span>
                                </div>
                            </div>
                            <div className="grid gap-3">
                                {dashboard.paymentDistribution.length > 0 ? (
                                    dashboard.paymentDistribution.map((payment, index) => (
                                        <div key={payment.code} className="flex items-center justify-between gap-4">
                                            <span className="flex min-w-0 items-center gap-3 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                                <span className={`size-2 rounded-full ${paymentColors[index % paymentColors.length]}`} />
                                                <span className="truncate">{payment.label}</span>
                                            </span>
                                            <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{percent(payment.percent)}</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Sin cobros registrados.</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="p-6">
                            <CardTitle className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Estado de documentos</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-0 p-0">
                            {[
                                ['Cobradas', dashboard.documentStatus.charged],
                                ['Pendientes de cobro', dashboard.documentStatus.pending],
                                ['Facturas anuladas', dashboard.documentStatus.voided],
                                ['Notas de credito', dashboard.documentStatus.creditNotes],
                            ].map(([label, metric]) => (
                                <div
                                    key={label as string}
                                    className="flex items-center justify-between gap-4 border-t border-neutral-200 px-6 py-4 dark:border-zinc-800"
                                >
                                    <span className="text-sm font-normal text-neutral-500 dark:text-zinc-400">{label as string}</span>
                                    <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">
                                        {(metric as MoneyMetric).count ?? 0} - {money((metric as MoneyMetric).amount)}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>

                <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                    <CardHeader className="p-6">
                        <CardTitle className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Compras a proveedores y vencimientos</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 p-6 pt-0">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Total compras</p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">
                                    {money(dashboard.supplierPurchases.total)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    {dashboard.supplierPurchases.count} facturas de proveedores
                                </p>
                            </div>
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Por pagar pronto</p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">
                                    {money(dashboard.supplierPurchases.dueSoonTotal)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-red-500">
                                    {dashboard.supplierPurchases.dueSoonCount} facturas vencen en menos de 3 dias
                                </p>
                            </div>
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Margen estimado</p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">
                                    {percent(dashboard.summary.grossMarginPercent)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-neutral-500 dark:text-zinc-400">Ventas CLI menos compras PRO</p>
                            </div>
                        </div>
                        {dashboard.supplierPurchases.alerts.length > 0 ? (
                            <div className="grid gap-3">
                                {dashboard.supplierPurchases.alerts.map((alert) => (
                                    <div
                                        key={`${alert.supplier}-${alert.document}`}
                                        className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30"
                                    >
                                        <span className="mt-1 size-2 rounded-full bg-amber-500" />
                                        <p className="text-sm leading-6 font-normal text-amber-900 dark:text-amber-100">
                                            Proveedor "{alert.supplier}": factura {alert.document} por {money(alert.amount)} vence en{' '}
                                            {alert.daysRemaining} dias.
                                        </p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="rounded-xl border border-neutral-200 p-4 text-sm font-normal text-neutral-500 dark:border-zinc-800 dark:text-zinc-400">
                                Sin alertas de proveedores venciendo en menos de 3 dias.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                    <CardHeader className="p-6">
                        <CardTitle className="text-lg font-medium text-neutral-900 dark:text-zinc-50">Cuentas por pagar</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 p-6 pt-0">
                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Pendiente total</p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">
                                    {money(dashboard.accountsPayable.total)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    {dashboard.accountsPayable.count} documentos PRO
                                </p>
                            </div>
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Vencido</p>
                                <p className="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                                    {money(dashboard.accountsPayable.overdueTotal)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    {dashboard.accountsPayable.overdueCount} facturas
                                </p>
                            </div>
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Vence hoy</p>
                                <p className="mt-2 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                                    {money(dashboard.accountsPayable.dueTodayTotal)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    {dashboard.accountsPayable.dueTodayCount} facturas
                                </p>
                            </div>
                            <div className="rounded-xl border border-neutral-200 p-4 dark:border-zinc-800">
                                <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Proximos 7 dias</p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-zinc-50">
                                    {money(dashboard.accountsPayable.dueNext7Total)}
                                </p>
                                <p className="mt-1 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    {dashboard.accountsPayable.dueNext7Count} facturas
                                </p>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-zinc-800">
                            {dashboard.accountsPayable.items.length > 0 ? (
                                dashboard.accountsPayable.items.map((item) => (
                                    <div
                                        key={`${item.supplier}-${item.document}`}
                                        className="grid gap-2 border-b border-neutral-200 px-4 py-3 last:border-b-0 md:grid-cols-[1fr_auto_auto] md:items-center dark:border-zinc-800"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">{item.supplier}</p>
                                            <p className="text-sm font-normal text-neutral-500 dark:text-zinc-400">Factura {item.document}</p>
                                        </div>
                                        <span className="text-sm font-medium text-neutral-900 dark:text-zinc-50">{money(item.amount)}</span>
                                        <span className="flex items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                            <span
                                                className={`size-2 rounded-full ${
                                                    item.daysRemaining !== null && item.daysRemaining < 0
                                                        ? 'bg-red-500'
                                                        : item.daysRemaining === 0
                                                          ? 'bg-amber-500'
                                                          : 'bg-sky-500'
                                                }`}
                                            />
                                            {item.daysRemaining === null
                                                ? 'Sin vencimiento'
                                                : item.daysRemaining < 0
                                                  ? `Vencida hace ${Math.abs(item.daysRemaining)} dias`
                                                  : item.daysRemaining === 0
                                                    ? 'Vence hoy'
                                                    : `Vence en ${item.daysRemaining} dias`}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="p-4 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                                    Sin cuentas por pagar pendientes en el cache actual.
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
