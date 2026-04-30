import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Check, LoaderCircle, Save, TrendingDown, UtensilsCrossed } from 'lucide-react';
import { FormEventHandler } from 'react';

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
    return new Intl.NumberFormat('es-EC', { maximumFractionDigits: 4 }).format(value ?? 0);
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
        <form onSubmit={submit} className="grid gap-3 border-b border-neutral-200 p-4 last:border-b-0 lg:grid-cols-[1.2fr_1fr_120px_auto] dark:border-zinc-800">
            <div className="min-w-0">
                <p className="truncate text-sm font-medium text-neutral-900 dark:text-zinc-50">{item.ingredient}</p>
                <p className="mt-1 truncate text-xs text-neutral-500 dark:text-zinc-400">
                    {item.recipe} · {numberFormat(item.quantityUsed)} {item.unit}
                </p>
            </div>
            <Select value={data.kitchen_daily_stock_item_id} onValueChange={(value) => setData('kitchen_daily_stock_item_id', value)}>
                <SelectTrigger className="rounded-lg">
                    <SelectValue placeholder="Producto stock cocina" />
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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis restaurante" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col gap-2">
                    <span className="flex w-fit items-center gap-2 text-sm text-neutral-500 dark:text-zinc-400">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Ciclo lunes a domingo
                    </span>
                    <h1 className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Conciliación de inventario cocina</h1>
                    <p className="text-sm text-neutral-500 dark:text-zinc-400">Semana {week.label}</p>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Días cerrados', value: summary.closedDays, icon: Check },
                        { label: 'Alertas', value: summary.alerts, icon: AlertTriangle },
                        { label: 'Mermas', value: numberFormat(summary.wasteTotal), icon: UtensilsCrossed },
                        { label: 'Faltante', value: numberFormat(summary.negativeDiscrepancyTotal), icon: TrendingDown },
                    ].map((metric) => (
                        <Card key={metric.label} className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="flex items-start justify-between gap-4 p-6">
                                <div className="grid gap-2">
                                    <p className="text-sm text-neutral-500 dark:text-zinc-400">{metric.label}</p>
                                    <p className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">{metric.value}</p>
                                </div>
                                <metric.icon className="size-5 text-neutral-500 dark:text-zinc-400" />
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                    <CardHeader className="p-5">
                        <CardTitle className="text-lg">Semana operativa</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-0 p-0">
                        {days.map((day) => (
                            <div key={day.date} className="border-t border-neutral-200 p-5 dark:border-zinc-800">
                                <div className="grid gap-3 lg:grid-cols-[160px_repeat(4,1fr)] lg:items-center">
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-zinc-50">{day.label}</p>
                                        <p className="text-sm text-neutral-500 dark:text-zinc-400">{day.status}</p>
                                    </div>
                                    <Metric label="Mermas" value={numberFormat(day.wasteTotal)} />
                                    <Metric
                                        label="Discrepancia negativa"
                                        value={numberFormat(day.negativeDiscrepancyTotal)}
                                        danger={day.negativeDiscrepancyTotal < 0}
                                    />
                                    <Metric label="Reposición requerida" value={numberFormat(day.replenishmentRequiredTotal)} />
                                    <Metric
                                        label="Reposición real"
                                        value={numberFormat(day.replenishmentActualTotal)}
                                        danger={day.replenishmentActualTotal !== day.replenishmentRequiredTotal && day.status === 'closed'}
                                    />
                                </div>
                                {day.hasAlert && (
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {day.negativeDiscrepancyTotal < 0 && <Badge className="bg-red-100 text-red-800 hover:bg-red-100">Alerta Operativa</Badge>}
                                        {day.pendingMappings?.length ? <Badge variant="outline">Mapeo pendiente</Badge> : null}
                                    </div>
                                )}
                                {day.items.length > 0 && (
                                    <div className="mt-4 overflow-x-auto">
                                        <table className="w-full min-w-[760px] text-left text-sm">
                                            <thead className="text-xs uppercase text-neutral-500 dark:text-zinc-400">
                                                <tr>
                                                    <th className="py-2">Producto</th>
                                                    <th>Conteo</th>
                                                    <th>Merma</th>
                                                    <th>Final teórico</th>
                                                    <th>Discrepancia</th>
                                                    <th>Reposición</th>
                                                    <th>Real</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {day.items.slice(0, 12).map((item) => (
                                                    <tr key={item.id} className="border-t border-neutral-200 dark:border-zinc-800">
                                                        <td className="py-2">
                                                            <p className="font-medium">{item.productName}</p>
                                                            <p className="text-xs text-neutral-500">{item.category}</p>
                                                        </td>
                                                        <td>{numberFormat(item.physicalCount)}</td>
                                                        <td>{numberFormat(item.wasteQuantity)}</td>
                                                        <td>{numberFormat(item.theoreticalFinal)}</td>
                                                        <td className={item.hasNegativeDiscrepancy ? 'font-semibold text-red-600' : ''}>{numberFormat(item.discrepancy)}</td>
                                                        <td>{numberFormat(item.replenishmentRequired)}</td>
                                                        <td className={item.hasReplenishmentAlert ? 'font-semibold text-amber-600' : ''}>{numberFormat(item.replenishmentActual)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                    <CardHeader className="p-5">
                        <CardTitle className="text-lg">Mapeo receta a stock cocina</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {mappings.recipeItems.length > 0 ? (
                            mappings.recipeItems.map((item) => <MappingForm key={item.id} item={item} stockItems={mappings.stockItems} />)
                        ) : (
                            <p className="p-5 text-sm text-neutral-500 dark:text-zinc-400">No hay ingredientes de recetas para mapear.</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function Metric({ label, value, danger = false }: { label: string; value: string; danger?: boolean }) {
    return (
        <div>
            <p className="text-xs uppercase text-neutral-500 dark:text-zinc-400">{label}</p>
            <p className={`mt-1 text-lg font-semibold ${danger ? 'text-red-600' : 'text-neutral-900 dark:text-zinc-50'}`}>{value}</p>
        </div>
    );
}
