<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3QualityAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SubscriptionService::class)->ensureDefaultPlans();
    }

    public function test_security_headers_middleware_attaches_hardened_headers_to_responses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_disbursed_payroll_slip_cannot_be_deleted(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $employee = Employee::create([
            'team_id' => $team->id,
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'employee_code' => 'EMP-0010',
            'designation' => 'Store Keeper',
            'basic_salary' => 22000,
            'status' => 'active',
        ]);

        $payroll = Payroll::create([
            'team_id' => $team->id,
            'employee_id' => $employee->id,
            'payroll_number' => 'PAY-202610-0099',
            'month_year' => '2026-10',
            'basic_salary' => 22000,
            'gross_salary' => 22000,
            'net_salary' => 22000,
            'status' => 'paid', // Already disbursed!
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->delete(route('payroll.destroy', $payroll));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id]);
    }

    public function test_delivery_challan_rejects_quantities_exceeding_sales_order_balance(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Main WH',
            'code' => 'MWH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'Challan Client',
            'phone' => '01700998877',
            'address' => 'Mirpur, Dhaka',
            'is_active' => true,
        ]);

        $product = Product::create([
            'team_id' => $team->id,
            'name' => 'Thermal Receipt Printer',
            'sku' => 'TRP-01',
            'cost_price' => 5000,
            'unit_price' => 7500,
            'is_active' => true,
        ]);

        $order = SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'SO-202610-0999',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'order_date' => now()->toDateString(),
            'subtotal' => 15000,
            'total_amount' => 15000,
            'paid_amount' => 15000,
            'due_amount' => 0,
            'shipping_address' => 'Mirpur 10, Dhaka',
        ]);

        $orderItem = SalesOrderItem::create([
            'team_id' => $team->id,
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2, // Only 2 ordered
            'unit_price' => 7500,
            'unit_cost' => 5000,
            'subtotal' => 15000,
        ]);

        // Attempting to dispatch 5 when only 2 were ordered must be rejected
        $response = $this->actingAs($user)->post(route('challans.store'), [
            'sales_order_id' => $order->id,
            'dispatch_date' => now()->toDateString(),
            'delivery_address' => 'Mirpur 10, Dhaka',
            'items' => [
                [
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals(0, DeliveryChallan::where('sales_order_id', $order->id)->count());
    }

    public function test_letterhead_background_safeguard_removes_path_even_if_disk_file_absent(): void
    {
        Storage::fake('public');

        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $team->update(['invoice_bg_path' => 'tenants/' . $team->id . '/letterheads/non_existent.png']);

        $response = $this->actingAs($user)->post(route('settings.company.remove-invoice-bg'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNull($team->fresh()->invoice_bg_path);
    }
}
