<?php

use App\Domain\EmployeePortal\Http\Controllers\EmployeeHomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned'])->group(function () {
    Route::get('operativo', EmployeeHomeController::class)->name('employee.home');
    Route::patch('operativo/tasks/{task}/complete', [EmployeeHomeController::class, 'completeTask'])->name('employee.tasks.complete');
    Route::post('operativo/kitchen/shortages', [EmployeeHomeController::class, 'reportSupplyShortage'])->name('employee.kitchen.shortages.store');
});
