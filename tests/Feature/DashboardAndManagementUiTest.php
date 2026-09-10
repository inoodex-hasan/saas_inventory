<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Livewire\WarehouseManager;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        $response->assertSee('Operational Dashboard');
        $response->assertSee('Inventory Value');
        $response->assertSee('Main Hub');
    }

    public function test_warehouse_manager_livewire_can_create_and_manage_warehouses(): void
    {
        Livewire::test(WarehouseManager::class)
            ->set('code', 'ORD-02')
            ->set('name', 'O\'Hare Distribution Hub')
            ->set('city', 'Chicago')
            ->set('state', 'IL')
            ->set('is_default', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('O\'Hare Distribution Hub');

        $this->assertDatabaseHas('warehouses', [
            'team_id' => $this->team->id,
            'code' => 'ORD-02',
            'name' => 'O\'Hare Distribution Hub',
        ]);
    }

    public function test_product_manager_livewire_can_create_and_generate_sku(): void
    {
        $category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Electronics',
            'code' => 'ELEC',
        ]);

        Livewire::test(ProductManager::class)
            ->set('name', 'Mechanical Keyboard')
            ->call('generateSku')
            ->set('category_id', $category->id)
            ->set('cost_price', 45.00)
            ->set('selling_price', 89.99)
            ->set('reorder_level', 10)
            ->set('reorder_quantity', 50)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Mechanical Keyboard');

        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'name' => 'Mechanical Keyboard',
            'category_id' => $category->id,
            'cost_price' => 45.00,
        ]);
    }
}
