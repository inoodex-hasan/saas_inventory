<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Customer;
use App\Models\OrderChargeField;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderChargeFieldTest extends TestCase
{
    use RefreshDatabase;

    protected $userA;
    protected $userB;
    protected Warehouse $warehouseA;
    protected Customer $customerA;
    protected Supplier $supplierA;
    protected Product $productA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->userA = \App\Models\User::factory()->withPersonalTeam()->create([
            'name' => 'Tenant A Owner',
            'email' => 'tenanta@test.com',
            'phone' => '01711000001',
        ]);
        $this->userA->current_team_id = $this->userA->currentTeam->id;
        $this->userA->save();

        $this->userB = \App\Models\User::factory()->withPersonalTeam()->create([
            'name' => 'Tenant B Owner',
            'email' => 'tenantb@test.com',
            'phone' => '01711000002',
        ]);
        $this->userB->current_team_id = $this->userB->currentTeam->id;
        $this->userB->save();

        $this->actingAs($this->userA);

        $this->warehouseA = Warehouse::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Central Hub A',
            'code' => 'WH-A',
            'is_default' => true,
        ]);

        $this->customerA = Customer::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Customer A',
            'email' => 'customerA@test.com',
            'phone' => '01811000001',
        ]);

        $this->supplierA = Supplier::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Vendor A',
            'email' => 'vendorA@test.com',
            'phone' => '01911000001',
        ]);

        $this->productA = Product::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Smartphone Pro',
            'sku' => 'SP-001',
            'cost_price' => 200.00,
            'unit_price' => 300.00,
        ]);

        app(StockService::class)->stockIn(
            warehouseId: $this->warehouseA->id,
            productId: $this->productA->id,
            quantity: 50,
            unitCost: 200.00,
            referenceType: 'Initial',
            referenceId: 1
        );
    }

    public function test_tenant_can_view_order_charges_page_via_inertia(): void
    {
        $this->actingAs($this->userA);

        OrderChargeField::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Courier Delivery',
            'applies_to' => 'sales',
            'type' => 'fixed',
            'default_amount' => 80.00,
            'is_active' => true,
        ]);

        $response = $this->get(route('settings.order-charges.index'));
        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Settings/OrderCharges')
            ->has('fields', 1)
            ->where('fields.0.name', 'Courier Delivery')
        );
    }

    public function test_tenant_can_create_update_toggle_and_delete_charge_field(): void
    {
        $this->actingAs($this->userA);

        // 1. Create charge field
        $response = $this->post(route('settings.order-charges.store'), [
            'name' => 'Express Delivery',
            'applies_to' => 'sales',
            'type' => 'fixed',
            'default_amount' => 120.00,
            'is_taxable' => false,
            'show_in_pos' => true,
            'is_active' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_charge_fields', [
            'team_id' => $this->userA->current_team_id,
            'name' => 'Express Delivery',
            'default_amount' => 120.00,
        ]);

        $field = OrderChargeField::where('name', 'Express Delivery')->first();

        // 2. Update charge field
        $updateResp = $this->put(route('settings.order-charges.update', $field->id), [
            'name' => 'Fast Delivery',
            'applies_to' => 'sales',
            'type' => 'fixed',
            'default_amount' => 150.00,
            'is_taxable' => false,
            'show_in_pos' => true,
            'is_active' => true,
        ]);
        $updateResp->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_charge_fields', [
            'id' => $field->id,
            'name' => 'Fast Delivery',
            'default_amount' => 150.00,
        ]);

        // 3. Toggle active
        $this->post(route('settings.order-charges.toggle-active', $field->id));
        $this->assertFalse($field->fresh()->is_active);

        $this->post(route('settings.order-charges.toggle-active', $field->id));
        $this->assertTrue($field->fresh()->is_active);

        // 4. Delete charge field (Soft Delete)
        $delResp = $this->delete(route('settings.order-charges.destroy', $field->id));
        $delResp->assertSessionHasNoErrors();
        $this->assertSoftDeleted('order_charge_fields', ['id' => $field->id]);
    }

    public function test_multi_tenant_isolation_for_charge_fields(): void
    {
        // Tenant A creates a charge field
        $this->actingAs($this->userA);
        $fieldA = OrderChargeField::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Customs Tariff A',
            'applies_to' => 'purchases',
            'type' => 'percentage',
            'default_amount' => 5.0,
            'is_active' => true,
        ]);

        // Tenant B tries to modify/delete Tenant A's charge field
        $this->actingAs($this->userB);

        $updateAttempt = $this->put(route('settings.order-charges.update', $fieldA->id), [
            'name' => 'Hacked Tariff',
            'applies_to' => 'purchases',
            'type' => 'fixed',
            'default_amount' => 999,
        ]);
        $updateAttempt->assertStatus(404);

        $deleteAttempt = $this->delete(route('settings.order-charges.destroy', $fieldA->id));
        $deleteAttempt->assertStatus(404);

        // Tenant B's API query returns 0 fields
        $apiResp = $this->getJson(route('api.order-charges.active', ['context' => 'purchases']));
        $apiResp->assertOk();
        $this->assertCount(0, $apiResp->json('fields'));
    }

    public function test_api_active_filters_by_context(): void
    {
        $this->actingAs($this->userA);

        OrderChargeField::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Sales Delivery',
            'applies_to' => 'sales',
            'type' => 'fixed',
            'default_amount' => 60,
            'show_in_pos' => true,
            'is_active' => true,
        ]);

        OrderChargeField::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Import Duty',
            'applies_to' => 'purchases',
            'type' => 'fixed',
            'default_amount' => 500,
            'is_active' => true,
        ]);

        OrderChargeField::create([
            'team_id' => $this->userA->current_team_id,
            'name' => 'Handling Charge',
            'applies_to' => 'both',
            'type' => 'fixed',
            'default_amount' => 25,
            'show_in_pos' => false,
            'is_active' => true,
        ]);

        // Sales context should return 'sales' and 'both'
        $salesResp = $this->getJson(route('api.order-charges.active', ['context' => 'sales']));
        $salesResp->assertOk();
        $this->assertCount(2, $salesResp->json('fields'));

        // Purchases context should return 'purchases' and 'both'
        $poResp = $this->getJson(route('api.order-charges.active', ['context' => 'purchases']));
        $poResp->assertOk();
        $this->assertCount(2, $poResp->json('fields'));

        // POS context should return 'sales' and 'both' WHERE show_in_pos is true
        $posResp = $this->getJson(route('api.order-charges.active', ['context' => 'pos']));
        $posResp->assertOk();
        $this->assertCount(1, $posResp->json('fields'));
        $this->assertEquals('Sales Delivery', $posResp->json('fields.0.name'));
    }

    public function test_sales_order_calculates_and_stores_additional_charges(): void
    {
        $this->actingAs($this->userA);

        $soService = app(SalesOrderService::class);

        $items = [
            [
                'product_id' => $this->productA->id,
                'quantity' => 2,
                'unit_price' => 300.00, // 2 * 300 = 600
            ],
        ];

        $charges = [
            ['name' => 'Home Delivery', 'type' => 'fixed', 'amount' => 80.00],
            ['name' => 'Special Packaging', 'type' => 'fixed', 'amount' => 20.00],
        ];

        $order = $soService->createOrder([
            'team_id' => $this->userA->current_team_id,
            'customer_id' => $this->customerA->id,
            'warehouse_id' => $this->warehouseA->id,
            'discount_amount' => 50.00,
            'tax_amount' => 15.00,
            'shipping_cost' => 0.00,
            'additional_charges' => $charges,
            'additional_charges_total' => 100.00,
            'status' => 'draft',
        ], $items);

        // Expected: Subtotal(600) - Discount(50) + Tax(15) + Charges(100) = 665.00
        $this->assertEquals(600.00, (float) $order->subtotal);
        $this->assertEquals(100.00, (float) $order->additional_charges_total);
        $this->assertEquals(665.00, (float) $order->total_amount);
        $this->assertCount(2, $order->additional_charges);
        $this->assertEquals('Home Delivery', $order->additional_charges[0]['name']);
    }

    public function test_purchase_order_calculates_and_stores_landed_charges(): void
    {
        $this->actingAs($this->userA);

        $poService = app(PurchaseOrderService::class);

        $items = [
            [
                'product_id' => $this->productA->id,
                'quantity' => 10,
                'unit_cost' => 200.00, // 10 * 200 = 2000
            ],
        ];

        $charges = [
            ['name' => 'Customs Tariff', 'type' => 'fixed', 'amount' => 150.00],
            ['name' => 'Port Handling Fee', 'type' => 'fixed', 'amount' => 50.00],
        ];

        $order = $poService->createOrder([
            'team_id' => $this->userA->current_team_id,
            'supplier_id' => $this->supplierA->id,
            'warehouse_id' => $this->warehouseA->id,
            'additional_charges' => $charges,
            'additional_charges_total' => 200.00,
            'status' => 'draft',
        ], $items);

        // Expected: Subtotal(2000) + Charges(200) = 2200.00
        $this->assertEquals(2000.00, (float) $order->subtotal);
        $this->assertEquals(200.00, (float) $order->additional_charges_total);
        $this->assertEquals(2200.00, (float) $order->total_amount);
        $this->assertCount(2, $order->additional_charges);
    }

    public function test_quotation_stores_and_converts_additional_charges_to_sales_order(): void
    {
        $this->actingAs($this->userA);

        // 1. Create Quotation with additional charges
        $quotationResp = $this->post(route('quotations.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customerA->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'discount_amount' => 20.00,
            'tax_rate' => 0,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1,
                    'unit_price' => 300.00,
                ],
            ],
            'additional_charges' => [
                ['name' => 'Installation Fee', 'type' => 'fixed', 'amount' => 50.00],
            ],
            'additional_charges_total' => 50.00,
        ]);
        $quotationResp->assertSessionHasNoErrors();

        $quotation = Quotation::latest('id')->first();
        // 300 - 20 + 50 = 330
        $this->assertEquals(330.00, (float) $quotation->total_amount);
        $this->assertEquals(50.00, (float) $quotation->additional_charges_total);

        // 2. Convert to Sales Order
        $convertResp = $this->post(route('quotations.convert', $quotation->id), [
            'warehouse_id' => $this->warehouseA->id,
        ]);
        $convertResp->assertSessionHasNoErrors();

        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status);
        $this->assertNotNull($quotation->converted_sales_order_id);

        $convertedOrder = \App\Models\SalesOrder::find($quotation->converted_sales_order_id);
        $this->assertNotNull($convertedOrder);
        $this->assertEquals(50.00, (float) $convertedOrder->additional_charges_total);
        $this->assertEquals(330.00, (float) $convertedOrder->total_amount);
    }
}
