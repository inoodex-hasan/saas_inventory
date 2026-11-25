<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InvoiceTemplate;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTemplateAndPadTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $tenantOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Ensure templates exist
        if (InvoiceTemplate::count() === 0) {
            InvoiceTemplate::create([
                'key' => 'invoice_pdf1',
                'name' => 'Classic Modern',
                'description' => 'Dark slate header, rounded customer info card.',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ]);
            InvoiceTemplate::create([
                'key' => 'invoice_pdf2',
                'name' => 'Minimal Executive',
                'description' => 'Bold uppercase typography, 2-column Bill To & Ship To cards.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ]);
            InvoiceTemplate::create([
                'key' => 'invoice_pdf3',
                'name' => 'Corporate Navy & Gold',
                'description' => 'Premium navy & gold accents.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ]);
        }

        $this->superAdmin = User::factory()->withPersonalTeam()->create([
            'name' => 'Platform Super Admin',
            'email' => 'superadmin@platform.com',
        ]);
        $this->superAdmin->is_super_admin = true;
        $this->superAdmin->current_team_id = $this->superAdmin->personalTeam()->id;
        $this->superAdmin->save();

        $this->tenantOwner = User::factory()->withPersonalTeam()->create([
            'name' => 'Tenant Business Owner',
            'email' => 'tenant@business.com',
        ]);
        $this->tenantOwner->is_super_admin = false;
        $this->tenantOwner->current_team_id = $this->tenantOwner->personalTeam()->id;
        $this->tenantOwner->save();
    }

    public function test_super_admin_can_view_invoice_templates_list(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.invoice-templates'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/InvoiceTemplates')
            ->has('templates', 3)
            ->has('stats.total')
            ->has('stats.active')
            ->has('stats.default')
        );
    }

    public function test_super_admin_can_toggle_template_status(): void
    {
        $template = InvoiceTemplate::where('key', 'invoice_pdf2')->firstOrFail();
        $this->assertTrue($template->is_active);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.invoice-templates.toggle-status', $template->id));

        $response->assertRedirect();
        $this->assertFalse($template->fresh()->is_active);

        // Toggle back
        $this->actingAs($this->superAdmin)
            ->post(route('admin.invoice-templates.toggle-status', $template->id));
        $this->assertTrue($template->fresh()->is_active);
    }

    public function test_super_admin_cannot_deactivate_default_template(): void
    {
        $defaultTemplate = InvoiceTemplate::where('is_default', true)->firstOrFail();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.invoice-templates.toggle-status', $defaultTemplate->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue($defaultTemplate->fresh()->is_active);
    }

    public function test_super_admin_can_set_default_template(): void
    {
        $template2 = InvoiceTemplate::where('key', 'invoice_pdf2')->firstOrFail();
        $this->assertFalse($template2->is_default);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.invoice-templates.set-default', $template2->id));

        $response->assertRedirect();
        $this->assertTrue($template2->fresh()->is_default);
        $this->assertTrue($template2->fresh()->is_active);

        $template1 = InvoiceTemplate::where('key', 'invoice_pdf1')->firstOrFail();
        $this->assertFalse($template1->is_default);
    }

    public function test_super_admin_can_preview_template_pdf(): void
    {
        $template = InvoiceTemplate::where('key', 'invoice_pdf1')->firstOrFail();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.invoice-templates.preview', $template->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tenant_settings_includes_active_templates(): void
    {
        $response = $this->actingAs($this->tenantOwner)
            ->get(route('settings.company'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Company')
            ->has('availableTemplates')
            ->has('settings.invoice_template')
            ->has('settings.hide_header_on_pad')
        );
    }

    public function test_tenant_can_select_template_and_update_margins_and_pad_options(): void
    {
        $team = $this->tenantOwner->currentTeam;

        $response = $this->actingAs($this->tenantOwner)
            ->put(route('settings.company.update'), [
                'name' => $team->name,
                'legal_name' => 'Acme Corporation Ltd',
                'currency_symbol' => '৳',
                'currency_code' => 'BDT',
                'invoice_prefix' => 'INV-',
                'po_prefix' => 'PO-',
                'invoice_template' => 'invoice_pdf3',
                'hide_header_on_pad' => true,
                'invoice_margin_top' => 50,
                'invoice_margin_bottom' => 30,
            ]);

        $response->assertRedirect();
        $team->refresh();
        $this->assertEquals('invoice_pdf3', $team->invoice_template);
        $this->assertTrue((bool)$team->hide_header_on_pad);
        $this->assertEquals(50, $team->invoice_margin_top);
        $this->assertEquals(30, $team->invoice_margin_bottom);
    }

    public function test_tenant_can_preview_sample_invoice_pdf_with_chosen_template(): void
    {
        $team = $this->tenantOwner->currentTeam;
        $team->update(['invoice_template' => 'invoice_pdf2']);

        $response = $this->actingAs($this->tenantOwner)
            ->get(route('settings.company.preview-invoice-bg', ['template' => 'invoice_pdf2']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_sales_order_pdf_renders_with_chosen_tenant_template(): void
    {
        $team = $this->tenantOwner->currentTeam;
        $team->update([
            'invoice_template' => 'invoice_pdf3',
            'currency_symbol' => '৳',
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Main Test Hub',
            'code' => 'HUB-01',
            'is_default' => true,
        ]);

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'Test Customer One',
            'phone' => '01811000000',
        ]);

        $product = Product::create([
            'team_id' => $team->id,
            'name' => 'Test Enterprise Router',
            'sku' => 'RTR-001',
            'cost_price' => 500,
            'selling_price' => 1000,
            'current_stock' => 10,
        ]);

        $order = SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->tenantOwner->id,
            'order_number' => 'INV-TEST-999',
            'order_date' => now(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'discount_amount' => 50,
            'tax_amount' => 50,
            'shipping_cost' => 60,
            'additional_charges' => [
                ['name' => 'Handling Fee', 'amount' => 40],
            ],
            'additional_charges_total' => 40,
            'total_amount' => 1100,
            'paid_amount' => 1100,
        ]);

        SalesOrderItem::create([
            'team_id' => $team->id,
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'warranty_days' => 365,
        ]);

        $response = $this->actingAs($this->tenantOwner)
            ->get(route('sales.invoice', $order->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
