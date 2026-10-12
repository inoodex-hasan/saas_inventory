<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'categories')
            ->set('category_name', 'Office Supplies')
            ->set('category_description', 'Pens, paper, and staplers')
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->assertSee('Office Supplies');

        $this->assertDatabaseHas('product_categories', [
            'team_id' => $this->team->id,
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
            'description' => 'Pens, paper, and staplers',
        ]);

        $category = ProductCategory::where('name', 'Office Supplies')->first();

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'categories')
            ->call('editCategory', $category->id)
            ->assertSet('category_name', 'Office Supplies')
            ->set('category_name', 'Office & Desk Supplies')
            ->call('saveCategory')
            ->assertHasNoErrors();

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

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'categories')
            ->call('deleteCategory', $category->id)
            ->assertSee('Cannot delete category');

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);

        // Remove product and delete should succeed
        $product->delete();

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'categories')
            ->call('deleteCategory', $category->id);

        $this->assertSoftDeleted('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_can_create_and_edit_brand(): void
    {
        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'brands')
            ->set('brand_name', 'Logitech')
            ->set('brand_description', 'Keyboards and mice')
            ->call('saveBrand')
            ->assertHasNoErrors()
            ->assertSee('Logitech');

        $this->assertDatabaseHas('brands', [
            'team_id' => $this->team->id,
            'name' => 'Logitech',
            'slug' => 'logitech',
            'description' => 'Keyboards and mice',
        ]);

        $brand = Brand::where('name', 'Logitech')->first();

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'brands')
            ->call('editBrand', $brand->id)
            ->assertSet('brand_name', 'Logitech')
            ->set('brand_name', 'Logitech G')
            ->call('saveBrand')
            ->assertHasNoErrors();

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

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'brands')
            ->call('deleteBrand', $brand->id)
            ->assertSee('Cannot delete brand');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'deleted_at' => null,
        ]);

        $product->delete();

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'brands')
            ->call('deleteBrand', $brand->id);

        $this->assertSoftDeleted('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_quick_add_category_and_brand_directly_in_product_modal(): void
    {
        Livewire::test(ProductManager::class)
            ->call('create')
            ->call('toggleQuickAddCategory')
            ->set('quick_category_name', 'Quick Gaming Gear')
            ->call('quickAddCategory')
            ->assertHasNoErrors()
            ->assertSet('showQuickAddCategory', false)
            ->call('toggleQuickAddBrand')
            ->set('quick_brand_name', 'Razer')
            ->call('quickAddBrand')
            ->assertHasNoErrors()
            ->assertSet('showQuickAddBrand', false)
            ->set('name', 'Razer DeathAdder')
            ->set('cost_price', 30)
            ->set('selling_price', 69.99)
            ->set('reorder_level', 5)
            ->set('reorder_quantity', 20)
            ->call('save')
            ->assertHasNoErrors();

        $category = ProductCategory::where('name', 'Quick Gaming Gear')->firstOrFail();
        $brand = Brand::where('name', 'Razer')->firstOrFail();

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

        ProductCategory::create([
            'team_id' => $otherTeam->id,
            'name' => 'Secret Other Category',
            'slug' => 'secret-other-category',
        ]);

        Brand::create([
            'team_id' => $otherTeam->id,
            'name' => 'Secret Other Brand',
            'slug' => 'secret-other-brand',
        ]);

        Livewire::test(ProductManager::class)
            ->call('openCatBrandModal', 'categories')
            ->assertDontSee('Secret Other Category')
            ->call('switchCatBrandTab', 'brands')
            ->assertDontSee('Secret Other Brand');
    }
}
