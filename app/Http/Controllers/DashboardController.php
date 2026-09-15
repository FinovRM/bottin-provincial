<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $organization = Auth::user()->load('children');

        return view('dashboard.index', [
            'organization' => $organization,
        ]);
    }
}
