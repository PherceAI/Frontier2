<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Actions\GetSecurityOverview;
use Inertia\Inertia;
use Inertia\Response;

class SecurityOverviewController
{
    public function __invoke(GetSecurityOverview $overview): Response
    {
        return Inertia::render('modules/overview', [
            'overview' => $overview->handle(),
        ]);
    }
}
