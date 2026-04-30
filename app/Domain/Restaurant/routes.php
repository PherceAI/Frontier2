<?php

use App\Domain\Restaurant\Http\Controllers\KitchenDailyStockController;
use App\Domain\Restaurant\Http\Controllers\KitchenInventoryAnalysisController;
use App\Domain\Restaurant\Http\Controllers\KitchenInventoryMappingController;
use App\Domain\Restaurant\Http\Controllers\RestaurantDashboardController;
use App\Domain\Restaurant\Http\Controllers\StandardRecipesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('restaurant', RestaurantDashboardController::class)->name('restaurant.dashboard');
    Route::get('restaurant/analysis', KitchenInventoryAnalysisController::class)->name('restaurant.analysis');
    Route::patch('kitchen-inventory-mappings/{recipeItem}', [KitchenInventoryMappingController::class, 'update'])->name('kitchen-inventory-mappings.update');
    Route::get('recipes', [StandardRecipesController::class, 'index'])->name('recipes.index');
    Route::patch('recipes/{recipe}', [StandardRecipesController::class, 'updateRecipe'])->name('recipes.update');
    Route::patch('recipe-items/{item}', [StandardRecipesController::class, 'updateItem'])->name('recipe-items.update');
    Route::get('kitchen-stock', [KitchenDailyStockController::class, 'index'])->name('kitchen-stock.index');
    Route::patch('kitchen-stock/{item}', [KitchenDailyStockController::class, 'update'])->name('kitchen-stock.update');
});
