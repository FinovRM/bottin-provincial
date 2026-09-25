<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A responsable only works on their organization's properties: the bottin
 * pages (members, organizations, profile) are for members. The home page
 * then sends them to their properties, or lets them pick an organization.
 */
class KeepResponsablesOnProperties
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('bottin');
        }

        return $next($request);
    }
}
