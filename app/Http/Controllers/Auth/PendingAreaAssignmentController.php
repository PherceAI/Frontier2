<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PendingAreaAssignmentController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('auth/pending-assignment');
    }
}
