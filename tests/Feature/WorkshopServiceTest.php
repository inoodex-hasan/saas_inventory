<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketItem;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WorkshopServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private Product $partProduct;
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
            'name' => 'Main Repair Hub',
            'code' => 'MRH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->partProduct = Product::create([
            'team_id' => $this->team->id,
            'name' => 'Original iPhone 13 OLED Display',
            'sku' => 'PART-IPH-13-SCR',
            'cost_price' => 8500,
            'unit_price' => 14000,
            'is_active' => true,
        ]);

        StockLevel::create([
            'team_id' => $this->team->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->partProduct->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $this->customer = Customer::create([
            'team_id' => $this->team->id,
            'name' => 'Tariqul Islam',
            'phone' => '01711223344',
            'email' => 'tariqul@client.test',
            'address' => 'Gulshan-2, Dhaka',
        ]);
    }

    public function test_user_can_access_service_pages(): void
    {
        $response = $this->get(route('services.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Services/Index')
            ->has('tickets')
            ->has('metrics')
        );

        $responseCreate = $this->get(route('services.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Services/Create')
            ->has('customers')
            ->has('products')
        );
    }

    public function test_user_can_create_service_ticket_with_existing_customer(): void
    {
        $response = $this->post(route('services.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'device_name' => 'MacBook Pro 14 M2',
            'device_brand' => 'Apple',
            'serial_no' => 'C02G1234MD6R',
            'accessories_received' => '96W Magsafe 3 Charger & Cable',
            'problem_description' => 'Battery draining very fast and kernel panic on sleep',
            'initial_diagnosis' => 'Battery health at 68%, cycle count 890',
            'priority' => 'high',
            'service_charge' => 1500,
            'discount_amount' => 200,
            'paid_amount' => 500,
            'warranty_days' => 30,
            'received_date' => now()->toDateString(),
            'estimated_completion_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();

        $ticket = ServiceTicket::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('services.show', $ticket->id));

        $this->assertEquals($this->team->id, $ticket->team_id);
        $this->assertEquals('MacBook Pro 14 M2', $ticket->device_name);
        $this->assertEquals(1500, (float) $ticket->service_charge);
        $this->assertEquals(200, (float) $ticket->discount_amount);
        $this->assertEquals(1300, (float) $ticket->total_amount); // 1500 - 200
        $this->assertEquals(500, (float) $ticket->paid_amount);
        $this->assertEquals(800, (float) $ticket->due_amount); // 1300 - 500
        $this->assertEquals('partially_paid', $ticket->payment_status);
        $this->assertEquals('received', $ticket->status);
        $this->assertStringStartsWith('SRV-', $ticket->ticket_number);
    }

    public function test_user_can_create_service_ticket_with_inline_new_customer(): void
    {
        $response = $this->post(route('services.store'), [
            'customer_mode' => 'new',
            'new_customer_name' => 'Nasir Uddin',
            'new_customer_phone' => '01899112233',
            'new_customer_email' => 'nasir@test.com',
            'new_customer_address' => 'Dhanmondi 27, Dhaka',
            'device_name' => 'Dell Latitude 7420',
            'device_brand' => 'Dell',
            'serial_no' => 'DL-9821-X',
            'problem_description' => 'No power, charging LED blinks 3 amber 2 white',
            'priority' => 'urgent',
            'service_charge' => 2500,
            'discount_amount' => 0,
            'paid_amount' => 2500,
            'warranty_days' => 60,
            'received_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();

        $newCustomer = Customer::where('phone', '01899112233')->first();
        $this->assertNotNull($newCustomer);
        $this->assertEquals('Nasir Uddin', $newCustomer->name);

        $ticket = ServiceTicket::where('customer_id', $newCustomer->id)->first();
        $this->assertNotNull($ticket);
        $this->assertEquals(2500, (float) $ticket->total_amount);
        $this->assertEquals(2500, (float) $ticket->paid_amount);
        $this->assertEquals(0, (float) $ticket->due_amount);
        $this->assertEquals('paid', $ticket->payment_status);
        $this->assertEquals('urgent', $ticket->priority);
    }

    public function test_creating_service_ticket_with_initial_parts_consumes_inventory(): void
    {
        $response = $this->post(route('services.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'device_name' => 'iPhone 13',
            'serial_no' => 'IMEI354678912345671',
            'problem_description' => 'Cracked front screen touch unresponsive',
            'priority' => 'normal',
            'service_charge' => 1000,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'received_date' => now()->toDateString(),
            'parts' => [
                [
                    'product_id' => $this->partProduct->id,
                    'warehouse_id' => $this->warehouse->id,
                    'part_name' => 'OLED Screen Replacement',
                    'quantity' => 2,
                    'unit_price' => 14000,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $ticket = ServiceTicket::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($ticket);
        $this->assertEquals(28000, (float) $ticket->parts_total);
        $this->assertEquals(29000, (float) $ticket->total_amount); // 1000 + 28000

        // Check stock level was deducted from 10 to 8
        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->partProduct->id)->first();
        $this->assertEquals(8, (float) $stock->quantity);
    }

    public function test_user_can_view_and_update_service_ticket(): void
    {
        $ticket = ServiceTicket::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'ticket_number' => 'SRV-20261014-0010',
            'device_name' => 'HP Envy x360',
            'status' => 'received',
            'priority' => 'normal',
            'service_charge' => 1000,
            'total_amount' => 1000,
            'due_amount' => 1000,
            'received_date' => now()->toDateString(),
        ]);

        $responseShow = $this->get(route('services.show', $ticket->id));
        $responseShow->assertStatus(200);
        $responseShow->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Services/Show')
            ->where('ticket.id', $ticket->id)
        );

        $responseUpdate = $this->put(route('services.update', $ticket->id), [
            'device_name' => 'HP Envy x360 15 Convertible',
            'priority' => 'high',
            'service_charge' => 1500,
            'discount_amount' => 100,
            'warranty_days' => 45,
            'problem_description' => 'Hinge broken and display flickering',
            'initial_diagnosis' => 'Right hinge damaged, EDP cable pinched',
        ]);
        $responseUpdate->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertEquals('HP Envy x360 15 Convertible', $ticket->device_name);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals(1400, (float) $ticket->total_amount); // 1500 - 100
    }

    public function test_adding_and_removing_spare_parts_updates_inventory_and_totals(): void
    {
        $ticket = ServiceTicket::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'ticket_number' => 'SRV-20261014-0015',
            'device_name' => 'iPhone 13',
            'status' => 'diagnosing',
            'priority' => 'normal',
            'service_charge' => 1000,
            'total_amount' => 1000,
            'due_amount' => 1000,
            'received_date' => now()->toDateString(),
        ]);

        // Add part (qty: 3)
        $responseAdd = $this->post(route('services.parts.add', $ticket->id), [
            'part_name' => 'OLED Screen Panel',
            'product_id' => $this->partProduct->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 3,
            'unit_price' => 14000,
        ]);
        $responseAdd->assertSessionHasNoErrors();

        // Check stock: 10 - 3 = 7
        $stock = StockLevel::where('warehouse_id', $this->warehouse->id)->where('product_id', $this->partProduct->id)->first();
        $this->assertEquals(7, (float) $stock->quantity);

        $ticket->refresh();
        $this->assertEquals(42000, (float) $ticket->parts_total);
        $this->assertEquals(43000, (float) $ticket->total_amount);

        // Remove the part
        $item = $ticket->items()->first();
        $this->assertNotNull($item);

        $responseRemove = $this->delete(route('services.parts.remove', [$ticket->id, $item->id]));
        $responseRemove->assertSessionHasNoErrors();

        // Check stock restored: 7 + 3 = 10
        $stock->refresh();
        $this->assertEquals(10, (float) $stock->quantity);

        $ticket->refresh();
        $this->assertEquals(0, (float) $ticket->parts_total);
        $this->assertEquals(1000, (float) $ticket->total_amount);
    }

    public function test_status_lifecycle_transitions(): void
    {
        $ticket = ServiceTicket::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'ticket_number' => 'SRV-20261014-0020',
            'device_name' => 'Lenovo ThinkPad X1',
            'status' => 'received',
            'priority' => 'normal',
            'service_charge' => 1200,
            'total_amount' => 1200,
            'due_amount' => 1200,
            'received_date' => now()->toDateString(),
        ]);

        // Move to diagnosing
        $this->post(route('services.transition', $ticket->id), ['status' => 'diagnosing'])->assertSessionHasNoErrors();
        $ticket->refresh();
        $this->assertEquals('diagnosing', $ticket->status);

        // Move to repaired
        $this->post(route('services.transition', $ticket->id), ['status' => 'repaired'])->assertSessionHasNoErrors();
        $ticket->refresh();
        $this->assertEquals('repaired', $ticket->status);
        $this->assertNotNull($ticket->completed_date);

        // Move to delivered
        $this->post(route('services.transition', $ticket->id), ['status' => 'delivered'])->assertSessionHasNoErrors();
        $ticket->refresh();
        $this->assertEquals('delivered', $ticket->status);
        $this->assertNotNull($ticket->delivered_date);
    }

    public function test_recording_payment_against_service_ticket_and_overpayment_guard(): void
    {
        $ticket = ServiceTicket::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'ticket_number' => 'SRV-20261014-0025',
            'device_name' => 'iPad Air 5',
            'status' => 'repaired',
            'priority' => 'normal',
            'service_charge' => 5000,
            'total_amount' => 5000,
            'paid_amount' => 1000,
            'due_amount' => 4000,
            'payment_status' => 'partially_paid',
            'received_date' => now()->toDateString(),
        ]);

        // Partial payment of 2000
        $this->post(route('services.pay', $ticket->id), [
            'amount' => 2000,
            'payment_method' => 'mobile_banking',
        ])->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertEquals(3000, (float) $ticket->paid_amount);
        $this->assertEquals(2000, (float) $ticket->due_amount);
        $this->assertEquals('partially_paid', $ticket->payment_status);

        // Overpayment attempt of 3000 (exceeds due of 2000)
        try {
            $this->withoutExceptionHandling()->post(route('services.pay', $ticket->id), [
                'amount' => 3000,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('exceeds', $e->getMessage());
        }

        $ticket->refresh();
        $this->assertEquals(2000, (float) $ticket->due_amount);

        // Settle full remaining due of 2000
        $this->post(route('services.pay', $ticket->id), [
            'amount' => 2000,
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertEquals(5000, (float) $ticket->paid_amount);
        $this->assertEquals(0, (float) $ticket->due_amount);
        $this->assertEquals('paid', $ticket->payment_status);
    }

    public function test_authenticated_tenant_can_stream_service_token_and_invoice_pdf(): void
    {
        $ticket = ServiceTicket::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'ticket_number' => 'SRV-20261014-0030',
            'device_name' => 'Asus ROG Strix G15',
            'serial_no' => 'ROG-SN-998877',
            'status' => 'repaired',
            'priority' => 'high',
            'service_charge' => 2000,
            'total_amount' => 2000,
            'paid_amount' => 2000,
            'due_amount' => 0,
            'payment_status' => 'paid',
            'warranty_days' => 30,
            'received_date' => now()->toDateString(),
        ]);

        $responseToken = $this->get(route('services.token', $ticket->id));
        $responseToken->assertStatus(200);
        $responseToken->assertHeader('content-type', 'application/pdf');

        $responseInvoice = $this->get(route('services.invoice', $ticket->id));
        $responseInvoice->assertStatus(200);
        $responseInvoice->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_isolation_protects_service_tickets(): void
    {
        $ticket = ServiceTicket::create([
            'team_id' => $this->team->id,
            'customer_id' => $this->customer->id,
            'ticket_number' => 'SRV-20261014-0040',
            'device_name' => 'Private Tenant Device',
            'status' => 'received',
            'service_charge' => 1000,
            'total_amount' => 1000,
            'due_amount' => 1000,
            'received_date' => now()->toDateString(),
        ]);

        // Login as another tenant
        $otherUser = User::factory()->withPersonalTeam()->create();
        $this->actingAs($otherUser);

        // Accessing other tenant ticket returns 403 or 404
        $response = $this->get(route('services.show', $ticket->id));
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_cannot_assign_technician_from_another_tenant(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();

        $response = $this->post(route('services.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'device_name' => 'Dell Laptop',
            'priority' => 'normal',
            'received_date' => now()->toDateString(),
            'technician_id' => $otherUser->id,
        ]);

        $response->assertSessionHasErrors('technician_id');
    }
}
