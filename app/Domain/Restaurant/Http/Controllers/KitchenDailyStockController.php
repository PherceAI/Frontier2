<?php

namespace App\Domain\Restaurant\Http\Controllers;

use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenDailyStockController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $items = KitchenDailyStockItem::query()
            ->when($search !== '', fn ($query) => $query->where('product_name', 'ilike', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderBy('category')
            ->orderBy('product_name')
            ->get();

        $allItems = KitchenDailyStockItem::query()->get();

        return Inertia::render('restaurant/kitchen-stock', [
            'items' => $items->map(fn (KitchenDailyStockItem $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'product_name' => $item->product_name,
                'target_stock' => (float) $item->target_stock,
                'unit' => $item->unit,
                'unit_detail' => $item->unit_detail,
                'is_active' => $item->is_active,
                'imported_at' => $item->imported_at?->toISOString(),
                'updated_at' => $item->updated_at?->toISOString(),
            ])->values(),
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
            'categories' => $allItems->pluck('category')->filter()->unique()->sort()->values(),
            'summary' => [
                'items' => $allItems->count(),
                'active' => $allItems->where('is_active', true)->count(),
                'categories' => $allItems->pluck('category')->filter()->unique()->count(),
                'lastImportedAt' => $allItems->max('imported_at')?->toISOString(),
            ],
        ]);
    }

    public function update(Request $request, KitchenDailyStockItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:120'],
            'product_name' => ['required', 'string', 'max:255'],
            'target_stock' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:40'],
            'unit_detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $item->update($this->nullableStrings($validated, ['unit_detail']));

        return back();
    }

    /**
     * @param array<string, mixed> $values
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    private function nullableStrings(array $values, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values) && trim((string) $values[$key]) === '') {
                $values[$key] = null;
            }
        }

        return $values;
    }
}
