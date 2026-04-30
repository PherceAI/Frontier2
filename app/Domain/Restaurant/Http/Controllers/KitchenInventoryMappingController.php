<?php

namespace App\Domain\Restaurant\Http\Controllers;

use App\Domain\Restaurant\Models\KitchenInventoryProductMapping;
use App\Domain\Restaurant\Models\StandardRecipeItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KitchenInventoryMappingController extends Controller
{
    public function update(Request $request, StandardRecipeItem $recipeItem): RedirectResponse
    {
        $validated = $request->validate([
            'kitchen_daily_stock_item_id' => ['required', 'integer', 'exists:kitchen_daily_stock_items,id'],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        KitchenInventoryProductMapping::updateOrCreate(
            ['restaurant_standard_recipe_item_id' => $recipeItem->id],
            $validated,
        );

        return back()->with('status', 'kitchen-mapping-updated');
    }
}
