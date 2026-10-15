<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DeliveryChallanTest extends TestCase
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
            'name' => 'Depot Central',
            'code' => 'DC-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'HP LaserJet Pro Printer',
            'sku' => 'HP-LJP-01',
            'cost_price' => 22000,
            'unit_price' => 31000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Beximco Communications',
            'phone' => '01700112233',
            'address' => 'Gulshan 2, Dhaka',
        ]);

        $this->order = SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'SO-20261012-0044',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 62000,
            'total_amount' => 62000,
            'paid_amount' => 62000,
            'due_amount' => 0,
            'shipping_address' => 'Gulshan 2 Site, Road 45, House 12',
        ]);

        $this->orderItem = SalesOrderItem::create([
            'team_id' => $this->team->id,
            'sales_order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 31000,
            'unit_cost' => 22000,
            'subtotal' => 62000,
        ]);
    }

    public function test_user_can_access_delivery_challans_page(): void
    {
        $response = $this->get(route('challans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Challans/Index')
            ->has('challans')
            ->has('customers')
        );
    }

    public function test_user_can_generate_delivery_challan_from_sales_order(): void
    {
        $response = $this->post(route('challans.store'), [
            'sales_order_id' => $this->order->id,
            'dispatch_date' => now()->toDateString(),
            'transport_carrier' => 'Steadfast Courier',
            'vehicle_no' => 'DHAKA-METRO-TA-22-1144',
            'driver_name' => 'Mokbul Hossain',
            'driver_phone' => '01899112233',
            'delivery_address' => 'Gulshan 2 Site, Road 45, House 12',
            'items' => [
                [
                    'sales_order_item_id' => $this->orderItem->id,
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('challans.index'));

        $this->assertDatabaseHas('delivery_challans', [
            'team_id' => $this->team->id,
            'sales_order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'transport_carrier' => 'Steadfast Courier',
            'vehicle_no' => 'DHAKA-METRO-TA-22-1144',
            'driver_name' => 'Mokbul Hossain',
            'status' => 'dispatched',
        ]);

        $challan = DeliveryChallan::where('sales_order_id', $this->order->id)->first();
        $this->assertNotNull($challan);
        $this->assertCount(1, $challan->items);
        $this->assertEquals(2, (float) $challan->items->first()->quantity);
    }

    public function test_user_can_update_challan_status_to_delivered(): void
    {
        $challan = DeliveryChallan::create([
            'team_id' => $this->team->id,
            'challan_number' => 'CH-20261012-0088',
            'sales_order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'dispatch_date' => now()->toDateString(),
            'status' => 'dispatched',
        ]);

        $response = $this->post(route('challans.deliver', $challan->id));
        $response->assertSessionHasNoErrors();

        $challan->refresh();
        $this->assertEquals('delivered', $challan->status);
    }

    public function test_authenticated_tenant_can_stream_delivery_challan_pdf(): void
    {
        $challan = DeliveryChallan::create([
            'team_id' => $this->team->id,
            'challan_number' => 'CH-20261012-0077',
            'sales_order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'dispatch_date' => now()->toDateString(),
            'transport_carrier' => 'Truck Consignment',
            'vehicle_no' => 'DHAKA-TA-9988',
            'status' => 'dispatched',
        ]);

        DeliveryChallanItem::create([
            'team_id' => $this->team->id,
            'delivery_challan_id' => $challan->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->get(route('challans.pdf', $challan->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_can_access_dedicated_create_delivery_challan_page(): void
    {
        $response = $this->get(route('challans.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Challans/Create')
            ->has('orders')
        );
    }
}
