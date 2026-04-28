<?php

use App\Domain\Reservations\Http\Controllers\ReservationsOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('reservations', ReservationsOverviewController::class)->name('reservations.index');
});
