<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SmsOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhonePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders_with_new_design(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Reset your password');
        $response->assertSee('Via Mobile OTP');
        $response->assertSee('Via Email Link');
    }

    public function test_can_request_otp_for_registered_phone(): void
    {
        config(['services.bulksmsbd.driver' => 'log']);

        $user = User::factory()->create([
            'phone' => '8801712345678',
        ]);

        $response = $this->post(route('password.phone'), [
            'phone' => '01712345678',
        ]);

        $response->assertRedirect(route('password.reset.phone'));
        $this->assertEquals('8801712345678', session('password_reset_phone'));

        $user->refresh();
        $this->assertNotNull($user->phone_otp_code);
    }

    public function test_cannot_request_otp_for_unregistered_phone(): void
    {
        $response = $this->post(route('password.phone'), [
            'phone' => '01999999999',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_can_reset_password_with_valid_phone_otp(): void
    {
        $user = User::factory()->create([
            'phone' => '8801712345678',
            'phone_otp_code' => Hash::make('654321'),
            'phone_otp_expires_at' => now()->addMinutes(5),
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->withSession([
            'password_reset_phone' => '8801712345678',
            'password_reset_user_id' => $user->id,
        ])->post(route('password.update.phone'), [
            'otp' => '654321',
            'password' => 'new-secret-password-456',
            'password_confirmation' => 'new-secret-password-456',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-password-456', $user->password));
    }
}
