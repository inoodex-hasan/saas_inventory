<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WarehouseInertiaTest extends TestCase
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

    public function test_authenticated_tenant_can_view_warehouses_with_inertia(): void
    {
        Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Central Hub',
            'code' => 'HUB-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('warehouses.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Warehouses/Index')
            ->has('warehouses.data', 1)
            ->where('warehouses.data.0.name', 'Central Hub')
        );
    }

    public function test_tenant_can_create_warehouse_hub(): void
    {
        $response = $this->post(route('warehouses.store'), [
            'name' => 'Secondary Dock',
            'code' => 'DOCK-02',
            'address' => 'Plot 4, Export Zone, Chittagong',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouses', [
            'team_id' => $this->team->id,
            'name' => 'Secondary Dock',
            'code' => 'DOCK-02',
            'address' => 'Plot 4, Export Zone, Chittagong',
        ]);
    }

    public function test_tenant_can_update_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Old Name',
            'code' => 'OLD-01',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->put(route('warehouses.update', $warehouse), [
            'name' => 'Renamed Hub',
            'code' => 'REN-01',
            'address' => 'Zindabazar, Sylhet',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $warehouse->refresh();
        $this->assertEquals('Renamed Hub', $warehouse->name);
        $this->assertEquals('REN-01', $warehouse->code);
        $this->assertEquals('Zindabazar, Sylhet', $warehouse->address);
    }

    public function test_tenant_can_set_warehouse_as_default(): void
    {
        $wh1 = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Primary Hub',
            'code' => 'PRI-01',
            'is_default' => true,
        ]);

        $wh2 = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Secondary Hub',
            'code' => 'SEC-02',
            'is_default' => false,
        ]);

        $response = $this->post(route('warehouses.set-default', $wh2));

        $response->assertRedirect();
        $wh1->refresh();
        $wh2->refresh();

        $this->assertFalse((bool) $wh1->is_default);
        $this->assertTrue((bool) $wh2->is_default);
    }

    public function test_can_inspect_warehouse_stocks(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Hub',
            'code' => 'HUB-01',
            'is_default' => true,
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Gaming Laptop',
            'sku' => 'LAP-001',
            'cost_price' => 1000.00,
            'selling_price' => 1500.00,
        ]);

        app(StockService::class)->stockIn($warehouse->id, $product->id, 5, 1000.00, null, null, null, 'Initial stock');

        $response = $this->getJson(route('warehouses.inspect', $warehouse));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'warehouse',
            'stocks' => [
                '*' => [
                    'id',
                    'product_name',
                    'sku',
                    'quantity_on_hand',
                    'quantity_reserved',
                    'quantity_available',
                    'cost_price',
                    'total_value',
                ],
            ],
        ]);
        $response->assertJsonPath('stocks.0.product_name', 'Gaming Laptop');
        $response->assertJsonPath('stocks.0.quantity_on_hand', 5);
    }
}
