<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected SalesOrderService $soService;
    protected StockService $stockService;
    protected $user;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->soService = app(SalesOrderService::class);
        $this->stockService = app(StockService::class);

        $creator = new CreateNewUser();
        $this->user = $creator->create([
            'name' => 'Sales Lead',
            'email' => 'sales@company.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create(['name' => 'Dhaka Hub', 'code' => 'WH-DHK', 'is_default' => true]);
        $this->customer = Customer::create(['name' => 'Acme Retailers', 'email' => 'acme@retail.test']);
        
        $this->product = Product::create([
            'name' => 'Noise Cancelling Headphones',
            'sku' => 'HD-NC-900',
            'cost_price' => 80.00,
            'unit_price' => 150.00,
        ]);

        // Stock in 50 units
        $this->stockService->stockIn(
            warehouseId: $this->warehouse->id,
            productId: $this->product->id,
            quantity: 50,
            unitCost: 80.00
        );
    }

    public function test_can_create_sales_order_and_compute_totals(): void
    {
        $so = $this->soService->createOrder(
            data: [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'tax_amount' => 15.00,
                'shipping_cost' => 10.00,
                'discount_amount' => 25.00,
            ],
            items: [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 150.00],
            ]
        );

        $this->assertEquals('draft', $so->status);
        $this->assertEquals(300.00, $so->subtotal); // 2 * 150 = 300
        $this->assertEquals(300.00, $so->total_amount); // 300 + 15 + 10 - 25 = 300
        $this->assertEquals(300.00, $so->due_amount);
        $this->assertEquals('unpaid', $so->payment_status);
        $this->assertEquals(80.00, $so->items->first()->unit_cost); // Snapshotted COGS
    }

    public function test_confirming_order_reserves_inventory(): void
    {
        $so = $this->soService->createOrder(
            data: [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
            ],
            items: [
                ['product_id' => $this->product->id, 'quantity' => 10],
            ]
        );

        $level = StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(50, $level->quantity);
        $this->assertEquals(0, $level->reserved_quantity);
        $this->assertEquals(50, $level->available_quantity);

        // Confirm order -> Reserves 10 units
        $this->soService->confirmOrder($so);
        $level->refresh();

        $this->assertEquals('confirmed', $so->fresh()->status);
        $this->assertEquals(50, $level->quantity); // Physical count unchanged
        $this->assertEquals(10, $level->reserved_quantity); // 10 reserved
        $this->assertEquals(40, $level->available_quantity); // 40 available for other sales
    }

    public function test_dispatching_order_releases_reservation_and_deducts_stock(): void
    {
        $so = $this->soService->createOrder(
            data: [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'confirmed',
            ],
            items: [
                ['product_id' => $this->product->id, 'quantity' => 15],
            ]
        );

        // Dispatch
        $this->soService->dispatchOrder($so);

        $level = StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals('dispatched', $so->fresh()->status);
        $this->assertEquals(35, $level->quantity); // 50 - 15 = 35
        $this->assertEquals(0, $level->reserved_quantity); // Reservation cleared
        $this->assertEquals(35, $level->available_quantity);
        $this->assertEquals(35, $this->product->fresh()->total_stock);
    }

    public function test_cannot_confirm_order_with_insufficient_stock(): void
    {
        $this->expectException(InsufficientStockException::class);

        // Attempt to order 100 when only 50 in stock
        $so = $this->soService->createOrder(
            data: [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
            ],
            items: [
                ['product_id' => $this->product->id, 'quantity' => 100],
            ]
        );

        $this->soService->confirmOrder($so);
    }

    public function test_cancelling_confirmed_order_releases_reservation(): void
    {
        $so = $this->soService->createOrder(
            data: [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'confirmed',
            ],
            items: [
                ['product_id' => $this->product->id, 'quantity' => 20],
            ]
        );

        $level = StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(20, $level->reserved_quantity);
        $this->assertEquals(30, $level->available_quantity);

        // Cancel order
        $this->soService->cancelOrder($so);
        $level->refresh();

        $this->assertEquals('cancelled', $so->fresh()->status);
        $this->assertEquals(0, $level->reserved_quantity); // Restored
        $this->assertEquals(50, $level->available_quantity);
    }

    public function test_recording_payment_updates_payment_status(): void
    {
        $so = $this->soService->createOrder(
            data: [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
            ],
            items: [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 150.00], // Total = 300
            ]
        );

        // Partial payment 100
        $this->soService->recordPayment($so, 100.00);
        $so->refresh();
        $this->assertEquals(100.00, $so->paid_amount);
        $this->assertEquals(200.00, $so->due_amount);
        $this->assertEquals('partially_paid', $so->payment_status);

        // Remaining payment 200
        $this->soService->recordPayment($so, 200.00);
        $so->refresh();
        $this->assertEquals(300.00, $so->paid_amount);
        $this->assertEquals(0.00, $so->due_amount);
        $this->assertEquals('paid', $so->payment_status);
        $this->assertTrue($so->isFullyPaid());
    }
}
