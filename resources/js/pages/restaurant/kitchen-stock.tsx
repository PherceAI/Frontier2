import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Filter, LoaderCircle, PackageCheck, Save, Search, Scale } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type StockItem = {
    id: number;
    category: string;
    product_name: string;
    target_stock: number;
    unit: string;
    unit_detail: string | null;
    is_active: boolean;
    imported_at: string | null;
    updated_at: string | null;
};

type Filters = {
    search: string;
    category: string;
};

type Summary = {
    items: number;
    active: number;
    categories: number;
    lastImportedAt: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stock Cocina', href: '/kitchen-stock' }];

function numberFormat(value: number) {
    return new Intl.NumberFormat('es-EC', { maximumFractionDigits: 4 }).format(value);
}

function dateLabel(value: string | null) {
    if (!value) {
        return 'Pendiente';
    }

    return new Date(value).toLocaleString('es-EC', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function StockItemForm({ item }: { item: StockItem }) {
    const { data, setData, patch, processing, recentlySuccessful, errors } = useForm({
        category: item.category,
        product_name: item.product_name,
        target_stock: String(item.target_stock),
        unit: item.unit,
        unit_detail: item.unit_detail ?? '',
        is_active: item.is_active,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('kitchen-stock.update', item.id), { preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="grid gap-3 border-b border-neutral-200 p-4 last:border-b-0 md:grid-cols-[150px_1.4fr_120px_100px_1fr_90px_auto] md:items-start dark:border-zinc-800"
        >
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Categoria</label>
                <Input value={data.category} onChange={(event) => setData('category', event.target.value)} className="rounded-lg" />
                {errors.category && <span className="text-xs text-red-500">{errors.category}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Producto</label>
                <Input value={data.product_name} onChange={(event) => setData('product_name', event.target.value)} className="rounded-lg" />
                {errors.product_name && <span className="text-xs text-red-500">{errors.product_name}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Objetivo</label>
                <Input
                    type="number"
                    min="0"
                    step="0.0001"
                    value={data.target_stock}
                    onChange={(event) => setData('target_stock', event.target.value)}
                    className="rounded-lg"
                />
                {errors.target_stock && <span className="text-xs text-red-500">{errors.target_stock}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Unidad</label>
                <Input value={data.unit} onChange={(event) => setData('unit', event.target.value)} className="rounded-lg" />
                {errors.unit && <span className="text-xs text-red-500">{errors.unit}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Detalle unidad</label>
                <Input value={data.unit_detail} onChange={(event) => setData('unit_detail', event.target.value)} className="rounded-lg" />
            </div>
            <label className="flex h-16 items-center gap-2 text-sm text-neutral-600 dark:text-zinc-300">
                <Checkbox checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked === true)} />
                Activo
            </label>
            <Button type="submit" disabled={processing} className="rounded-lg md:mt-6">
                {processing ? <LoaderCircle className="size-4 animate-spin" /> : recentlySuccessful ? <Check className="size-4" /> : <Save className="size-4" />}
                Guardar
            </Button>
        </form>
    );
}

export default function KitchenStockIndex({
    items,
    filters,
    categories,
    summary,
}: {
    items: StockItem[];
    filters: Filters;
    categories: string[];
    summary: Summary;
}) {
    const [search, setSearch] = useState(filters.search);

    const applyFilters = (next: Partial<Filters>) => {
        router.get(
            '/kitchen-stock',
            {
                search,
                category: filters.category,
                ...next,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const submitSearch: FormEventHandler = (event) => {
        event.preventDefault();
        applyFilters({ search });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stock diario cocina" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col gap-4">
                    <span className="flex w-fit items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Catalogo de cierre
                    </span>
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div className="grid gap-2">
                            <h1 className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Stock diario cocina</h1>
                            <p className="max-w-3xl text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">
                                Catalogo editable para conteo ciego, reposicion nocturna y cierre diario de cocina.
                            </p>
                        </div>
                        <div className="flex flex-col gap-2 sm:items-end">
                            <span className="text-sm text-neutral-500 dark:text-zinc-400">Ultima importacion: {dateLabel(summary.lastImportedAt)}</span>
                            <span className="text-sm text-neutral-500 dark:text-zinc-400">Fuente inicial: STOCK DIARIO COCINA.docx</span>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Productos', value: summary.items, icon: PackageCheck },
                        { label: 'Activos', value: summary.active, icon: Check },
                        { label: 'Categorias', value: summary.categories, icon: Filter },
                        { label: 'Filtrados', value: items.length, icon: Scale },
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

                <section className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-4 md:flex-row md:items-center dark:border-zinc-800 dark:bg-zinc-900">
                    <form onSubmit={submitSearch} className="flex flex-1 flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Buscar producto"
                                className="rounded-lg pl-9"
                            />
                        </div>
                        <Button type="submit" className="rounded-lg">
                            <Search className="size-4" />
                            Buscar
                        </Button>
                    </form>
                    <div className="w-full md:w-64">
                        <Select value={filters.category || 'all'} onValueChange={(value) => applyFilters({ category: value === 'all' ? '' : value })}>
                            <SelectTrigger className="rounded-lg">
                                <SelectValue placeholder="Todas las categorias" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas las categorias</SelectItem>
                                {categories.map((category) => (
                                    <SelectItem key={category} value={category}>
                                        {category}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </section>

                <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                    <div className="hidden border-b border-neutral-200 bg-neutral-50 px-4 py-2 text-xs font-medium uppercase text-neutral-500 md:grid md:grid-cols-[150px_1.4fr_120px_100px_1fr_90px_auto] dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                        <span>Categoria</span>
                        <span>Producto</span>
                        <span>Objetivo</span>
                        <span>Unidad</span>
                        <span>Detalle</span>
                        <span>Estado</span>
                        <span>Guardar</span>
                    </div>
                    <CardContent className="p-0">
                        {items.length > 0 ? (
                            items.map((item) => <StockItemForm key={item.id} item={item} />)
                        ) : (
                            <div className="flex items-center gap-3 p-6 text-sm text-neutral-500 dark:text-zinc-400">
                                <PackageCheck className="size-5" />
                                No hay productos para los filtros actuales.
                            </div>
                        )}
                    </CardContent>
                </Card>

                <section className="grid gap-3 rounded-xl border border-neutral-200 bg-white p-5 text-sm text-neutral-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                    <div className="flex flex-wrap gap-2">
                        {categories.map((category) => (
                            <Badge key={category} variant="outline">
                                {category}
                            </Badge>
                        ))}
                    </div>
                    <p>El stock objetivo solo se muestra a gerencia. El portal operativo recibira estos productos activos sin exponer cantidades teoricas.</p>
                    <code className="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-neutral-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-200">
                        php artisan frontier:import-kitchen-daily-stock "STOCK DIARIO COCINA (1).docx"
                    </code>
                </section>
            </div>
        </AppLayout>
    );
}
