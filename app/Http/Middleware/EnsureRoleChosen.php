<?php

namespace App\Http\Middleware;

use App\Support\VisitorIdentities;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages tied to one organization need a chosen role: a visitor with several
 * roles who hasn't picked one is sent home, where the role menu is.
 */
class EnsureRoleChosen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (VisitorIdentities::hasNoChosenRole()) {
            return redirect()->route('bottin');
        }

        return $next($request);
    }
}
