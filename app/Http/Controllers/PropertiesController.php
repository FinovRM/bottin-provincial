<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function __invoke(): View
    {
        $organization = Auth::user()->load('children', 'memberRoles.member');

        return view('dashboard.properties', [
            'organization' => $organization,
        ]);
    }
}
