<?php

use App\Domain\Auth\Http\Controllers\SecurityOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('security', SecurityOverviewController::class)->name('security.index');
});
