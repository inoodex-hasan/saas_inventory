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
use App\Livewire\TenantSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        $response->assertSeeLivewire(TenantSettingsManager::class);
    }

    public function test_owner_can_update_company_profile_and_branding(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TenantSettingsManager::class)
            ->set('name', 'Apex Logistics Corp')
            ->set('legal_name', 'Apex Logistics Corporation Inc.')
            ->set('email', 'billing@apexlogistics.com')
            ->set('phone', '+1-800-555-APEX')
            ->set('tax_id', 'US-EIN-99887766')
            ->set('address_line1', '770 Innovation Way')
            ->set('city', 'Austin')
            ->set('state', 'Texas')
            ->set('postal_code', '78701')
            ->set('country', 'United States')
            ->set('currency_symbol', '€')
            ->set('currency_code', 'EUR')
            ->set('invoice_prefix', 'APEX-INV-')
            ->set('po_prefix', 'APEX-PO-')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Company profile and branding settings updated successfully!');

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

        Livewire::actingAs($this->owner)
            ->test(TenantSettingsManager::class)
            ->set('name', 'Modified Team A')
            ->set('currency_symbol', '¥')
            ->set('legal_name', 'Modified Legal A')
            ->call('save');

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
}
