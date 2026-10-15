<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DedicatedOrderPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Receiving Warehouse',
            'code' => 'MRW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Dell Latitude Laptop',
            'sku' => 'LAP-DELL-01',
            'cost_price' => 500.00,
            'selling_price' => 750.00,
            'warranty_days' => 365,
            'is_active' => true,
        ]);

        // Stock in for sales testing
        app(StockService::class)->stockIn(
            warehouseId: $this->warehouse->id,
            productId: $this->product->id,
            quantity: 20,
            reason: 'Opening test stock'
        );
    }

    public function test_user_can_access_dedicated_sales_create_page(): void
    {
        $response = $this->get(route('sales.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Sales/Create')
            ->has('customers')
            ->has('warehouses')
            ->has('products')
        );
    }

    public function test_can_create_sales_order_from_dedicated_page_with_new_customer(): void
    {
        $response = $this->post(route('sales.store'), [
            'customer_mode' => 'new',
            'new_customer_name' => 'Jahangir Alam',
            'new_customer_phone' => '01711223399',
            'new_customer_address' => 'Mirpur-10, Dhaka',
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 750.00,
                    'warranty_days' => 365,
                ],
            ],
            'paid_amount' => 1000.00,
            'order_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sales.index'));

        // Assert customer created
        $customer = Customer::where('phone', '01711223399')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Jahangir Alam', $customer->name);

        // Assert sales order created
        $order = SalesOrder::where('customer_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(1500.00, (float) $order->total_amount);
        $this->assertEquals(1000.00, (float) $order->paid_amount);
        $this->assertEquals(500.00, (float) $order->due_amount);
        $this->assertEquals('partially_paid', $order->payment_status);
    }

    public function test_user_can_access_dedicated_purchases_create_page(): void
    {
        $response = $this->get(route('purchases.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Purchases/Create')
            ->has('suppliers')
            ->has('warehouses')
            ->has('products')
        );
    }

    public function test_can_create_purchase_order_from_dedicated_page_with_new_vendor(): void
    {
        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'new',
            'new_vendor_name' => 'Star Tech Wholesale Ltd.',
            'new_vendor_phone' => '01999887766',
            'new_vendor_address' => 'Kawran Bazar C/A, Dhaka',
            'new_vendor_contact' => 'Mahfuz Rahman',
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 480.00,
                ],
            ],
            'order_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('purchases.index'));

        // Assert supplier created
        $supplier = Supplier::where('phone', '01999887766')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals('Star Tech Wholesale Ltd.', $supplier->name);

        // Assert purchase order created
        $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(2400.00, (float) $po->total_amount);
    }
}
