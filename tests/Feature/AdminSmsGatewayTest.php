<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_access_sms_gateway_hub()
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.sms-gateway'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SmsGateway')
            ->has('balance')
            ->has('gatewayConfig')
            ->has('recentUsers')
        );
    }

    public function test_regular_user_cannot_access_sms_gateway()
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.sms-gateway'));
        $response->assertForbidden();
    }

    public function test_super_admin_can_refresh_balance()
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.sms-gateway.refresh'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_super_admin_can_send_test_sms()
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.sms-gateway.test'), [
            'phone' => '01812345678',
            'message' => 'Hello test message',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
