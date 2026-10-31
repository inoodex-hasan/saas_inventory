<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DedicatedInventoryPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouseA;
    private Warehouse $warehouseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->actingAs($this->user);

        $this->warehouseA = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Hub',
            'code' => 'HUB-A',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Secondary Hub',
            'code' => 'HUB-B',
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_inventory_page_renders_with_inertia(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Ergonomic Keyboard',
            'sku' => 'KEY-ERG-01',
            'cost_price' => 2500,
            'unit_price' => 3500,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $supplier = \App\Models\Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Tech Distributor Inc',
            'phone' => '+8801700000001',
            'is_active' => true,
        ]);

        $po = \App\Models\PurchaseOrder::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'supplier_id' => $supplier->id,
            'order_number' => 'PO-TEST-001',
            'status' => 'ordered',
            'order_date' => now()->toDateString(),
            'total_amount' => 12500,
            'paid_amount' => 0,
            'due_amount' => 12500,
            'payment_status' => 'unpaid',
        ]);

        \App\Models\PurchaseOrderItem::create([
            'team_id' => $this->team->id,
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'received_quantity' => 0,
            'unit_cost' => 2500,
            'subtotal' => 12500,
        ]);

        $response = $this->get(route('inventory.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Index')
            ->has('stockItems.data', 1)
            ->where('stockItems.data.0.on_hand', 20)
            ->where('stockItems.data.0.incoming', 5)
            ->has('metrics')
            ->where('metrics.total_units', 20)
            ->where('metrics.total_incoming_units', 5)
            ->where('metrics.total_valuation', 50000)
            ->has('warehouses')
            ->has('categories')
            ->has('productsList')
        );
    }

    public function test_can_reconcile_and_adjust_stock_via_inventory(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Monitor Stand',
            'sku' => 'MON-STD-01',
            'cost_price' => 1000,
            'unit_price' => 1500,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $response = $this->post(route('inventory.adjust'), [
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'new_quantity' => 25,
            'reason' => 'Annual physical audit count',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_levels', [
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'quantity' => 25,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 15, // +15 delta
            'reason' => 'Annual physical audit count',
        ]);
    }

    public function test_can_transfer_stock_between_warehouses(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'USB-C Cable',
            'sku' => 'CAB-USBC-01',
            'cost_price' => 300,
            'unit_price' => 600,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $response = $this->post(route('inventory.transfer'), [
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'product_id' => $product->id,
            'quantity' => 15,
            'reason' => 'Fulfill Chittagong hub deficit',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_levels', [
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $product->id,
            'quantity' => 35, // 50 - 15
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouseB->id,
            'product_id' => $product->id,
            'quantity' => 15, // 0 + 15
        ]);
    }
}
