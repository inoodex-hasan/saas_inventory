<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAndBrandCrudTest extends TestCase
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

    public function test_can_create_and_edit_category(): void
    {
        $response = $this->post(route('products.categories.store'), [
            'name' => 'Office Supplies',
            'description' => 'Pens, paper, and staplers',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_categories', [
            'team_id' => $this->team->id,
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
            'description' => 'Pens, paper, and staplers',
        ]);

        $category = ProductCategory::where('name', 'Office Supplies')->first();

        $updateResponse = $this->put(route('products.categories.update', $category->id), [
            'name' => 'Office & Desk Supplies',
            'description' => 'Updated description',
        ]);

        $updateResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Office & Desk Supplies',
        ]);
    }

    public function test_cannot_delete_category_with_assigned_products(): void
    {
        $category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $category->id,
            'name' => 'Hammer',
            'sku' => 'HAM-01',
            'unit' => 'pcs',
            'cost_price' => 10,
            'selling_price' => 15,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
        ]);

        $deleteResponse = $this->delete(route('products.categories.destroy', $category->id));
        $deleteResponse->assertSessionHas('error');

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);

        // Remove product and delete should succeed
        $product->delete();

        $deleteResponseSuccess = $this->delete(route('products.categories.destroy', $category->id));
        $deleteResponseSuccess->assertSessionHas('success');

        $this->assertSoftDeleted('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_can_create_and_edit_brand(): void
    {
        $response = $this->post(route('products.brands.store'), [
            'name' => 'Logitech',
            'description' => 'Keyboards and mice',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('brands', [
            'team_id' => $this->team->id,
            'name' => 'Logitech',
            'slug' => 'logitech',
            'description' => 'Keyboards and mice',
        ]);

        $brand = Brand::where('name', 'Logitech')->first();

        $updateResponse = $this->put(route('products.brands.update', $brand->id), [
            'name' => 'Logitech G',
            'description' => 'Gaming gear',
        ]);

        $updateResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Logitech G',
        ]);
    }

    public function test_cannot_delete_brand_with_assigned_products(): void
    {
        $brand = Brand::create([
            'team_id' => $this->team->id,
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'brand_id' => $brand->id,
            'name' => 'Anvil',
            'sku' => 'ANV-01',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 100,
            'reorder_level' => 2,
            'reorder_quantity' => 10,
        ]);

        $deleteResponse = $this->delete(route('products.brands.destroy', $brand->id));
        $deleteResponse->assertSessionHas('error');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'deleted_at' => null,
        ]);

        $product->delete();

        $deleteSuccess = $this->delete(route('products.brands.destroy', $brand->id));
        $deleteSuccess->assertSessionHas('success');

        $this->assertSoftDeleted('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_quick_add_category_and_brand_directly_creates_entities(): void
    {
        $catResponse = $this->post(route('products.categories.store'), [
            'name' => 'Quick Gaming Gear',
        ]);
        $catResponse->assertSessionHasNoErrors();

        $brandResponse = $this->post(route('products.brands.store'), [
            'name' => 'Razer',
        ]);
        $brandResponse->assertSessionHasNoErrors();

        $category = ProductCategory::where('name', 'Quick Gaming Gear')->firstOrFail();
        $brand = Brand::where('name', 'Razer')->firstOrFail();

        $productResponse = $this->post(route('products.store'), [
            'name' => 'Razer DeathAdder',
            'sku' => 'RZR-DA-01',
            'unit' => 'pcs',
            'cost_price' => 30,
            'selling_price' => 69.99,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $productResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'team_id' => $this->team->id,
            'name' => 'Razer DeathAdder',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_tenant_isolation_for_categories_and_brands(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $otherCat = ProductCategory::create([
            'team_id' => $otherTeam->id,
            'name' => 'Secret Other Category',
            'slug' => 'secret-other-category',
        ]);

        $otherBrand = Brand::create([
            'team_id' => $otherTeam->id,
            'name' => 'Secret Other Brand',
            'slug' => 'secret-other-brand',
        ]);

        // Attempting to delete or update other tenant's category/brand returns 404 due to tenant scoping
        $catResponse = $this->delete(route('products.categories.destroy', $otherCat->id));
        $catResponse->assertStatus(404);

        $brandResponse = $this->delete(route('products.brands.destroy', $otherBrand->id));
        $brandResponse->assertStatus(404);
    }
}
