<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class VendorDueAndBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Supplier $vendorWithDue;
    private Supplier $vendorPaid;
    private PurchaseOrder $dueOrder;

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
            'name' => 'Central Hub',
            'code' => 'CH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Industrial Lubricant 5L',
            'sku' => 'LUB-IND-5L',
            'cost_price' => 2500,
            'unit_price' => 3500,
            'is_active' => true,
        ]);

        $this->vendorWithDue = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Bengal Petrochemicals Ltd.',
            'phone' => '01711002233',
            'address' => 'Tejgaon I/A, Dhaka',
            'is_active' => true,
        ]);

        $this->vendorPaid = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Prime Packaging Corp.',
            'phone' => '01811556677',
            'address' => 'Gazipur Industrial Area',
            'is_active' => true,
        ]);

        // Create PO with partial payment (due) for vendorWithDue
        $poService = app(PurchaseOrderService::class);
        $this->dueOrder = $poService->createOrder([
            'supplier_id' => $this->vendorWithDue->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'paid_amount' => 10000,
            'notes' => 'Advance 10k paid upon PO approval',
        ], [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 3000,
                'batch_number' => 'LOT-2026-X1',
                'manufacture_date' => '2026-01-15',
                'expiry_date' => '2027-01-15',
            ]
        ]);
    }

    public function test_can_create_purchase_order_with_advance_payment_and_batch_details()
    {
        $this->assertEquals(30000, (float) $this->dueOrder->total_amount);
        $this->assertEquals(10000, (float) $this->dueOrder->paid_amount);
        $this->assertEquals(20000, (float) $this->dueOrder->due_amount);
        $this->assertEquals('partially_paid', $this->dueOrder->payment_status);

        $item = $this->dueOrder->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals('LOT-2026-X1', $item->batch_number);
        $this->assertEquals('2026-01-15', $item->manufacture_date->format('Y-m-d'));
        $this->assertEquals('2027-01-15', $item->expiry_date->format('Y-m-d'));
    }

    public function test_create_purchase_order_controller_persists_batch_and_advance_due()
    {
        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $this->vendorPaid->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'paid_amount' => 4000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 4,
                    'unit_cost' => 2500,
                    'batch_number' => 'BATCH-99',
                    'expiry_date' => '2028-12-31',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('purchases.index'));

        $createdPo = PurchaseOrder::where('supplier_id', $this->vendorPaid->id)->latest('id')->first();
        $this->assertNotNull($createdPo);
        $this->assertEquals(10000, (float) $createdPo->total_amount);
        $this->assertEquals(4000, (float) $createdPo->paid_amount);
        $this->assertEquals(6000, (float) $createdPo->due_amount);
        $this->assertEquals('partially_paid', $createdPo->payment_status);

        $item = $createdPo->items()->first();
        $this->assertEquals('BATCH-99', $item->batch_number);
    }

    public function test_receiving_stock_updates_product_cost_price()
    {
        $this->assertEquals(2500, (float) $this->product->fresh()->cost_price);

        // Mark PO as ordered first
        $poService = app(PurchaseOrderService::class);
        $poService->markAsOrdered($this->dueOrder);

        // Receive the stock at 3000 unit_cost
        $item = $this->dueOrder->items()->first();
        $poService->receiveItems($this->dueOrder, [
            [
                'item_id' => $item->id,
                'quantity' => 10,
            ]
        ]);

        $this->product->refresh();
        $this->assertEquals(3000, (float) $this->product->cost_price);
    }

    public function test_vendor_due_manager_displays_payables_and_supports_filtering()
    {
        $response = $this->get(route('vendor-dues.index', ['filter' => 'all_with_dues']));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('VendorDues/Index')
            ->has('vendors', 1)
            ->where('vendors.0.name', 'Bengal Petrochemicals Ltd.')
            ->where('vendors.0.outstanding_due', 20000)
        );

        $searchResponse = $this->get(route('vendor-dues.index', ['search' => 'NonExistentVendor']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertInertia(fn (AssertableInertia $page) => $page
            ->component('VendorDues/Index')
            ->has('vendors', 0)
        );
    }

    public function test_can_pay_vendor_due_partially_and_fully()
    {
        $this->assertEquals(20000, (float) $this->dueOrder->fresh()->due_amount);

        // Partial payment of 8,000
        $response1 = $this->post(route('vendor-dues.pay'), [
            'order_id' => $this->dueOrder->id,
            'amount' => 8000,
            'payment_method' => 'bank_transfer',
            'notes' => 'TRX-987654',
        ]);
        $response1->assertSessionHasNoErrors();

        $this->dueOrder->refresh();
        $this->assertEquals(18000, (float) $this->dueOrder->paid_amount);
        $this->assertEquals(12000, (float) $this->dueOrder->due_amount);
        $this->assertEquals('partially_paid', $this->dueOrder->payment_status);

        // Pay remaining 12,000 in full
        $response2 = $this->post(route('vendor-dues.pay'), [
            'order_id' => $this->dueOrder->id,
            'amount' => 12000,
            'payment_method' => 'bank_transfer',
        ]);
        $response2->assertSessionHasNoErrors();

        $this->dueOrder->refresh();
        $this->assertEquals(30000, (float) $this->dueOrder->paid_amount);
        $this->assertEquals(0, (float) $this->dueOrder->due_amount);
        $this->assertEquals('paid', $this->dueOrder->payment_status);
    }

    public function test_vendor_voucher_pdf_streams_correctly()
    {
        $response = $this->get(route('purchases.voucher', $this->dueOrder->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
