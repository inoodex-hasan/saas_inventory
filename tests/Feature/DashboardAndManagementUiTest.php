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

class DashboardAndManagementUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->actingAs($this->user);
    }

    public function test_dashboard_renders_successfully(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'code' => 'MAIN-01',
            'name' => 'Main Hub',
            'is_default' => true,
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Ergonomic Desk',
            'sku' => 'DESK-001',
            'cost_price' => 150.00,
            'selling_price' => 299.00,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
        ]);

        $stockService = app(StockService::class);
        $stockService->stockIn($warehouse->id, $product->id, 15, 150.00, null, null, null, 'Init stock');

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('warehouseStockBreakdown')
            ->where('warehouseStockBreakdown.0.name', 'Main Hub')
        );
    }

    public function test_can_create_and_manage_warehouses(): void
    {
        $response = $this->post(route('warehouses.store'), [
            'code' => 'ORD-02',
            'name' => 'O\'Hare Distribution Hub',
            'city' => 'Chicago',
            'state' => 'IL',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('warehouses', [
            'team_id' => $this->team->id,
            'code' => 'ORD-02',
            'name' => 'O\'Hare Distribution Hub',
        ]);
    }

    public function test_can_create_product_via_controller(): void
    {
        $category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Electronics',
            'code' => 'ELEC',
        ]);

        $response = $this->post(route('products.store'), [
            'name' => 'Mechanical Keyboard',
            'sku' => 'ELEC-KEY-001',
            'category_id' => $category->id,
            'cost_price' => 45.00,
            'selling_price' => 89.99,
            'unit' => 'pcs',
            'reorder_level' => 10,
            'reorder_quantity' => 50,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'name' => 'Mechanical Keyboard',
            'category_id' => $category->id,
            'cost_price' => 45.00,
        ]);
    }
}
