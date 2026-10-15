<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\StockLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DedicatedProductPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;

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
            'name' => 'Central Hub',
            'code' => 'CH-01',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_user_can_render_dedicated_create_product_page(): void
    {
        $response = $this->get(route('products.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Products/Create')
            ->has('warehouses')
        );
    }

    public function test_user_can_create_product_with_opening_stock_allocation(): void
    {
        $response = $this->post(route('products.store'), [
            'name' => 'Mechanical Gaming Keyboard',
            'sku' => 'KB-MECH-01',
            'cost_price' => 1200,
            'selling_price' => 1800,
            'unit' => 'pcs',
            'reorder_level' => 15,
            'reorder_quantity' => 50,
            'warranty_days' => 365,
            'is_serialized' => true,
            'initial_warehouse_id' => $this->warehouse->id,
            'initial_stock_quantity' => 25,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'name' => 'Mechanical Gaming Keyboard',
            'sku' => 'KB-MECH-01',
            'cost_price' => 1200,
            'unit_price' => 1800,
            'warranty_days' => 365,
            'is_serialized' => true,
        ]);

        $product = Product::where('sku', 'KB-MECH-01')->first();
        $this->assertNotNull($product);

        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(25, $stock->quantity);
    }

    public function test_user_can_quick_add_category_and_brand_inline(): void
    {
        $this->post(route('products.categories.store'), [
            'name' => 'Peripherals',
        ]);

        $this->post(route('products.brands.store'), [
            'name' => 'Keychron',
        ]);

        $this->assertDatabaseHas('product_categories', [
            'team_id' => $this->team->id,
            'name' => 'Peripherals',
        ]);

        $this->assertDatabaseHas('brands', [
            'team_id' => $this->team->id,
            'name' => 'Keychron',
        ]);
    }

    public function test_user_can_render_and_update_product_via_dedicated_edit_page(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Logitech MX Master 3S',
            'sku' => 'LOGI-MX3S',
            'cost_price' => 6000,
            'selling_price' => 8500,
            'unit_price' => 8500,
            'unit' => 'pcs',
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'warranty_days' => 730,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.edit', $product->id));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Products/Edit')
            ->where('product.sku', 'LOGI-MX3S')
        );

        $updateResponse = $this->put(route('products.update', $product->id), [
            'name' => 'Logitech MX Master 3S Graphite',
            'sku' => 'LOGI-MX3S',
            'cost_price' => 6000,
            'selling_price' => 9000,
            'unit' => 'pcs',
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'warranty_days' => 730,
            'is_active' => true,
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Logitech MX Master 3S Graphite',
            'unit_price' => 9000,
        ]);
    }
}
