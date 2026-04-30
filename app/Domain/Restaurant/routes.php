<?php

use App\Domain\Restaurant\Http\Controllers\RestaurantDashboardController;
use App\Domain\Restaurant\Http\Controllers\StandardRecipesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('restaurant', RestaurantDashboardController::class)->name('restaurant.dashboard');
    Route::get('recipes', [StandardRecipesController::class, 'index'])->name('recipes.index');
    Route::patch('recipes/{recipe}', [StandardRecipesController::class, 'updateRecipe'])->name('recipes.update');
    Route::patch('recipe-items/{item}', [StandardRecipesController::class, 'updateItem'])->name('recipe-items.update');
});
