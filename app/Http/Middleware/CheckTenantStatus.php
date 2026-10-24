<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
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

        $team = $user->currentTeam;
        if ($team) {
            // Super admins can always view and manage any tenant workspace
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // 1. Administrative Manual Suspension Check
            if ($team->isSuspended()) {
                if (!$request->routeIs('tenant.suspended', 'logout')) {
                    return redirect()->route('tenant.suspended');
                }
                return $next($request);
            }

            // 2. Strict Paywall Gate for Expired Subscriptions
            $subscription = app(SubscriptionService::class)->ensureTenantSubscription($team);

            if ($subscription->isExpired()) {
                $whitelistedRoutes = [
                    'subscription.index',
                    'settings.subscription',
                    'subscription.pay',
                    'tenant.suspended',
                    'logout',
                    'current-team.update',
                    'teams.create',
                    'teams.show',
                ];

                if (!$request->routeIs($whitelistedRoutes) && !$request->is('sslcommerz/*')) {
                    if ($request->wantsJson() || $request->is('api/*')) {
                        return response()->json([
                            'message' => 'Your workspace subscription has expired. Please renew your plan to continue using your workspace.',
                            'subscription_expired' => true,
                            'redirect' => route('subscription.index'),
                        ], 403);
                    }

                    return redirect()->route('subscription.index')->with(
                        'error',
                        'Your workspace subscription has expired. Please choose a plan or renew your subscription to continue.'
                    );
                }
            }
        }

        return $next($request);
    }
}
