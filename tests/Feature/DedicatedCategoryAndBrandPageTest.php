<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DedicatedCategoryAndBrandPageTest extends TestCase
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

    public function test_categories_index_page_renders_with_inertia(): void
    {
        $parent = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
        ]);

        $child = ProductCategory::create([
            'team_id' => $this->team->id,
            'parent_id' => $parent->id,
            'name' => 'Processors',
            'slug' => 'processors',
        ]);

        $response = $this->get(route('categories.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Categories/Index')
            ->has('categories.data', 2)
            ->has('metrics')
            ->where('metrics.total_categories', 2)
            ->where('metrics.root_categories', 1)
            ->where('metrics.sub_categories', 1)
            ->has('parentOptions')
        );
    }

    public function test_category_can_be_created_updated_and_deleted_via_dedicated_routes(): void
    {
        // 1. Create
        $response = $this->post(route('categories.store'), [
            'name' => 'Storage Drives',
            'description' => 'SSDs and HDDs',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_categories', [
            'team_id' => $this->team->id,
            'name' => 'Storage Drives',
            'slug' => 'storage-drives',
        ]);

        $category = ProductCategory::where('name', 'Storage Drives')->first();

        // 2. Update
        $updateResponse = $this->put(route('categories.update', $category->id), [
            'name' => 'Fast Storage Drives',
            'description' => 'Updated SSDs',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Fast Storage Drives',
        ]);

        // 3. Delete
        $deleteResponse = $this->delete(route('categories.destroy', $category->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertSoftDeleted('product_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_brands_index_page_renders_with_inertia(): void
    {
        Brand::create([
            'team_id' => $this->team->id,
            'name' => 'Logitech',
            'slug' => 'logitech',
        ]);

        $response = $this->get(route('brands.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Brands/Index')
            ->has('brands.data', 1)
            ->has('metrics')
            ->where('metrics.total_brands', 1)
        );
    }

    public function test_brand_can_be_created_updated_and_deleted_via_dedicated_routes(): void
    {
        // 1. Create
        $response = $this->post(route('brands.store'), [
            'name' => 'Sony',
            'description' => 'Electronics and Cameras',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('brands', [
            'team_id' => $this->team->id,
            'name' => 'Sony',
            'slug' => 'sony',
        ]);

        $brand = Brand::where('name', 'Sony')->first();

        // 2. Update
        $updateResponse = $this->put(route('brands.update', $brand->id), [
            'name' => 'Sony Corporation',
            'description' => 'Updated desc',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Sony Corporation',
        ]);

        // 3. Delete
        $deleteResponse = $this->delete(route('brands.destroy', $brand->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertSoftDeleted('brands', [
            'id' => $brand->id,
        ]);
    }
}
