<?php

namespace App\Domain\Billing\Http\Controllers;

use App\Domain\Billing\Actions\GetBillingOverview;
use Inertia\Inertia;
use Inertia\Response;

class BillingOverviewController
{
    public function __invoke(GetBillingOverview $overview): Response
    {
        return Inertia::render('modules/overview', [
            'overview' => $overview->handle(),
        ]);
    }
}
