<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DynamicPaymentAccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $tenantUser;
    private Team $tenantTeam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->tenantUser = User::factory()->withPersonalTeam()->create();
        $this->tenantTeam = $this->tenantUser->currentTeam;
        $this->tenantUser->current_team_id = $this->tenantTeam->id;
        $this->tenantUser->save();

        app(SubscriptionService::class)->ensureDefaultPlans();
    }

    public function test_super_admin_can_view_default_payment_accounts_in_dedicated_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.payment-accounts'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/PaymentAccounts')
            ->where('settings.payment_bank_active', true)
            ->where('settings.payment_bkash_active', true)
            ->where('settings.payment_bank_name', 'City Bank Ltd')
            ->where('settings.payment_bank_account_number', '1102938475001')
            ->where('settings.payment_bank_swift', 'CIBLBDDH')
            ->where('settings.payment_bkash_number', '01712-345678')
            ->where('settings.payment_nagad_number', '01812-345678')
            ->where('settings.payment_rocket_number', '01912-345678-9')
        );
    }

    public function test_super_admin_can_update_payment_accounts_via_dedicated_endpoint(): void
    {
        $payload = [
            'payment_bank_active' => true,
            'payment_bank_name' => 'BRAC Bank PLC',
            'payment_bank_account_number' => '15012099887766',
            'payment_bank_account_name' => 'InoManage Bangladesh Ltd',
            'payment_bank_branch' => 'Banani Branch',
            'payment_bank_routing' => '060260432',
            'payment_bank_swift' => 'BRACBDDH',
            'payment_bank_type' => 'Corporate Current Account',
            'payment_bank_instructions' => 'Deposit directly or transfer via BEFTN/NPSB to our BRAC Bank account',

            'payment_bkash_active' => false,
            'payment_bkash_name' => 'InoManage Official bKash',
            'payment_bkash_number' => '01700-112233',
            'payment_bkash_counter' => 'Counter 2',
            'payment_bkash_type' => 'Merchant Payment',
            'payment_bkash_instructions' => 'Payment option via bKash App',

            'payment_nagad_active' => true,
            'payment_nagad_name' => 'InoManage Nagad Merchant',
            'payment_nagad_number' => '01800-445566',
            'payment_nagad_counter' => 'Counter 1',
            'payment_nagad_type' => 'Merchant Payment',
            'payment_nagad_instructions' => 'Select Merchant Pay on Nagad App',

            'payment_rocket_active' => true,
            'payment_rocket_name' => 'InoManage Rocket Biller',
            'payment_rocket_number' => '01900-778899-0',
            'payment_rocket_counter' => 'Biller ID 9988',
            'payment_rocket_type' => 'Biller Pay',
            'payment_rocket_instructions' => 'Pay via Rocket Biller ID 9988',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.payment-accounts.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('1', SiteSetting::get('payment_bank_active'));
        $this->assertEquals('0', SiteSetting::get('payment_bkash_active'));
        $this->assertEquals('BRAC Bank PLC', SiteSetting::get('payment_bank_name'));
        $this->assertEquals('15012099887766', SiteSetting::get('payment_bank_account_number'));
        $this->assertEquals('InoManage Bangladesh Ltd', SiteSetting::get('payment_bank_account_name'));
        $this->assertEquals('BRACBDDH', SiteSetting::get('payment_bank_swift'));
        $this->assertEquals('01700-112233', SiteSetting::get('payment_bkash_number'));
        $this->assertEquals('01800-445566', SiteSetting::get('payment_nagad_number'));
        $this->assertEquals('01900-778899-0', SiteSetting::get('payment_rocket_number'));
    }

    public function test_tenant_subscription_page_receives_dynamic_payment_accounts(): void
    {
        SiteSetting::set('payment_bank_name', 'Dutch-Bangla Bank Ltd');
        SiteSetting::set('payment_bank_account_number', '1051100998877');
        SiteSetting::set('payment_bank_account_name', 'InoManage Cloud Limited');
        SiteSetting::set('payment_bank_branch', 'Dhanmondi Branch');
        SiteSetting::set('payment_bank_swift', 'DBBLBDDH');
        SiteSetting::set('payment_bkash_number', '01799-887766');
        SiteSetting::set('payment_bkash_active', '0');

        $response = $this->actingAs($this->tenantUser)->get(route('subscription.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Subscription/Index')
            ->where('payment_accounts.bank_transfer.name', 'Dutch-Bangla Bank Ltd')
            ->where('payment_accounts.bank_transfer.number', '1051100998877')
            ->where('payment_accounts.bank_transfer.account_name', 'InoManage Cloud Limited')
            ->where('payment_accounts.bank_transfer.counter', 'Dhanmondi Branch')
            ->where('payment_accounts.bank_transfer.swift', 'DBBLBDDH')
            ->where('payment_accounts.bkash.number', '01799-887766')
            ->where('payment_accounts.bkash.is_active', false)
            ->where('payment_accounts.bank_transfer.is_active', true)
            ->has('payment_instructions')
        );
    }

    public function test_tenant_cannot_submit_payment_for_inactive_payment_method(): void
    {
        SiteSetting::set('payment_bkash_active', '0');
        $plan = \App\Models\Plan::where('slug', 'starter')->firstOrFail();

        $response = $this->actingAs($this->tenantUser)->post(route('subscription.pay'), [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'bkash',
            'sender_number' => '01711223344',
            'trx_id' => 'INACTIVEBKASH123',
        ]);

        $response->assertSessionHasErrors('payment_method');
    }
}
