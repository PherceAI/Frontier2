<?php

namespace App\Domain\Management\Http\Controllers;

use App\Domain\Management\Actions\GetManagementModuleOverview;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ManagementModuleOverviewController extends Controller
{
    public function __invoke(string $module, GetManagementModuleOverview $overview): Response
    {
        return Inertia::render('modules/overview', [
            'overview' => $overview->handle($module),
        ]);
    }
}
