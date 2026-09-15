<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function store(Organization $organization): RedirectResponse
    {
        Auth::guard('web')->login($organization);

        return redirect()->route('dashboard');
    }

    public function storeMember(Member $member): RedirectResponse
    {
        Auth::guard('member')->login($member);

        return redirect()->route('member.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('bottin');
    }

    public function destroyMember(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('bottin');
    }
}
