<?php

namespace App\Domain\Restaurant\Actions;

use App\Domain\Restaurant\Models\ContificoDocument;
use App\Domain\Restaurant\Models\KitchenInventoryProductMapping;
use App\Domain\Restaurant\Models\StandardRecipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalculateKitchenTheoreticalConsumption
{
    /**
     * @return array{consumption: array<int, float>, pendingMappings: array<int, array<string, string>>}
     */
    public function handle(Carbon $operatingDate): array
    {
        $recipes = StandardRecipe::query()
            ->with('items')
            ->where('is_active', true)
            ->get();
        $recipesByCode = $recipes->filter(fn (StandardRecipe $recipe): bool => filled($recipe->dish_code))->keyBy('dish_code');
        $recipesByName = $recipes->keyBy(fn (StandardRecipe $recipe): string => NormalizeKitchenInventoryText::handle($recipe->dish_name));
        $mappings = KitchenInventoryProductMapping::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('restaurant_standard_recipe_item_id');

        $consumption = [];
        $pending = [];

        $this->sales($operatingDate)
            ->flatMap(fn (ContificoDocument $document): array => $document->detalles ?? [])
            ->each(function (array $detail) use ($recipesByCode, $recipesByName, $mappings, &$consumption, &$pending): void {
                $recipe = $this->recipeForDetail($detail, $recipesByCode, $recipesByName);

                if (! $recipe) {
                    return;
                }

                $soldQuantity = (float) ($detail['cantidad'] ?? 1);

                foreach ($recipe->items as $recipeItem) {
                    $mapping = $mappings->get($recipeItem->id);

                    if (! $mapping) {
                        $pending[$recipeItem->id] = [
                            'recipe' => $recipe->dish_name,
                            'ingredient' => $recipeItem->inventory_product_name ?: ('Ingrediente '.$recipeItem->id),
                        ];

                        continue;
                    }

                    $stockItemId = (int) $mapping->kitchen_daily_stock_item_id;
                    $consumption[$stockItemId] = ($consumption[$stockItemId] ?? 0)
                        + ($soldQuantity * (float) $recipeItem->quantity_used * (float) $mapping->conversion_factor);
                }
            });

        return [
            'consumption' => collect($consumption)->map(fn (float $value): float => round($value, 4))->all(),
            'pendingMappings' => array_values($pending),
        ];
    }

    /**
     * @return Collection<int, ContificoDocument>
     */
    private function sales(Carbon $operatingDate): Collection
    {
        return ContificoDocument::query()
            ->whereDate('fecha_emision', $operatingDate->toDateString())
            ->where('tipo_registro', 'CLI')
            ->where('tipo_documento', 'FAC')
            ->where('anulado', false)
            ->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', '!=', 'A');
            })
            ->get();
    }

    /**
     * @param  Collection<string, StandardRecipe>  $recipesByCode
     * @param  Collection<string, StandardRecipe>  $recipesByName
     */
    private function recipeForDetail(array $detail, Collection $recipesByCode, Collection $recipesByName): ?StandardRecipe
    {
        $productId = (string) ($detail['producto_id'] ?? $detail['product_id'] ?? '');

        if ($productId !== '' && $recipesByCode->has($productId)) {
            return $recipesByCode->get($productId);
        }

        $name = NormalizeKitchenInventoryText::handle(
            $detail['producto_nombre'] ?? $detail['nombre'] ?? $detail['nombre_manual'] ?? $detail['descripcion'] ?? ''
        );

        return $name !== '' ? $recipesByName->get($name) : null;
    }
}
