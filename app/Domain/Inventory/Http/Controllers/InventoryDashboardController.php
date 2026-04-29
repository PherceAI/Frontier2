<?php

namespace App\Domain\Inventory\Http\Controllers;

use App\Domain\Inventory\Actions\GetInventoryDashboard;
use Inertia\Inertia;
use Inertia\Response;

class InventoryDashboardController
{
    public function __invoke(GetInventoryDashboard $dashboard): Response
    {
        return Inertia::render('inventory/dashboard', [
            'dashboard' => $dashboard->handle(),
        ]);
    }
}
