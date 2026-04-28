<?php

namespace App\Domain\Housekeeping\Http\Controllers;

use App\Domain\Housekeeping\Actions\GetHousekeepingOverview;
use Inertia\Inertia;
use Inertia\Response;

class HousekeepingOverviewController
{
    public function __invoke(GetHousekeepingOverview $overview): Response
    {
        return Inertia::render('modules/overview', [
            'overview' => $overview->handle(),
        ]);
    }
}
