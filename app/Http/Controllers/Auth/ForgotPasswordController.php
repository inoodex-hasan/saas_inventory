<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form (New InoManage design).
     */
    public function showRequestForm(Request $request): View
    {
        return view('auth.forgot-password', [
            'mode' => $request->query('mode', 'phone'), // 'phone' or 'email'
        ]);
    }

    /**
     * Send OTP to the user's phone for password reset.
     */
    public function sendOtp(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ], [
            'phone.required' => 'Please enter your registered mobile number.',
        ]);

        $normalizedPhone = $smsOtpService->normalizePhone($request->input('phone'));
        $user = User::where('phone', $normalizedPhone)->first();

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors(['phone' => 'No account found with this mobile number.']);
        }

        $result = $smsOtpService->sendOtp($user);

        if (! $result['success']) {
            return back()
                ->withInput()
                ->withErrors(['phone' => $result['message']]);
        }

        // Store reset state in session
        $request->session()->put('password_reset_phone', $user->phone);
        $request->session()->put('password_reset_user_id', $user->id);
        $request->session()->save();

        return redirect('/verify-reset-otp')
            ->with('status', 'Verification OTP sent to ' . $smsOtpService->maskPhone($user->phone));
    }

    /**
     * Show the phone OTP verify & new password form.
     */
    public function showResetPhoneForm(Request $request, SmsOtpService $smsOtpService): View|RedirectResponse
    {
        $phone = $request->session()->get('password_reset_phone');
        $userId = $request->session()->get('password_reset_user_id');

        if (! $phone || ! $userId) {
            return redirect()->route('password.request');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password-phone', [
            'phone' => $phone,
            'maskedPhone' => $smsOtpService->maskPhone($phone),
            'cooldown' => $smsOtpService->resendCooldownRemaining($user),
        ]);
    }

    /**
     * Verify OTP and update password.
     */
    public function resetWithPhone(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $phone = $request->session()->get('password_reset_phone');
        $userId = $request->session()->get('password_reset_user_id');

        if (! $phone || ! $userId) {
            return redirect()->route('password.request')
                ->withErrors(['phone' => 'Session expired. Please request a new OTP.']);
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ], [
            'otp.digits' => 'Please enter the 6-digit numeric verification code.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $result = $smsOtpService->verifyOtp($user, $request->string('otp')->toString());

        if (! $result['success']) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['otp' => $result['message']]);
        }

        // Update password & clear reset session
        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ])->save();

        $request->session()->forget(['password_reset_phone', 'password_reset_user_id']);

        return redirect()->route('login')->with('status', 'Your password has been reset successfully. Please sign in with your new password.');
    }

    /**
     * Resend OTP for password reset.
     */
    public function resendResetOtp(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $userId = $request->session()->get('password_reset_user_id');
        if (! $userId) {
            return redirect()->route('password.request');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('password.request');
        }

        $result = $smsOtpService->sendOtp($user);

        if (! $result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return back()->with('status', 'A new verification code has been sent to your phone.');
    }
}
