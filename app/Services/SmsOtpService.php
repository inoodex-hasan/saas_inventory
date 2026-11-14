<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsOtpService
{
    public const OTP_EXPIRY_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_VERIFY_ATTEMPTS = 5;

    /**
     * Normalize phone numbers to international digits without '+' (e.g. 01712345678 -> 8801712345678).
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            return '88'.$digits;
        }

        if (str_starts_with($digits, '88001') && strlen($digits) === 14) {
            return substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Seconds remaining before another OTP can be sent, or 0 if ready.
     */
    public function resendCooldownRemaining(User $user): int
    {
        if (! $user->phone_otp_expires_at) {
            return 0;
        }

        $sentAt = $user->phone_otp_expires_at->copy()->subMinutes(self::OTP_EXPIRY_MINUTES);
        $elapsed = $sentAt->diffInSeconds(now(), false);

        if ($elapsed < 0 || $elapsed >= self::RESEND_COOLDOWN_SECONDS) {
            return 0;
        }

        return self::RESEND_COOLDOWN_SECONDS - (int) $elapsed;
    }

    /**
     * Generate and dispatch a 6-digit OTP via BulkSMSBD (or log driver in local/dev).
     *
     * @return array{success: bool, message: string, cooldown?: int}
     */
    public function sendOtp(User $user, bool $ignoreCooldown = false): array
    {
        if (empty($user->phone)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid phone number first.',
            ];
        }

        if (! $ignoreCooldown) {
            $remaining = $this->resendCooldownRemaining($user);
            if ($remaining > 0) {
                return [
                    'success' => false,
                    'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                    'cooldown' => $remaining,
                ];
            }
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'phone_otp_code' => Hash::make($otp),
            'phone_otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'phone_otp_attempts' => 0,
        ])->save();

        $appName = config('app.name', 'Inoodex Inventory');
        $message = "Your InoManage verification code is {$otp}. Valid for ".self::OTP_EXPIRY_MINUTES.' minutes.';

        return $this->dispatchSms($user->phone, $message, $otp);
    }

    /**
     * Verify a submitted OTP code for the user.
     *
     * @return array{success: bool, message: string}
     */
    public function verifyOtp(User $user, string $code): array
    {
        if (! $user->phone_otp_code || ! $user->phone_otp_expires_at) {
            return [
                'success' => false,
                'message' => 'No active OTP found. Please request a new verification code.',
            ];
        }

        if (now()->greaterThan($user->phone_otp_expires_at)) {
            return [
                'success' => false,
                'message' => 'This OTP has expired. Please request a new verification code.',
            ];
        }

        if ($user->phone_otp_attempts >= self::MAX_VERIFY_ATTEMPTS) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ];
        }

        if (! Hash::check(trim($code), $user->phone_otp_code)) {
            $user->forceFill([
                'phone_otp_attempts' => $user->phone_otp_attempts + 1,
            ])->save();

            return [
                'success' => false,
                'message' => 'The verification code you entered is incorrect.',
            ];
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'phone_otp_code' => null,
            'phone_otp_expires_at' => null,
            'phone_otp_attempts' => 0,
        ])->save();

        return [
            'success' => true,
            'message' => 'Phone number verified successfully.',
        ];
    }

    /**
     * Mask phone number for UI display.
     */
    public function maskPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $len = strlen($phone);
        if ($len <= 6) {
            return $phone;
        }

        return substr($phone, 0, 5).str_repeat('*', $len - 9 > 0 ? $len - 9 : 4).substr($phone, -4);
    }

    /**
     * Send SMS through BulkSMSBD HTTP API or fallback to log driver.
     *
     * @return array{success: bool, message: string}
     */
    protected function dispatchSms(string $number, string $message, string $otp): array
    {
        $driver = config('services.bulksmsbd.driver', 'bulksmsbd');
        $apiKey = config('services.bulksmsbd.api_key');
        $senderId = config('services.bulksmsbd.sender_id');
        $endpoint = config('services.bulksmsbd.endpoint', 'https://bulksmsbd.net/api/smsapi');

        if ($driver === 'log' || empty($apiKey) || empty($senderId)) {
            Log::info('SMS OTP dispatched (log driver)', [
                'number' => $number,
                'otp' => $otp,
                'message' => $message,
            ]);

            return [
                'success' => true,
                'message' => 'Verification code sent to your phone.',
            ];
        }

        try {
            $response = Http::timeout(10)->asForm()->post($endpoint, [
                'api_key' => $apiKey,
                'type' => 'text',
                'number' => $number,
                'senderid' => $senderId,
                'message' => $message,
            ]);

            $payload = $response->json();
            $code = (string) ($payload['response_code'] ?? '');

            if ($response->successful() && $code === '202') {
                return [
                    'success' => true,
                    'message' => 'Verification code sent to your phone.',
                ];
            }

            Log::error('BulkSMSBD OTP dispatch failed', [
                'number' => $number,
                'status' => $response->status(),
                'response' => $payload ?? $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to send SMS right now. Please try again in a moment.',
            ];
        } catch (\Throwable $e) {
            Log::error('BulkSMSBD OTP exception', [
                'number' => $number,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS gateway connection failed. Please try again shortly.',
            ];
        }
    }
}

