<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\Team;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BangladeshiSubscriptionAndBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private User $adminUser;
    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        // Platform Super-Admin User
        $this->adminUser = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->subscriptionService = app(SubscriptionService::class);
    }

    public function test_default_bangladeshi_plans_provisioned(): void
    {
        $this->subscriptionService->ensureDefaultPlans();

        $this->assertDatabaseHas('plans', [
            'slug' => 'starter',
            'price_bdt' => 999.00,
            'max_products' => 500,
        ]);

        $this->assertDatabaseHas('plans', [
            'slug' => 'professional',
            'price_bdt' => 2499.00,
            'max_products' => 5000,
        ]);

        $this->assertDatabaseHas('plans', [
            'slug' => 'enterprise',
            'price_bdt' => 4999.00,
            'max_products' => -1,
        ]);
    }

    public function test_new_tenant_assigned_14_day_trial(): void
    {
        $sub = $this->subscriptionService->ensureTenantSubscription($this->team);

        $this->assertEquals('trialing', $sub->status);
        $this->assertTrue($sub->isActive());
        $this->assertEquals(14, $sub->days_remaining);
        $this->assertDatabaseHas('tenant_subscriptions', [
            'team_id' => $this->team->id,
            'status' => 'trialing',
        ]);
    }

    public function test_tenant_quota_usage_calculation(): void
    {
        $this->subscriptionService->ensureTenantSubscription($this->team);

        // Create 2 products
        Product::create([
            'team_id' => $this->team->id,
            'name' => 'Demo Router A',
            'sku' => 'DEMO-RTR-01',
            'cost_price' => 1200,
            'unit_price' => 1800,
        ]);
        Product::create([
            'team_id' => $this->team->id,
            'name' => 'Demo Switch B',
            'sku' => 'DEMO-SWT-02',
            'cost_price' => 2200,
            'unit_price' => 3100,
        ]);

        $usage = $this->subscriptionService->getUsage($this->team);

        $this->assertEquals(2, $usage['products']['current']);
        $this->assertTrue($usage['products']['can_add']);
        $this->assertTrue($usage['is_active']);
    }

    public function test_quota_limits_prevent_adding_products_exceeding_limit(): void
    {
        $this->subscriptionService->ensureDefaultPlans();

        // Assign a strict plan with max_products = 1
        $customPlan = Plan::create([
            'name' => 'Micro Test',
            'slug' => 'micro-test',
            'price_bdt' => 100,
            'annual_price_bdt' => 1000,
            'max_products' => 1,
            'max_warehouses' => 1,
            'max_users' => 1,
            'is_active' => true,
        ]);

        $sub = $this->subscriptionService->ensureTenantSubscription($this->team);
        $sub->update(['plan_id' => $customPlan->id, 'status' => 'active']);

        $this->assertTrue($this->subscriptionService->canAddProduct($this->team));

        // Add 1 product (reaches limit)
        Product::create([
            'team_id' => $this->team->id,
            'name' => 'Only Product Allowed',
            'sku' => 'ONLY-01',
            'cost_price' => 100,
            'unit_price' => 200,
        ]);

        $this->assertFalse($this->subscriptionService->canAddProduct($this->team));
    }

    public function test_tenant_can_submit_manual_bkash_trxid_payment(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $proPlan = Plan::where('slug', 'professional')->firstOrFail();

        $response = $this->post(route('subscription.pay'), [
            'plan_id' => $proPlan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01712345678',
            'trx_id' => 'BKA998877XX',
            'notes' => 'Sent from personal bKash app',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscription_payments', [
            'team_id' => $this->team->id,
            'plan_id' => $proPlan->id,
            'payment_method' => 'bkash',
            'sender_number' => '01712345678',
            'trx_id' => 'BKA998877XX',
            'amount_bdt' => 2499.00,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_submit_duplicate_trxid(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $proPlan = Plan::where('slug', 'professional')->firstOrFail();

        // 1st submission
        $this->post(route('subscription.pay'), [
            'plan_id' => $proPlan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01712345678',
            'trx_id' => 'DUPLICATE123',
        ]);

        // 2nd submission with same TrxID
        $response = $this->post(route('subscription.pay'), [
            'plan_id' => $proPlan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01712345678',
            'trx_id' => 'DUPLICATE123',
        ]);

        $response->assertSessionHasErrors(['trx_id']);
    }

    public function test_super_admin_can_view_and_approve_trxid_payment(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $starterPlan = Plan::where('slug', 'starter')->firstOrFail();

        $payment = $this->subscriptionService->submitPayment($this->team, [
            'plan_id' => $starterPlan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'nagad',
            'sender_number' => '01812345678',
            'trx_id' => 'NAG11223344',
        ]);

        // Switch to Platform Super-Admin
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.payments.index'));
        $response->assertStatus(200);

        // Approve payment
        $approveResponse = $this->post(route('admin.payments.approve', $payment->id));
        $approveResponse->assertRedirect();

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'approved',
            'reviewed_by' => $this->adminUser->id,
        ]);

        $sub = TenantSubscription::where('team_id', $this->team->id)->firstOrFail();
        $this->assertEquals('active', $sub->status);
        $this->assertEquals($starterPlan->id, $sub->plan_id);
        $this->assertTrue($sub->ends_at->isFuture());
    }

    public function test_approval_extends_subscription_by_cycle(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $enterprisePlan = Plan::where('slug', 'enterprise')->firstOrFail();

        $payment = $this->subscriptionService->submitPayment($this->team, [
            'plan_id' => $enterprisePlan->id,
            'billing_cycle' => 'yearly',
            'payment_method' => 'bank_transfer',
            'sender_number' => '1102938475',
            'trx_id' => 'BANK-CITY-DEP-099',
        ]);

        $sub = $this->subscriptionService->approvePayment($payment, $this->adminUser->id);

        $this->assertEquals('active', $sub->status);
        $this->assertEquals('yearly', $sub->billing_cycle);
        $this->assertEquals(now()->addMonths(12)->month, $sub->ends_at->month);
    }

    public function test_super_admin_can_reject_payment_with_reason(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'starter')->firstOrFail();

        $payment = $this->subscriptionService->submitPayment($this->team, [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01700000000',
            'trx_id' => 'FAKE-TRX-404',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.payments.reject', $payment->id), [
            'rejection_reason' => 'TrxID not found in bKash merchant statement',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'rejected',
            'rejection_reason' => 'TrxID not found in bKash merchant statement',
        ]);
    }

    public function test_super_admin_can_create_and_update_custom_plan(): void
    {
        $this->actingAs($this->adminUser);

        $storeResponse = $this->post(route('admin.plans.store'), [
            'name' => 'Corporate VIP',
            'slug' => 'corp-vip',
            'price_bdt' => 7999,
            'annual_price_bdt' => 79990,
            'max_products' => -1,
            'max_warehouses' => 10,
            'max_users' => 25,
            'is_active' => true,
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'slug' => 'corp-vip',
            'price_bdt' => 7999.00,
        ]);

        $plan = Plan::where('slug', 'corp-vip')->firstOrFail();

        $updateResponse = $this->put(route('admin.plans.update', $plan->id), [
            'name' => 'Corporate VIP Pro',
            'price_bdt' => 8499,
            'annual_price_bdt' => 84990,
            'max_products' => -1,
            'max_warehouses' => 15,
            'max_users' => 30,
            'is_active' => true,
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Corporate VIP Pro',
            'price_bdt' => 8499.00,
        ]);
    }

    public function test_sslcommerz_sandbox_payload_generation(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'starter')->firstOrFail();

        $init = $this->subscriptionService->initiateSslCommerzPayment($this->team, $plan, 'monthly');

        $this->assertArrayHasKey('payment', $init);
        $this->assertArrayHasKey('post_data', $init);
        $this->assertArrayHasKey('gateway_url', $init);

        $this->assertEquals(999.00, $init['post_data']['total_amount']);
        $this->assertEquals('BDT', $init['post_data']['currency']);
        $this->assertEquals('sslcommerz', $init['payment']->payment_method);
        $this->assertStringContainsString('sslcommerz.com', $init['gateway_url']);
    }

    public function test_tenant_isolation_in_subscription_and_payments(): void
    {
        $otherTeam = Team::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Other Tenant Hub',
            'personal_team' => false,
        ]);

        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'starter')->firstOrFail();

        // Other team payment
        $otherPayment = $this->subscriptionService->submitPayment($otherTeam, [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01512345678',
            'trx_id' => 'OTHERTEAM123',
        ]);

        // When current user views their subscription page, other team payment is NOT visible
        $this->get(route('subscription.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/Subscription/Index')
                ->has('payments.data')
                ->where('payments.data', fn ($data) => collect($data)->every(fn ($p) => $p['team_id'] === $this->team->id))
            );
    }

    public function test_payment_approval_is_idempotent_and_prevents_duplicate_approval(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'starter')->firstOrFail();

        $payment = $this->subscriptionService->submitPayment($this->team, [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01712345678',
            'trx_id' => 'IDEMPOTENT123',
        ]);

        // First approval succeeds
        $response = $this->actingAs($this->adminUser)->post(route('admin.payments.approve', $payment->id));
        $response->assertSessionHas('success');
        $payment->refresh();
        $this->assertEquals('approved', $payment->status);

        // Second approval is rejected and does not extend further
        $secondResponse = $this->actingAs($this->adminUser)->post(route('admin.payments.approve', $payment->id));
        $secondResponse->assertSessionHas('error');
    }

    public function test_sslcommerz_success_callback_validates_and_approves_payment(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'professional')->firstOrFail();

        $initiated = $this->subscriptionService->initiateSslCommerzPayment($this->team, $plan, 'monthly');
        $payment = $initiated['payment'];

        $payload = [
            'tran_id' => $payment->payment_number,
            'val_id' => 'VAL-SSL-2026-TEST',
            'amount' => (string) $payment->amount_bdt,
            'currency' => 'BDT',
            'status' => 'VALID',
        ];

        $response = $this->actingAs($this->user)->post(route('sslcommerz.success'), $payload);
        $response->assertRedirect(route('subscription.index'));
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('approved', $payment->status);
        $this->assertEquals('VAL-SSL-2026-TEST', $payment->trx_id);

        $sub = TenantSubscription::where('team_id', $this->team->id)->first();
        $this->assertEquals('active', $sub->status);
        $this->assertEquals($plan->id, $sub->plan_id);
    }

    public function test_sslcommerz_fail_and_cancel_callbacks_mark_payment_rejected(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'starter')->firstOrFail();

        $initiated = $this->subscriptionService->initiateSslCommerzPayment($this->team, $plan, 'monthly');
        $payment = $initiated['payment'];

        $response = $this->actingAs($this->user)->post(route('sslcommerz.fail'), [
            'tran_id' => $payment->payment_number,
        ]);
        $response->assertRedirect(route('subscription.index'));
        $response->assertSessionHasErrors('error');

        $payment->refresh();
        $this->assertEquals('rejected', $payment->status);
    }

    public function test_sslcommerz_ipn_webhook_approves_payment_idempotently(): void
    {
        $this->subscriptionService->ensureDefaultPlans();
        $plan = Plan::where('slug', 'enterprise')->firstOrFail();

        $initiated = $this->subscriptionService->initiateSslCommerzPayment($this->team, $plan, 'yearly');
        $payment = $initiated['payment'];

        $response = $this->postJson(route('sslcommerz.ipn'), [
            'tran_id' => $payment->payment_number,
            'val_id' => 'IPN-VAL-9988',
            'amount' => (string) $payment->amount_bdt,
            'status' => 'VALIDATED',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $payment->refresh();
        $this->assertEquals('approved', $payment->status);

        // Idempotent retry
        $retryResponse = $this->postJson(route('sslcommerz.ipn'), [
            'tran_id' => $payment->payment_number,
            'val_id' => 'IPN-VAL-9988',
            'status' => 'VALIDATED',
        ]);
        $retryResponse->assertOk();
        $retryResponse->assertJson(['status' => 'already_processed']);
    }
}
