<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosPdfDesignTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Customer $customer;
    private SalesOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create([
            'team_id' => $this->team->id,
            'name' => 'Main Outlet Hub',
            'code' => 'OUT-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Tanvir Hasan',
            'phone' => '01712345678',
            'address' => 'House 14, Road 5, Banani, Dhaka',
        ]);

        $this->order = SalesOrder::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_number' => 'POS-20261031-0001',
            'status' => 'completed',
            'payment_status' => 'paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 5000.00,
            'discount_amount' => 200.00,
            'tax_amount' => 250.00,
            'shipping_cost' => 50.00,
            'total_amount' => 5100.00,
            'paid_amount' => 5100.00,
            'due_amount' => 0.00,
            'notes' => 'Customer paid in cash at counter.',
            'created_by' => $this->user->id,
        ]);

        $product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Wireless Gaming Mouse',
            'sku' => 'MOU-WL-01',
            'cost_price' => 1500,
            'unit_price' => 2500,
            'is_active' => true,
        ]);

        SalesOrderItem::create([
            'team_id' => $this->team->id,
            'sales_order_id' => $this->order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 2500,
            'subtotal' => 5000,
            'warranty_days' => 365,
        ]);
    }

    public function test_a4_sales_invoice_pdf_streams_successfully(): void
    {
        $response = $this->get(route('sales.invoice', $this->order->id));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Invoice-POS-20261031-0001.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_thermal_pos_slip_pdf_streams_successfully(): void
    {
        $response = $this->get(route('sales.pos', $this->order->id));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('POS-POS-20261031-0001.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_pos_interactive_invoice_page_renders_successfully(): void
    {
        $response = $this->get(route('pos.invoice', $this->order->id));

        $response->assertOk();
        $response->assertSee('Tanvir Hasan');
        $response->assertSee('POS-20261031-0001');
        $response->assertSee('Thermal Print');
        $response->assertSee('Download PDF');
        $response->assertSee('Print Invoice');
    }
}

