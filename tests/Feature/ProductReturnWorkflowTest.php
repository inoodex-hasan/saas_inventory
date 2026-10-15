<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductSerial;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReturnService;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customer;
    private SalesOrder $salesOrder;

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
            'name' => 'Main Logistics Hub',
            'code' => 'MLH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Laser Barcode Scanner',
            'sku' => 'SCN-LSR-01',
            'cost_price' => 3000,
            'unit_price' => 5000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Karim Electronics',
            'phone' => '01719887766',
            'address' => 'IDB Bhaban, Agargaon, Dhaka',
        ]);

        // Stock initial 20 units into warehouse
        $stockService = app(StockService::class);
        $stockService->stockIn(
            warehouseId: $this->warehouse->id,
            productId: $this->product->id,
            quantity: 20,
            unitCost: 3000
        );

        // Create and dispatch Sales Order (5 units)
        $soService = app(SalesOrderService::class);
        $this->salesOrder = $soService->createOrder([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'paid_amount' => 25000,
        ], [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'unit_price' => 5000,
            ]
        ]);

        $soService->confirmOrder($this->salesOrder);
        $soService->dispatchOrder($this->salesOrder);
    }

    public function test_can_create_product_return_request()
    {
        $returnService = app(ReturnService::class);
        $orderItem = $this->salesOrder->items->first();

        $return = $returnService->createReturn([
            'sales_order_id' => $this->salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Customer ordered wrong model',
            'refund_type' => 'credit_note',
        ], [
            [
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_price' => 5000,
                'condition' => 'sellable',
                'action_taken' => 'restock',
            ]
        ]);

        $this->assertDatabaseHas('product_returns', [
            'id' => $return->id,
            'status' => 'pending',
            'refund_amount' => 10000.00,
            'sales_order_id' => $this->salesOrder->id,
        ]);

        $this->assertDatabaseHas('product_return_items', [
            'product_return_id' => $return->id,
            'quantity' => 2,
            'condition' => 'sellable',
            'action_taken' => 'restock',
        ]);
    }

    public function test_controller_can_log_return_request()
    {
        $orderItem = $this->salesOrder->items->first();

        $response = $this->post(route('returns.store'), [
            'sales_order_id' => $this->salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Damaged packaging on delivery',
            'refund_type' => 'credit_note',
            'items' => [
                [
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'condition' => 'sellable',
                    'action_taken' => 'restock',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $createdReturn = ProductReturn::where('sales_order_id', $this->salesOrder->id)->latest('id')->first();
        $this->assertNotNull($createdReturn);
        $this->assertEquals('pending', $createdReturn->status);
        $this->assertEquals(5000.00, (float) $createdReturn->refund_amount);
    }

    public function test_manager_can_approve_return_request()
    {
        $returnService = app(ReturnService::class);
        $orderItem = $this->salesOrder->items->first();

        $return = $returnService->createReturn([
            'sales_order_id' => $this->salesOrder->id,
            'reason' => 'Defective port',
        ], [
            [
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'quantity' => 1,
                'unit_price' => 5000,
            ]
        ]);

        $response = $this->post(route('returns.approve', $return->id));
        $response->assertSessionHasNoErrors();

        $return->refresh();
        $this->assertEquals('approved', $return->status);
        $this->assertNotNull($return->approved_at);
        $this->assertEquals($this->user->id, $return->approved_by);
    }

    public function test_completing_return_restocks_inventory_and_updates_serial()
    {
        $serial = ProductSerial::create([
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-SCANNER-9988',
            'status' => 'sold',
            'sales_order_id' => $this->salesOrder->id,
            'customer_id' => $this->customer->id,
            'sold_at' => now(),
        ]);

        $orderItem = $this->salesOrder->items->first();
        $returnService = app(ReturnService::class);

        $return = $returnService->createReturn([
            'sales_order_id' => $this->salesOrder->id,
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Replacement return',
        ], [
            [
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'serial_number' => 'SN-SCANNER-9988',
                'quantity' => 1,
                'unit_price' => 5000,
                'condition' => 'sellable',
                'action_taken' => 'restock',
            ]
        ]);

        $returnService->approveReturn($return, $this->user->id);

        $stockBefore = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(15, (float) $stockBefore);

        $response = $this->post(route('returns.complete', $return->id));
        $response->assertSessionHasNoErrors();

        $return->refresh();
        $this->assertEquals('completed', $return->status);

        $stockAfter = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(16, (float) $stockAfter);

        $serial->refresh();
        $this->assertEquals('in_stock', $serial->status);
        $this->assertNull($serial->sales_order_id);
    }

    public function test_manager_can_reject_return_request()
    {
        $orderItem = $this->salesOrder->items->first();
        $returnService = app(ReturnService::class);

        $return = $returnService->createReturn([
            'sales_order_id' => $this->salesOrder->id,
            'reason' => 'Customer changed mind',
        ], [
            [
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'quantity' => 1,
                'unit_price' => 5000,
            ]
        ]);

        $response = $this->post(route('returns.reject', $return->id), [
            'reason' => 'Exceeded 7-day return policy',
        ]);
        $response->assertSessionHasNoErrors();

        $return->refresh();
        $this->assertEquals('rejected', $return->status);
        $this->assertStringContainsString('Exceeded 7-day return policy', $return->notes);

        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
        $this->assertEquals(15, (float) $stock);
    }

    public function test_credit_note_pdf_streams_correctly()
    {
        $orderItem = $this->salesOrder->items->first();
        $returnService = app(ReturnService::class);

        $return = $returnService->createReturn([
            'sales_order_id' => $this->salesOrder->id,
            'reason' => 'Customer claim',
        ], [
            [
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'quantity' => 1,
                'unit_price' => 5000,
            ]
        ]);

        $response = $this->get(route('returns.credit-note', $return->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
