<?php

namespace App\Domain\Rooms\Http\Controllers;

use App\Domain\Housekeeping\Actions\GetRoomCleaningOverview;
use App\Domain\Rooms\Actions\GetRoomsOverview;
use Inertia\Inertia;
use Inertia\Response;

class RoomsOverviewController
{
    public function __invoke(GetRoomsOverview $overview, GetRoomCleaningOverview $cleaningOverview): Response
    {
        return Inertia::render('rooms/dashboard', [
            'dashboard' => $overview->handle(),
            'cleaning' => $cleaningOverview->handle(),
        ]);
    }
}
