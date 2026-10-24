<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TenantSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->withPersonalTeam()->create();
        $this->team = $this->owner->currentTeam;
    }

    public function test_company_settings_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->owner)->get(route('settings.company'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Company')
            ->has('settings')
        );
    }

    public function test_owner_can_update_company_profile_and_branding(): void
    {
        $response = $this->actingAs($this->owner)->put(route('settings.company.update'), [
            'name' => 'Apex Logistics Corp',
            'legal_name' => 'Apex Logistics Corporation Inc.',
            'email' => 'billing@apexlogistics.com',
            'phone' => '+1-800-555-APEX',
            'tax_id' => 'US-EIN-99887766',
            'address_line1' => '770 Innovation Way',
            'city' => 'Austin',
            'state' => 'Texas',
            'postal_code' => '78701',
            'country' => 'United States',
            'currency_symbol' => '€',
            'currency_code' => 'EUR',
            'invoice_prefix' => 'APEX-INV-',
            'po_prefix' => 'APEX-PO-',
        ]);

        $response->assertSessionHasNoErrors();

        $this->team->refresh();

        $this->assertEquals('Apex Logistics Corp', $this->team->name);
        $this->assertEquals('Apex Logistics Corporation Inc.', $this->team->legal_name);
        $this->assertEquals('billing@apexlogistics.com', $this->team->email);
        $this->assertEquals('+1-800-555-APEX', $this->team->phone);
        $this->assertEquals('US-EIN-99887766', $this->team->tax_id);
        $this->assertEquals('770 Innovation Way', $this->team->address_line1);
        $this->assertEquals('Austin', $this->team->city);
        $this->assertEquals('€', $this->team->currency_symbol);
        $this->assertEquals('EUR', $this->team->currency_code);
        $this->assertEquals('APEX-INV-', $this->team->invoice_prefix);
        $this->assertEquals('Apex Logistics Corporation Inc.', $this->team->displayName());
    }

    public function test_updating_one_tenant_does_not_affect_another_tenant(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->currentTeam;

        $otherTeam->update([
            'name' => 'Original Team B',
            'currency_symbol' => '£',
            'legal_name' => 'Original Legal B',
        ]);

        $response = $this->actingAs($this->owner)->put(route('settings.company.update'), [
            'name' => 'Modified Team A',
            'currency_symbol' => '¥',
            'currency_code' => 'JPY',
            'invoice_prefix' => 'INV-',
            'po_prefix' => 'PO-',
            'legal_name' => 'Modified Legal A',
            'email' => $this->owner->email,
        ]);
        $response->assertSessionHasNoErrors();

        $otherTeam->refresh();
        $this->assertEquals('Original Team B', $otherTeam->name);
        $this->assertEquals('£', $otherTeam->currency_symbol);
        $this->assertEquals('Original Legal B', $otherTeam->legal_name);
    }

    public function test_pdf_invoice_reflects_tenant_branding_and_custom_currency(): void
    {
        $this->team->update([
            'legal_name' => 'Nordic Warehousing AB',
            'tax_id' => 'SE-556123456701',
            'currency_symbol' => 'kr ',
            'address_line1' => 'Storgatan 12',
            'city' => 'Stockholm',
            'country' => 'Sweden',
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Stockholm Central Hub',
            'code' => 'STH-01',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'team_id' => $this->team->id,
            'name' => 'Hardware',
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'category_id' => $category->id,
            'name' => 'Precision Router',
            'sku' => 'RTR-100',
            'cost_price' => 50.00,
            'selling_price' => 120.00,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Svenska Tech AB',
            'email' => 'tech@svenska.se',
        ]);

        $salesOrder = SalesOrder::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'order_number' => 'SO-TEST-001',
            'order_date' => now(),
            'status' => 'draft',
            'subtotal' => 240.00,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 240.00,
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid',
        ]);

        SalesOrderItem::create([
            'team_id' => $this->team->id,
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 120.00,
            'subtotal' => 240.00,
        ]);

        $response = $this->actingAs($this->owner)->get(route('sales.invoice', $salesOrder));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_can_upload_invoice_and_report_backgrounds_and_set_margins(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $invoiceFile = \Illuminate\Http\UploadedFile::fake()->image('invoice_letterhead.png', 1000, 1400);
        $reportFile = \Illuminate\Http\UploadedFile::fake()->image('report_letterhead.png', 1000, 1400);

        $response = $this->actingAs($this->owner)->post(route('settings.company.update'), [
            'name' => $this->team->name,
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'invoice_prefix' => 'INV-',
            'po_prefix' => 'PO-',
            'invoice_bg' => $invoiceFile,
            'report_bg' => $reportFile,
            'invoice_margin_top' => 52,
            'invoice_margin_bottom' => 30,
            'report_margin_top' => 45,
            'report_margin_bottom' => 28,
        ]);

        $response->assertSessionHasNoErrors();

        $this->team->refresh();

        $this->assertNotNull($this->team->invoice_bg_path);
        $this->assertNotNull($this->team->report_bg_path);
        $this->assertEquals(52, $this->team->invoice_margin_top);
        $this->assertEquals(30, $this->team->invoice_margin_bottom);
        $this->assertEquals(45, $this->team->report_margin_top);
        $this->assertEquals(28, $this->team->report_margin_bottom);

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($this->team->invoice_bg_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($this->team->report_bg_path);
    }

    public function test_tenant_can_preview_sample_invoice_pdf(): void
    {
        $response = $this->actingAs($this->owner)->get(route('settings.company.preview-invoice-bg'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_can_preview_sample_report_pdf(): void
    {
        $response = $this->actingAs($this->owner)->get(route('settings.company.preview-report-bg'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_can_remove_invoice_and_report_backgrounds(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->team->update([
            'invoice_bg_path' => 'tenants/test/letterheads/dummy_invoice.png',
            'report_bg_path' => 'tenants/test/letterheads/dummy_report.png',
        ]);
        \Illuminate\Support\Facades\Storage::disk('public')->put('tenants/test/letterheads/dummy_invoice.png', 'fake image content');
        \Illuminate\Support\Facades\Storage::disk('public')->put('tenants/test/letterheads/dummy_report.png', 'fake report content');

        // Remove Invoice BG
        $resp1 = $this->actingAs($this->owner)->post(route('settings.company.remove-invoice-bg'));
        $resp1->assertSessionHasNoErrors();
        $this->team->refresh();
        $this->assertNull($this->team->invoice_bg_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('tenants/test/letterheads/dummy_invoice.png');

        // Remove Report BG
        $resp2 = $this->actingAs($this->owner)->post(route('settings.company.remove-report-bg'));
        $resp2->assertSessionHasNoErrors();
        $this->team->refresh();
        $this->assertNull($this->team->report_bg_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('tenants/test/letterheads/dummy_report.png');
    }
}
