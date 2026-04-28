<?php

use App\Domain\Rooms\Http\Controllers\RoomsOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('rooms', RoomsOverviewController::class)->name('rooms.index');
});
