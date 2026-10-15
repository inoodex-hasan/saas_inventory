<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InventoryReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected Warehouse $warehouse;
    protected ProductCategory $category;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->stockService = app(StockService::class);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Distribution Hub',
            'code' => 'MDH-01',
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Industrial Tools',
        ]);
    }

    public function test_reports_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('valuation')
            ->has('lowStockItems')
            ->has('movements')
        );
    }

    public function test_stock_valuation_calculates_cost_and_retail_accurately(): void
    {
        // Product 1: 10 units @ cost $50, selling $100 = $500 cost, $1000 retail
        $product1 = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Cordless Drill',
            'sku' => 'DRL-001',
            'cost_price' => 50.00,
            'selling_price' => 100.00,
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        // Product 2: 5 units @ cost $20, selling $40 = $100 cost, $200 retail
        $product2 = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Safety Goggles',
            'sku' => 'SFT-002',
            'cost_price' => 20.00,
            'selling_price' => 40.00,
            'unit_price' => 40.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->stockService->stockIn($this->warehouse->id, $product1->id, 10, 50.00, null, null, null, 'Opening inventory');
        $this->stockService->stockIn($this->warehouse->id, $product2->id, 5, 20.00, null, null, null, 'Opening inventory');

        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('valuation.total_cost', 600)
            ->where('valuation.total_retail', 1200)
            ->where('valuation.total_units', 15)
        );
    }

    public function test_low_stock_tab_detects_items_at_or_below_reorder_level(): void
    {
        $lowStockProduct = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Heavy Duty Grinder',
            'sku' => 'GRD-900',
            'cost_price' => 80.00,
            'selling_price' => 150.00,
            'unit_price' => 150.00,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $healthyProduct = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Measuring Tape',
            'sku' => 'MSR-100',
            'cost_price' => 5.00,
            'selling_price' => 12.00,
            'unit_price' => 12.00,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->stockService->stockIn($this->warehouse->id, $lowStockProduct->id, 3, 80.00, null, null, null, 'Initial stock');
        $this->stockService->stockIn($this->warehouse->id, $healthyProduct->id, 20, 5.00, null, null, null, 'Initial stock');

        $response = $this->actingAs($this->user)->get(route('reports.index', ['tab' => 'low_stock']));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('lowStockItems', 1)
            ->where('lowStockItems.0.product.name', 'Heavy Duty Grinder')
            ->where('lowStockItems.0.product.sku', 'GRD-900')
            ->where('lowStockItems.0.deficit', 7)
        );
    }

    public function test_stock_movement_ledger_filters_by_type_and_warehouse(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Rotary Hammer',
            'sku' => 'HAM-500',
            'cost_price' => 110.00,
            'selling_price' => 200.00,
            'unit_price' => 200.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->stockService->stockIn($this->warehouse->id, $product->id, 25, 110.00, null, null, null, 'Inbound stock delivery');
        $this->stockService->stockOut($this->warehouse->id, $product->id, 5, null, null, null, 'Customer store pickup');
        $this->stockService->adjust($this->warehouse->id, $product->id, 18, 'Annual physical stock audit count');

        $response = $this->actingAs($this->user)->get(route('reports.index', [
            'tab' => 'movements',
            'movement_type' => 'adjustment',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('movements.data', 1)
            ->where('movements.data.0.reason', 'Annual physical stock audit count')
        );
    }

    public function test_multi_tenant_isolation_in_reports(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $otherWarehouse = Warehouse::create([
            'team_id' => $otherTeam->id,
            'name' => 'Competitor Secret Facility',
            'code' => 'SEC-99',
            'is_active' => true,
        ]);

        $otherProduct = Product::create([
            'team_id' => $otherTeam->id,
            'name' => 'Classified Prototype Device',
            'sku' => 'CLS-999',
            'cost_price' => 5000.00,
            'selling_price' => 10000.00,
            'unit_price' => 10000.00,
            'reorder_level' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($otherUser);
        $this->stockService->stockIn($otherWarehouse->id, $otherProduct->id, 2, 5000.00, null, null, null, 'Confidential Tenant B Stock In');

        // Login as our tenant and verify we NEVER see Tenant B's data
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('valuation.warehouse_breakdown', [])
            ->has('lowStockItems', 0)
            ->has('movements.data', 0)
        );
    }
}
