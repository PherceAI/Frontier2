<?php

use App\Domain\Billing\Http\Controllers\BillingOverviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'area.assigned', 'management.access'])->group(function () {
    Route::get('billing', BillingOverviewController::class)->name('billing.index');
});
