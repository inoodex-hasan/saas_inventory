<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_registration_submits_and_does_not_create_user_in_database_yet(): void
    {
        $response = $this->post('/register', [
            'name' => 'Pending Merchant',
            'email' => 'merchant@example.com',
            'phone' => '01712345678',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        // Must NOT create user in database yet!
        $this->assertEquals(0, User::where('email', 'merchant@example.com')->count());
        $this->assertGuest();

        $response->assertRedirect(route('register.verify-phone'));
        $this->assertTrue(session()->has('pending_reg_token'));

        $token = session('pending_reg_token');
        $this->assertTrue(Cache::has("pending_reg:{$token}"));
    }

    public function test_cannot_create_account_with_invalid_otp(): void
    {
        $this->post('/register', [
            'name' => 'Pending Merchant',
            'email' => 'merchant@example.com',
            'phone' => '01712345678',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response = $this->post(route('register.verify-phone.submit'), [
            'otp' => '000000',
        ]);

        // Still ZERO users in database!
        $this->assertEquals(0, User::where('email', 'merchant@example.com')->count());
        $this->assertGuest();
        $response->assertSessionHasErrors('otp');
    }

    public function test_user_and_tenant_created_only_upon_valid_otp(): void
    {
        $this->post('/register', [
            'name' => 'Verified Merchant',
            'email' => 'verified@example.com',
            'phone' => '01799887766',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $token = session('pending_reg_token');
        $cachedData = Cache::get("pending_reg:{$token}");
        $validOtp = $cachedData['otp_raw'];

        $response = $this->post(route('register.verify-phone.submit'), [
            'otp' => $validOtp,
        ]);

        // NOW User & Team exist in database!
        $this->assertEquals(1, User::where('email', 'verified@example.com')->count());
        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNotNull($user->current_team_id);
        $this->assertEquals("Verified's Team", $user->currentTeam->name);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }
}
