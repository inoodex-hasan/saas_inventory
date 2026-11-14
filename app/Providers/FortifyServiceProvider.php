<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\LoginResponse {
                public function toResponse($request)
                {
                    $user = $request->user();
                    if ($user && $user->isSuperAdmin()) {
                        $request->session()->forget('url.intended');
                        $request->session()->forget('impersonated_tenant_id');
                        $user->forceFill(['current_team_id' => null])->save();
                        return redirect()->route('admin.dashboard');
                    }
                    return redirect()->intended(config('fortify.home'));
                }
            };
        });

        $this->app->singleton(\Laravel\Fortify\Contracts\TwoFactorLoginResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\TwoFactorLoginResponse {
                public function toResponse($request)
                {
                    $user = $request->user();
                    if ($user && $user->isSuperAdmin()) {
                        $request->session()->forget('url.intended');
                        $request->session()->forget('impersonated_tenant_id');
                        $user->forceFill(['current_team_id' => null])->save();
                        return redirect()->route('admin.dashboard');
                    }
                    return redirect()->intended(config('fortify.home'));
                }
            };
        });

        $this->app->singleton(\Laravel\Fortify\Contracts\LogoutResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\LogoutResponse {
                public function toResponse($request)
                {
                    return \Inertia\Inertia::location(route('login'));
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Support authentication via either Email or Mobile Phone Number
        Fortify::authenticateUsing(function (Request $request) {
            $loginInput = trim((string) $request->input('email'));
            $password = (string) $request->input('password');

            if ($loginInput === '' || $password === '') {
                return null;
            }

            // Check if input is an email or phone number
            if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', Str::lower($loginInput))->first();
            } else {
                $normalized = app(SmsOtpService::class)->normalizePhone($loginInput);
                $user = User::where('phone', $normalized)
                    ->orWhere('phone', $loginInput)
                    ->first();
            }

            if ($user && Hash::check($password, $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
