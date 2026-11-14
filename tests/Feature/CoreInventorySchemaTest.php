<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreInventorySchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_create_core_inventory_entities_with_tenant_isolation(): void
    {
        $creator = new CreateNewUser();

        // 1. Setup Tenant A
        $userA = $creator->create([
            'name' => 'Alice Tenant',
            'email' => 'alice@inventory.test',
            'phone' => '01711000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->actingAs($userA);

        $warehouseA = Warehouse::create([
            'name' => 'Main Dhaka Warehouse',
            'code' => 'WH-DHK',
            'is_default' => true,
        ]);
        $this->assertEquals($userA->current_team_id, $warehouseA->team_id);

        $categoryA = ProductCategory::create([
            'name' => 'Laptops & Computers',
        ]);
        $this->assertEquals('laptops-computers', $categoryA->slug);

        $brandA = Brand::create([
            'name' => 'Apple',
        ]);

        $productA = Product::create([
            'category_id' => $categoryA->id,
            'brand_id' => $brandA->id,
            'name' => 'MacBook Pro 16"',
            'sku' => 'MBP-16-M3',
            'cost_price' => 2200.00,
            'unit_price' => 2700.00,
            'reorder_level' => 5,
        ]);
        $this->assertEquals($userA->current_team_id, $productA->team_id);

        $variantA = ProductVariant::create([
            'product_id' => $productA->id,
            'name' => 'Space Black / 36GB / 512GB',
            'sku' => 'MBP-16-BLK-36-512',
            'unit_price' => 2900.00,
            'attributes' => ['color' => 'Space Black', 'ram' => '36GB', 'ssd' => '512GB'],
        ]);

        $supplierA = Supplier::create([
            'name' => 'Tech Wholesale Ltd',
            'email' => 'contact@techwholesale.test',
        ]);

        // Record stock movement (IN)
        $movementA = StockMovement::create([
            'warehouse_id' => $warehouseA->id,
            'product_id' => $productA->id,
            'product_variant_id' => $variantA->id,
            'type' => 'in',
            'quantity' => 20,
            'unit_cost' => 2200.00,
            'reason' => 'Initial PO Receipt',
            'created_by' => $userA->id,
        ]);

        // Stock level record
        $levelA = StockLevel::create([
            'warehouse_id' => $warehouseA->id,
            'product_id' => $productA->id,
            'product_variant_id' => $variantA->id,
            'quantity' => 20,
            'reserved_quantity' => 2,
        ]);

        $this->assertEquals(18, $levelA->available_quantity);
        $this->assertEquals(20, $productA->fresh()->total_stock);

        // 2. Setup Tenant B
        $userB = $creator->create([
            'name' => 'Bob Tenant',
            'email' => 'bob@inventory.test',
            'phone' => '01711000002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->actingAs($userB);

        // Assert Tenant B sees 0 products / warehouses
        $this->assertEquals(0, Warehouse::count());
        $this->assertEquals(0, ProductCategory::count());
        $this->assertEquals(0, Brand::count());
        $this->assertEquals(0, Product::count());
        $this->assertEquals(0, Supplier::count());
        $this->assertEquals(0, StockMovement::count());
        $this->assertEquals(0, StockLevel::count());

        // Assert Tenant B can reuse the same SKU without database unique violation
        $productB = Product::create([
            'name' => 'Tenant B MacBook',
            'sku' => 'MBP-16-M3', // Same SKU as Tenant A!
            'cost_price' => 2100.00,
            'unit_price' => 2600.00,
        ]);
        $this->assertEquals($userB->current_team_id, $productB->team_id);
        $this->assertEquals(1, Product::count());
    }
}
