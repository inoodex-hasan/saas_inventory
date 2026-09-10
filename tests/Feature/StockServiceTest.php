<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockOperationException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;
    protected $user;
    protected Warehouse $warehouseA;
    protected Warehouse $warehouseB;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->stockService = app(StockService::class);

        $creator = new CreateNewUser();
        $this->user = $creator->create([
            'name' => 'Stock Manager',
            'email' => 'manager@stock.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->actingAs($this->user);

        $this->warehouseA = Warehouse::create(['name' => 'Central Hub', 'code' => 'WH-A', 'is_default' => true]);
        $this->warehouseB = Warehouse::create(['name' => 'Branch Store', 'code' => 'WH-B']);
        $this->product = Product::create([
            'name' => 'Wireless Mouse G502',
            'sku' => 'LOGI-G502',
            'cost_price' => 35.00,
            'unit_price' => 55.00,
            'reorder_level' => 10,
        ]);
    }

    public function test_stock_in_creates_movement_and_increments_stock_level(): void
    {
        $movement = $this->stockService->stockIn(
            warehouseId: $this->warehouseA->id,
            productId: $this->product->id,
            quantity: 50,
            unitCost: 35.00,
            reason: 'PO #1001 Received'
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals('in', $movement->type);
        $this->assertEquals(50, $movement->quantity);
        $this->assertEquals('PO #1001 Received', $movement->reason);

        $level = StockLevel::where('warehouse_id', $this->warehouseA->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($level);
        $this->assertEquals(50, $level->quantity);
        $this->assertEquals(50, $this->product->fresh()->total_stock);
    }

    public function test_stock_out_decrements_stock_level_and_records_movement(): void
    {
        // First stock in 30
        $this->stockService->stockIn($this->warehouseA->id, $this->product->id, 30);

        // Then stock out 10
        $movement = $this->stockService->stockOut(
            warehouseId: $this->warehouseA->id,
            productId: $this->product->id,
            quantity: 10,
            reason: 'Order #SO-500 Dispatch'
        );

        $this->assertEquals('out', $movement->type);
        $this->assertEquals(-10, $movement->quantity);

        $level = StockLevel::where('warehouse_id', $this->warehouseA->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(20, $level->quantity);
        $this->assertEquals(20, $this->product->fresh()->total_stock);
    }

    public function test_stock_out_throws_exception_when_insufficient_stock(): void
    {
        $this->expectException(InsufficientStockException::class);

        // Stock in 5, attempt to dispatch 10
        $this->stockService->stockIn($this->warehouseA->id, $this->product->id, 5);
        $this->stockService->stockOut($this->warehouseA->id, $this->product->id, 10);
    }

    public function test_stock_adjustment_requires_reason_and_updates_count(): void
    {
        $this->stockService->stockIn($this->warehouseA->id, $this->product->id, 20);

        // Adjust to physical count of 18 (e.g. 2 damaged/lost items)
        $movement = $this->stockService->adjust(
            warehouseId: $this->warehouseA->id,
            productId: $this->product->id,
            newPhysicalCount: 18,
            reason: 'Monthly physical inventory count variance'
        );

        $this->assertEquals('adjustment', $movement->type);
        $this->assertEquals(-2, $movement->quantity);

        $level = StockLevel::where('warehouse_id', $this->warehouseA->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(18, $level->quantity);
    }

    public function test_stock_adjustment_fails_without_reason(): void
    {
        $this->expectException(InvalidStockOperationException::class);

        $this->stockService->adjust(
            warehouseId: $this->warehouseA->id,
            productId: $this->product->id,
            newPhysicalCount: 15,
            reason: '' // empty reason
        );
    }

    public function test_warehouse_transfer_moves_stock_atomically_between_locations(): void
    {
        // Central Hub has 100
        $this->stockService->stockIn($this->warehouseA->id, $this->product->id, 100);

        // Transfer 40 from Central Hub (WH-A) to Branch Store (WH-B)
        $result = $this->stockService->transfer(
            fromWarehouseId: $this->warehouseA->id,
            toWarehouseId: $this->warehouseB->id,
            productId: $this->product->id,
            quantity: 40,
            reason: 'Stock replenishment for Branch B'
        );

        $this->assertArrayHasKey('out', $result);
        $this->assertArrayHasKey('in', $result);
        $this->assertEquals('transfer_out', $result['out']->type);
        $this->assertEquals('transfer_in', $result['in']->type);

        $levelA = StockLevel::where('warehouse_id', $this->warehouseA->id)->where('product_id', $this->product->id)->first();
        $levelB = StockLevel::where('warehouse_id', $this->warehouseB->id)->where('product_id', $this->product->id)->first();

        $this->assertEquals(60, $levelA->quantity);
        $this->assertEquals(40, $levelB->quantity);
        $this->assertEquals(100, $this->product->fresh()->total_stock); // Total inventory across tenant remains 100
    }

    public function test_cannot_transfer_to_same_warehouse(): void
    {
        $this->expectException(InvalidStockOperationException::class);

        $this->stockService->transfer(
            fromWarehouseId: $this->warehouseA->id,
            toWarehouseId: $this->warehouseA->id,
            productId: $this->product->id,
            quantity: 10
        );
    }

    public function test_reserve_and_release_adjusts_available_quantity(): void
    {
        $this->stockService->stockIn($this->warehouseA->id, $this->product->id, 50);

        // Reserve 15 for an online checkout
        $this->stockService->reserve($this->warehouseA->id, $this->product->id, 15);

        $level = StockLevel::where('warehouse_id', $this->warehouseA->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(50, $level->quantity);
        $this->assertEquals(15, $level->reserved_quantity);
        $this->assertEquals(35, $level->available_quantity);

        // Releasing 5
        $this->stockService->release($this->warehouseA->id, $this->product->id, 5);
        $level->refresh();

        $this->assertEquals(10, $level->reserved_quantity);
        $this->assertEquals(40, $level->available_quantity);
    }
}
