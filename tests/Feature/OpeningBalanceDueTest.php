<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningBalanceDueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Warehouse $warehouse;
    private FinancialAccount $financialAccount;

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
            'name' => 'Main Warehouse',
            'code' => 'MW-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->financialAccount = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Main Cash Counter',
            'account_type' => 'cash',
            'current_balance' => 50000.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        app(AccountingService::class)->ensureDefaultAccounts($this->team->id);
    }

    public function test_can_create_vendor_with_opening_balance_due(): void
    {
        $response = $this->post(route('vendors.store'), [
            'name' => 'Apex Hardware Supplies',
            'contact_name' => 'Kamal Hossain',
            'phone' => '01700112233',
            'email' => 'apex@example.com',
            'address' => 'Motijheel C/A, Dhaka',
            'tax_number' => 'TAX-APEX-99',
            'opening_balance' => 25000.50,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $vendor = Supplier::where('phone', '01700112233')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals(25000.50, (float) $vendor->opening_balance);

        // Verify opening PurchaseOrder created
        $po = PurchaseOrder::where('supplier_id', $vendor->id)->first();
        $this->assertNotNull($po);
        $this->assertStringStartsWith('PO-OPEN-', $po->order_number);
        $this->assertEquals('received', $po->status);
        $this->assertEquals('unpaid', $po->payment_status);
        $this->assertEquals(25000.50, (float) $po->total_amount);
        $this->assertEquals(25000.50, (float) $po->due_amount);
        $this->assertEquals(0.00, (float) $po->paid_amount);

        // Verify journal entry created
        $je = JournalEntry::where('reference_type', PurchaseOrder::class)
            ->where('reference_id', $po->id)
            ->first();
        $this->assertNotNull($je);
        $this->assertEquals(25000.50, $je->total_debit);
        $this->assertEquals(25000.50, $je->total_credit);
    }

    public function test_vendor_opening_due_appears_in_due_ledger_and_can_be_settled(): void
    {
        $this->post(route('vendors.store'), [
            'name' => 'Delta Steel Mill',
            'phone' => '01899112233',
            'address' => 'Chittagong Port Area',
            'opening_balance' => 15000.00,
        ]);

        $vendor = Supplier::where('phone', '01899112233')->first();
        $po = PurchaseOrder::where('supplier_id', $vendor->id)->first();

        // Check vendor due index
        $indexResponse = $this->get(route('vendor-dues.index'));
        $indexResponse->assertOk();

        // Settle vendor due via vendor-dues.pay
        $payResponse = $this->post(route('vendor-dues.pay'), [
            'order_id' => $po->id,
            'amount' => 15000.00,
            'financial_account_id' => $this->financialAccount->id,
            'payment_reference' => 'CHQ-889911',
            'notes' => 'Opening balance settled in full',
        ]);

        $payResponse->assertRedirect();
        $po->refresh();
        $this->assertEquals('paid', $po->payment_status);
        $this->assertEquals(0.00, (float) $po->due_amount);
        $this->assertEquals(15000.00, (float) $po->paid_amount);

        // Financial account balance decreased
        $this->financialAccount->refresh();
        $this->assertEquals(35000.00, (float) $this->financialAccount->current_balance);
    }

    public function test_can_create_customer_with_opening_balance_due(): void
    {
        $response = $this->post(route('dues.customer.store'), [
            'name' => 'Rahim Chowdhury',
            'phone' => '01900112244',
            'company_name' => 'Chowdhury Traders',
            'email' => 'rahim@example.com',
            'address' => 'Dhanmondi 27, Dhaka',
            'opening_balance' => 18500.00,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $customer = Customer::where('phone', '01900112244')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(18500.00, (float) $customer->opening_balance);

        // Verify opening SalesOrder created
        $so = SalesOrder::where('customer_id', $customer->id)->first();
        $this->assertNotNull($so);
        $this->assertStringStartsWith('INV-OPEN-', $so->order_number);
        $this->assertEquals('completed', $so->status);
        $this->assertEquals('unpaid', $so->payment_status);
        $this->assertEquals(18500.00, (float) $so->total_amount);
        $this->assertEquals(18500.00, (float) $so->due_amount);
        $this->assertEquals(0.00, (float) $so->paid_amount);

        // Verify journal entry created
        $je = JournalEntry::where('reference_type', SalesOrder::class)
            ->where('reference_id', $so->id)
            ->first();
        $this->assertNotNull($je);
        $this->assertEquals(18500.00, $je->total_debit);
        $this->assertEquals(18500.00, $je->total_credit);
    }

    public function test_customer_opening_due_appears_in_due_ledger_and_can_be_collected(): void
    {
        $this->post(route('dues.customer.store'), [
            'name' => 'Salma Begum',
            'phone' => '01600112255',
            'address' => 'Banani, Dhaka',
            'opening_balance' => 8000.00,
        ]);

        $customer = Customer::where('phone', '01600112255')->first();
        $so = SalesOrder::where('customer_id', $customer->id)->first();

        // Check customer dues index
        $indexResponse = $this->get(route('dues.index'));
        $indexResponse->assertOk();

        // Collect payment via dues.pay
        $payResponse = $this->post(route('dues.pay'), [
            'order_id' => $so->id,
            'amount' => 5000.00,
            'financial_account_id' => $this->financialAccount->id,
            'payment_reference' => 'MFS-TRX-102',
            'notes' => 'Partial collection of opening due',
        ]);

        $payResponse->assertRedirect();
        $so->refresh();
        $this->assertEquals('partially_paid', $so->payment_status);
        $this->assertEquals(3000.00, (float) $so->due_amount);
        $this->assertEquals(5000.00, (float) $so->paid_amount);

        // Financial account balance increased
        $this->financialAccount->refresh();
        $this->assertEquals(55000.00, (float) $this->financialAccount->current_balance);
    }

    public function test_customer_and_vendor_created_with_zero_opening_balance_do_not_create_orders(): void
    {
        $this->post(route('vendors.store'), [
            'name' => 'Clean Slate Vendor',
            'phone' => '01500000001',
            'address' => 'Dhaka',
            'opening_balance' => 0,
        ]);

        $vendor = Supplier::where('phone', '01500000001')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals(0, PurchaseOrder::where('supplier_id', $vendor->id)->count());

        $this->post(route('dues.customer.store'), [
            'name' => 'Clean Slate Customer',
            'phone' => '01500000002',
            'opening_balance' => 0,
        ]);

        $customer = Customer::where('phone', '01500000002')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(0, SalesOrder::where('customer_id', $customer->id)->count());
    }

    public function test_can_update_vendor_opening_balance_and_sync_po_and_journal(): void
    {
        $this->post(route('vendors.store'), [
            'name' => 'Initial Vendor',
            'phone' => '01811223344',
            'address' => 'Chittagong',
            'opening_balance' => 10000.00,
        ]);

        $vendor = Supplier::where('phone', '01811223344')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals(10000.00, (float) $vendor->opening_balance);

        $po = PurchaseOrder::where('supplier_id', $vendor->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(10000.00, (float) $po->total_amount);

        // Update opening balance to 15,000
        $updateResponse = $this->put(route('vendors.update', $vendor->id), [
            'name' => 'Initial Vendor Updated',
            'phone' => '01811223344',
            'address' => 'Chittagong Port',
            'opening_balance' => 15000.00,
            'is_active' => true,
        ]);

        $updateResponse->assertRedirect();
        $vendor->refresh();
        $this->assertEquals(15000.00, (float) $vendor->opening_balance);

        $po->refresh();
        $this->assertEquals(15000.00, (float) $po->total_amount);
        $this->assertEquals(15000.00, (float) $po->due_amount);

        $je = JournalEntry::where('reference_type', PurchaseOrder::class)
            ->where('reference_id', $po->id)
            ->first();
        $this->assertNotNull($je);
        $this->assertEquals(15000.00, (float) $je->total_debit);

        // Update opening balance to 0 (unpaid) -> deletes opening PO and journal entry
        $this->put(route('vendors.update', $vendor->id), [
            'name' => 'Initial Vendor Updated',
            'phone' => '01811223344',
            'address' => 'Chittagong Port',
            'opening_balance' => 0.00,
            'is_active' => true,
        ]);

        $vendor->refresh();
        $this->assertEquals(0.00, (float) $vendor->opening_balance);
        $this->assertNull(PurchaseOrder::find($po->id));
        $this->assertEquals(0, JournalEntry::where('reference_type', PurchaseOrder::class)->where('reference_id', $po->id)->count());
    }

    public function test_can_update_customer_opening_balance_and_sync_sales_order_and_journal(): void
    {
        $this->post(route('dues.customer.store'), [
            'name' => 'Original Customer',
            'phone' => '01799887766',
            'opening_balance' => 8000.00,
        ]);

        $customer = Customer::where('phone', '01799887766')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(8000.00, (float) $customer->opening_balance);

        $so = SalesOrder::where('customer_id', $customer->id)->first();
        $this->assertNotNull($so);
        $this->assertEquals(8000.00, (float) $so->total_amount);

        // Update customer opening balance to 12,000
        $updateResponse = $this->put(route('dues.customer.update', $customer->id), [
            'name' => 'Updated Customer Name',
            'phone' => '01799887766',
            'company_name' => 'Star Corp',
            'opening_balance' => 12000.00,
        ]);

        $updateResponse->assertRedirect();
        $customer->refresh();
        $this->assertEquals('Updated Customer Name', $customer->name);
        $this->assertEquals(12000.00, (float) $customer->opening_balance);

        $so->refresh();
        $this->assertEquals(12000.00, (float) $so->total_amount);
        $this->assertEquals(12000.00, (float) $so->due_amount);

        $je = JournalEntry::where('reference_type', SalesOrder::class)
            ->where('reference_id', $so->id)
            ->first();
        $this->assertNotNull($je);
        $this->assertEquals(12000.00, (float) $je->total_debit);

        // Update opening balance to 0 (unpaid) -> deletes opening sales order and journal entry
        $this->put(route('dues.customer.update', $customer->id), [
            'name' => 'Updated Customer Name',
            'phone' => '01799887766',
            'opening_balance' => 0.00,
        ]);

        $customer->refresh();
        $this->assertEquals(0.00, (float) $customer->opening_balance);
        $this->assertNull(SalesOrder::find($so->id));
        $this->assertEquals(0, JournalEntry::where('reference_type', SalesOrder::class)->where('reference_id', $so->id)->count());
    }

    public function test_cannot_reduce_opening_balance_below_settled_amount(): void
    {
        $this->post(route('dues.customer.store'), [
            'name' => 'Settled Customer',
            'phone' => '01899001122',
            'opening_balance' => 10000.00,
        ]);

        $customer = Customer::where('phone', '01899001122')->first();
        $so = SalesOrder::where('customer_id', $customer->id)->first();

        // Partially pay 4,000
        $this->post(route('dues.pay'), [
            'order_id' => $so->id,
            'amount' => 4000.00,
            'financial_account_id' => $this->financialAccount->id,
        ]);

        $so->refresh();
        $this->assertEquals(4000.00, (float) $so->paid_amount);

        // Attempt to reduce opening balance to 2,000 (below 4,000 paid)
        $response = $this->put(route('dues.customer.update', $customer->id), [
            'name' => 'Settled Customer',
            'phone' => '01899001122',
            'opening_balance' => 2000.00,
        ]);

        $response->assertSessionHasErrors('opening_balance');
    }
}

