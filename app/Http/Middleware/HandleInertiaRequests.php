<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'is_impersonating' => $user->isSuperAdmin() && $user->current_team_id !== null && !$user->ownedTeams->contains('id', $user->current_team_id),
                    'current_team_id' => $user->current_team_id,
                    'all_teams' => $user->allTeams()->map(fn ($team) => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'personal_team' => $team->personal_team,
                    ]),
                ] : null,
                'team' => ($user && $user->currentTeam) ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                    'status' => $user->currentTeam->status ?? 'active',
                    'currency_symbol' => $user->currentTeam->currency_symbol ?? '৳',
                    'currency_code' => $user->currentTeam->currency_code ?? 'BDT',
                    'company_phone' => $user->currentTeam->company_phone,
                    'company_email' => $user->currentTeam->company_email,
                    'company_address' => $user->currentTeam->company_address,
                    'logo_path' => $user->currentTeam->logo_path,
                ] : null,
                'roles' => ($user && $user->currentTeam) ? $user->roles()->pluck('name')->toArray() : [],
                'permissions' => ($user && $user->currentTeam) ? $user->getAllPermissions()->pluck('name')->toArray() : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'subscription_notice' => function () use ($user) {
                if (!$user || !$user->currentTeam) {
                    return null;
                }

                // Super Admins in platform mode don't need tenant expiration notices for themselves
                if ($user->isSuperAdmin() && !$user->current_team_id) {
                    return null;
                }

                $team = $user->currentTeam;
                if (!$team) {
                    return null;
                }

                $sub = $team->subscription;
                if (!$sub) {
                    return null;
                }

                $isExpired = $sub->isExpired() || in_array($sub->status, ['expired', 'past_due', 'canceled']);
                $hasExpiryDate = ($sub->status === 'trialing' && $sub->trial_ends_at) || ($sub->status === 'active' && $sub->ends_at);

                if (!$isExpired && !$hasExpiryDate) {
                    return null;
                }

                $days = $sub->days_remaining;
                $isExpiringSoon = $hasExpiryDate && $days <= 7;

                if (!$isExpiringSoon && !$isExpired) {
                    return null;
                }

                return [
                    'days_remaining' => $days,
                    'is_expired' => $isExpired,
                    'plan_name' => $sub->plan?->name ?? 'Workspace Plan',
                    'ends_at' => $sub->status === 'trialing' && $sub->trial_ends_at
                        ? $sub->trial_ends_at->format('M d, Y')
                        : ($sub->ends_at ? $sub->ends_at->format('M d, Y') : 'N/A'),
                    'status' => $sub->status,
                ];
            },
            'subscription_login_alert' => fn () => (bool) $request->session()->get('subscription_login_alert'),
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
