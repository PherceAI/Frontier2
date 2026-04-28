<?php

use App\Domain\Restaurant\Http\Controllers\RestaurantDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('restaurant', RestaurantDashboardController::class)->name('restaurant.dashboard');
});
