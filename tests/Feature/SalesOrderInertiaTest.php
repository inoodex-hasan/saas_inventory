<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesOrderInertiaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customer;

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
            'name' => 'Main Fulfilment Depot',
            'code' => 'MFD-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'HP EliteBook 840 G8',
            'sku' => 'LAP-HP-840',
            'cost_price' => 70000.00,
            'selling_price' => 85000.00,
            'warranty_days' => 365,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Techno Soft BD',
            'phone' => '01711229988',
            'address' => 'Kawran Bazar, Dhaka',
            'is_active' => true,
        ]);

        // Stock in 20 units for testing
        app(StockService::class)->stockIn(
            warehouseId: $this->warehouse->id,
            productId: $this->product->id,
            quantity: 20,
            unitCost: 70000.00,
            reason: 'Opening stock for test'
        );
    }

    public function test_can_view_sales_orders_index_via_inertia(): void
    {
        $response = $this->get(route('sales.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Sales/Index')
            ->has('orders.data')
            ->has('customers')
            ->has('warehouses')
            ->has('metrics')
        );
    }

    public function test_can_view_sales_orders_create_terminal_via_inertia(): void
    {
        $response = $this->get(route('sales.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Sales/Create')
            ->has('customers')
            ->has('warehouses')
            ->has('products')
            ->where('defaultWarehouseId', $this->warehouse->id)
        );
    }

    public function test_can_create_sales_order_with_existing_customer(): void
    {
        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'discount_amount' => 5000.00,
            'tax_amount' => 1000.00,
            'shipping_cost' => 500.00,
            'paid_amount' => 80000.00,
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 85000.00,
                    'warranty_days' => 365,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales_orders', [
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'subtotal' => 85000.00,
            'discount_amount' => 5000.00,
            'tax_amount' => 1000.00,
            'shipping_cost' => 500.00,
            'total_amount' => 81500.00,
            'paid_amount' => 80000.00,
            'due_amount' => 1500.00,
            'status' => 'draft',
        ]);
    }

    public function test_can_create_sales_order_with_inline_new_customer(): void
    {
        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'new',
            'new_customer_name' => 'Akbar Telecom Ltd',
            'new_customer_phone' => '01855443322',
            'new_customer_address' => 'Agrabad C/A, Chittagong',
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 170000.00,
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 85000.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('customers', [
            'team_id' => $this->team->id,
            'name' => 'Akbar Telecom Ltd',
            'phone' => '01855443322',
        ]);

        $newCustomer = Customer::where('phone', '01855443322')->first();
        $this->assertDatabaseHas('sales_orders', [
            'team_id' => $this->team->id,
            'customer_id' => $newCustomer->id,
            'total_amount' => 170000.00,
            'status' => 'confirmed',
        ]);

        // Stock reserved check
        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(2, (float) $stock->reserved_quantity);
    }

    public function test_can_confirm_dispatch_and_complete_sales_order(): void
    {
        $this->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 85000.00,
                ],
            ],
        ]);

        $order = SalesOrder::where('team_id', $this->team->id)->latest('id')->first();
        $this->assertEquals('draft', $order->status);

        // Confirm
        $this->post(route('sales.confirm', $order));
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        // Dispatch
        $this->post(route('sales.dispatch', $order));
        $order->refresh();
        $this->assertEquals('dispatched', $order->status);

        // Complete
        $this->post(route('sales.complete', $order));
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    public function test_can_view_quotations_and_convert_to_sales_order(): void
    {
        // View index
        $indexResponse = $this->get(route('quotations.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Quotations/Index')
            ->has('quotations.data')
            ->has('metrics')
        );

        // View create
        $createResponse = $this->get(route('quotations.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertInertia(fn (Assert $page) => $page
            ->component('Quotations/Create')
            ->has('customers')
            ->has('products')
        );

        // Store quotation
        $storeResponse = $this->post(route('quotations.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(15)->format('Y-m-d'),
            'status' => 'sent',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 84000.00,
                ],
            ],
        ]);
        $storeResponse->assertRedirect(route('quotations.index'));

        $quotation = Quotation::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($quotation);

        // Convert to sales order
        $convertResponse = $this->post(route('quotations.convert', $quotation), [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $convertResponse->assertRedirect(route('sales.index'));

        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status);

        $this->assertDatabaseHas('sales_orders', [
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'total_amount' => 168000.00,
            'status' => 'confirmed',
        ]);
    }

    public function test_can_view_challans_and_issue_delivery_challan(): void
    {
        // Create an order first
        $this->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 85000.00,
                ],
            ],
        ]);
        $order = SalesOrder::where('team_id', $this->team->id)->latest('id')->first();
        $orderItem = $order->items->first();

        // View challans index
        $indexResponse = $this->get(route('challans.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Challans/Index')
            ->has('challans.data')
            ->has('metrics')
        );

        // Store challan
        $storeResponse = $this->post(route('challans.store'), [
            'sales_order_id' => $order->id,
            'dispatch_date' => now()->format('Y-m-d'),
            'transport_carrier' => 'Sundarban Courier',
            'vehicle_no' => 'DHK-METRO-1122',
            'driver_name' => 'Rafiq Driver',
            'driver_phone' => '01700998877',
            'delivery_address' => 'Kawran Bazar, Dhaka',
            'items' => [
                [
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $storeResponse->assertRedirect(route('challans.index'));

        $challan = DeliveryChallan::where('sales_order_id', $order->id)->first();
        $this->assertNotNull($challan);
        $this->assertEquals('dispatched', $challan->status);

        // Mark delivered
        $this->post(route('challans.deliver', $challan));
        $challan->refresh();
        $this->assertEquals('delivered', $challan->status);
    }

    public function test_can_view_customer_dues_and_record_collections(): void
    {
        // Create an order with due
        $this->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 35000.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 85000.00,
                ],
            ],
        ]);
        $order = SalesOrder::where('team_id', $this->team->id)->latest('id')->first();
        $this->assertEquals(50000.00, (float) $order->due_amount);

        // View customer dues index
        $duesResponse = $this->get(route('dues.index'));
        $duesResponse->assertStatus(200);
        $duesResponse->assertInertia(fn (Assert $page) => $page
            ->component('Dues/Index')
            ->has('customers')
            ->has('metrics')
        );

        // Collect payment
        $payResponse = $this->post(route('dues.pay'), [
            'order_id' => $order->id,
            'amount' => 20000.00,
            'payment_method' => 'bkash',
            'notes' => 'TrxID #TXN998811',
        ]);
        $payResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals(55000.00, (float) $order->paid_amount);
        $this->assertEquals(30000.00, (float) $order->due_amount);
        $this->assertEquals('partially_paid', $order->payment_status);
    }
}
