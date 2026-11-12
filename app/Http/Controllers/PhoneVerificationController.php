<?php

namespace App\Http\Controllers;

use App\Services\SmsOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PhoneVerificationController extends Controller
{
    public function show(Request $request, SmsOtpService $smsOtpService): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin() || $user->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        if ($user->phone && ! $user->phone_otp_expires_at) {
            $smsOtpService->sendOtp($user, true);
            $user->refresh();
        }

        return view('auth.verify-phone', [
            'phone' => $user->phone,
            'maskedPhone' => $smsOtpService->maskPhone($user->phone),
            'cooldown' => $smsOtpService->resendCooldownRemaining($user),
        ]);
    }

    public function verify(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.digits' => 'Please enter the 6-digit numeric verification code.',
        ]);

        $result = $smsOtpService->verifyOtp($user, $request->string('otp')->toString());

        if (! $result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return redirect()->intended(route('dashboard'))->with('success', $result['message']);
    }

    public function resend(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        $result = $smsOtpService->sendOtp($user);

        if (! $result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }

    public function updatePhone(Request $request, SmsOtpService $smsOtpService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        $normalizedPhone = $smsOtpService->normalizePhone((string) $request->input('phone', ''));

        Validator::make(['phone' => $normalizedPhone], [
            'phone' => [
                'required',
                'string',
                'regex:/^(8801[3-9]\d{8}|[1-9]\d{7,14})$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
        ], [
            'phone.regex' => 'Please enter a valid mobile number (e.g. 017XXXXXXXX).',
            'phone.unique' => 'This phone number is already registered with another account.',
        ])->validate();

        $user->forceFill([
            'phone' => $normalizedPhone,
            'phone_verified_at' => null,
        ])->save();

        $result = $smsOtpService->sendOtp($user, true);

        if (! $result['success']) {
            return back()->withErrors(['phone' => $result['message']]);
        }

        return back()->with('status', 'Phone number updated and a new verification code has been sent.');
    }
}

