<?php

namespace Database\Seeders;

use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Domain\Restaurant\Models\KitchenInventoryProductMapping;
use App\Domain\Restaurant\Models\StandardRecipe;
use App\Domain\Restaurant\Models\StandardRecipeItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class RestaurantCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/restaurant-catalog.json');

        if (! File::exists($path)) {
            $this->command?->warn('Restaurant catalog seed file not found.');

            return;
        }

        $data = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $importedAt = Carbon::now();

        foreach ($data['standard_recipes'] ?? [] as $recipeData) {
            $recipe = StandardRecipe::updateOrCreate(
                $this->recipeIdentity($recipeData),
                [
                    'dish_code' => $recipeData['dish_code'] ?? null,
                    'dish_name' => $recipeData['dish_name'],
                    'category' => $recipeData['category'] ?? null,
                    'subcategory' => $recipeData['subcategory'] ?? null,
                    'is_active' => (bool) ($recipeData['is_active'] ?? true),
                    'imported_at' => $importedAt,
                ],
            );

            foreach ($recipeData['items'] ?? [] as $itemData) {
                $recipe->items()->updateOrCreate(
                    ['sort_order' => (int) $itemData['sort_order']],
                    [
                        'inventory_product_id' => $itemData['inventory_product_id'] ?? null,
                        'inventory_product_name' => $itemData['inventory_product_name'],
                        'quantity_used' => $itemData['quantity_used'] ?? 0,
                        'unit' => $itemData['unit'],
                        'equivalence' => $itemData['equivalence'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                    ],
                );
            }
        }

        foreach ($data['kitchen_daily_stock_items'] ?? [] as $itemData) {
            KitchenDailyStockItem::updateOrCreate(
                [
                    'category' => $itemData['category'],
                    'product_name' => $itemData['product_name'],
                ],
                [
                    'target_stock' => $itemData['target_stock'] ?? 0,
                    'unit' => $itemData['unit'],
                    'unit_detail' => $itemData['unit_detail'] ?? null,
                    'is_active' => (bool) ($itemData['is_active'] ?? true),
                    'imported_at' => $importedAt,
                ],
            );
        }

        foreach ($data['kitchen_inventory_product_mappings'] ?? [] as $mappingData) {
            $recipe = StandardRecipe::query()
                ->when($mappingData['recipe_dish_code'] ?? null, fn ($query, string $code) => $query->where('dish_code', $code))
                ->when(! ($mappingData['recipe_dish_code'] ?? null), fn ($query) => $query->where('dish_name', $mappingData['recipe_dish_name']))
                ->first();
            $recipeItem = $recipe
                ? StandardRecipeItem::query()
                    ->where('restaurant_standard_recipe_id', $recipe->id)
                    ->where('sort_order', $mappingData['recipe_item_sort_order'])
                    ->first()
                : null;
            $stockItem = KitchenDailyStockItem::query()
                ->where('category', $mappingData['stock_category'])
                ->where('product_name', $mappingData['stock_product_name'])
                ->first();

            if (! $recipeItem || ! $stockItem) {
                continue;
            }

            KitchenInventoryProductMapping::updateOrCreate(
                ['restaurant_standard_recipe_item_id' => $recipeItem->id],
                [
                    'kitchen_daily_stock_item_id' => $stockItem->id,
                    'conversion_factor' => $mappingData['conversion_factor'] ?? 1,
                    'is_active' => (bool) ($mappingData['is_active'] ?? true),
                    'notes' => $mappingData['notes'] ?? null,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $recipeData
     * @return array<string, string>
     */
    private function recipeIdentity(array $recipeData): array
    {
        if (! empty($recipeData['dish_code'])) {
            return ['dish_code' => $recipeData['dish_code']];
        }

        return ['dish_name' => $recipeData['dish_name']];
    }
}
