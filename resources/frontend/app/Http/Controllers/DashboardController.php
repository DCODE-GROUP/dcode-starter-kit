<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->user()) {
            return to_route('login');
        }

        return Inertia::render('Dashboard');
    }
}
