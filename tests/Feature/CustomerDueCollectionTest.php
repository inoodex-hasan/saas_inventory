<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CustomerDueCollectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customerWithDue;
    private Customer $customerPaid;
    private SalesOrder $dueOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Warehouse',
            'code' => 'MW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'LED Monitor 27"',
            'sku' => 'MON-27-01',
            'cost_price' => 12000,
            'unit_price' => 18000,
            'is_active' => true,
        ]);

        $this->customerWithDue = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Rafiqul Islam',
            'phone' => '01711223344',
            'address' => 'Mirpur 10, Dhaka',
        ]);

        $this->customerPaid = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Farhana Akter',
            'phone' => '01811998877',
            'address' => 'Uttara Sector 3, Dhaka',
        ]);

        // Create an order with due for Customer 1
        $this->dueOrder = SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customerWithDue->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'SO-20261012-0010',
            'status' => 'confirmed',
            'payment_status' => 'partially_paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 18000,
            'total_amount' => 18000,
            'paid_amount' => 6000,
            'due_amount' => 12000,
        ]);

        // Create a fully paid order for Customer 2
        SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customerPaid->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'SO-20261012-0011',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 18000,
            'total_amount' => 18000,
            'paid_amount' => 18000,
            'due_amount' => 0,
        ]);
    }

    public function test_user_can_access_customer_due_ledger_page(): void
    {
        $response = $this->get(route('dues.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dues/Index')
            ->has('customers', 1)
            ->where('customers.0.name', 'Rafiqul Islam')
            ->where('customers.0.total_due', 12000)
        );
    }

    public function test_user_can_filter_and_search_dues(): void
    {
        $responseFiltered = $this->get(route('dues.index', ['filter' => 'all']));
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dues/Index')
            ->has('customers', 2)
        );

        $responseSearch = $this->get(route('dues.index', ['search' => 'Rafiqul']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dues/Index')
            ->has('customers', 1)
            ->where('customers.0.name', 'Rafiqul Islam')
        );
    }

    public function test_user_can_collect_partial_due_payment(): void
    {
        $response = $this->post(route('dues.pay'), [
            'order_id' => $this->dueOrder->id,
            'amount' => 5000.0,
            'payment_method' => 'mobile_banking',
            'notes' => 'bKash TrxID: 9X29A100',
        ]);

        $response->assertSessionHasNoErrors();

        $this->dueOrder->refresh();
        $this->assertEquals(11000.00, (float) $this->dueOrder->paid_amount);
        $this->assertEquals(7000.00, (float) $this->dueOrder->due_amount);
        $this->assertEquals('partially_paid', $this->dueOrder->payment_status);
    }

    public function test_user_can_fully_settle_due_balance(): void
    {
        $response = $this->post(route('dues.pay'), [
            'order_id' => $this->dueOrder->id,
            'amount' => 12000.0,
        ]);

        $response->assertSessionHasNoErrors();

        $this->dueOrder->refresh();
        $this->assertEquals(18000.00, (float) $this->dueOrder->paid_amount);
        $this->assertEquals(0.00, (float) $this->dueOrder->due_amount);
        $this->assertEquals('paid', $this->dueOrder->payment_status);
    }

    public function test_cannot_collect_more_than_outstanding_due(): void
    {
        $order = SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customerWithDue->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'SO-20261012-0099',
            'status' => 'confirmed',
            'payment_status' => 'partially_paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 18000,
            'total_amount' => 18000,
            'paid_amount' => 6000,
            'due_amount' => 12000,
        ]);

        try {
            $this->withoutExceptionHandling()->post(route('dues.pay'), [
                'order_id' => $order->id,
                'amount' => 15000.0,
            ]);
        } catch (\Throwable $e) {
            $this->assertTrue(
                $e instanceof \Illuminate\Validation\ValidationException || $e instanceof \InvalidArgumentException
            );
            $this->assertStringContainsString('exceed', strtolower($e->getMessage()));
        }

        $order->refresh();
        $this->assertEquals(12000.00, (float) $order->due_amount);
    }
}
