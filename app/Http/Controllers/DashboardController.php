<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var Organization $authOrganization */
        $authOrganization = Auth::user();

        return view('dashboard.hub', [
            'identity' => $authOrganization->identity(),
        ]);
    }
}
