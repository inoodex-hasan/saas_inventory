<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductInertiaTest extends TestCase
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

    public function test_authenticated_tenant_can_view_products_page_with_inertia(): void
    {
        Product::create([
            'team_id' => $this->team->id,
            'name' => 'Mechanical Keyboard',
            'sku' => 'KEY-001',
            'cost_price' => 50.00,
            'selling_price' => 85.00,
            'unit' => 'pcs',
            'reorder_level' => 5,
            'reorder_quantity' => 20,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Mechanical Keyboard')
            ->has('categories')
            ->has('brands')
            ->has('warehouses')
        );
    }

    public function test_tenant_can_create_product_with_opening_stock(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Hub',
            'code' => 'MAIN-01',
            'is_default' => true,
        ]);

        $response = $this->post(route('products.store'), [
            'name' => 'USB-C Fast Charger',
            'sku' => 'CHG-999',
            'barcode' => '8901234567890',
            'unit' => 'pcs',
            'cost_price' => 12.50,
            'selling_price' => 25.00,
            'reorder_level' => 10,
            'reorder_quantity' => 50,
            'warranty_days' => 180,
            'is_serialized' => false,
            'initial_warehouse_id' => $warehouse->id,
            'initial_stock_quantity' => 40,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'name' => 'USB-C Fast Charger',
            'sku' => 'CHG-999',
            'warranty_days' => 180,
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'team_id' => $this->team->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 40,
        ]);
    }

    public function test_tenant_can_create_serialized_product_with_initial_serials(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Hub',
            'code' => 'MAIN-02',
            'is_default' => true,
        ]);

        $response = $this->post(route('products.store'), [
            'name' => 'Dell Latitude Laptop',
            'sku' => 'LAP-DELL-01',
            'unit' => 'pcs',
            'cost_price' => 500.00,
            'selling_price' => 750.00,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'warranty_days' => 365,
            'is_serialized' => true,
            'initial_warehouse_id' => $warehouse->id,
            'initial_stock_quantity' => 3,
            'initial_serials' => "SN-DELL-001\nSN-DELL-002, SN-DELL-003",
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('team_id', $this->team->id)->where('sku', 'LAP-DELL-01')->firstOrFail();
        $this->assertTrue($product->is_serialized);

        $this->assertDatabaseHas('stock_levels', [
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'serial_number' => 'SN-DELL-001',
            'status' => 'in_stock',
            'warehouse_id' => $warehouse->id,
            'warranty_days' => 365,
        ]);

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'serial_number' => 'SN-DELL-002',
            'status' => 'in_stock',
        ]);

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'serial_number' => 'SN-DELL-003',
            'status' => 'in_stock',
        ]);
    }

    public function test_cannot_create_serialized_product_when_serials_count_mismatches_quantity(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Hub',
            'code' => 'MAIN-03',
            'is_default' => true,
        ]);

        // Opening quantity 5, but 6 serial numbers entered
        $response = $this->post(route('products.store'), [
            'name' => 'Logitech Mouse',
            'sku' => 'LOGI-M01',
            'unit' => 'pcs',
            'cost_price' => 20.00,
            'selling_price' => 35.00,
            'reorder_level' => 2,
            'reorder_quantity' => 10,
            'warranty_days' => 180,
            'is_serialized' => true,
            'initial_warehouse_id' => $warehouse->id,
            'initial_stock_quantity' => 5,
            'initial_serials' => "SN-001, SN-002, SN-003, SN-004, SN-005, SN-006",
        ]);

        $response->assertSessionHasErrors(['initial_serials']);
        $this->assertDatabaseMissing('products', [
            'team_id' => $this->team->id,
            'sku' => 'LOGI-M01',
        ]);
        $this->assertDatabaseMissing('product_serials', [
            'serial_number' => 'SN-001',
        ]);
    }

    public function test_tenant_can_update_product(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Old Monitor',
            'sku' => 'MON-001',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'unit' => 'pcs',
            'reorder_level' => 2,
            'reorder_quantity' => 10,
        ]);

        $response = $this->put(route('products.update', $product), [
            'name' => '4K Ultra HD Monitor',
            'sku' => 'MON-001',
            'unit' => 'pcs',
            'cost_price' => 200.00,
            'selling_price' => 320.00,
            'reorder_level' => 5,
            'reorder_quantity' => 15,
        ]);

        $response->assertRedirect(route('products.index'));
        $product->refresh();
        $this->assertEquals('4K Ultra HD Monitor', $product->name);
        $this->assertEquals(200.00, (float) $product->cost_price);
        $this->assertEquals(320.00, (float) $product->selling_price);
    }

    public function test_cannot_delete_product_with_active_warehouse_stock(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Hub',
            'code' => 'MAIN-01',
            'is_default' => true,
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Stocked Item',
            'sku' => 'STK-001',
            'cost_price' => 10.00,
            'selling_price' => 15.00,
            'unit' => 'pcs',
            'reorder_level' => 1,
            'reorder_quantity' => 5,
        ]);

        app(StockService::class)->stockIn($warehouse->id, $product->id, 10, 10.00, null, null, null, 'Initial');

        $response = $this->delete(route('products.destroy', $product));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_can_delete_product_without_stock(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Empty Item',
            'sku' => 'EMP-001',
            'cost_price' => 10.00,
            'selling_price' => 15.00,
            'unit' => 'pcs',
            'reorder_level' => 1,
            'reorder_quantity' => 5,
        ]);

        $response = $this->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_inspect_product_stock_distribution(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Hub Alpha',
            'code' => 'ALPHA-01',
            'is_default' => true,
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Smart Watch',
            'sku' => 'WCH-001',
            'cost_price' => 80.00,
            'selling_price' => 120.00,
            'unit' => 'pcs',
            'reorder_level' => 2,
            'reorder_quantity' => 10,
        ]);

        app(StockService::class)->stockIn($warehouse->id, $product->id, 7, 80.00, null, null, null, 'Init');

        $response = $this->getJson(route('products.inspect', $product));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product',
            'levels' => [
                '*' => [
                    'warehouse_name',
                    'quantity_on_hand',
                    'quantity_reserved',
                    'quantity_available',
                    'valuation',
                ],
            ],
        ]);
        $response->assertJsonPath('levels.0.warehouse_name', 'Hub Alpha');
        $response->assertJsonPath('levels.0.quantity_on_hand', 7);
    }

    public function test_can_create_and_delete_category_and_brand(): void
    {
        // Category
        $catResponse = $this->post(route('products.categories.store'), [
            'name' => 'Smartphones & Mobile',
        ]);
        $catResponse->assertSessionHas('success');
        $category = ProductCategory::where('team_id', $this->team->id)->where('name', 'Smartphones & Mobile')->first();
        $this->assertNotNull($category);

        // Brand
        $brandResponse = $this->post(route('products.brands.store'), [
            'name' => 'Samsung Electronics',
        ]);
        $brandResponse->assertSessionHas('success');
        $brand = Brand::where('team_id', $this->team->id)->where('name', 'Samsung Electronics')->first();
        $this->assertNotNull($brand);

        // Delete empty category and brand
        $this->delete(route('products.categories.destroy', $category))->assertSessionHas('success');
        $this->assertSoftDeleted('product_categories', ['id' => $category->id]);

        $this->delete(route('products.brands.destroy', $brand))->assertSessionHas('success');
        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    }
}
