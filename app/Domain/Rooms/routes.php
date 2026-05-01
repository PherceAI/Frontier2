<?php

use App\Domain\Rooms\Http\Controllers\RoomCleaningManagementController;
use App\Domain\Rooms\Http\Controllers\RoomsOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('rooms', RoomsOverviewController::class)->name('rooms.index');
    Route::patch('rooms/cleaning/settings', [RoomCleaningManagementController::class, 'updateSettings'])->name('rooms.cleaning.settings.update');
    Route::post('rooms/cleaning/assignments', [RoomCleaningManagementController::class, 'generate'])->name('rooms.cleaning.assignments.store');
    Route::patch('rooms/cleaning/tasks/{task}', [RoomCleaningManagementController::class, 'updateTask'])->name('rooms.cleaning.tasks.update');
});
