<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReportInertiaTest extends TestCase
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

    public function test_reports_page_renders_with_correct_props(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('valuation')
            ->has('warehouses')
            ->has('categories')
            ->has('lowStockItems')
            ->has('movements')
            ->where('activeTab', 'valuation')
        );
    }

    public function test_stock_valuation_props_reflect_accurate_inventory_aggregates(): void
    {
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
        $this->stockService->stockIn($this->warehouse->id, $product1->id, 10, 50.00, null, null, null, 'Opening stock');
        $this->stockService->stockIn($this->warehouse->id, $product2->id, 5, 20.00, null, null, null, 'Opening stock');

        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('valuation.total_units', 15)
            ->where('valuation.total_cost', 600)
            ->where('valuation.total_retail', 1200)
            ->where('valuation.potential_profit', 600)
            ->where('valuation.margin_percent', 50)
        );
    }

    public function test_low_stock_filter_detects_items_at_or_below_reorder_level(): void
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
            ->where('lowStockItems.0.deficit', 7)
        );
    }

    public function test_stock_movements_ledger_can_be_filtered(): void
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

        // Request filtered by movement_type = adjustment
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

    public function test_multi_tenant_isolation_in_inertia_reports(): void
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

        // Now test our user - should see empty/zero
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('valuation.total_units', 0)
            ->where('valuation.total_cost', 0)
            ->where('valuation.warehouse_breakdown', [])
            ->has('lowStockItems', 0)
            ->has('movements.data', 0)
        );
    }

    public function test_stock_movements_can_be_filtered_by_date_preset(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Impact Driver',
            'sku' => 'IMP-400',
            'cost_price' => 75.00,
            'selling_price' => 140.00,
            'unit_price' => 140.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
        $this->stockService->stockIn($this->warehouse->id, $product->id, 10, 75.00, null, null, null, 'Today shipment');

        // Test with date_preset = today
        $response = $this->actingAs($this->user)->get(route('reports.index', [
            'tab' => 'movements',
            'date_preset' => 'today',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('movements.data', 1)
            ->where('filters.date_preset', 'today')
        );
    }

    public function test_user_can_download_excel_reports(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Jigsaw Pro',
            'sku' => 'JIG-100',
            'cost_price' => 60.00,
            'selling_price' => 120.00,
            'unit_price' => 120.00,
            'reorder_level' => 15,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
        $this->stockService->stockIn($this->warehouse->id, $product->id, 5, 60.00, null, null, null, 'Opening Jigsaw');

        // 1. Valuation Excel
        $valResp = $this->actingAs($this->user)->get(route('reports.excel', ['tab' => 'valuation']));
        $valResp->assertStatus(200);
        $this->assertTrue(str_contains($valResp->headers->get('content-disposition'), 'stock-valuation-'));

        // 2. Low Stock Excel
        $lowResp = $this->actingAs($this->user)->get(route('reports.excel', ['tab' => 'low_stock']));
        $lowResp->assertStatus(200);
        $this->assertTrue(str_contains($lowResp->headers->get('content-disposition'), 'low-stock-radar-'));

        // 3. Movements Excel
        $movResp = $this->actingAs($this->user)->get(route('reports.excel', ['tab' => 'movements']));
        $movResp->assertStatus(200);
        $this->assertTrue(str_contains($movResp->headers->get('content-disposition'), 'stock-movements-'));

        // 4. Expiry Excel
        $expResp = $this->actingAs($this->user)->get(route('reports.excel', ['tab' => 'expiry']));
        $expResp->assertStatus(200);
        $this->assertTrue(str_contains($expResp->headers->get('content-disposition'), 'batch-expiry-radar-'));

        // 5. Aging Excel
        $agingResp = $this->actingAs($this->user)->get(route('reports.excel', ['tab' => 'aging']));
        $agingResp->assertStatus(200);
        $this->assertTrue(str_contains($agingResp->headers->get('content-disposition'), 'stock-aging-deadstock-'));

        // 6. Profitability Excel
        $profResp = $this->actingAs($this->user)->get(route('reports.excel', ['tab' => 'profitability']));
        $profResp->assertStatus(200);
        $this->assertTrue(str_contains($profResp->headers->get('content-disposition'), 'sales-profitability-report-'));
    }

    public function test_batch_expiry_radar_props_and_filters(): void
    {
        $supplier = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Pharma Supply Co',
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'team_id' => $this->team->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'PO-TEST-EXP-01',
            'status' => 'received',
            'order_date' => now()->subDays(30),
            'subtotal' => 1000,
            'total_amount' => 1000,
            'created_by' => $this->user->id,
        ]);

        $productA = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Chemical Solvent A',
            'sku' => 'CHM-001',
            'cost_price' => 25.00,
            'unit_price' => 50.00,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Adhesive B',
            'sku' => 'ADH-002',
            'cost_price' => 15.00,
            'unit_price' => 30.00,
            'is_active' => true,
        ]);

        // Expired batch (-5 days)
        PurchaseOrderItem::create([
            'team_id' => $this->team->id,
            'purchase_order_id' => $po->id,
            'product_id' => $productA->id,
            'batch_number' => 'BATCH-EXP-01',
            'quantity' => 10,
            'received_quantity' => 10,
            'unit_cost' => 25.00,
            'subtotal' => 250.00,
            'expiry_date' => now()->subDays(5)->toDateString(),
        ]);

        // Urgent expiring batch (+15 days)
        PurchaseOrderItem::create([
            'team_id' => $this->team->id,
            'purchase_order_id' => $po->id,
            'product_id' => $productB->id,
            'batch_number' => 'BATCH-URG-02',
            'quantity' => 20,
            'received_quantity' => 20,
            'unit_cost' => 15.00,
            'subtotal' => 300.00,
            'expiry_date' => now()->addDays(15)->toDateString(),
        ]);

        // Fetch expiry tab
        $response = $this->actingAs($this->user)->get(route('reports.index', ['tab' => 'expiry']));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('activeTab', 'expiry')
            ->where('expiryData.summary.total_batches', 2)
            ->where('expiryData.summary.expired_count', 1)
            ->where('expiryData.summary.expiring_30_count', 1)
            ->where('expiryData.summary.at_risk_cost', 550) // 10*25 + 20*15 = 250 + 300 = 550
            ->has('expiryData.items', 2)
        );

        // Filter by status = expired
        $filteredResponse = $this->actingAs($this->user)->get(route('reports.index', [
            'tab' => 'expiry',
            'expiry_status' => 'expired',
        ]));

        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('expiryData.items', 1)
            ->where('expiryData.items.0.batch_number', 'BATCH-EXP-01')
        );
    }

    public function test_stock_aging_and_dead_stock_props_and_filters(): void
    {
        $freshProduct = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Fast Turn Sandpaper',
            'sku' => 'SND-010',
            'cost_price' => 5.00,
            'unit_price' => 10.00,
            'is_active' => true,
        ]);

        $deadProduct = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Legacy Copper Pipe',
            'sku' => 'COP-999',
            'cost_price' => 40.00,
            'unit_price' => 80.00,
            'is_active' => true,
            'created_at' => now()->subDays(120),
        ]);

        $this->actingAs($this->user);
        // Fresh product has recent stock in
        $this->stockService->stockIn($this->warehouse->id, $freshProduct->id, 50, 5.00, null, null, null, 'Stock in today');

        // Dead product has stock in 120 days ago, no recent movement
        $this->stockService->stockIn($this->warehouse->id, $deadProduct->id, 10, 40.00, null, null, null, 'Opening stock 120 days ago');

        // Artificially age the stock movement and product timestamp
        \Illuminate\Support\Facades\DB::table('stock_movements')
            ->where('product_id', $deadProduct->id)
            ->update(['created_at' => now()->subDays(120)]);
        \Illuminate\Support\Facades\DB::table('products')
            ->where('id', $deadProduct->id)
            ->update(['created_at' => now()->subDays(120)]);

        // Fetch aging tab
        $response = $this->actingAs($this->user)->get(route('reports.index', ['tab' => 'aging']));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('activeTab', 'aging')
            ->where('agingData.summary.fresh_count', 1)
            ->where('agingData.summary.dead_count', 1)
            ->where('agingData.summary.dead_stock_cost', 400) // 10 * 40 = 400
            ->has('agingData.items', 2)
        );

        // Filter by bracket = dead
        $filteredResponse = $this->actingAs($this->user)->get(route('reports.index', [
            'tab' => 'aging',
            'aging_bracket' => 'dead',
        ]));

        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('agingData.items', 1)
            ->where('agingData.items.0.sku', 'COP-999')
        );
    }

    public function test_sales_profitability_props_and_margin_calculations(): void
    {
        $customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Acme Corporation',
            'is_active' => true,
        ]);

        $salesOrder = SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'SO-PROF-001',
            'status' => 'completed',
            'payment_status' => 'paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => $this->user->id,
        ]);

        $highMarginProduct = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Premium Laser Level',
            'sku' => 'LSR-999',
            'cost_price' => 20.00,
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        $lowMarginProduct = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $this->category->id,
            'name' => 'Bulk Fasteners',
            'sku' => 'FST-100',
            'cost_price' => 90.00,
            'unit_price' => 100.00,
            'is_active' => true,
        ]);

        // High margin sale: 5 units @ $100, cost $20
        SalesOrderItem::create([
            'team_id' => $this->team->id,
            'sales_order_id' => $salesOrder->id,
            'product_id' => $highMarginProduct->id,
            'quantity' => 5,
            'unit_price' => 100.00,
            'unit_cost' => 20.00,
            'subtotal' => 500.00,
        ]);

        // Low margin sale: 5 units @ $100, cost $90
        SalesOrderItem::create([
            'team_id' => $this->team->id,
            'sales_order_id' => $salesOrder->id,
            'product_id' => $lowMarginProduct->id,
            'quantity' => 5,
            'unit_price' => 100.00,
            'unit_cost' => 90.00,
            'subtotal' => 500.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index', ['tab' => 'profitability']));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->where('activeTab', 'profitability')
            ->where('profitabilityData.summary.total_revenue', 1000)
            ->where('profitabilityData.summary.total_cogs', 550)
            ->where('profitabilityData.summary.total_gross_profit', 450)
            ->where('profitabilityData.summary.overall_margin_percent', 45)
            ->where('profitabilityData.summary.high_count', 1)
            ->where('profitabilityData.summary.low_count', 1)
            ->has('profitabilityData.items', 2)
        );

        // Filter by high performance (>= 40%)
        $filteredResponse = $this->actingAs($this->user)->get(route('reports.index', [
            'tab' => 'profitability',
            'profitability_performance' => 'high',
        ]));

        $filteredResponse->assertStatus(200);
        $filteredResponse->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('profitabilityData.items', 1)
            ->where('profitabilityData.items.0.sku', 'LSR-999')
            ->where('profitabilityData.items.0.margin_percent', 80)
        );
    }
}
