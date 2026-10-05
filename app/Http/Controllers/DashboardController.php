<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The landing page after signing in. The role-specific dashboards from the
     * designs replace this in the following phases.
     */
    public function __invoke(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user()->load('department'),
        ]);
    }
}
