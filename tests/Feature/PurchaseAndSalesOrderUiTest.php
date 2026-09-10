<?php

namespace Tests\Feature;

use App\Livewire\PurchaseOrderManager;
use App\Livewire\SalesOrderManager;
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
use Livewire\Livewire;
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
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Tech Solutions LLC',
            'email' => 'orders@techsolutions.com',
        ]);
    }

    public function test_purchase_order_livewire_can_create_and_receive_goods(): void
    {
        // 1. Create Purchase Order via Livewire
        $test = Livewire::test(PurchaseOrderManager::class)
            ->set('supplier_id', $this->supplier->id)
            ->set('warehouse_id', $this->warehouse->id)
            ->set('order_date', now()->format('Y-m-d'))
            ->set('items', [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 20,
                    'unit_cost' => 50.00,
                ],
            ])
            ->call('savePurchaseOrder')
            ->assertHasNoErrors();

        $po = PurchaseOrder::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals('draft', $po->status);
        $this->assertEquals(1000.00, (float) $po->total_amount);

        // 2. Mark as Ordered
        $test->call('markAsOrdered', $po->id);
        $po->refresh();
        $this->assertEquals('ordered', $po->status);

        // 3. Receive Goods via Livewire
        $firstItem = $po->items->first();
        $test->call('openReceiveModal', $po->id)
            ->set("receivingItems.{$firstItem->id}.quantity_to_receive", 20)
            ->set("receivingItems.{$firstItem->id}.batch_number", 'BATCH-001')
            ->call('processReceive')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals('received', $po->status);

        // Verify stock in warehouse is 20
        $this->assertDatabaseHas('stock_levels', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
        ]);
    }

    public function test_sales_order_livewire_can_create_confirm_dispatch_and_record_payment(): void
    {
        // Add initial stock of 30 units
        $stockService = app(StockService::class);
        $stockService->stockIn($this->warehouse->id, $this->product->id, 30, 50.00);

        // 1. Create Sales Order via Livewire
        $test = Livewire::test(SalesOrderManager::class)
            ->set('customer_id', $this->customer->id)
            ->set('warehouse_id', $this->warehouse->id)
            ->set('order_date', now()->format('Y-m-d'))
            ->set('items', [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 120.00,
                ],
            ])
            ->call('saveSalesOrder')
            ->assertHasNoErrors();

        $order = SalesOrder::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('draft', $order->status);
        $this->assertEquals(1200.00, (float) $order->total_amount);

        // 2. Confirm Order (reserves stock)
        $test->call('confirmOrder', $order->id);
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        $this->assertDatabaseHas('stock_levels', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
            'reserved_quantity' => 10,
        ]);

        // 3. Dispatch Order (deducts stock)
        $test->call('dispatchOrder', $order->id);
        $order->refresh();
        $this->assertEquals('dispatched', $order->status);

        $this->assertDatabaseHas('stock_levels', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        // 4. Record Payment
        $test->call('openPaymentModal', $order->id)
            ->set('paymentAmount', 1200.00)
            ->call('recordPayment')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(1200.00, (float) $order->paid_amount);
    }
}
