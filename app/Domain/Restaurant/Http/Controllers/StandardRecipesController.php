<?php

namespace App\Domain\Restaurant\Http\Controllers;

use App\Domain\Restaurant\Models\StandardRecipe;
use App\Domain\Restaurant\Models\StandardRecipeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StandardRecipesController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $recipes = StandardRecipe::query()
            ->with('items')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('dish_code', 'ilike', "%{$search}%")
                        ->orWhere('dish_name', 'ilike', "%{$search}%")
                        ->orWhereHas('items', fn ($items) => $items->where('inventory_product_name', 'ilike', "%{$search}%"));
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderBy('dish_name')
            ->get();

        $allRecipes = StandardRecipe::query()->withCount('items')->get();
        $itemsCount = (int) $allRecipes->sum('items_count');

        return Inertia::render('restaurant/recipes', [
            'recipes' => $recipes->map(fn (StandardRecipe $recipe): array => [
                'id' => $recipe->id,
                'dish_code' => $recipe->dish_code,
                'dish_name' => $recipe->dish_name,
                'category' => $recipe->category,
                'subcategory' => $recipe->subcategory,
                'is_active' => $recipe->is_active,
                'imported_at' => $recipe->imported_at?->toISOString(),
                'updated_at' => $recipe->updated_at?->toISOString(),
                'items' => $recipe->items->map(fn (StandardRecipeItem $item): array => [
                    'id' => $item->id,
                    'sort_order' => $item->sort_order,
                    'inventory_product_id' => $item->inventory_product_id,
                    'inventory_product_name' => $item->inventory_product_name,
                    'quantity_used' => (float) $item->quantity_used,
                    'unit' => $item->unit,
                    'equivalence' => $item->equivalence,
                    'notes' => $item->notes,
                ])->values(),
            ])->values(),
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
            'categories' => StandardRecipe::query()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->values(),
            'summary' => [
                'recipes' => $allRecipes->count(),
                'items' => $itemsCount,
                'categories' => $allRecipes->pluck('category')->filter()->unique()->count(),
                'lastImportedAt' => $allRecipes->max('imported_at')?->toISOString(),
            ],
        ]);
    }

    public function updateRecipe(Request $request, StandardRecipe $recipe): RedirectResponse
    {
        $validated = $request->validate([
            'dish_code' => ['nullable', 'string', 'max:80', 'unique:restaurant_standard_recipes,dish_code,'.$recipe->id],
            'dish_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $recipe->update($this->nullableStrings($validated, ['dish_code', 'category', 'subcategory']));

        return back();
    }

    public function updateItem(Request $request, StandardRecipeItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_product_id' => ['nullable', 'string', 'max:255'],
            'inventory_product_name' => ['required', 'string', 'max:255'],
            'quantity_used' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:80'],
            'equivalence' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item->update($this->nullableStrings($validated, ['inventory_product_id', 'equivalence', 'notes']));

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
