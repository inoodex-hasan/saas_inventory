<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Ensure current_team_id is initialized if user has teams
            if (!$user->current_team_id && $user->allTeams()->isNotEmpty()) {
                $user->switchTeam($user->allTeams()->first());
            }

            // Sync Spatie Permissions team context
            if ($user->current_team_id && function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($user->current_team_id);
            }
        }

        return $next($request);
    }
}
