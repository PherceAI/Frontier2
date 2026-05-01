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
            className="grid gap-3 border-b border-border/60 p-4 transition-colors hover:bg-muted/50 last:border-b-0 md:grid-cols-[150px_1.4fr_120px_100px_1fr_90px_auto] md:items-start"
        >
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-muted-foreground">Categoria</label>
                <Input value={data.category} onChange={(event) => setData('category', event.target.value)} className="rounded-lg" />
                {errors.category && <span className="text-xs text-red-500">{errors.category}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-muted-foreground">Producto</label>
                <Input value={data.product_name} onChange={(event) => setData('product_name', event.target.value)} className="rounded-lg" />
                {errors.product_name && <span className="text-xs text-red-500">{errors.product_name}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-muted-foreground">Objetivo</label>
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
                <label className="text-xs font-medium uppercase text-muted-foreground">Unidad</label>
                <Input value={data.unit} onChange={(event) => setData('unit', event.target.value)} className="rounded-lg" />
                {errors.unit && <span className="text-xs text-red-500">{errors.unit}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-muted-foreground">Detalle unidad</label>
                <Input value={data.unit_detail} onChange={(event) => setData('unit_detail', event.target.value)} className="rounded-lg" />
            </div>
            <label className="flex h-16 items-center gap-2 text-sm text-muted-foreground">
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
            <div className="flex h-full flex-1 flex-col gap-8 p-5 md:p-8">
                <section className="flex flex-col gap-4">
                    <span className="flex w-fit items-center gap-2 rounded-full border border-border/60 bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Catalogo de cierre
                    </span>
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div className="grid gap-2">
                            <h1 className="text-2xl font-semibold text-foreground">Stock diario cocina</h1>
                            <p className="max-w-3xl text-sm leading-relaxed font-normal text-muted-foreground">
                                Catalogo editable para conteo ciego, reposicion nocturna y cierre diario de cocina.
                            </p>
                        </div>
                        <div className="flex flex-col gap-2 sm:items-end">
                            <span className="text-sm text-muted-foreground">Ultima importacion: {dateLabel(summary.lastImportedAt)}</span>
                            <span className="text-sm text-muted-foreground">Fuente inicial: STOCK DIARIO COCINA.docx</span>
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
                        <Card key={metric.label} className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
                            <CardContent className="flex items-start justify-between gap-4 p-4">
                                <div className="grid gap-2">
                                    <p className="text-xs font-medium text-muted-foreground">{metric.label}</p>
                                    <p className="tabular-nums text-2xl font-semibold text-foreground">{metric.value}</p>
                                </div>
                                <metric.icon className="size-4 shrink-0 text-muted-foreground" />
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="flex flex-col gap-3 rounded-lg border border-border/60 bg-card p-4 md:flex-row md:items-center">
                    <form onSubmit={submitSearch} className="flex flex-1 flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
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

                <Card className="border-border/60 bg-card shadow-none transition-colors duration-150 hover:border-border">
                    <div className="hidden border-b border-border/60 bg-muted/40 px-4 py-2 text-xs font-medium uppercase text-muted-foreground md:grid md:grid-cols-[150px_1.4fr_120px_100px_1fr_90px_auto]">
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
                            <div className="flex items-center gap-3 p-6 text-sm text-muted-foreground">
                                <PackageCheck className="size-5" />
                                No hay productos para los filtros actuales.
                            </div>
                        )}
                    </CardContent>
                </Card>

                <section className="grid gap-3 rounded-lg border border-border/60 bg-card p-6 text-sm text-muted-foreground transition-colors duration-150 hover:border-border">
                    <div className="flex flex-wrap gap-2">
                        {categories.map((category) => (
                            <Badge key={category} variant="outline">
                                {category}
                            </Badge>
                        ))}
                    </div>
                    <p>El stock objetivo solo se muestra a gerencia. El portal operativo recibira estos productos activos sin exponer cantidades teoricas.</p>
                    <code className="rounded-lg border border-border/60 bg-muted/40 px-3 py-2 text-foreground">
                        php artisan frontier:import-kitchen-daily-stock "STOCK DIARIO COCINA (1).docx"
                    </code>
                </section>
            </div>
        </AppLayout>
    );
}
