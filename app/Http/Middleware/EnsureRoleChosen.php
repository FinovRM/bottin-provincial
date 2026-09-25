<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages tied to one organization (Organisations, Propriétés) need a chosen
 * role: a visitor with several roles who hasn't picked one is sent home.
 */
class EnsureRoleChosen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('member')->user()?->hasNoChosenRole()) {
            return redirect()->route('bottin');
        }

        return $next($request);
    }
}
