<?php

namespace App\Domain\Reservations\Http\Controllers;

use App\Domain\Reservations\Actions\GetReservationsOverview;
use Inertia\Inertia;
use Inertia\Response;

class ReservationsOverviewController
{
    public function __invoke(GetReservationsOverview $overview): Response
    {
        return Inertia::render('modules/overview', [
            'overview' => $overview->handle(),
        ]);
    }
}
