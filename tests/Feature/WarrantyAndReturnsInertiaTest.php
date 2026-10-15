<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductSerial;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WarrantyAndReturnsInertiaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customer;
    private SalesOrder $order;
    private SalesOrderItem $orderItem;

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
            'name' => 'LaserJet Pro M404',
            'sku' => 'PRN-HP-404',
            'cost_price' => 20000.00,
            'selling_price' => 26000.00,
            'warranty_days' => 365,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Grameen Solutions',
            'phone' => '01711002233',
            'address' => 'Mirpur, Dhaka',
            'is_active' => true,
        ]);

        $this->order = SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'SO-20261013-0001',
            'status' => 'completed',
            'order_date' => now()->toDateString(),
            'total_amount' => 52000.00,
            'paid_amount' => 52000.00,
        ]);

        $this->orderItem = SalesOrderItem::create([
            'team_id' => $this->team->id,
            'sales_order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 26000.00,
            'unit_cost' => 20000.00,
            'subtotal' => 52000.00,
        ]);
    }

    public function test_can_view_warranties_index_via_inertia(): void
    {
        ProductSerial::create([
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-HP-TEST-001',
            'status' => 'sold',
            'customer_id' => $this->customer->id,
            'sales_order_id' => $this->order->id,
            'warranty_expires_at' => now()->addYear(),
        ]);

        $response = $this->get(route('warranties.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Warranties/Index')
            ->has('serials.data', 1)
            ->has('metrics')
        );
    }

    public function test_can_mark_serial_defective_via_inertia(): void
    {
        $serial = ProductSerial::create([
            'team_id' => $this->team->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-HP-TEST-002',
            'status' => 'in_stock',
        ]);

        $response = $this->post(route('warranties.defective', $serial), [
            'reason' => 'Damaged paper feeder roller',
        ]);

        $response->assertRedirect();
        $serial->refresh();
        $this->assertEquals('defective', $serial->status);
    }

    public function test_can_view_returns_index_and_log_return_request(): void
    {
        $response = $this->get(route('returns.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Returns/Index')
            ->has('returns.data')
            ->has('eligibleOrders')
            ->has('metrics')
        );

        $storeResponse = $this->post(route('returns.store'), [
            'sales_order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Customer ordered wrong spec',
            'refund_type' => 'credit_note',
            'items' => [
                [
                    'sales_order_item_id' => $this->orderItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 26000.00,
                    'condition' => 'sellable',
                    'action_taken' => 'restock',
                ],
            ],
        ]);

        $storeResponse->assertRedirect();

        $return = ProductReturn::where('sales_order_id', $this->order->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals('pending', $return->status);
        $this->assertEquals(26000.00, (float) $return->refund_amount);

        // Approve
        $this->post(route('returns.approve', $return));
        $return->refresh();
        $this->assertEquals('approved', $return->status);

        // Complete & restock
        $this->post(route('returns.complete', $return));
        $return->refresh();
        $this->assertEquals('completed', $return->status);

        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(1, (float) $stock->quantity);
    }

    public function test_can_view_and_update_tenant_settings_via_inertia(): void
    {
        $response = $this->get(route('settings.company'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Company')
            ->has('settings')
        );

        $updateResponse = $this->put(route('settings.company.update'), [
            'name' => 'Nova Logistics BD',
            'legal_name' => 'Nova Logistics Private Ltd.',
            'email' => 'contact@novalogistics.com',
            'phone' => '01911223344',
            'tax_id' => 'BIN-990011',
            'address_line1' => 'Plot 10, Agrabad C/A',
            'city' => 'Chittagong',
            'state' => 'Chittagong',
            'postal_code' => '4000',
            'country' => 'Bangladesh',
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'invoice_prefix' => 'NL-INV-',
            'po_prefix' => 'NL-PO-',
        ]);

        $updateResponse->assertRedirect();
        $this->team->refresh();
        $this->assertEquals('Nova Logistics BD', $this->team->name);
        $this->assertEquals('NL-INV-', $this->team->invoice_prefix);
    }
}
