<?php

use App\Domain\Housekeeping\Http\Controllers\HousekeepingOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('housekeeping', HousekeepingOverviewController::class)->name('housekeeping.index');
});
