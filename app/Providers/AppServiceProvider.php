<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event) {
            $user = $event->user;
            if (!$user) {
                return;
            }

            // Super admins in platform mode don't need tenant expiration notices
            if ($user->isSuperAdmin() && !$user->current_team_id) {
                return;
            }

            $team = $user->currentTeam;
            if (!$team) {
                return;
            }

            $sub = $team->subscription;
            if (!$sub) {
                return;
            }

            $isExpired = $sub->isExpired() || in_array($sub->status, ['expired', 'past_due', 'canceled']);
            $hasExpiryDate = ($sub->status === 'trialing' && $sub->trial_ends_at) || ($sub->status === 'active' && $sub->ends_at);

            if (!$isExpired && !$hasExpiryDate) {
                return;
            }

            $days = $sub->days_remaining;
            $isExpiringSoon = $hasExpiryDate && $days <= 7;

            if ($isExpiringSoon || $isExpired) {
                session()->flash('subscription_login_alert', true);
                $planName = $sub->plan?->name ?? 'Workspace';
                if ($isExpired) {
                    session()->flash('warning', "Your {$planName} subscription has expired. Please renew to keep your workspace active.");
                } else {
                    $dayWord = $days === 1 ? 'day' : 'days';
                    session()->flash('warning', "Attention: Your {$planName} subscription expires in {$days} {$dayWord}. Please renew to prevent service interruption.");
                }
            }
        });
    }
}
