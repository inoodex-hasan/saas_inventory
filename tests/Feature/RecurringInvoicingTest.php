<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\RecurringInvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RecurringInvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected Customer $customer;
    protected Warehouse $warehouse;
    protected Product $product;
    protected RecurringInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        $this->service = app(RecurringInvoiceService::class);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Acme Corporation',
            'phone' => '+8801711223344',
            'email' => 'billing@acme.com',
            'address' => 'Dhaka, Bangladesh',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Central Hub',
            'code' => 'WH-01',
            'is_active' => true,
            'is_default' => true,
        ]);

        $category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Cloud Infrastructure',
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $category->id,
            'name' => 'Dedicated VPS Hosting',
            'sku' => 'SRV-VPS-01',
            'cost_price' => 2000.00,
            'sale_price' => 3500.00,
            'is_active' => true,
        ]);
    }

    public function test_tenant_can_view_recurring_invoices_index(): void
    {
        // Seed a recurring profile
        $this->service->createProfile([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'profile_name' => 'Monthly Managed Server Hosting',
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $this->product->id,
                'item_name' => $this->product->name,
                'quantity' => 1,
                'unit_price' => 3500.00,
            ],
        ]);

        $response = $this->get(route('recurring-invoices.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('RecurringInvoices/Index')
            ->has('profiles.data', 1)
            ->where('metrics.active_subscriptions', 1)
            ->where('metrics.total_mrr', 3500)
            ->where('metrics.due_today', 1)
        );
    }

    public function test_tenant_can_create_recurring_subscription_profile(): void
    {
        $payload = [
            'profile_name' => 'Quarterly ERP Maintenance Retainer',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'frequency' => 'quarterly',
            'interval_count' => 1,
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
            'total_cycles_limit' => 4,
            'discount_amount' => 500.00,
            'tax_amount' => 250.00,
            'payment_terms_days' => 15,
            'auto_send_email' => true,
            'notes' => 'Covers SLA support 24/7',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'item_name' => 'VPS Maintenance',
                    'description' => 'Dedicated core maintenance',
                    'quantity' => 2,
                    'unit_price' => 5000.00,
                ],
            ],
        ];

        $response = $this->post(route('recurring-invoices.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('recurring_invoices', [
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'profile_name' => 'Quarterly ERP Maintenance Retainer',
            'frequency' => 'quarterly',
            'subtotal' => 10000.00,
            'discount_amount' => 500.00,
            'tax_amount' => 250.00,
            'total_amount' => 9750.00,
            'total_cycles_limit' => 4,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('recurring_invoice_items', [
            'item_name' => 'VPS Maintenance',
            'quantity' => 2,
            'unit_price' => 5000.00,
            'subtotal' => 10000.00,
        ]);
    }

    public function test_cross_tenant_validation_prevents_foreign_customer_assignment(): void
    {
        // Other team's customer
        $otherTeam = Team::factory()->create();
        $otherCustomer = Customer::create([
            'team_id' => $otherTeam->id,
            'name' => 'Foreign Corp',
            'phone' => '+880199999999',
            'is_active' => true,
        ]);

        $payload = [
            'profile_name' => 'Unauthorized Subscription',
            'customer_id' => $otherCustomer->id, // Belongs to other team
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
            'items' => [
                [
                    'item_name' => 'Cloud Hosting',
                    'quantity' => 1,
                    'unit_price' => 1000.00,
                ],
            ],
        ];

        $response = $this->post(route('recurring-invoices.store'), $payload);
        $response->assertSessionHasErrors('customer_id');
    }

    public function test_immediate_invoice_generation_creates_sales_order_and_advances_date(): void
    {
        $startDate = Carbon::parse('2026-05-01');
        $profile = $this->service->createProfile([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'profile_name' => 'Monthly SaaS Subscription',
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => $startDate->toDateString(),
            'next_issue_date' => $startDate->toDateString(),
        ], [
            [
                'product_id' => $this->product->id,
                'item_name' => 'Standard SaaS License',
                'quantity' => 3,
                'unit_price' => 1000.00,
            ],
        ]);

        $this->assertEquals(0, $profile->cycles_completed);
        $this->assertEquals('2026-05-01', $profile->next_issue_date->toDateString());

        // Trigger manual invoice generation
        $response = $this->post(route('recurring-invoices.generate-now', $profile->id));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Verify SalesOrder was created
        $order = SalesOrder::where('recurring_invoice_id', $profile->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertEquals(3000.00, (float) $order->total_amount);
        $this->assertEquals($this->customer->id, $order->customer_id);

        // Verify order items
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 1000.00,
            'subtotal' => 3000.00,
        ]);

        // Verify profile updated
        $profile->refresh();
        $this->assertEquals(1, $profile->cycles_completed);
        $this->assertEquals('2026-06-01', $profile->next_issue_date->toDateString());
        $this->assertNotNull($profile->last_issued_at);
    }

    public function test_lifecycle_status_transitions_pause_resume_cancel(): void
    {
        $profile = $this->service->createProfile([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'profile_name' => 'Lifecycle Test Profile',
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
        ], [
            [
                'item_name' => 'Consulting Hours',
                'quantity' => 5,
                'unit_price' => 1500.00,
            ],
        ]);

        $this->assertEquals('active', $profile->status);

        // Pause
        $this->post(route('recurring-invoices.pause', $profile->id));
        $this->assertEquals('paused', $profile->fresh()->status);

        // Resume
        $this->post(route('recurring-invoices.resume', $profile->id));
        $this->assertEquals('active', $profile->fresh()->status);

        // Cancel
        $this->post(route('recurring-invoices.cancel', $profile->id));
        $this->assertEquals('cancelled', $profile->fresh()->status);
    }

    public function test_cycle_limit_completion(): void
    {
        $profile = $this->service->createProfile([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'profile_name' => 'One-Time 2-Cycle Contract',
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
            'total_cycles_limit' => 2,
        ], [
            [
                'item_name' => 'Monthly Retainer',
                'quantity' => 1,
                'unit_price' => 2000.00,
            ],
        ]);

        // Cycle 1
        $this->service->generateInvoice($profile);
        $profile->refresh();
        $this->assertEquals('active', $profile->status);
        $this->assertEquals(1, $profile->cycles_completed);

        // Cycle 2 (reaches limit)
        $this->service->generateInvoice($profile);
        $profile->refresh();
        $this->assertEquals('completed', $profile->status);
        $this->assertEquals(2, $profile->cycles_completed);
    }

    public function test_artisan_process_recurring_command(): void
    {
        // Profile due yesterday
        $profile = $this->service->createProfile([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'profile_name' => 'Automated Scheduled Profile',
            'frequency' => 'weekly',
            'interval_count' => 1,
            'start_date' => now()->subDays(7)->toDateString(),
            'next_issue_date' => now()->subDay()->toDateString(), // Due!
        ], [
            [
                'item_name' => 'Weekly SLA Cleaning',
                'quantity' => 1,
                'unit_price' => 750.00,
            ],
        ]);

        $exitCode = Artisan::call('invoices:process-recurring', [
            '--team' => $this->team->id,
        ]);

        $this->assertEquals(0, $exitCode);

        // Confirm sales order was generated
        $this->assertDatabaseHas('sales_orders', [
            'team_id' => $this->team->id,
            'recurring_invoice_id' => $profile->id,
            'total_amount' => 750.00,
            'status' => 'confirmed',
        ]);

        $profile->refresh();
        $this->assertEquals(1, $profile->cycles_completed);
    }

    public function test_tenant_isolation_prevents_viewing_or_managing_other_tenants_profile(): void
    {
        $otherTeam = Team::factory()->create();
        $otherUser = User::factory()->create(['current_team_id' => $otherTeam->id]);

        $foreignProfile = $this->service->createProfile([
            'team_id' => $otherTeam->id,
            'customer_id' => Customer::create([
                'team_id' => $otherTeam->id,
                'name' => 'Foreign Client',
                'is_active' => true,
            ])->id,
            'profile_name' => 'Foreign Confidential Profile',
            'frequency' => 'monthly',
            'interval_count' => 1,
            'start_date' => now()->toDateString(),
            'next_issue_date' => now()->toDateString(),
        ], [
            [
                'item_name' => 'Private Service',
                'quantity' => 1,
                'unit_price' => 9999.00,
            ],
        ]);

        // Attempt show
        $this->get(route('recurring-invoices.show', $foreignProfile->id))->assertStatus(404);

        // Attempt edit
        $this->get(route('recurring-invoices.edit', $foreignProfile->id))->assertStatus(404);

        // Attempt generate now
        $this->post(route('recurring-invoices.generate-now', $foreignProfile->id))->assertStatus(404);

        // Attempt pause
        $this->post(route('recurring-invoices.pause', $foreignProfile->id))->assertStatus(404);

        // Attempt delete
        $this->delete(route('recurring-invoices.destroy', $foreignProfile->id))->assertStatus(404);
    }
}
