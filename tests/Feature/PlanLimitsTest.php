<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\TenantSubscription;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_configure_extended_plan_limits(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($admin)->post(route('admin.plans.store'), [
            'name' => 'Custom Growth',
            'price_bdt' => 1500,
            'annual_price_bdt' => 15000,
            'max_products' => 200,
            'max_warehouses' => 2,
            'max_users' => 5,
            'max_quotations' => 25,
            'max_sales_orders' => 100,
            'max_purchase_orders' => 40,
            'max_projects' => 4,
            'max_employees' => 8,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $plan = Plan::where('name', 'Custom Growth')->firstOrFail();
        $this->assertSame(25, $plan->max_quotations);
        $this->assertSame(100, $plan->max_sales_orders);
        $this->assertSame(40, $plan->max_purchase_orders);
        $this->assertSame(4, $plan->max_projects);
        $this->assertSame(8, $plan->max_employees);

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), [
            'name' => 'Custom Growth',
            'price_bdt' => 1500,
            'annual_price_bdt' => 15000,
            'max_products' => 200,
            'max_warehouses' => 2,
            'max_users' => 5,
            'max_quotations' => 30,
            'max_sales_orders' => -1,
            'max_purchase_orders' => 50,
            'max_projects' => 6,
            'max_employees' => 12,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $plan->refresh();
        $this->assertSame(30, $plan->max_quotations);
        $this->assertSame(-1, $plan->max_sales_orders);
        $this->assertSame(50, $plan->max_purchase_orders);
        $this->assertSame(6, $plan->max_projects);
        $this->assertSame(12, $plan->max_employees);
    }

    public function test_extended_plan_limits_block_resource_creation_when_quota_is_reached(): void
    {
        $plan = Plan::create([
            'name' => 'Strict Tier',
            'slug' => 'strict-tier',
            'price_bdt' => 999,
            'annual_price_bdt' => 9990,
            'max_products' => 10,
            'max_warehouses' => 1,
            'max_users' => 2,
            'max_quotations' => 1,
            'max_sales_orders' => 1,
            'max_purchase_orders' => 1,
            'max_projects' => 1,
            'max_employees' => 1,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        TenantSubscription::create([
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(29),
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Main Hub',
            'code' => 'WH-1',
            'is_default' => true,
            'is_active' => true,
        ]);

        $product = Product::create([
            'team_id' => $team->id,
            'name' => 'Test Item',
            'sku' => 'SKU-001',
            'unit' => 'pcs',
            'cost_price' => 100,
            'selling_price' => 150,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'Customer A',
        ]);

        $vendor = Supplier::create([
            'team_id' => $team->id,
            'name' => 'Vendor A',
        ]);

        // Seed 1 record for each limited resource
        Quotation::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-001',
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'subtotal' => 150,
            'grand_total' => 150,
        ]);

        SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'SO-001',
            'order_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 150,
            'grand_total' => 150,
        ]);

        PurchaseOrder::create([
            'team_id' => $team->id,
            'supplier_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'PO-001',
            'order_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 100,
            'grand_total' => 100,
        ]);

        $client = Client::create([
            'team_id' => $team->id,
            'name' => 'Client A',
        ]);

        Project::create([
            'team_id' => $team->id,
            'client_id' => $client->id,
            'project_code' => 'PRJ-001',
            'name' => 'Project One',
            'status' => 'planning',
        ]);

        Employee::create([
            'team_id' => $team->id,
            'employee_code' => 'EMP-001',
            'name' => 'Staff One',
            'designation' => 'Cashier',
            'basic_salary' => 10000,
            'status' => 'active',
        ]);

        $service = app(SubscriptionService::class);
        $this->assertFalse($service->canAddQuotation($team));
        $this->assertFalse($service->canAddSalesOrder($team));
        $this->assertFalse($service->canAddPurchaseOrder($team));
        $this->assertFalse($service->canAddProject($team));
        $this->assertFalse($service->canAddEmployee($team));

        // Controller enforcement checks
        $this->actingAs($user)
            ->post(route('quotations.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'quotation_date' => now()->toDateString(),
                'valid_until' => now()->addDays(7)->toDateString(),
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 150]],
            ])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('sales.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->toDateString(),
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 150]],
            ])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->postJson(route('pos.store'), [
                'warehouse_id' => $warehouse->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 150]],
                'subtotal' => 150,
            ])
            ->assertStatus(403);

        $this->actingAs($user)
            ->post(route('purchases.store'), [
                'vendor_mode' => 'existing',
                'vendor_id' => $vendor->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->toDateString(),
                'items' => [['product_id' => $product->id, 'quantity_ordered' => 1, 'unit_cost' => 100]],
            ])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'client_mode' => 'existing',
                'customer_id' => $customer->id,
                'name' => 'Second Project',
                'status' => 'planning',
            ])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('employees.store'), [
                'name' => 'Second Staff',
                'designation' => 'Manager',
                'basic_salary' => 20000,
                'status' => 'active',
            ])
            ->assertSessionHas('error');

        // Verify counts did not exceed 1
        $this->assertSame(1, Quotation::where('team_id', $team->id)->count());
        $this->assertSame(1, SalesOrder::where('team_id', $team->id)->count());
        $this->assertSame(1, PurchaseOrder::where('team_id', $team->id)->count());
        $this->assertSame(1, Project::where('team_id', $team->id)->count());
        $this->assertSame(1, Employee::where('team_id', $team->id)->count());
    }
}
