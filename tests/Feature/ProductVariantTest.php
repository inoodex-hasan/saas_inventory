<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private ProductCategory $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Warehouse Hub',
            'code' => 'WH-MAIN',
            'address' => 'Dhaka Central Hub',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Apparel',
            'slug' => 'apparel',
        ]);

        $this->brand = Brand::create([
            'team_id' => $this->team->id,
            'name' => 'Nike',
            'slug' => 'nike',
        ]);

        app(SubscriptionService::class)->ensureDefaultPlans();
    }

    public function test_can_create_product_with_variants_and_initial_variant_stock(): void
    {
        $payload = [
            'name' => 'Nike Dri-FIT T-Shirt',
            'sku' => 'NK-DRIFIT',
            'barcode' => '890123456789',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'unit' => 'pcs',
            'cost_price' => 500,
            'selling_price' => 950,
            'reorder_level' => 10,
            'reorder_quantity' => 50,
            'warranty_days' => 30,
            'has_variants' => true,
            'initial_warehouse_id' => $this->warehouse->id,
            'variants' => [
                [
                    'name' => 'Black / M',
                    'sku' => 'NK-DRIFIT-BLK-M',
                    'barcode' => '890987654321',
                    'attributes' => ['Color' => 'Black', 'Size' => 'M'],
                    'cost_price' => 500,
                    'selling_price' => 950,
                    'initial_stock_quantity' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Black / L',
                    'sku' => 'NK-DRIFIT-BLK-L',
                    'barcode' => '890987654322',
                    'attributes' => ['Color' => 'Black', 'Size' => 'L'],
                    'cost_price' => 520,
                    'selling_price' => 980,
                    'initial_stock_quantity' => 20,
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('sku', 'NK-DRIFIT')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->has_variants);
        $this->assertEquals(2, $product->variants()->count());

        $v1 = ProductVariant::where('sku', 'NK-DRIFIT-BLK-M')->first();
        $this->assertNotNull($v1);
        $this->assertEquals('Black / M', $v1->name);
        $this->assertEquals(500, $v1->cost_price);
        $this->assertEquals(950, $v1->unit_price);

        // Verify variant opening stock was allocated
        $stock1 = StockLevel::where('product_id', $product->id)
            ->where('product_variant_id', $v1->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($stock1);
        $this->assertEquals(15, (float) $stock1->quantity);

        $v2 = ProductVariant::where('sku', 'NK-DRIFIT-BLK-L')->first();
        $this->assertNotNull($v2);
        $stock2 = StockLevel::where('product_id', $product->id)
            ->where('product_variant_id', $v2->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($stock2);
        $this->assertEquals(20, (float) $stock2->quantity);
    }

    public function test_can_create_serialized_product_with_variants_and_serials(): void
    {
        $payload = [
            'name' => 'iPhone 15 Pro',
            'sku' => 'IPHONE-15-PRO',
            'unit' => 'pcs',
            'cost_price' => 1000,
            'selling_price' => 1200,
            'reorder_level' => 2,
            'reorder_quantity' => 5,
            'warranty_days' => 365,
            'is_serialized' => true,
            'has_variants' => true,
            'initial_warehouse_id' => $this->warehouse->id,
            'variants' => [
                [
                    'name' => 'Titanium / 128GB',
                    'sku' => 'IPH-15-TIT-128',
                    'attributes' => ['Color' => 'Titanium', 'Storage' => '128GB'],
                    'cost_price' => 1000,
                    'selling_price' => 1200,
                    'initial_stock_quantity' => 2,
                    'initial_serials' => "SN-IPH-001\nSN-IPH-002",
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'IPHONE-15-PRO')->firstOrFail();
        $variant = ProductVariant::where('sku', 'IPH-15-TIT-128')->firstOrFail();

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'serial_number' => 'SN-IPH-001',
            'status' => 'in_stock',
        ]);

        $this->assertDatabaseHas('product_serials', [
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'serial_number' => 'SN-IPH-002',
            'status' => 'in_stock',
        ]);
    }

    public function test_cannot_create_variants_with_duplicate_skus(): void
    {
        $payload = [
            'name' => 'Duplicate Test Shirt',
            'sku' => 'DUP-TEST',
            'unit' => 'pcs',
            'cost_price' => 200,
            'selling_price' => 400,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'has_variants' => true,
            'variants' => [
                [
                    'name' => 'Red / S',
                    'sku' => 'SAME-SKU-123',
                    'cost_price' => 200,
                    'selling_price' => 400,
                ],
                [
                    'name' => 'Blue / S',
                    'sku' => 'SAME-SKU-123',
                    'cost_price' => 200,
                    'selling_price' => 400,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('products.store'), $payload);
        $response->assertSessionHasErrors('variants');
    }

    public function test_can_update_product_variants(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Polo Shirt',
            'sku' => 'POLO-BASE',
            'unit' => 'pcs',
            'cost_price' => 400,
            'unit_price' => 800,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $v1 = ProductVariant::create([
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'name' => 'Navy / S',
            'sku' => 'POLO-NVY-S',
            'cost_price' => 400,
            'unit_price' => 800,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Polo Shirt Premium',
            'sku' => 'POLO-BASE',
            'unit' => 'pcs',
            'cost_price' => 450,
            'selling_price' => 850,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'has_variants' => true,
            'variants' => [
                [
                    'id' => $v1->id,
                    'name' => 'Navy Blue / Small',
                    'sku' => 'POLO-NVY-S',
                    'cost_price' => 450,
                    'selling_price' => 850,
                    'is_active' => true,
                ],
                [
                    'id' => null,
                    'name' => 'Navy Blue / Medium',
                    'sku' => 'POLO-NVY-M',
                    'cost_price' => 460,
                    'selling_price' => 870,
                    'is_active' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('products.update.post', $product->id), $payload);

        $response->assertRedirect(route('products.index'));

        $v1->refresh();
        $this->assertEquals('Navy Blue / Small', $v1->name);
        $this->assertEquals(450, $v1->cost_price);

        $v2 = ProductVariant::where('sku', 'POLO-NVY-M')->first();
        $this->assertNotNull($v2);
        $this->assertEquals('Navy Blue / Medium', $v2->name);
        $this->assertEquals(2, $product->variants()->count());
    }

    public function test_barcode_lookup_returns_variant_information(): void
    {
        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Sneakers',
            'sku' => 'SNK-01',
            'unit' => 'pcs',
            'cost_price' => 1200,
            'unit_price' => 2500,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
            'has_variants' => true,
        ]);

        $variant = ProductVariant::create([
            'team_id' => $this->team->id,
            'product_id' => $product->id,
            'name' => 'White / 42',
            'sku' => 'SNK-01-WHT-42',
            'barcode' => '778899001122',
            'cost_price' => 1200,
            'unit_price' => 2500,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('products.lookup-barcode', ['barcode' => '778899001122']));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'product' => [
                'sku' => 'SNK-01-WHT-42',
                'barcode' => '778899001122',
            ],
        ]);
    }
}
