<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SuperAdminControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Team $clientTeam;

    protected function setUp(): void
    {
        parent::setUp();

        // Create platform super administrator
        $this->superAdmin = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => true,
        ]);

        // Create normal renting client
        $this->regularUser = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => false,
        ]);
        $this->clientTeam = $this->regularUser->currentTeam;
    }

    public function test_non_super_admin_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics')
            ->has('tenants')
        );
    }

    public function test_super_admin_can_suspend_and_reactivate_client_tenant(): void
    {
        $this->assertEquals('active', $this->clientTeam->status);

        // Suspend the tenant
        $suspendResponse = $this->actingAs($this->superAdmin)->post(route('admin.tenants.toggle-status', $this->clientTeam->id));
        $suspendResponse->assertSessionHas('success');

        $this->clientTeam->refresh();
        $this->assertEquals('suspended', $this->clientTeam->status);
        $this->assertTrue($this->clientTeam->isSuspended());

        // Reactivate the tenant
        $reactivateResponse = $this->actingAs($this->superAdmin)->post(route('admin.tenants.toggle-status', $this->clientTeam->id));
        $reactivateResponse->assertSessionHas('success');

        $this->clientTeam->refresh();
        $this->assertEquals('active', $this->clientTeam->status);
        $this->assertFalse($this->clientTeam->isSuspended());
    }

    public function test_suspended_tenant_users_are_redirected_to_suspension_page(): void
    {
        // Suspend client team
        $this->clientTeam->update(['status' => 'suspended']);

        // Regular user attempts to access dashboard
        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));

        $response->assertRedirect(route('tenant.suspended'));

        // Suspended page itself should be accessible
        $suspendedResponse = $this->actingAs($this->regularUser)->get(route('tenant.suspended'));
        $suspendedResponse->assertStatus(200);
        $suspendedResponse->assertSee('Organization Workspace Suspended');
    }

    public function test_super_admin_is_never_blocked_by_tenant_suspension(): void
    {
        // Suspend client team
        $this->clientTeam->update(['status' => 'suspended']);

        // Super Admin switches to suspended team and visits dashboard
        $this->superAdmin->forceFill(['current_team_id' => $this->clientTeam->id])->save();

        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_super_admin_can_switch_tenant_context(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.tenants.switch', $this->clientTeam->id));
        $response->assertRedirect(route('dashboard'));

        $this->superAdmin->refresh();
        $this->assertEquals($this->clientTeam->id, $this->superAdmin->current_team_id);
    }

    public function test_super_admin_personal_team_is_excluded_from_client_list(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenants.data.0.name', $this->clientTeam->name)
            ->where('tenants.total', 1)
        );
    }

    public function test_super_admin_can_provision_new_tenant(): void
    {
        $plan = \App\Models\Plan::create([
            'name' => 'Corporate Enterprise Test',
            'slug' => 'corp-test-' . uniqid(),
            'description' => 'Test Plan',
            'price_bdt' => 1000,
            'annual_price_bdt' => 10000,
            'max_products' => 500,
            'max_warehouses' => 5,
            'max_users' => 10,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Nexus Retail Ltd',
            'legal_name' => 'Nexus Retail Global Inc',
            'owner_name' => 'Kamal Hossain',
            'owner_email' => 'kamal@nexusretail.com',
            'owner_password' => 'secretPassword123!',
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'validity_days' => 45,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'phone' => '01711223344',
            'city' => 'Chittagong',
            'country' => 'Bangladesh',
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.tenants.store'), $payload);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('teams', [
            'name' => 'Nexus Retail Ltd',
            'legal_name' => 'Nexus Retail Global Inc',
            'currency_code' => 'BDT',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'kamal@nexusretail.com',
            'name' => 'Kamal Hossain',
        ]);

        $createdTeam = Team::where('name', 'Nexus Retail Ltd')->first();
        $this->assertNotNull($createdTeam);
        $this->assertDatabaseHas('tenant_subscriptions', [
            'team_id' => $createdTeam->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_update_tenant_profile(): void
    {
        $payload = [
            'name' => 'Updated Apex Superstore',
            'legal_name' => 'Apex Retail Enterprises Ltd',
            'email' => 'support@apexsuperstore.com',
            'phone' => '01999888777',
            'tax_id' => 'TAX-998822',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'address_line1' => 'Plot 45, Gulshan Avenue',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.tenants.update', $this->clientTeam->id), $payload);
        $response->assertSessionHas('success');

        $this->clientTeam->refresh();
        $this->assertEquals('Updated Apex Superstore', $this->clientTeam->name);
        $this->assertEquals('TAX-998822', $this->clientTeam->tax_id);
    }

    public function test_super_admin_can_override_subscription_and_extend_days(): void
    {
        $newPlan = \App\Models\Plan::create([
            'name' => 'Enterprise Corporate Plan',
            'slug' => 'ent-corp-' . uniqid(),
            'description' => 'Enterprise corporate subscription',
            'price_bdt' => 5000,
            'annual_price_bdt' => 50000,
            'max_products' => -1,
            'max_warehouses' => -1,
            'max_users' => -1,
            'is_active' => true,
        ]);

        $payload = [
            'plan_id' => $newPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'extension_days' => 90,
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.tenants.subscription.update', $this->clientTeam->id), $payload);
        $response->assertSessionHas('success');

        $this->clientTeam->refresh();
        $sub = $this->clientTeam->subscription;
        $this->assertNotNull($sub);
        $this->assertEquals($newPlan->id, $sub->plan_id);
        $this->assertEquals('yearly', $sub->billing_cycle);
        $this->assertTrue($sub->ends_at->isFuture());
    }

    public function test_super_admin_cannot_delete_super_admin_team(): void
    {
        $superAdminTeam = $this->superAdmin->currentTeam;
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.tenants.destroy', $superAdminTeam->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('teams', ['id' => $superAdminTeam->id]);
    }

    public function test_super_admin_can_delete_client_tenant(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.tenants.destroy', $this->clientTeam->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('teams', ['id' => $this->clientTeam->id]);
    }
}

