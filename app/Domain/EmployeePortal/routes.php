<?php

use App\Domain\EmployeePortal\Http\Controllers\EmployeeHomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned'])->group(function () {
    Route::get('operativo', EmployeeHomeController::class)->name('employee.home');
    Route::post('operativo/forms/{form}/entries', [EmployeeHomeController::class, 'submitForm'])->name('employee.forms.entries.store');
    Route::patch('operativo/tasks/{task}/complete', [EmployeeHomeController::class, 'completeTask'])->name('employee.tasks.complete');
    Route::patch('operativo/tasks/{task}/validate', [EmployeeHomeController::class, 'validateTask'])->name('employee.tasks.validate');
    Route::patch('operativo/room-cleaning/{task}/start', [EmployeeHomeController::class, 'startRoomCleaning'])->name('employee.room-cleaning.start');
    Route::patch('operativo/room-cleaning/{task}/complete', [EmployeeHomeController::class, 'completeRoomCleaning'])->name('employee.room-cleaning.complete');
    Route::post('operativo/room-cleaning/{task}/notes', [EmployeeHomeController::class, 'addRoomCleaningNote'])->name('employee.room-cleaning.notes.store');
    Route::post('operativo/kitchen/shortages', [EmployeeHomeController::class, 'reportSupplyShortage'])->name('employee.kitchen.shortages.store');
    Route::post('operativo/kitchen-closings/{closing}/count', [EmployeeHomeController::class, 'submitKitchenClosingCount'])->name('employee.kitchen-closings.count');
    Route::post('operativo/kitchen-closings/{closing}/replenishment', [EmployeeHomeController::class, 'confirmKitchenClosingReplenishment'])->name('employee.kitchen-closings.replenishment');
});
