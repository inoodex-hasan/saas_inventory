<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VendorInertiaTest extends TestCase
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

    public function test_authenticated_user_can_view_vendors_index_via_inertia(): void
    {
        Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Prime Tech Supplies',
            'contact_name' => 'John Doe',
            'phone' => '01711223344',
            'email' => 'contact@primetech.com',
            'address' => '120 Gulshan Ave, Dhaka',
            'is_active' => true,
        ]);

        $response = $this->get(route('vendors.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Vendors/Index')
            ->has('vendors.data', 1)
            ->where('vendors.data.0.name', 'Prime Tech Supplies')
        );
    }

    public function test_user_can_create_vendor_via_inertia(): void
    {
        $response = $this->post(route('vendors.store'), [
            'name' => 'Universal Distribution Co.',
            'contact_name' => 'Mr. Tareq Ahmed',
            'phone' => '01899123456',
            'email' => 'tareq@udc.com.bd',
            'address' => 'Plot 14, Agrabad C/A, Chittagong',
            'tax_number' => 'TIN-482910',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', [
            'team_id' => $this->team->id,
            'name' => 'Universal Distribution Co.',
            'phone' => '01899123456',
            'address' => 'Plot 14, Agrabad C/A, Chittagong',
            'tax_number' => 'TIN-482910',
            'is_active' => true,
        ]);
    }

    public function test_vendor_validation_requires_name_phone_and_address(): void
    {
        $response = $this->post(route('vendors.store'), [
            'name' => '',
            'phone' => '',
            'address' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone', 'address']);
        $this->assertEquals(0, Supplier::where('team_id', $this->team->id)->count());
    }

    public function test_user_can_update_vendor(): void
    {
        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Old Name Traders',
            'phone' => '01700000000',
            'address' => 'Old Paltan, Dhaka',
            'is_active' => true,
        ]);

        $response = $this->put(route('vendors.update', $vendor), [
            'name' => 'Updated Prestige Traders',
            'phone' => '01799999999',
            'address' => 'New Paltan, Dhaka',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $vendor->refresh();
        $this->assertEquals('Updated Prestige Traders', $vendor->name);
        $this->assertEquals('01799999999', $vendor->phone);
    }

    public function test_user_can_toggle_vendor_active_status(): void
    {
        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Fast Track Electronics',
            'phone' => '01511112222',
            'address' => 'Motijheel, Dhaka',
            'is_active' => true,
        ]);

        $this->post(route('vendors.toggle-active', $vendor));
        $this->assertFalse((bool) $vendor->fresh()->is_active);

        $this->post(route('vendors.toggle-active', $vendor));
        $this->assertTrue((bool) $vendor->fresh()->is_active);
    }

    public function test_user_can_delete_vendor_without_orders(): void
    {
        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Temporary Supplies',
            'phone' => '01600000000',
            'address' => 'Elephant Road, Dhaka',
            'is_active' => true,
        ]);

        $response = $this->delete(route('vendors.destroy', $vendor));
        $response->assertRedirect();

        $this->assertSoftDeleted('suppliers', ['id' => $vendor->id]);
    }

    public function test_cannot_delete_vendor_with_orders(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Central Hub',
            'code' => 'CH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Critical Core Vendor',
            'phone' => '01722334455',
            'address' => 'Tejgaon I/A, Dhaka',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'team_id' => $this->team->id,
            'supplier_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'PO-20261012-0001',
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('vendors.destroy', $vendor));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', [
            'id' => $vendor->id,
            'deleted_at' => null,
        ]);
    }
}
