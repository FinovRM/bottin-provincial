<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\VisitorIdentities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BottinLoginController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.bottin-declaration', [
            'email' => $request->string('email')->toString(),
        ]);
    }

    /**
     * Once the declaration is confirmed, the visitor is logged in with their
     * only identity, or — when they have several — as a plain member (or their
     * most senior organization), then picks a role from the menu at any time.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['confirmed' => ['accepted']]);

        $identity = VisitorIdentities::defaultForEmail($request->string('email')->toString());

        abort_unless($identity, 404);

        VisitorIdentities::login($identity);

        return redirect()->route('bottin');
    }
}
