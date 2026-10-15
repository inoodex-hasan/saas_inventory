<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class VendorCrudTest extends TestCase
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

    public function test_user_can_view_vendors_page(): void
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
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vendors/Index')
            ->has('vendors.data', 1)
            ->where('vendors.data.0.name', 'Prime Tech Supplies')
        );
    }

    public function test_can_create_vendor_with_valid_details(): void
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

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'team_id' => $this->team->id,
            'name' => 'Universal Distribution Co.',
            'contact_name' => 'Mr. Tareq Ahmed',
            'phone' => '01899123456',
            'email' => 'tareq@udc.com.bd',
            'address' => 'Plot 14, Agrabad C/A, Chittagong',
            'tax_number' => 'TIN-482910',
            'is_active' => true,
        ]);
    }

    public function test_validation_requires_name_phone_and_address(): void
    {
        $response = $this->post(route('vendors.store'), [
            'name' => '',
            'phone' => '',
            'address' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone', 'address']);
    }

    public function test_can_edit_existing_vendor(): void
    {
        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Old Tech BD',
            'phone' => '01700112233',
            'address' => 'Old Dhaka',
            'is_active' => true,
        ]);

        $response = $this->put(route('vendors.update', $vendor->id), [
            'name' => 'New Tech Bangladesh Ltd.',
            'phone' => '01700998877',
            'address' => 'Gulshan 2, Dhaka',
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $vendor->refresh();
        $this->assertEquals('New Tech Bangladesh Ltd.', $vendor->name);
        $this->assertEquals('01700998877', $vendor->phone);
        $this->assertEquals('Gulshan 2, Dhaka', $vendor->address);
    }

    public function test_can_toggle_vendor_active_status(): void
    {
        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Toggle Vendor',
            'phone' => '01711223344',
            'address' => 'Banani, Dhaka',
            'is_active' => true,
        ]);

        $response = $this->post(route('vendors.toggle-active', $vendor->id));
        $response->assertSessionHasNoErrors();

        $vendor->refresh();
        $this->assertFalse((bool) $vendor->is_active);

        $this->post(route('vendors.toggle-active', $vendor->id));
        $vendor->refresh();
        $this->assertTrue((bool) $vendor->is_active);
    }

    public function test_can_delete_vendor_without_purchase_orders(): void
    {
        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Inactive Unused Vendor',
            'phone' => '01888000000',
            'address' => 'Mirpur, Dhaka',
            'is_active' => true,
        ]);

        $response = $this->delete(route('vendors.destroy', $vendor->id));
        $response->assertSessionHasNoErrors();

        $this->assertSoftDeleted('suppliers', [
            'id' => $vendor->id,
        ]);
    }

    public function test_cannot_delete_vendor_with_associated_purchase_orders(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Warehouse',
            'code' => 'MW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $vendor = Supplier::create([
            'team_id' => $this->team->id,
            'name' => 'Critical Core Vendor',
            'phone' => '01888111111',
            'address' => 'Dhanmondi, Dhaka',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'team_id' => $this->team->id,
            'supplier_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'PO-TEST-0001',
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('vendors.destroy', $vendor->id));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', [
            'id' => $vendor->id,
            'deleted_at' => null,
        ]);
    }

    public function test_tenant_isolation_vendors_from_another_tenant_are_protected(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $otherVendor = Supplier::create([
            'team_id' => $otherTeam->id,
            'name' => 'Other Tenant Secret Supplier',
            'phone' => '01900000000',
            'address' => 'Uttara, Dhaka',
            'is_active' => true,
        ]);

        $response = $this->get(route('vendors.index'));
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Vendors/Index')
            ->where('vendors.data', [])
        );

        $deleteResponse = $this->delete(route('vendors.destroy', $otherVendor->id));
        $deleteResponse->assertStatus(404);
    }

    public function test_quick_add_vendor_via_controller(): void
    {
        $response = $this->post(route('vendors.store'), [
            'name' => 'Instant Quick Supplier',
            'phone' => '01300000000',
            'address' => 'Kawran Bazar, Dhaka',
            'contact_name' => 'Mr. Rahim',
        ]);
        $response->assertSessionHasNoErrors();

        $newVendor = Supplier::where('name', 'Instant Quick Supplier')->first();
        $this->assertNotNull($newVendor);
        $this->assertEquals($this->team->id, $newVendor->team_id);
    }

    public function test_can_create_purchase_order_with_inline_new_vendor(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Receiving Warehouse',
            'code' => 'MRW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Gigabyte B550 Motherboard',
            'sku' => 'MB-GB-B550',
            'cost_price' => 110.00,
            'selling_price' => 140.00,
            'is_active' => true,
        ]);

        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'new',
            'new_vendor_name' => 'Gigabyte Official Distributor',
            'new_vendor_phone' => '01755667788',
            'new_vendor_address' => 'BCS Computer City, IDB Bhaban, Dhaka',
            'new_vendor_contact' => 'Mr. Farhan',
            'new_vendor_email' => 'farhan@gigabyte-dist.com',
            'new_vendor_tax_number' => 'VAT-998877',
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 10,
                    'unit_cost' => 105.00,
                ],
            ],
        ]);
        $response->assertSessionHasNoErrors();

        $supplier = Supplier::where('team_id', $this->team->id)
            ->where('name', 'Gigabyte Official Distributor')
            ->first();

        $this->assertNotNull($supplier);
        $this->assertEquals('01755667788', $supplier->phone);
        $this->assertEquals('BCS Computer City, IDB Bhaban, Dhaka', $supplier->address);

        $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(1050.00, (float) $po->total_amount);
    }

    public function test_new_vendor_in_purchase_order_validation_requires_name_phone_and_address(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Receiving Warehouse',
            'code' => 'MRW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'new',
            'new_vendor_name' => '',
            'new_vendor_phone' => '',
            'new_vendor_address' => '',
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [],
        ]);

        $response->assertSessionHasErrors([
            'new_vendor_name',
            'new_vendor_phone',
            'new_vendor_address',
        ]);
    }

    public function test_existing_vendor_in_purchase_order_validation_requires_supplier_id(): void
    {
        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Receiving Warehouse',
            'code' => 'MRW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->post(route('purchases.store'), [
            'vendor_mode' => 'existing',
            'supplier_id' => null,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['supplier_id']);
    }
}
