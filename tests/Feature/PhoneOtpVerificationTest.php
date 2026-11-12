<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class PhoneOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::firstOrCreate(
            ['slug' => 'monthly'],
            [
                'name' => 'Monthly Pro',
                'price' => 999,
                'billing_cycle' => 'monthly',
                'trial_days' => 3,
                'max_users' => 5,
                'max_warehouses' => 3,
                'max_products' => 1000,
                'is_active' => true,
            ]
        );
    }

    public function test_registration_requires_valid_unique_phone_and_dispatches_bulksmsbd_otp(): void
    {
        config()->set('services.bulksmsbd.driver', 'bulksmsbd');
        config()->set('services.bulksmsbd.api_key', 'test-api-key');
        config()->set('services.bulksmsbd.sender_id', '8809612345678');
        config()->set('services.bulksmsbd.endpoint', 'https://bulksmsbd.net/api/smsapi');

        Http::fake([
            'https://bulksmsbd.net/api/smsapi*' => Http::response([
                'response_code' => '202',
                'message_id' => '998877',
                'message' => 'SMS Submitted Successfully',
            ], 200),
        ]);

        // Missing phone fails
        $this->post('/register', [
            'name' => 'Trial User',
            'email' => 'trial@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ])->assertSessionHasErrors('phone');

        // Valid BD phone succeeds and normalizes to 8801XXXXXXXXX
        $this->post('/register', [
            'name' => 'Trial User',
            'email' => 'trial@example.com',
            'phone' => '01712345678',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'trial@example.com')->firstOrFail();
        $this->assertSame('8801712345678', $user->phone);
        $this->assertNull($user->phone_verified_at);
        $this->assertNotNull($user->phone_otp_code);
        $this->assertNotNull($user->phone_otp_expires_at);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bulksmsbd.net/api/smsapi'
                && $request['api_key'] === 'test-api-key'
                && $request['senderid'] === '8809612345678'
                && $request['number'] === '8801712345678'
                && str_contains($request['message'], 'verification code is');
        });
    }

    public function test_unverified_trial_user_is_redirected_from_dashboard_to_verify_phone(): void
    {
        $user = User::factory()->withPersonalTeam()->unverifiedPhone()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('phone.verify.notice'));

        $this->actingAs($user)
            ->get('/verify-phone')
            ->assertOk()
            ->assertSee('Verify your phone number');
    }

    public function test_user_can_verify_otp_and_access_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->unverifiedPhone()->create([
            'phone' => '8801711223344',
            'phone_otp_code' => Hash::make('654321'),
            'phone_otp_expires_at' => now()->addMinutes(5),
            'phone_otp_attempts' => 0,
        ]);

        // Wrong OTP fails and increments attempts
        $this->actingAs($user)
            ->post('/verify-phone', ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertSame(1, $user->fresh()->phone_otp_attempts);
        $this->assertNull($user->fresh()->phone_verified_at);

        // Correct OTP verifies and redirects to dashboard
        $this->actingAs($user)
            ->post('/verify-phone', ['otp' => '654321'])
            ->assertRedirect(route('dashboard'));

        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->phone_verified_at);
        $this->assertNull($freshUser->phone_otp_code);

        $this->actingAs($freshUser)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->withPersonalTeam()->unverifiedPhone()->create([
            'phone' => '8801711223355',
            'phone_otp_code' => Hash::make('123456'),
            'phone_otp_expires_at' => now()->subMinute(),
            'phone_otp_attempts' => 0,
        ]);

        $this->actingAs($user)
            ->post('/verify-phone', ['otp' => '123456'])
            ->assertSessionHasErrors('otp');

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_resend_cooldown_and_phone_update_flow(): void
    {
        config()->set('services.bulksmsbd.driver', 'log');

        $user = User::factory()->withPersonalTeam()->unverifiedPhone()->create([
            'phone' => '8801711223366',
            'phone_otp_code' => Hash::make('111111'),
            'phone_otp_expires_at' => now()->addMinutes(SmsOtpService::OTP_EXPIRY_MINUTES),
            'phone_otp_attempts' => 0,
        ]);

        // Immediate resend is blocked by 60s cooldown
        $this->actingAs($user)
            ->post('/verify-phone/resend')
            ->assertSessionHasErrors('otp');

        // Updating phone number bypasses cooldown and sends fresh OTP
        $this->actingAs($user)
            ->put('/verify-phone/number', ['phone' => '01811223377'])
            ->assertSessionHas('status');

        $this->assertSame('8801811223377', $user->fresh()->phone);
    }
}

