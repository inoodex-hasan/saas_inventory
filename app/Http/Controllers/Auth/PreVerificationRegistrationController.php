<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\SmsOtpService;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Jetstream\Jetstream;

class PreVerificationRegistrationController extends Controller
{
    protected const CACHE_PREFIX = 'pending_reg:';
    protected const EXPIRY_MINUTES = 10;
    protected const RESEND_COOLDOWN_SECONDS = 60;
    protected const MAX_ATTEMPTS = 5;

    /**
     * Show the main registration form.
     */
    public function showRegistrationForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $selectedPlan = null;
        if ($request->filled('plan')) {
            $selectedPlan = \App\Models\Plan::where('slug', (string) $request->query('plan'))
                ->where('is_active', true)
                ->first();
        }

        return view('auth.register', [
            'siteSettings' => class_exists(\App\Models\SiteSetting::class) ? \App\Models\SiteSetting::getAll() : [],
            'selectedPlan' => $selectedPlan,
        ]);
    }

    /**
     * Process initial registration form, store temporarily in cache, and dispatch OTP.
     * ZERO rows are written to database at this step.
     */
    public function submitRegistration(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $normalizedPhone = $smsOtpService->normalizePhone((string) $request->input('phone', ''));

        $validationData = array_merge($request->all(), [
            'phone' => $normalizedPhone,
        ]);

        Validator::make($validationData, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^(8801[3-9]\d{8}|[1-9]\d{7,14})$/', 'unique:users,phone'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'phone.regex' => 'Please enter a valid mobile number (e.g. 017XXXXXXXX).',
            'phone.unique' => 'This phone number is already registered with another account.',
            'email.unique' => 'This email address is already registered with another account.',
        ])->validate();

        $token = Str::random(40);
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $payload = [
            'name' => trim((string) $request->input('name')),
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => $normalizedPhone,
            'password' => Hash::make((string) $request->input('password')),
            'plan_slug' => $request->filled('plan') ? (string) $request->input('plan') : null,
            'otp' => Hash::make($otp),
            'otp_raw' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES)->timestamp,
            'resend_available_at' => now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->timestamp,
        ];

        Cache::put(self::CACHE_PREFIX . $token, $payload, now()->addMinutes(self::EXPIRY_MINUTES));
        session(['pending_reg_token' => $token]);

        $smsResult = $smsOtpService->sendOtpToNumber($normalizedPhone, $otp);

        if (!$smsResult['success']) {
            // Keep going so user can see verify page and resend if needed
        }

        return redirect()->route('register.verify-phone')->with('status', 'Verification code sent to your phone via SMS.');
    }

    /**
     * Show the phone verification screen before creating database records.
     */
    public function showVerifyForm(Request $request, SmsOtpService $smsOtpService): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $token = session('pending_reg_token') ?? $request->query('token');

        if (!$token || !Cache::has(self::CACHE_PREFIX . $token)) {
            return redirect()->route('register')->withErrors([
                'phone' => 'Your registration session has expired. Please fill out the form again.',
            ]);
        }

        $data = Cache::get(self::CACHE_PREFIX . $token);
        $cooldown = max(0, ($data['resend_available_at'] ?? 0) - now()->timestamp);

        return view('auth.register-verify-phone', [
            'token' => $token,
            'phone' => $data['phone'],
            'maskedPhone' => $smsOtpService->maskPhone($data['phone']),
            'cooldown' => $cooldown,
            'siteSettings' => class_exists(\App\Models\SiteSetting::class) ? \App\Models\SiteSetting::getAll() : [],
        ]);
    }

    /**
     * Verify the 6-digit OTP code and create User, Team, and Workspace ONLY upon successful verification.
     */
    public function verifyAndCreateAccount(Request $request, SmsOtpService $smsOtpService, SubscriptionService $subscriptionService): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $token = session('pending_reg_token') ?? $request->input('token');

        if (!$token || !Cache::has(self::CACHE_PREFIX . $token)) {
            return redirect()->route('register')->withErrors([
                'phone' => 'Verification session expired. Please sign up again.',
            ]);
        }

        $data = Cache::get(self::CACHE_PREFIX . $token);

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.digits' => 'Please enter the 6-digit numeric verification code.',
        ]);

        $submittedOtp = (string) $request->input('otp');

        // Check max attempts
        if (($data['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            Cache::forget(self::CACHE_PREFIX . $token);
            session()->forget('pending_reg_token');
            return redirect()->route('register')->withErrors([
                'phone' => 'Too many failed verification attempts. Please register again.',
            ]);
        }

        $isMatch = Hash::check($submittedOtp, $data['otp']) || ($submittedOtp === ($data['otp_raw'] ?? ''));

        if (!$isMatch) {
            $data['attempts'] = ($data['attempts'] ?? 0) + 1;
            $remaining = self::MAX_ATTEMPTS - $data['attempts'];
            Cache::put(self::CACHE_PREFIX . $token, $data, now()->addMinutes(self::EXPIRY_MINUTES));

            return back()->withErrors([
                'otp' => "Invalid verification code. {$remaining} attempts remaining.",
            ]);
        }

        // Final safety check: ensure email or phone was not registered while user was verifying
        if (User::where('email', $data['email'])->orWhere('phone', $data['phone'])->exists()) {
            Cache::forget(self::CACHE_PREFIX . $token);
            session()->forget('pending_reg_token');
            return redirect()->route('login')->withErrors([
                'email' => 'An account with this email or phone number already exists. Please log in.',
            ]);
        }

        // All checks passed! Now, and ONLY now, create the user, team, and workspace in database.
        $user = DB::transaction(function () use ($data, $subscriptionService) {
            $user = User::forceCreate([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]);

            $team = $user->ownedTeams()->save(Team::forceCreate([
                'user_id' => $user->id,
                'name' => explode(' ', $user->name, 2)[0] . "'s Team",
                'personal_team' => true,
                'status' => 'active',
            ]));

            $user->switchTeam($team);

            RolesAndPermissionsSeeder::seedRolesForTeam($team->id);
            setPermissionsTeamId($team->id);
            $user->assignRole('owner');

            $subscriptionService->ensureTenantSubscription($team);

            if (!empty($data['plan_slug'])) {
                $chosenPlan = \App\Models\Plan::where('slug', $data['plan_slug'])
                    ->where('is_active', true)
                    ->first();
                if ($chosenPlan) {
                    $team->update(['subscription_plan' => $chosenPlan->slug]);
                    if ($team->subscription) {
                        $team->subscription->update([
                            'plan_id' => $chosenPlan->id,
                            'trial_ends_at' => now()->addDays(max(1, (int) ($chosenPlan->trial_days ?? 3))),
                        ]);
                    }
                }
            }

            return $user;
        });

        // Clean up cache & session
        Cache::forget(self::CACHE_PREFIX . $token);
        session()->forget('pending_reg_token');

        // Log the user in
        Auth::login($user);

        return redirect()->intended(route('dashboard'))->with(
            'success',
            'Welcome! Your mobile number has been verified and your workspace is ready.'
        );
    }

    /**
     * Resend verification OTP code to the phone number.
     */
    public function resendOtp(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $token = session('pending_reg_token') ?? $request->input('token');

        if (!$token || !Cache::has(self::CACHE_PREFIX . $token)) {
            return redirect()->route('register')->withErrors([
                'phone' => 'Verification session expired. Please register again.',
            ]);
        }

        $data = Cache::get(self::CACHE_PREFIX . $token);
        $now = now()->timestamp;

        if (($data['resend_available_at'] ?? 0) > $now) {
            $remaining = ($data['resend_available_at'] - $now);
            return back()->withErrors([
                'otp' => "Please wait {$remaining} seconds before requesting a new OTP.",
            ]);
        }

        $newOtp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $data['otp'] = Hash::make($newOtp);
        $data['otp_raw'] = $newOtp;
        $data['resend_available_at'] = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->timestamp;

        Cache::put(self::CACHE_PREFIX . $token, $data, now()->addMinutes(self::EXPIRY_MINUTES));

        $smsResult = $smsOtpService->sendOtpToNumber($data['phone'], $newOtp);

        return back()->with(
            'status',
            $smsResult['success'] ? 'A new verification code has been sent to your mobile phone.' : 'Failed to send SMS. Please check your phone number.'
        );
    }
}
