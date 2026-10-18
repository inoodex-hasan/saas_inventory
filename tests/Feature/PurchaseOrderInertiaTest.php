<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PurchaseOrderInertiaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Alpha Wholesale',
            'phone' => '01700112233',
            'address' => 'Motijheel, Dhaka',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Dell 24-inch Monitor',
            'sku' => 'MON-DELL-24',
            'cost_price' => 150.00,
            'selling_price' => 200.00,
            'is_active' => true,
        ]);

        $this->account = \App\Models\FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Cash Register',
            'type' => 'cash',
            'current_balance' => 100000.00,
            'opening_balance' => 100000.00,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_can_view_purchases_index_via_inertia(): void
    {
        $response = $this->get(route('purchases.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchases/Index')
            ->has('orders.data')
            ->has('suppliers')
            ->has('warehouses')
            ->has('metrics')
        );
    }

    public function test_can_view_purchase_create_page_via_inertia(): void
    {
        $response = $this->get(route('purchases.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchases/Create')
            ->has('suppliers')
            ->has('warehouses')
            ->has('products')
            ->where('defaultWarehouseId', $this->warehouse->id)
        );
    }

    public function test_can_create_purchase_order_with_existing_vendor(): void
    {
        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 500.00,
            'financial_account_id' => $this->account->id,
            'notes' => 'Test PO delivery',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 10,
                    'unit_cost' => 150.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $this->assertDatabaseHas('purchase_orders', [
            'team_id' => $this->team->id,
            'supplier_id' => $this->supplier->id,
            'total_amount' => 1500.00,
            'paid_amount' => 500.00,
            'due_amount' => 1000.00,
            'status' => 'ordered',
        ]);
    }

    public function test_can_create_purchase_order_with_new_vendor(): void
    {
        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'new',
            'new_vendor_name' => 'Brand New Supplier Ltd',
            'new_vendor_phone' => '01855667788',
            'new_vendor_address' => 'Banani, Dhaka',
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 140.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $this->assertDatabaseHas('suppliers', [
            'team_id' => $this->team->id,
            'name' => 'Brand New Supplier Ltd',
            'phone' => '01855667788',
        ]);

        $newSupplier = Supplier::where('name', 'Brand New Supplier Ltd')->first();
        $this->assertDatabaseHas('purchase_orders', [
            'team_id' => $this->team->id,
            'supplier_id' => $newSupplier->id,
            'total_amount' => 700.00,
        ]);
    }

    public function test_can_receive_purchase_order_items(): void
    {
        // First create order
        $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 1500.00,
            'financial_account_id' => $this->account->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 10,
                    'unit_cost' => 150.00,
                ],
            ],
        ]);

        $po = PurchaseOrder::where('team_id', $this->team->id)->latest('id')->first();
        $poItem = $po->items->first();

        // Receive items
        $response = $this->post(route('purchases.receive', $po), [
            'items' => [
                [
                    'item_id' => $poItem->id,
                    'quantity' => 10,
                    'batch_number' => 'BATCH-TEST-001',
                    'bin_location' => 'BIN-A1',
                ],
            ],
        ]);

        $response->assertRedirect();
        $po->refresh();
        $this->assertEquals('received', $po->status);

        // Check stock
        $stockLevel = \App\Models\StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertNotNull($stockLevel);
        $this->assertEquals(10, (float) $stockLevel->quantity);
    }

    public function test_can_view_vendor_dues_and_record_payment(): void
    {
        // Create order with due
        $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 500.00,
            'financial_account_id' => $this->account->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 10,
                    'unit_cost' => 150.00,
                ],
            ],
        ]);

        $po = PurchaseOrder::where('team_id', $this->team->id)->latest('id')->first();
        $this->assertEquals(1000.00, (float) $po->due_amount);

        // View dues page
        $response = $this->get(route('vendor-dues.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('VendorDues/Index')
            ->has('vendors')
            ->has('metrics')
        );

        // Record payment
        $payResponse = $this->post(route('vendor-dues.pay'), [
            'order_id' => $po->id,
            'amount' => 600.00,
            'financial_account_id' => $this->account->id,
            'notes' => 'Partial clearing',
        ]);

        $payResponse->assertRedirect();
        $po->refresh();
        $this->assertEquals(1100.00, (float) $po->paid_amount);
        $this->assertEquals(400.00, (float) $po->due_amount);
    }

    public function test_can_view_purchase_edit_page_via_inertia(): void
    {
        $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 120.00,
                ],
            ],
        ]);

        $po = PurchaseOrder::where('team_id', $this->team->id)->latest('id')->first();

        $response = $this->get(route('purchases.edit', $po->id));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchases/Edit')
            ->has('order')
            ->has('suppliers')
            ->has('warehouses')
            ->has('products')
        );
    }

    public function test_can_update_purchase_order(): void
    {
        $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 100.00,
                ],
            ],
        ]);

        $po = PurchaseOrder::where('team_id', $this->team->id)->latest('id')->first();
        $item = $po->items->first();

        $response = $this->put(route('purchases.update', $po->id), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'notes' => 'Updated PO note',
            'items' => [
                [
                    'id' => $item->id,
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 8,
                    'unit_cost' => 110.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $po->refresh();
        $this->assertEquals(880.00, (float) $po->total_amount);
        $this->assertEquals('Updated PO note', $po->notes);
        $this->assertEquals(8, (float) $po->items->first()->quantity);
    }
}
