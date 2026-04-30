<?php

namespace App\Domain\Restaurant\Http\Controllers;

use App\Domain\Restaurant\Actions\GetKitchenInventoryAnalysis;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenInventoryAnalysisController extends Controller
{
    public function __invoke(Request $request, GetKitchenInventoryAnalysis $analysis): Response
    {
        return Inertia::render('restaurant/inventory-analysis', $analysis->handle(
            $request->string('week')->toString() ?: null,
        ));
    }
}
