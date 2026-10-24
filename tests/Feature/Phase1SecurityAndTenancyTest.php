<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\TaDaClaim;
use App\Models\Team;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1SecurityAndTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SubscriptionService::class)->ensureDefaultPlans();
    }

    public function test_user_model_protects_is_super_admin_from_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Hacker Joe',
            'email' => 'joe@example.com',
            'password' => bcrypt('secret123'),
            'is_super_admin' => true,
        ]);

        $this->assertFalse((bool) $user->fresh()->is_super_admin);
    }

    public function test_warehouse_cannot_be_deleted_if_active_stock_or_orders_exist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $warehouse1 = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Main Warehouse',
            'code' => 'MAIN-01',
            'is_default' => true,
            'is_active' => true,
        ]);
        $warehouse2 = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Secondary Warehouse',
            'code' => 'SEC-02',
            'is_default' => false,
            'is_active' => true,
        ]);

        $product = Product::create([
            'team_id' => $team->id,
            'name' => 'Test Product',
            'sku' => 'TP-001',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'unit_price' => 100,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);

        StockLevel::create([
            'team_id' => $team->id,
            'warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($user)->delete(route('warehouses.destroy', $warehouse2));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse2->id]);
    }

    public function test_tenant_cannot_create_sales_order_with_cross_tenant_warehouse_or_product(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $team1 = $user1->currentTeam;
        $customer1 = Customer::create([
            'team_id' => $team1->id,
            'name' => 'Customer 1',
            'phone' => '01700000001',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);
        $warehouse1 = Warehouse::create([
            'team_id' => $team1->id,
            'name' => 'Warehouse 1',
            'code' => 'WH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $user2 = User::factory()->withPersonalTeam()->create();
        $team2 = $user2->currentTeam;
        $product2 = Product::create([
            'team_id' => $team2->id,
            'name' => 'Product 2',
            'sku' => 'P2-001',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'unit_price' => 100,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);
        $warehouse2 = Warehouse::create([
            'team_id' => $team2->id,
            'name' => 'Warehouse 2',
            'code' => 'WH-02',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Attempt using product from tenant 2 in tenant 1 order
        $response = $this->actingAs($user1)->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $customer1->id,
            'warehouse_id' => $warehouse1->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product2->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
            ],
        ]);
        $response->assertSessionHasErrors('items.0.product_id');

        // Attempt using warehouse from tenant 2 in tenant 1 order
        $product1 = Product::create([
            'team_id' => $team1->id,
            'name' => 'Product 1',
            'sku' => 'P1-001',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'unit_price' => 100,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);
        $response2 = $this->actingAs($user1)->post(route('sales.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $customer1->id,
            'warehouse_id' => $warehouse2->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
            ],
        ]);
        $response2->assertSessionHasErrors('warehouse_id');
    }

    public function test_tenant_cannot_create_purchase_order_with_cross_tenant_supplier_or_product(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $team1 = $user1->currentTeam;
        $warehouse1 = Warehouse::create([
            'team_id' => $team1->id,
            'name' => 'Warehouse 1',
            'code' => 'WH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $user2 = User::factory()->withPersonalTeam()->create();
        $team2 = $user2->currentTeam;
        $supplier2 = Supplier::create([
            'team_id' => $team2->id,
            'name' => 'Supplier 2',
            'phone' => '01800000002',
            'address' => 'Chittagong',
            'is_active' => true,
        ]);
        $product2 = Product::create([
            'team_id' => $team2->id,
            'name' => 'Product 2',
            'sku' => 'P2-002',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'unit_price' => 100,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);

        $response = $this->actingAs($user1)->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => $supplier2->id,
            'warehouse_id' => $warehouse1->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product2->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 50,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['supplier_id', 'items.0.product_id']);
    }

    public function test_product_quota_enforcement_blocks_creation_when_limit_reached(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        // Create a custom strict plan with max 1 product
        $plan = Plan::create([
            'name' => 'Single Product Plan',
            'slug' => 'single-product',
            'price_bdt' => 0,
            'max_products' => 1,
            'max_warehouses' => 1,
            'max_users' => 1,
            'is_active' => true,
        ]);

        $sub = TenantSubscription::where('team_id', $team->id)->first();
        if ($sub) {
            $sub->update(['plan_id' => $plan->id, 'status' => 'active', 'ends_at' => now()->addMonth()]);
        } else {
            TenantSubscription::create([
                'team_id' => $team->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        // Product 1
        Product::create([
            'team_id' => $team->id,
            'name' => 'Product 1',
            'sku' => 'PROD-001',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'unit_price' => 100,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);

        // Product 2 via controller store
        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Excess Product',
            'sku' => 'EXCESS-01',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Product::where('team_id', $team->id)->count());
    }

    public function test_claimant_cannot_approve_their_own_tada_claim(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $employee = Employee::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_code' => 'EMP-001',
            'designation' => 'Executive',
            'basic_salary' => 25000,
            'status' => 'active',
        ]);

        $claim = TaDaClaim::create([
            'team_id' => $team->id,
            'employee_id' => $employee->id,
            'claim_number' => 'TD-2026-0001',
            'travel_date' => now()->toDateString(),
            'destination' => 'Chittagong Port',
            'purpose' => 'Client Delivery Inspection',
            'travel_allowance' => 1500,
            'daily_allowance' => 500,
            'other_expenses' => 0,
            'total_amount' => 2000,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('tada.approve', $claim));
        $response->assertStatus(403);
        $this->assertEquals('pending', $claim->fresh()->status);
    }
}
