<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $product;
    private Customer $customer;

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
            'name' => 'Main Logistics Hub',
            'code' => 'MLH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Dell Optiplex Desktop',
            'sku' => 'DELL-OPT-01',
            'cost_price' => 35000,
            'unit_price' => 45000,
            'is_active' => true,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Standard Bank Ltd',
            'phone' => '01911887766',
            'address' => 'Motijheel C/A, Dhaka',
        ]);
    }

    public function test_user_can_access_quotation_pages(): void
    {
        $response = $this->get(route('quotations.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Quotations/Index')
            ->has('quotations')
        );

        $responseCreate = $this->get(route('quotations.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Quotations/Create')
            ->has('customers')
        );
    }

    public function test_user_can_create_quotation_with_existing_customer(): void
    {
        $response = $this->post(route('quotations.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'discount_amount' => 5000,
            'status' => 'sent',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 45000,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('quotations.index'));

        $this->assertDatabaseHas('quotations', [
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'subtotal' => 135000,
            'discount_amount' => 5000,
            'total_amount' => 130000,
            'status' => 'sent',
        ]);

        $quotation = Quotation::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($quotation);
        $this->assertCount(1, $quotation->items);
        $this->assertEquals(3, (float) $quotation->items->first()->quantity);
    }

    public function test_user_can_convert_accepted_quotation_to_sales_order(): void
    {
        $quotation = Quotation::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-20261012-0099',
            'status' => 'accepted',
            'quotation_date' => now()->toDateString(),
            'subtotal' => 90000,
            'total_amount' => 90000,
        ]);

        QuotationItem::create([
            'team_id' => $this->team->id,
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 45000,
            'subtotal' => 90000,
        ]);

        $response = $this->withoutExceptionHandling()->post(route('quotations.convert', $quotation->id), [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $response->assertSessionHasNoErrors();

        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status);
        $this->assertNotNull($quotation->converted_sales_order_id);

        $order = SalesOrder::find($quotation->converted_sales_order_id);
        $this->assertNotNull($order);
        $this->assertEquals(90000, (float) $order->total_amount);
        $this->assertEquals($this->customer->id, $order->customer_id);
        $this->assertCount(1, $order->items);
    }

    public function test_authenticated_tenant_can_stream_quotation_pdf(): void
    {
        $quotation = Quotation::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-20261012-0050',
            'status' => 'sent',
            'quotation_date' => now()->toDateString(),
            'subtotal' => 45000,
            'total_amount' => 45000,
        ]);

        QuotationItem::create([
            'team_id' => $this->team->id,
            'quotation_id' => $quotation->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 45000,
            'subtotal' => 45000,
        ]);

        $response = $this->get(route('quotations.pdf', $quotation->id));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
