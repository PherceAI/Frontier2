<?php

namespace App\Console\Commands;

use App\Domain\Restaurant\Models\KitchenDailyStockItem;
use App\Domain\Restaurant\Models\KitchenInventoryProductMapping;
use App\Domain\Restaurant\Models\StandardRecipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportRestaurantCatalogSeedCommand extends Command
{
    protected $signature = 'frontier:export-restaurant-catalog-seed {path=database/seeders/data/restaurant-catalog.json}';

    protected $description = 'Export non-sensitive restaurant catalog data for repeatable local seeding.';

    public function handle(): int
    {
        $path = base_path((string) $this->argument('path'));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'standard_recipes' => StandardRecipe::query()
                ->with('items')
                ->orderBy('dish_name')
                ->get()
                ->map(fn (StandardRecipe $recipe): array => [
                    'dish_code' => $recipe->dish_code,
                    'dish_name' => $recipe->dish_name,
                    'category' => $recipe->category,
                    'subcategory' => $recipe->subcategory,
                    'is_active' => $recipe->is_active,
                    'items' => $recipe->items
                        ->map(fn ($item): array => [
                            'sort_order' => $item->sort_order,
                            'inventory_product_id' => $item->inventory_product_id,
                            'inventory_product_name' => $item->inventory_product_name,
                            'quantity_used' => (string) $item->quantity_used,
                            'unit' => $item->unit,
                            'equivalence' => $item->equivalence,
                            'notes' => $item->notes,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'kitchen_daily_stock_items' => KitchenDailyStockItem::query()
                ->orderBy('category')
                ->orderBy('product_name')
                ->get()
                ->map(fn (KitchenDailyStockItem $item): array => [
                    'category' => $item->category,
                    'product_name' => $item->product_name,
                    'target_stock' => (string) $item->target_stock,
                    'unit' => $item->unit,
                    'unit_detail' => $item->unit_detail,
                    'is_active' => $item->is_active,
                ])
                ->values()
                ->all(),
            'kitchen_inventory_product_mappings' => KitchenInventoryProductMapping::query()
                ->with(['recipeItem.recipe', 'stockItem'])
                ->get()
                ->map(fn (KitchenInventoryProductMapping $mapping): array => [
                    'recipe_dish_code' => $mapping->recipeItem?->recipe?->dish_code,
                    'recipe_dish_name' => $mapping->recipeItem?->recipe?->dish_name,
                    'recipe_item_sort_order' => $mapping->recipeItem?->sort_order,
                    'stock_category' => $mapping->stockItem?->category,
                    'stock_product_name' => $mapping->stockItem?->product_name,
                    'conversion_factor' => (string) $mapping->conversion_factor,
                    'is_active' => $mapping->is_active,
                    'notes' => $mapping->notes,
                ])
                ->filter(fn (array $mapping): bool => $mapping['recipe_dish_name'] && $mapping['recipe_item_sort_order'] && $mapping['stock_category'] && $mapping['stock_product_name'])
                ->values()
                ->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info("Restaurant catalog seed exported to {$path}.");

        return self::SUCCESS;
    }
}
