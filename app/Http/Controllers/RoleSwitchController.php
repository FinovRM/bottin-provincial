<?php

namespace App\Http\Controllers;

use App\Support\VisitorIdentities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleSwitchController extends Controller
{
    /**
     * Switch to another identity of the same courriel, from the role menu.
     */
    public function store(Request $request): RedirectResponse
    {
        $identity = VisitorIdentities::choicesForEmail(VisitorIdentities::currentEmail())
            ->firstWhere('key', $request->string('identity')->toString());

        abort_unless($identity, 404);

        VisitorIdentities::login($identity);

        return redirect()->route('bottin');
    }
}
