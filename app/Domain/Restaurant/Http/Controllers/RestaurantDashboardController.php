<?php

namespace App\Domain\Restaurant\Http\Controllers;

use App\Domain\Restaurant\Actions\GetRestaurantDashboard;
use App\Domain\Restaurant\Actions\RestaurantPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantDashboardController
{
    public function __invoke(Request $request, GetRestaurantDashboard $dashboard): Response
    {
        $period = RestaurantPeriod::normalize($request->query('period'));

        return Inertia::render('restaurant/dashboard', [
            'dashboard' => $dashboard->handle($period),
        ]);
    }
}
