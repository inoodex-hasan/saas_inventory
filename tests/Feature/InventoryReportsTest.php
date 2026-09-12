<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Livewire\ReportManager;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        $response->assertSeeLivewire(ReportManager::class);
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

        Livewire::actingAs($this->user)
            ->test(ReportManager::class)
            ->assertSee('$600.00') // Total Cost ($500 + $100)
            ->assertSee('$1,200.00') // Total Retail ($1000 + $200)
            ->assertSee('15'); // Total Units (10 + 5)
    }

    public function test_low_stock_tab_detects_items_at_or_below_reorder_level(): void
    {
        // Low stock product: reorder_level 10, current stock 3
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

        // Healthy product: reorder_level 5, current stock 20
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

        Livewire::actingAs($this->user)
            ->test(ReportManager::class)
            ->set('activeTab', 'low_stock')
            ->assertSee('Heavy Duty Grinder')
            ->assertSee('GRD-900')
            ->assertSee('-7') // Deficit: 10 - 3 = 7
            ->assertDontSee('Measuring Tape');
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

        // Verify movement tab shows movements
        Livewire::actingAs($this->user)
            ->test(ReportManager::class)
            ->set('activeTab', 'movements')
            ->assertSee('Inbound stock delivery')
            ->assertSee('Customer store pickup')
            ->assertSee('Annual physical stock audit count')
            ->set('movementType', 'adjustment')
            ->assertSee('Annual physical stock audit count')
            ->assertDontSee('Inbound stock delivery');
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
        Livewire::actingAs($this->user)
            ->test(ReportManager::class)
            ->assertDontSee('Competitor Secret Facility')
            ->assertDontSee('Classified Prototype Device')
            ->assertDontSee('$10,000.00')
            ->set('activeTab', 'low_stock')
            ->assertDontSee('Classified Prototype Device')
            ->set('activeTab', 'movements')
            ->assertDontSee('Confidential Tenant B Stock In');
    }
}
