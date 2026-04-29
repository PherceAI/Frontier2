<?php

use App\Domain\Inventory\Http\Controllers\InventoryDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('inventory', InventoryDashboardController::class)->name('inventory.index');
});
