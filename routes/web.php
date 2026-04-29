<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require app_path('Domain/Rooms/routes.php');
require app_path('Domain/Reservations/routes.php');
require app_path('Domain/Housekeeping/routes.php');
require app_path('Domain/Billing/routes.php');
require app_path('Domain/Auth/routes.php');
require app_path('Domain/Management/routes.php');
require app_path('Domain/Restaurant/routes.php');
require app_path('Domain/Inventory/routes.php');
require app_path('Domain/EmployeePortal/routes.php');
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
