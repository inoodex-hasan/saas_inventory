<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($user->currentTeam) {
            // Super admins can always view any tenant
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            if ($user->currentTeam->isSuspended()) {
                if (!$request->routeIs('tenant.suspended', 'logout')) {
                    return redirect()->route('tenant.suspended');
                }
            }
        }

        return $next($request);
    }
}
