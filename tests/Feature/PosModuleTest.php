<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PosModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private ProductCategory $category;
    private Product $product;
    private Customer $customer;
    private FinancialAccount $cashAccount;

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
            'name' => 'Main Outlet Hub',
            'code' => 'OUT-01',
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Smart Gadgets',
            'slug' => 'smart-gadgets',
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Fast Charging PowerBank 20000mAh',
            'sku' => 'PB-20K',
            'barcode' => '8801234567890',
            'unit' => 'pcs',
            'cost_price' => 1200.00,
            'unit_price' => 1800.00,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
            'is_active' => true,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 25,
            'reserved_quantity' => 0,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Mahmudul Hasan',
            'phone' => '01711223344',
            'address' => 'Dhanmondi, Dhaka',
            'is_active' => true,
        ]);

        $this->cashAccount = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'POS Cash Register',
            'type' => 'cash',
            'account_type' => 'cash',
            'opening_balance' => 5000.00,
            'current_balance' => 5000.00,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_user_can_access_pos_terminal(): void
    {
        $response = $this->get(route('pos.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('POS/Index')
            ->has('warehouses', 1)
            ->has('products', 1)
            ->has('customers')
            ->has('financialAccounts')
            ->has('nextOrderNumber')
        );
    }

    public function test_user_can_quick_register_customer_from_pos(): void
    {
        $response = $this->postJson(route('pos.customer.store'), [
            'name' => 'Tariq Anowar',
            'phone' => '01899001122',
            'address' => 'Gulshan 2, Dhaka',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'name' => 'Tariq Anowar',
                'phone' => '01899001122',
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'team_id' => $this->team->id,
            'name' => 'Tariq Anowar',
            'phone' => '01899001122',
        ]);
    }

    public function test_user_can_quick_register_customer_with_only_phone(): void
    {
        $response = $this->postJson(route('pos.customer.store'), [
            'phone' => '01700998877',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'name' => 'Customer 01700998877',
                'phone' => '01700998877',
            ],
        ]);

        $this->assertDatabaseHas('customers', [
            'team_id' => $this->team->id,
            'name' => 'Customer 01700998877',
            'phone' => '01700998877',
        ]);
    }

    public function test_user_can_complete_pos_sale_and_deduct_stock_and_deposit_cash(): void
    {
        $response = $this->postJson(route('pos.store'), [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 1800.00,
                ],
            ],
            'subtotal' => 3600.00,
            'discount_amount' => 100.00,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'total_amount' => 3500.00,
            'paid_amount' => 3500.00,
            'tendered_amount' => 4000.00,
            'financial_account_id' => $this->cashAccount->id,
            'notes' => 'Counter sale with cash payment',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total_amount' => 3500.00,
            'paid_amount' => 3500.00,
        ]);

        // Assert Sales Order was created and completed
        $order = SalesOrder::where('team_id', $this->team->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertStringStartsWith('POS-', $order->order_number);
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(3500.00, (float) $order->total_amount);
        $this->assertEquals(0.00, (float) $order->due_amount);

        // Assert Stock deduction (25 - 2 = 23)
        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(23.0, (float) $stock->quantity);

        // Assert Cash Account received deposit (5000 + 3500 = 8500)
        $this->cashAccount->refresh();
        $this->assertEquals(8500.00, (float) $this->cashAccount->current_balance);
    }

    public function test_pos_fails_gracefully_when_stock_is_insufficient(): void
    {
        $response = $this->postJson(route('pos.store'), [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 100, // Available is only 25
                    'unit_price' => 1750.00,
                ],
            ],
            'subtotal' => 175000.00,
            'total_amount' => 175000.00,
            'paid_amount' => 175000.00,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Insufficient stock in warehouse', $response->json('message'));
    }

    public function test_pos_multi_warehouse_fulfillment_resolves_warehouse_with_complete_stock(): void
    {
        $warehouse2 = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Secondary Fulfillment Depot',
            'code' => 'DEP-02',
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Wireless Mouse',
            'sku' => 'WM-01',
            'unit' => 'pcs',
            'cost_price' => 300.00,
            'unit_price' => 500.00,
            'is_active' => true,
        ]);

        // In warehouse 1 ($this->warehouse): Product 1 has 25 stock, Product 2 has 0 stock.
        // In warehouse 2: Both Product 1 and Product 2 have 10 stock.
        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $warehouse2->id,
            'product_id' => $this->product->id,
            'quantity' => 10.0,
            'reserved_quantity' => 0.0,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $warehouse2->id,
            'product_id' => $product2->id,
            'quantity' => 10.0,
            'reserved_quantity' => 0.0,
        ]);

        $response = $this->postJson(route('pos.store'), [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 1750.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'unit_price' => 500.00,
                ],
            ],
            'subtotal' => 5000.00,
            'total_amount' => 5000.00,
            'paid_amount' => 5000.00,
            'financial_account_id' => $this->cashAccount->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Warehouse 2 should have been picked because Warehouse 1 cannot fulfill Product 2
        $order = SalesOrder::where('team_id', $this->team->id)->latest('id')->first();
        $this->assertEquals($warehouse2->id, $order->warehouse_id);

        $stockP2 = StockLevel::where('warehouse_id', $warehouse2->id)->where('product_id', $product2->id)->first();
        $this->assertEquals(7.0, (float) $stockP2->quantity); // 10 - 3 = 7
    }
}

