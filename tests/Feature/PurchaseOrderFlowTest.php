<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Exceptions\InvalidStockOperationException;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseOrderService $poService;
    protected $user;
    protected Warehouse $warehouse;
    protected Supplier $supplier;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->poService = app(PurchaseOrderService::class);

        $creator = new CreateNewUser();
        $this->user = $creator->create([
            'name' => 'Purchasing Manager',
            'email' => 'purchasing@company.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create(['name' => 'Main Receiving Warehouse', 'code' => 'WH-MAIN', 'is_default' => true]);
        $this->supplier = Supplier::create(['name' => 'Global Electronics Supplies', 'email' => 'supplier@global.test']);
        
        $this->productA = Product::create([
            'name' => 'Mechanical Keyboard RGB',
            'sku' => 'KEY-RGB-01',
            'cost_price' => 45.00,
            'unit_price' => 75.00,
        ]);

        $this->productB = Product::create([
            'name' => 'USB-C Fast Charger 65W',
            'sku' => 'CHG-65W-01',
            'cost_price' => 15.00,
            'unit_price' => 30.00,
        ]);
    }

    public function test_can_create_purchase_order_in_draft_and_transition_to_ordered(): void
    {
        $po = $this->poService->createOrder(
            data: [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'shipping_cost' => 50.00,
                'tax_amount' => 20.00,
            ],
            items: [
                ['product_id' => $this->productA->id, 'quantity' => 10, 'unit_cost' => 45.00],
                ['product_id' => $this->productB->id, 'quantity' => 20, 'unit_cost' => 15.00],
            ]
        );

        $this->assertEquals('draft', $po->status);
        $this->assertEquals(750.00, $po->subtotal); // (10 * 45) + (20 * 15) = 450 + 300 = 750
        $this->assertEquals(820.00, $po->total_amount); // 750 + 50 + 20 = 820
        $this->assertCount(2, $po->items);

        // Transition to ordered
        $this->poService->markAsOrdered($po);
        $this->assertEquals('ordered', $po->fresh()->status);
    }

    public function test_receiving_stock_updates_received_qty_and_triggers_stock_in(): void
    {
        $po = $this->poService->createOrder(
            data: [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'ordered',
            ],
            items: [
                ['product_id' => $this->productA->id, 'quantity' => 10, 'unit_cost' => 45.00],
            ]
        );

        $item = $po->items->first();

        // Initial warehouse stock is 0
        $this->assertEquals(0, $this->productA->fresh()->total_stock);

        // Receive partial quantity (4 units out of 10)
        $this->poService->receiveItems($po, [
            ['item_id' => $item->id, 'quantity' => 4]
        ]);

        $po->refresh();
        $item->refresh();

        $this->assertEquals('partially_received', $po->status);
        $this->assertEquals(4, $item->received_quantity);
        $this->assertEquals(6, $item->remaining_quantity);
        
        // Warehouse stock should now be 4
        $this->assertEquals(4, $this->productA->fresh()->total_stock);

        // Receive remaining 6 units
        $this->poService->receiveItems($po, [
            ['item_id' => $item->id, 'quantity' => 6]
        ]);

        $po->refresh();
        $item->refresh();

        $this->assertEquals('received', $po->status);
        $this->assertEquals(10, $item->received_quantity);
        $this->assertEquals(0, $item->remaining_quantity);
        $this->assertEquals(10, $this->productA->fresh()->total_stock);
    }

    public function test_cannot_receive_more_than_ordered_qty(): void
    {
        $this->expectException(InvalidStockOperationException::class);

        $po = $this->poService->createOrder(
            data: [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'ordered',
            ],
            items: [
                ['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => 45.00],
            ]
        );

        $item = $po->items->first();

        // Attempt to receive 10 when only 5 was ordered
        $this->poService->receiveItems($po, [
            ['item_id' => $item->id, 'quantity' => 10]
        ]);
    }

    public function test_cannot_cancel_received_order(): void
    {
        $this->expectException(InvalidStockOperationException::class);

        $po = $this->poService->createOrder(
            data: [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'ordered',
            ],
            items: [
                ['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => 45.00],
            ]
        );

        $item = $po->items->first();
        $this->poService->receiveItems($po, [['item_id' => $item->id, 'quantity' => 5]]);

        // Attempt to cancel after receiving
        $this->poService->cancelOrder($po);
    }
}
