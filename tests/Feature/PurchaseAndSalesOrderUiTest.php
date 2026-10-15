<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseAndSalesOrderUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Supplier $supplier;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'code' => 'MAIN-HUB',
            'name' => 'Main Logistics Hub',
            'is_default' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Mechanical Keyboard Pro',
            'sku' => 'KEY-PRO-01',
            'cost_price' => 50.00,
            'selling_price' => 120.00,
            'reorder_level' => 10,
            'reorder_quantity' => 50,
        ]);

        $this->supplier = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Apex Parts Global',
            'contact_name' => 'John Doe',
            'email' => 'john@apexparts.com',
            'phone' => '01711223344',
            'address' => 'Silicon Valley',
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Tech Solutions LLC',
            'email' => 'orders@techsolutions.com',
            'phone' => '01811223344',
            'address' => 'Downtown',
        ]);
    }

    public function test_purchase_order_can_create_and_receive_goods(): void
    {
        // 1. Create Purchase Order via Controller
        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 20,
                    'unit_cost' => 50.00,
                ],
            ],
        ]);
        $response->assertSessionHasNoErrors();

        $po = PurchaseOrder::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals('ordered', $po->status);
        $this->assertEquals(1000.00, (float) $po->total_amount);

        // 2. Receive Goods via Controller
        $firstItem = $po->items->first();
        $receiveResponse = $this->post(route('purchases.receive', $po->id), [
            'items' => [
                [
                    'item_id' => $firstItem->id,
                    'quantity' => 20,
                    'batch_number' => 'BATCH-001',
                ],
            ],
        ]);
        $receiveResponse->assertSessionHasNoErrors();

        $po->refresh();
        $this->assertEquals('received', $po->status);

        // Verify stock in warehouse is 20
        $this->assertDatabaseHas('stock_levels', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
        ]);
    }

    public function test_sales_order_can_create_confirm_dispatch_and_record_payment(): void
    {
        // Add initial stock of 30 units
        $stockService = app(StockService::class);
        $stockService->stockIn($this->warehouse->id, $this->product->id, 30, 50.00);

        // 1. Create Sales Order via Controller
        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 120.00,
                ],
            ],
        ]);
        $response->assertSessionHasNoErrors();

        $order = SalesOrder::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(1200.00, (float) $order->total_amount);

        // 2. Confirm Order (reserves stock)
        $this->post(route('sales.confirm', $order->id))->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        $this->assertDatabaseHas('stock_levels', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
            'reserved_quantity' => 10,
        ]);

        // 3. Dispatch Order (deducts stock)
        $this->post(route('sales.dispatch', $order->id))->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertEquals('dispatched', $order->status);

        $this->assertDatabaseHas('stock_levels', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        // 4. Record Payment
        $this->post(route('sales.pay', $order->id), [
            'amount' => 1200.00,
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(1200.00, (float) $order->paid_amount);
    }
}
