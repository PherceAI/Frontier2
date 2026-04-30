import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, ClipboardList, Filter, LoaderCircle, Save, Search, Scale, UtensilsCrossed } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type RecipeItem = {
    id: number;
    sort_order: number;
    inventory_product_id: string | null;
    inventory_product_name: string;
    quantity_used: number;
    unit: string;
    equivalence: string | null;
    notes: string | null;
};

type Recipe = {
    id: number;
    dish_code: string | null;
    dish_name: string;
    category: string | null;
    subcategory: string | null;
    is_active: boolean;
    imported_at: string | null;
    updated_at: string | null;
    items: RecipeItem[];
};

type Summary = {
    recipes: number;
    items: number;
    categories: number;
    lastImportedAt: string | null;
};

type Filters = {
    search: string;
    category: string;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Receta', href: '/recipes' }];

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

function RecipeMetadataForm({ recipe }: { recipe: Recipe }) {
    const { data, setData, patch, processing, recentlySuccessful, errors } = useForm({
        dish_code: recipe.dish_code ?? '',
        dish_name: recipe.dish_name,
        category: recipe.category ?? '',
        subcategory: recipe.subcategory ?? '',
        is_active: recipe.is_active,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('recipes.update', recipe.id), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid gap-3 border-b border-neutral-200 p-4 md:grid-cols-[120px_1.4fr_1fr_1fr_auto] md:items-start dark:border-zinc-800">
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Codigo</label>
                <Input value={data.dish_code} onChange={(event) => setData('dish_code', event.target.value)} className="rounded-lg" />
                {errors.dish_code && <span className="text-xs text-red-500">{errors.dish_code}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Plato</label>
                <Input value={data.dish_name} onChange={(event) => setData('dish_name', event.target.value)} className="rounded-lg" />
                {errors.dish_name && <span className="text-xs text-red-500">{errors.dish_name}</span>}
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Categoria</label>
                <Input value={data.category} onChange={(event) => setData('category', event.target.value)} className="rounded-lg" />
            </div>
            <div className="grid gap-1">
                <label className="text-xs font-medium uppercase text-neutral-500 dark:text-zinc-400">Subcategoria</label>
                <Input value={data.subcategory} onChange={(event) => setData('subcategory', event.target.value)} className="rounded-lg" />
            </div>
            <div className="flex gap-2 md:pt-5">
                <Button type="submit" disabled={processing} className="rounded-lg">
                    {processing ? <LoaderCircle className="size-4 animate-spin" /> : recentlySuccessful ? <Check className="size-4" /> : <Save className="size-4" />}
                    Plato
                </Button>
            </div>
        </form>
    );
}

function RecipeItemForm({ item }: { item: RecipeItem }) {
    const { data, setData, patch, processing, recentlySuccessful, errors } = useForm({
        inventory_product_id: item.inventory_product_id ?? '',
        inventory_product_name: item.inventory_product_name,
        quantity_used: String(item.quantity_used),
        unit: item.unit,
        equivalence: item.equivalence ?? '',
        notes: item.notes ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('recipe-items.update', item.id), { preserveScroll: true });
    };

    return (
        <form
            onSubmit={submit}
            className="grid gap-3 border-t border-neutral-200 px-4 py-3 md:grid-cols-[56px_1.4fr_120px_130px_1fr_auto] md:items-start dark:border-zinc-800"
        >
            <span className="flex h-10 items-center text-sm font-medium text-neutral-500 dark:text-zinc-400">#{item.sort_order}</span>
            <div className="grid gap-1">
                <Input
                    aria-label="Producto de inventario"
                    value={data.inventory_product_name}
                    onChange={(event) => setData('inventory_product_name', event.target.value)}
                    className="rounded-lg"
                />
                {errors.inventory_product_name && <span className="text-xs text-red-500">{errors.inventory_product_name}</span>}
            </div>
            <div className="grid gap-1">
                <Input
                    aria-label="Cantidad usada"
                    type="number"
                    min="0"
                    step="0.0001"
                    value={data.quantity_used}
                    onChange={(event) => setData('quantity_used', event.target.value)}
                    className="rounded-lg"
                />
                {errors.quantity_used && <span className="text-xs text-red-500">{errors.quantity_used}</span>}
            </div>
            <div className="grid gap-1">
                <Input aria-label="Unidad" value={data.unit} onChange={(event) => setData('unit', event.target.value)} className="rounded-lg" />
                {errors.unit && <span className="text-xs text-red-500">{errors.unit}</span>}
            </div>
            <Input
                aria-label="Equivalencia"
                value={data.equivalence}
                onChange={(event) => setData('equivalence', event.target.value)}
                placeholder="Equivalencia"
                className="rounded-lg"
            />
            <Button type="submit" disabled={processing} variant="outline" className="rounded-lg">
                {processing ? <LoaderCircle className="size-4 animate-spin" /> : recentlySuccessful ? <Check className="size-4" /> : <Save className="size-4" />}
            </Button>
        </form>
    );
}

function RecipeCard({ recipe }: { recipe: Recipe }) {
    const totalQuantity = recipe.items.reduce((sum, item) => sum + item.quantity_used, 0);

    return (
        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
            <CardHeader className="gap-3 p-5">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            {recipe.dish_code && <Badge variant="outline">{recipe.dish_code}</Badge>}
                            {recipe.category && <Badge className="bg-sky-100 text-sky-800 hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-200">{recipe.category}</Badge>}
                            {recipe.subcategory && <Badge variant="secondary">{recipe.subcategory}</Badge>}
                        </div>
                        <CardTitle className="mt-3 text-xl font-semibold text-neutral-900 dark:text-zinc-50">{recipe.dish_name}</CardTitle>
                    </div>
                    <div className="grid gap-1 text-sm text-neutral-500 sm:text-right dark:text-zinc-400">
                        <span>{recipe.items.length} ingredientes</span>
                        <span>{numberFormat(totalQuantity)} unidades teoricas</span>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="p-0">
                <RecipeMetadataForm recipe={recipe} />
                <div className="hidden border-t border-neutral-200 bg-neutral-50 px-4 py-2 text-xs font-medium uppercase text-neutral-500 md:grid md:grid-cols-[56px_1.4fr_120px_130px_1fr_auto] dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                    <span>Linea</span>
                    <span>Producto teorico</span>
                    <span>Cantidad</span>
                    <span>Unidad</span>
                    <span>Equivalencia</span>
                    <span>Guardar</span>
                </div>
                {recipe.items.map((item) => (
                    <RecipeItemForm key={item.id} item={item} />
                ))}
            </CardContent>
        </Card>
    );
}

export default function RecipesIndex({
    recipes,
    filters,
    categories,
    summary,
}: {
    recipes: Recipe[];
    filters: Filters;
    categories: string[];
    summary: Summary;
}) {
    const [search, setSearch] = useState(filters.search);

    const applyFilters = (next: Partial<Filters>) => {
        router.get(
            '/recipes',
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
            <Head title="Receta minima estandar" />
            <div className="flex h-full flex-1 flex-col gap-6 bg-neutral-50 p-6 dark:bg-zinc-950">
                <section className="flex flex-col gap-4">
                    <span className="flex w-fit items-center gap-2 text-sm font-normal text-neutral-500 dark:text-zinc-400">
                        <span className="size-2 rounded-full bg-emerald-500" />
                        Teorico de cocina
                    </span>
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div className="grid gap-2">
                            <h1 className="text-3xl font-semibold text-neutral-900 dark:text-zinc-50">Receta minima estandar</h1>
                            <p className="max-w-3xl text-sm leading-6 font-normal text-neutral-500 dark:text-zinc-400">
                                Base teorica editable para comparar platos vendidos, consumos reales de cocina e inventario.
                            </p>
                        </div>
                        <span className="text-sm text-neutral-500 dark:text-zinc-400">Ultima importacion: {dateLabel(summary.lastImportedAt)}</span>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Platos', value: summary.recipes, icon: UtensilsCrossed },
                        { label: 'Ingredientes', value: summary.items, icon: ClipboardList },
                        { label: 'Categorias', value: summary.categories, icon: Filter },
                        { label: 'Filtrados', value: recipes.length, icon: Scale },
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
                                placeholder="Buscar plato o ingrediente"
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

                <section className="grid gap-4">
                    {recipes.length > 0 ? (
                        recipes.map((recipe) => <RecipeCard key={recipe.id} recipe={recipe} />)
                    ) : (
                        <Card className="rounded-xl border-neutral-200 bg-white shadow-none dark:border-zinc-800 dark:bg-zinc-900">
                            <CardContent className="flex items-center gap-3 p-6 text-sm text-neutral-500 dark:text-zinc-400">
                                <ClipboardList className="size-5" />
                                No hay recetas para los filtros actuales.
                            </CardContent>
                        </Card>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
