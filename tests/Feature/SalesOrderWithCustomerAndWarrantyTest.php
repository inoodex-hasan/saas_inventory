<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderWithCustomerAndWarrantyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Distribution Hub',
            'code' => 'MDH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Dell Latitude 5420 Laptop',
            'sku' => 'LAP-DELL-5420',
            'cost_price' => 500.00,
            'unit_price' => 750.00,
            'selling_price' => 750.00,
            'warranty_days' => 365,
            'unit' => 'pcs',
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'is_active' => true,
        ]);

        // Add physical stock to warehouse
        $stockService = app(StockService::class);
        $stockService->stockIn(
            warehouseId: $this->warehouse->id,
            productId: $this->product->id,
            quantity: 20,
            reason: 'Opening stock for tests'
        );
    }

    public function test_new_customer_validation_requires_phone_and_name_and_address(): void
    {
        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'new',
            'new_customer_name' => '',
            'new_customer_phone' => '',
            'new_customer_address' => '',
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 750.00,
                ],
            ],
            'order_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors([
            'new_customer_name',
            'new_customer_phone',
            'new_customer_address',
        ]);
    }

    public function test_can_create_sales_order_with_new_customer_and_warranty_and_due_breakdown(): void
    {
        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'new',
            'new_customer_name' => 'Rafiqul Islam',
            'new_customer_phone' => '01712000000',
            'new_customer_address' => 'House 12, Road 4, Dhanmondi, Dhaka',
            'new_customer_email' => 'rafiq@example.com',
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 750.00,
                    'warranty_days' => 365,
                ],
            ],
            'discount_amount' => 50.00,
            'tax_amount' => 10.00,
            'shipping_cost' => 40.00,
            'paid_amount' => 500.00,
            'order_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();

        // Assert customer was automatically created for tenant
        $this->assertDatabaseHas('customers', [
            'team_id' => $this->team->id,
            'name' => 'Rafiqul Islam',
            'phone' => '01712000000',
            'address' => 'House 12, Road 4, Dhanmondi, Dhaka',
            'email' => 'rafiq@example.com',
        ]);

        $customer = Customer::where('phone', '01712000000')->first();
        $this->assertNotNull($customer);

        // Assert sales order created with correct totals and due amount
        $this->assertDatabaseHas('sales_orders', [
            'team_id' => $this->team->id,
            'customer_id' => $customer->id,
            'subtotal' => 1500.00,
            'discount_amount' => 50.00,
            'tax_amount' => 10.00,
            'shipping_cost' => 40.00,
            'total_amount' => 1500.00,
            'paid_amount' => 500.00,
            'due_amount' => 1000.00,
            'payment_status' => 'partially_paid',
        ]);

        $order = SalesOrder::where('customer_id', $customer->id)->first();

        // Assert line item has warranty_days snapshot and cost reference
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 750.00,
            'unit_cost' => 500.00,
            'warranty_days' => 365,
            'subtotal' => 1500.00,
        ]);
    }

    public function test_can_create_sales_order_with_existing_customer_fully_paid(): void
    {
        $existingCustomer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Apex Retail Ltd',
            'phone' => '01811999888',
            'address' => 'Motijheel C/A, Dhaka',
        ]);

        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $existingCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 800.00,
                    'warranty_days' => 365,
                ],
            ],
            'paid_amount' => 800.00,
            'order_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sales_orders', [
            'team_id' => $this->team->id,
            'customer_id' => $existingCustomer->id,
            'total_amount' => 800.00,
            'paid_amount' => 800.00,
            'due_amount' => 0.00,
            'payment_status' => 'paid',
        ]);
    }

    public function test_can_record_payment_and_clear_due_balance(): void
    {
        $customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Rahim Enterprise',
            'phone' => '01911223344',
            'address' => 'Gulshan, Dhaka',
        ]);

        $soService = app(SalesOrderService::class);
        $order = $soService->createOrder([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'paid_amount' => 200.00,
        ], [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
                'unit_price' => 750.00,
                'warranty_days' => 365,
            ]
        ]);

        $this->assertEquals(750.00, (float) $order->total_amount);
        $this->assertEquals(200.00, (float) $order->paid_amount);
        $this->assertEquals(550.00, (float) $order->due_amount);
        $this->assertEquals('partially_paid', $order->payment_status);

        // Record due installment payment of $550 via HTTP endpoint
        $response = $this->post(route('sales.pay', $order->id), [
            'amount' => 550.00,
        ]);
        $response->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals(750.00, (float) $order->paid_amount);
        $this->assertEquals(0.00, (float) $order->due_amount);
        $this->assertEquals('paid', $order->payment_status);
    }
}
