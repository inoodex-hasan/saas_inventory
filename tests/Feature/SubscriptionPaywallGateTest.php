<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaywallGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SubscriptionService::class)->ensureDefaultPlans();
    }

    public function test_active_tenant_can_freely_access_dashboard_and_workspace(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $plan = Plan::where('slug', 'starter')->first();
        TenantSubscription::create([
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_expired_tenant_is_redirected_to_subscription_paywall(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $plan = Plan::where('slug', 'starter')->first();
        TenantSubscription::create([
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(), // Expired yesterday!
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('subscription.index'));
        $response->assertSessionHas('error');
    }

    public function test_expired_tenant_can_still_access_subscription_portal_and_payment(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $plan = Plan::where('slug', 'starter')->first();
        TenantSubscription::create([
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        // Allowed to visit subscription billing portal
        $response = $this->actingAs($user)->get(route('subscription.index'));
        $response->assertStatus(200);

        // Allowed to submit renewal payment
        $payResponse = $this->actingAs($user)->post(route('subscription.pay'), [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01711223344',
            'trx_id' => 'TRX-RENEWAL-9988',
        ]);

        $payResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subscription_payments', [
            'team_id' => $team->id,
            'trx_id' => 'TRX-RENEWAL-9988',
        ]);
    }

    public function test_expired_tenant_receives_403_json_with_redirect_for_api_or_ajax_requests(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $plan = Plan::where('slug', 'starter')->first();
        TenantSubscription::create([
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard'));

        $response->assertStatus(403);
        $response->assertJson([
            'subscription_expired' => true,
            'redirect' => route('subscription.index'),
        ]);
    }

    public function test_super_admin_bypasses_expired_tenant_paywall(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'is_super_admin' => true,
        ]);
        $user->is_super_admin = true;
        $user->save();

        $clientUser = User::factory()->withPersonalTeam()->create();
        $clientTeam = $clientUser->currentTeam;

        $plan = Plan::where('slug', 'starter')->first();
        TenantSubscription::create([
            'team_id' => $clientTeam->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        // Super Admin switches into expired client team context
        $user->current_team_id = $clientTeam->id;
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    }
}
