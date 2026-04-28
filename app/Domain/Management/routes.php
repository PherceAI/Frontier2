<?php

use App\Domain\Management\Http\Controllers\EmployeesController;
use App\Domain\Management\Http\Controllers\ManagementModuleOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('logbook', [ManagementModuleOverviewController::class, '__invoke'])
        ->defaults('module', 'logbook')
        ->name('logbook.index');

    Route::get('events', [ManagementModuleOverviewController::class, '__invoke'])
        ->defaults('module', 'events')
        ->name('events.index');

    Route::get('analytics', [ManagementModuleOverviewController::class, '__invoke'])
        ->defaults('module', 'analytics')
        ->name('analytics.index');

    Route::get('employees', [EmployeesController::class, 'index'])->name('employees.index');
    Route::patch('employees/{employee}/areas', [EmployeesController::class, 'updateAreas'])->name('employees.areas.update');
});
