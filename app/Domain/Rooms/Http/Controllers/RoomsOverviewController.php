<?php

namespace App\Domain\Rooms\Http\Controllers;

use App\Domain\Rooms\Actions\GetRoomsOverview;
use Inertia\Inertia;
use Inertia\Response;

class RoomsOverviewController
{
    public function __invoke(GetRoomsOverview $overview): Response
    {
        return Inertia::render('modules/overview', [
            'overview' => $overview->handle(),
        ]);
    }
}
