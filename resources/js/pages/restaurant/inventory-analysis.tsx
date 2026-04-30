import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Check, ClipboardList, LoaderCircle, Save, Settings2, TrendingDown, UtensilsCrossed } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

type DayItem = {
    id: number;
    productName: string;
    category: string;
    unit: string;
    physicalCount: number;
    wasteQuantity: number;
    theoreticalFinal: number;
    discrepancy: number;
    replenishmentRequired: number;
    replenishmentActual: number;
    hasNegativeDiscrepancy: boolean;
    hasReplenishmentAlert: boolean;
};

type Day = {
    id?: number;
    date: string;
    label: string;
    status: string;
    countedBy?: string | null;
    replenishedBy?: string | null;
    wasteTotal: number;
    negativeDiscrepancyTotal: number;
    replenishmentRequiredTotal: number;
    replenishmentActualTotal: number;
    hasAlert: boolean;
    pendingMappings?: { recipe: string; ingredient: string }[];
    items: DayItem[];
};

type MappingRecipeItem = {
    id: number;
    recipe: string;
    ingredient: string;
    quantityUsed: number;
    unit: string;
    stockItemId: number | null;
    conversionFactor: number;
    isActive: boolean;
};

type StockOption = {
    id: number;
    label: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Restaurante', href: '/restaurant' },
    { title: 'Analisis', href: '/restaurant/analysis' },
];

function numberFormat(value: number) {
    return new Intl.NumberFormat('es-EC', { maximumFractionDigits: 2 }).format(value ?? 0);
}

function statusLabel(status: string) {
    const labels: Record<string, string> = {
        pending_count: 'Pendiente de conteo',
        count_submitted: 'Conteo enviado',
        closed: 'Cerrado',
        'sin cierre': 'Sin cierre',
    };

    return labels[status] ?? status.replaceAll('_', ' ');
}

function moneyTone(value: number) {
    return value < 0 ? 'text-red-700 dark:text-red-300' : 'text-neutral-900 dark:text-zinc-50';
}

function dayOutcome(day: Day) {
    if (day.status === 'sin cierre') {
        return 'No auditado';
    }

    if (day.negativeDiscrepancyTotal < 0) {
        return 'Faltante no justificado';
    }

    if (day.replenishmentActualTotal !== day.replenishmentRequiredTotal && day.status === 'closed') {
        return 'Reposicion no coincide';
    }

    if (day.pendingMappings?.length) {
        return 'Cruce incompleto';
    }

    return 'Sin alerta';
}

function issueItems(day: Day) {
    return day.items
        .filter((item) => item.hasNegativeDiscrepancy || item.hasReplenishmentAlert || item.wasteQuantity > 0)
        .sort((a, b) => Math.abs(b.discrepancy) - Math.abs(a.discrepancy))
        .slice(0, 8);
}

function MappingForm({ item, stockItems }: { item: MappingRecipeItem; stockItems: StockOption[] }) {
    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        kitchen_daily_stock_item_id: item.stockItemId ? String(item.stockItemId) : '',
        conversion_factor: String(item.conversionFactor || 1),
        is_active: item.isActive,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('kitchen-inventory-mappings.update', item.id), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid gap-3 border-b border-neutral-200 p-4 last:border-b-0 lg:grid-cols-[1.4fr_1fr_120px_auto] dark:border-zinc-800">
            <div className="min-w-0">
                <p className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">{item.ingredient}</p>
                <p className="mt-1 truncate text-xs text-neutral-500 dark:text-zinc-400">
                    {item.recipe} / {numberFormat(item.quantityUsed)} {item.unit}
                </p>
            </div>
            <Select value={data.kitchen_daily_stock_item_id} onValueChange={(value) => setData('kitchen_daily_stock_item_id', value)}>
                <SelectTrigger className="rounded-lg">
                    <SelectValue placeholder="Producto de stock" />
                </SelectTrigger>
                <SelectContent>
                    {stockItems.map((stockItem) => (
                        <SelectItem key={stockItem.id} value={String(stockItem.id)}>
                            {stockItem.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Input
                aria-label="Factor"
                type="number"
                min="0.000001"
                step="0.000001"
                value={data.conversion_factor}
                onChange={(event) => setData('conversion_factor', event.target.value)}
                className="rounded-lg"
            />
            <Button type="submit" disabled={processing || !data.kitchen_daily_stock_item_id} className="rounded-lg">
                {processing ? <LoaderCircle className="size-4 animate-spin" /> : recentlySuccessful ? <Check className="size-4" /> : <Save className="size-4" />}
                Guardar
            </Button>
        </form>
    );
}

export default function InventoryAnalysis({
    week,
    days,
    summary,
    mappings,
}: {
    week: { start: string; end: string; label: string };
    days: Day[];
    summary: { closedDays: number; alerts: number; wasteTotal: number; negativeDiscrepancyTotal: number };
    mappings: { stockItems: StockOption[]; recipeItems: MappingRecipeItem[] };
}) {
    const [selectedDate, setSelectedDate] = useState(days.find((day) => day.hasAlert)?.date ?? days[0]?.date);
    const selectedDay = useMemo(() => days.find((day) => day.date === selectedDate) ?? days[0], [days, selectedDate]);
    const pendingMappingCount = days.reduce((total, day) => total + (day.pendingMappings?.length ?? 0), 0);
    const replenishmentMismatch = days.filter((day) => day.status === 'closed' && day.replenishmentActualTotal !== day.replenishmentRequiredTotal).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis restaurante" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col gap-3">
                    <span className="flex w-fit items-center gap-2 text-sm text-neutral-500 dark:text-zinc-400">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Cocina / inventario
                    </span>
                    <div className="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                        <div className="grid gap-2">
                            <h1 className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Cierre semanal de cocina</h1>
                            <p className="text-sm text-neutral-500 dark:text-zinc-400">Semana {week.label}</p>
                        </div>
                        <Badge variant={summary.alerts > 0 ? 'destructive' : 'outline'} className="w-fit rounded-lg px-3 py-1">
                            {summary.alerts > 0 ? `${summary.alerts} dias con alerta` : 'Semana sin alertas'}
                        </Badge>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard label="Dias cerrados" value={`${summary.closedDays}/7`} icon={Check} />
                    <SummaryCard label="Faltante no justificado" value={numberFormat(summary.negativeDiscrepancyTotal)} icon={TrendingDown} danger={summary.negativeDiscrepancyTotal < 0} />
                    <SummaryCard label="Merma registrada" value={numberFormat(summary.wasteTotal)} icon={UtensilsCrossed} />
                    <SummaryCard label="Reposiciones con diferencia" value={replenishmentMismatch} icon={AlertTriangle} danger={replenishmentMismatch > 0} />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="border-b border-neutral-200 p-5 dark:border-zinc-800">
                            <CardTitle className="text-lg">Lectura por dia</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="hidden grid-cols-[1fr_140px_150px_150px_150px] border-b border-neutral-200 bg-neutral-50 px-5 py-3 text-xs font-medium uppercase text-neutral-500 lg:grid dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                                <span>Dia operativo</span>
                                <span>Estado</span>
                                <span>Resultado</span>
                                <span>Faltante</span>
                                <span>Reposicion</span>
                            </div>
                            {days.map((day) => (
                                <button
                                    key={day.date}
                                    type="button"
                                    onClick={() => setSelectedDate(day.date)}
                                    className={`grid w-full gap-3 border-b border-neutral-200 px-5 py-4 text-left last:border-b-0 lg:grid-cols-[1fr_140px_150px_150px_150px] lg:items-center dark:border-zinc-800 ${
                                        selectedDay?.date === day.date ? 'bg-sky-50 dark:bg-sky-950/30' : 'bg-white dark:bg-zinc-900'
                                    }`}
                                >
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-zinc-50">{day.label}</p>
                                        <p className="mt-1 text-xs text-neutral-500 dark:text-zinc-400">{day.countedBy ? `Conteo: ${day.countedBy}` : day.date}</p>
                                    </div>
                                    <span className="text-sm text-neutral-600 dark:text-zinc-300">{statusLabel(day.status)}</span>
                                    <span className={day.hasAlert ? 'text-sm font-semibold text-red-700 dark:text-red-300' : 'text-sm text-emerald-700 dark:text-emerald-300'}>
                                        {dayOutcome(day)}
                                    </span>
                                    <span className={`text-sm font-semibold ${moneyTone(day.negativeDiscrepancyTotal)}`}>{numberFormat(day.negativeDiscrepancyTotal)}</span>
                                    <span className="text-sm text-neutral-600 dark:text-zinc-300">
                                        {numberFormat(day.replenishmentActualTotal)} / {numberFormat(day.replenishmentRequiredTotal)}
                                    </span>
                                </button>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="border-b border-neutral-200 p-5 dark:border-zinc-800">
                            <CardTitle className="text-lg">Auditoria del dia</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 p-5">
                            {selectedDay ? (
                                <>
                                    <div className="grid gap-1">
                                        <p className="text-xl font-semibold text-neutral-900 dark:text-zinc-50">{selectedDay.label}</p>
                                        <p className="text-sm text-neutral-500 dark:text-zinc-400">{dayOutcome(selectedDay)}</p>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <MiniMetric label="Merma" value={numberFormat(selectedDay.wasteTotal)} />
                                        <MiniMetric label="Faltante" value={numberFormat(selectedDay.negativeDiscrepancyTotal)} danger={selectedDay.negativeDiscrepancyTotal < 0} />
                                        <MiniMetric label="Pedir" value={numberFormat(selectedDay.replenishmentRequiredTotal)} />
                                        <MiniMetric label="Sacado" value={numberFormat(selectedDay.replenishmentActualTotal)} danger={selectedDay.replenishmentActualTotal !== selectedDay.replenishmentRequiredTotal && selectedDay.status === 'closed'} />
                                    </div>
                                    <div className="grid gap-2">
                                        {issueItems(selectedDay).length > 0 ? (
                                            issueItems(selectedDay).map((item) => (
                                                <div key={item.id} className="rounded-lg border border-neutral-200 p-3 dark:border-zinc-800">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">{item.productName}</p>
                                                            <p className="mt-1 text-xs text-neutral-500 dark:text-zinc-400">{item.category}</p>
                                                        </div>
                                                        <Badge variant={item.hasNegativeDiscrepancy ? 'destructive' : 'outline'}>
                                                            {item.hasNegativeDiscrepancy ? 'Faltante' : 'Revision'}
                                                        </Badge>
                                                    </div>
                                                    <div className="mt-3 grid grid-cols-3 gap-2 text-xs">
                                                        <MiniLine label="Contado" value={`${numberFormat(item.physicalCount)} ${item.unit}`} />
                                                        <MiniLine label="Teorico" value={`${numberFormat(item.theoreticalFinal)} ${item.unit}`} />
                                                        <MiniLine label="Diferencia" value={`${numberFormat(item.discrepancy)} ${item.unit}`} danger={item.discrepancy < 0} />
                                                    </div>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="rounded-lg border border-dashed border-neutral-200 p-4 text-sm text-neutral-500 dark:border-zinc-800 dark:text-zinc-400">
                                                No hay productos para auditar en este dia.
                                            </p>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-neutral-500 dark:text-zinc-400">Sin datos de cierre para esta semana.</p>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                    <CardHeader className="flex flex-col gap-3 border-b border-neutral-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Settings2 className="size-5 text-neutral-500" />
                                Cruce receta-stock
                            </CardTitle>
                            <p className="mt-1 text-sm text-neutral-500 dark:text-zinc-400">{pendingMappingCount} cruces pendientes en la semana</p>
                        </div>
                        <Badge variant="outline" className="w-fit">
                            Configuracion
                        </Badge>
                    </CardHeader>
                    <CardContent className="p-0">
                        {mappings.recipeItems.length > 0 ? (
                            mappings.recipeItems.map((item) => <MappingForm key={item.id} item={item} stockItems={mappings.stockItems} />)
                        ) : (
                            <p className="flex items-center gap-2 p-5 text-sm text-neutral-500 dark:text-zinc-400">
                                <ClipboardList className="size-4" />
                                No hay ingredientes de recetas para mapear.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, icon: Icon, danger = false }: { label: string; value: string | number; icon: typeof Check; danger?: boolean }) {
    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardContent className="flex items-start justify-between gap-4 p-6">
                <div className="grid gap-2">
                    <p className="text-sm text-neutral-500 dark:text-zinc-400">{label}</p>
                    <p className={`text-3xl font-semibold ${danger ? 'text-red-700 dark:text-red-300' : 'text-neutral-900 dark:text-zinc-50'}`}>{value}</p>
                </div>
                <Icon className={danger ? 'size-5 text-red-600' : 'size-5 text-neutral-500 dark:text-zinc-400'} />
            </CardContent>
        </Card>
    );
}

function MiniMetric({ label, value, danger = false }: { label: string; value: string; danger?: boolean }) {
    return (
        <div className="rounded-lg border border-neutral-200 p-3 dark:border-zinc-800">
            <p className="text-xs text-neutral-500 dark:text-zinc-400">{label}</p>
            <p className={`mt-1 text-lg font-semibold ${danger ? 'text-red-700 dark:text-red-300' : 'text-neutral-900 dark:text-zinc-50'}`}>{value}</p>
        </div>
    );
}

function MiniLine({ label, value, danger = false }: { label: string; value: string; danger?: boolean }) {
    return (
        <div>
            <p className="text-neutral-500 dark:text-zinc-400">{label}</p>
            <p className={`mt-1 font-semibold ${danger ? 'text-red-700 dark:text-red-300' : 'text-neutral-900 dark:text-zinc-50'}`}>{value}</p>
        </div>
    );
}
