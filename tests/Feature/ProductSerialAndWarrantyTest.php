<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\SerialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProductSerialAndWarrantyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected Warehouse $warehouse;
    protected Product $product;
    protected Supplier $supplier;
    protected Customer $customer;

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
            'name' => 'Uttara Hub',
            'code' => 'UH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'ASUS ROG Gaming Laptop',
            'sku' => 'ASUS-ROG-X1',
            'unit' => 'pcs',
            'cost_price' => 120000.00,
            'selling_price' => 145000.00,
            'unit_price' => 145000.00,
            'is_serialized' => true,
            'warranty_days' => 730,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Global Brand Pvt Ltd',
            'phone' => '01711002233',
            'address' => 'Panthapath, Dhaka',
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Mahmudul Hasan',
            'phone' => '01811223344',
            'address' => 'Dhanmondi 27, Dhaka',
        ]);
    }

    public function test_purchase_order_can_receive_serialized_items(): void
    {
        $poService = app(PurchaseOrderService::class);
        $serialService = app(SerialService::class);

        $po = $poService->createOrder([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_cost' => 120000.00,
            ],
        ]);

        $poService->markAsOrdered($po);
        $item = $po->items->first();

        $serialNumbers = ['ROG-SN-001', 'ROG-SN-002'];
        $serialService->ingestSerialsForPurchase($po, $item, $serialNumbers);

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'ROG-SN-001',
            'status' => 'in_stock',
            'purchase_order_id' => $po->id,
        ]);

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'ROG-SN-002',
            'status' => 'in_stock',
            'purchase_order_id' => $po->id,
        ]);

        $this->assertEquals(2, ProductSerial::where('purchase_order_id', $po->id)->count());
    }

    public function test_cannot_ingest_duplicate_serial_for_same_tenant(): void
    {
        $poService = app(PurchaseOrderService::class);
        $serialService = app(SerialService::class);

        $po = $poService->createOrder([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
        ], [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_cost' => 120000.00,
            ],
        ]);

        $poService->markAsOrdered($po);
        $item = $po->items->first();

        $serialService->ingestSerialsForPurchase($po, $item, ['SN-UNIQUE-999']);

        $this->expectException(\InvalidArgumentException::class);
        $serialService->ingestSerialsForPurchase($po, $item, ['SN-UNIQUE-999']);
    }

    public function test_sales_order_allocates_serials_and_computes_warranty_expiry(): void
    {
        $serial = ProductSerial::create([
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-ROG-SALE-77',
            'status' => 'in_stock',
            'warranty_days' => 730,
        ]);

        $soService = app(SalesOrderService::class);

        $saleDate = Carbon::create(2026, 10, 1);

        $order = $soService->createOrder([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => $saleDate->toDateString(),
            'paid_amount' => 145000.00,
        ], [
            [
                'product_id' => $this->product->id,
                'quantity' => 1,
                'unit_price' => 145000.00,
                'warranty_days' => 730,
                'serials' => ['SN-ROG-SALE-77'],
            ],
        ]);

        $serial->refresh();

        $this->assertEquals('sold', $serial->status);
        $this->assertEquals($order->id, $serial->sales_order_id);
        $this->assertEquals($this->customer->id, $serial->customer_id);
        $this->assertEquals($saleDate->toDateString(), $serial->sold_at->toDateString());
        
        $expectedExpiry = $saleDate->copy()->addDays(730)->toDateString();
        $this->assertEquals($expectedExpiry, $serial->warranty_expires_at->toDateString());
        $this->assertEquals('active', $serial->warranty_status);
        $this->assertTrue($serial->remaining_days > 0);
    }

    public function test_can_search_and_mark_serial_as_defective(): void
    {
        $serial = ProductSerial::create([
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-FAULTY-007',
            'status' => 'in_stock',
            'warranty_days' => 365,
        ]);

        $response = $this->post(route('warranties.defective', $serial->id), [
            'reason' => 'Burnt motherboard capacitor during stress test',
        ]);

        $response->assertSessionHasNoErrors();

        $serial->refresh();
        $this->assertEquals('defective', $serial->status);
        $this->assertStringContainsString('Burnt motherboard capacitor', $serial->notes);
    }

    public function test_tenant_isolation_protects_serials(): void
    {
        ProductSerial::create([
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-TEAM-A-SECRET',
            'status' => 'in_stock',
        ]);

        // Second tenant
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;
        $otherUser->current_team_id = $otherTeam->id;
        $otherUser->save();
        $this->actingAs($otherUser);

        $response = $this->get(route('warranties.index', ['search' => 'SECRET']));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Warranties/Index')
            ->has('serials.data', 0)
        );
    }
}
