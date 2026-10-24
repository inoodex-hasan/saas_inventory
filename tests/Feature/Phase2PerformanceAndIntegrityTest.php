<?php

namespace Tests\Feature;

use App\Exceptions\InvalidStockOperationException;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\ReturnService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2PerformanceAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SubscriptionService::class)->ensureDefaultPlans();
    }

    public function test_return_service_blocks_rma_quantity_exceeding_sold_quantity(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'Acme Corp',
            'phone' => '01711112222',
            'address' => 'Motijheel, Dhaka',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Central Hub',
            'code' => 'CENTRAL-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $product = Product::create([
            'team_id' => $team->id,
            'name' => 'Laser Scanner',
            'sku' => 'SCN-01',
            'unit' => 'pcs',
            'cost_price' => 200,
            'selling_price' => 350,
            'unit_price' => 350,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
        ]);

        $order = SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'SO-2026-001',
            'order_date' => now()->toDateString(),
            'status' => 'dispatched',
            'payment_status' => 'paid',
            'total_amount' => 700,
            'subtotal' => 700,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'paid_amount' => 700,
            'due_amount' => 0,
        ]);

        $item = SalesOrderItem::create([
            'team_id' => $team->id,
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 350,
            'subtotal' => 700,
            'total_price' => 700,
        ]);

        $returnService = app(ReturnService::class);

        // Attempting to return 5 when only 2 were purchased must throw InvalidStockOperationException
        $this->expectException(InvalidStockOperationException::class);

        $returnService->createReturn([
            'team_id' => $team->id,
            'sales_order_id' => $order->id,
            'warehouse_id' => $warehouse->id,
            'reason' => 'Defective item batch',
            'notes' => 'Excess return test',
            'created_by' => $user->id,
        ], [
            [
                'sales_order_item_id' => $item->id,
                'product_id' => $product->id,
                'quantity' => 5,
                'condition' => 'damaged',
                'action' => 'refund',
            ],
        ]);
    }

    public function test_return_cannot_be_completed_unless_status_is_approved(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'Acme Corp',
            'phone' => '01711112222',
            'address' => 'Motijheel, Dhaka',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Central Hub',
            'code' => 'CENTRAL-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $order = SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'SO-2026-002',
            'order_date' => now()->toDateString(),
            'status' => 'dispatched',
            'payment_status' => 'paid',
            'total_amount' => 500,
            'subtotal' => 500,
            'paid_amount' => 500,
            'due_amount' => 0,
        ]);

        $return = ProductReturn::create([
            'team_id' => $team->id,
            'sales_order_id' => $order->id,
            'return_number' => 'RET-2026-0001',
            'warehouse_id' => $warehouse->id,
            'status' => 'pending', // Not approved yet!
            'reason' => 'Wrong model received',
            'created_by' => $user->id,
        ]);

        $returnService = app(ReturnService::class);

        $this->expectException(InvalidStockOperationException::class);
        $returnService->completeReturn($return, $warehouse->id);
    }

    public function test_accounting_service_aggregates_financial_reports_accurately_with_single_query_group_by(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $assetAccount = ChartOfAccount::create([
            'team_id' => $team->id,
            'account_code' => '1010',
            'name' => 'Cash in Hand',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $revenueAccount = ChartOfAccount::create([
            'team_id' => $team->id,
            'account_code' => '4010',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'is_active' => true,
        ]);

        $journalEntry = JournalEntry::create([
            'team_id' => $team->id,
            'entry_number' => 'JE-2026-0001',
            'entry_date' => now()->toDateString(),
            'narration' => 'Initial Cash Sale',
            'reference' => 'Cash Sale',
            'status' => 'posted',
        ]);

        JournalEntryLine::create([
            'team_id' => $team->id,
            'journal_entry_id' => $journalEntry->id,
            'chart_of_account_id' => $assetAccount->id,
            'debit' => 5000,
            'credit' => 0,
            'description' => 'Debit Cash',
        ]);

        JournalEntryLine::create([
            'team_id' => $team->id,
            'journal_entry_id' => $journalEntry->id,
            'chart_of_account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 5000,
            'description' => 'Credit Revenue',
        ]);

        $accountingService = app(AccountingService::class);

        // Trial Balance
        $trialBalance = $accountingService->getTrialBalance($team->id, now()->toDateString());
        $this->assertEquals(5000, $trialBalance['total_debit']);
        $this->assertEquals(5000, $trialBalance['total_credit']);

        // Profit and Loss
        $pnl = $accountingService->getProfitAndLoss($team->id, now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertEquals(5000, $pnl['total_revenue']);
        $this->assertEquals(5000, $pnl['net_income']);

        // Balance Sheet
        $balanceSheet = $accountingService->getBalanceSheet($team->id, now()->toDateString());
        $this->assertEquals(5000, $balanceSheet['total_assets']);
    }

    public function test_customer_due_overpayment_throws_validation_exception_in_web_request(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'High End Client',
            'phone' => '01912345678',
            'address' => 'Gulshan 2, Dhaka',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Gulshan Hub',
            'code' => 'GLS-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        $order = SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'SO-DUE-01',
            'order_date' => now()->toDateString(),
            'status' => 'dispatched',
            'payment_status' => 'partially_paid',
            'total_amount' => 1000,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'paid_amount' => 400,
            'due_amount' => 600,
        ]);

        // Attempt to pay 1000 when only 600 is due
        $response = $this->actingAs($user)->post(route('dues.pay'), [
            'order_id' => $order->id,
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_dashboard_monthly_revenue_and_procurement_aggregates_correctly(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $customer = Customer::create([
            'team_id' => $team->id,
            'name' => 'Test Customer',
            'phone' => '01711223344',
            'address' => 'Banani',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'team_id' => $team->id,
            'name' => 'Main Supplier',
            'phone' => '01811223344',
            'address' => 'Tejgaon',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'team_id' => $team->id,
            'name' => 'Main WH',
            'code' => 'MWH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Order this month
        SalesOrder::create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'SO-CURR-01',
            'order_date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 1250,
            'subtotal' => 1250,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'paid_amount' => 1250,
            'due_amount' => 0,
        ]);

        // Purchase order this month
        PurchaseOrder::create([
            'team_id' => $team->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_number' => 'PO-CURR-01',
            'order_date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'paid',
            'total_amount' => 800,
            'subtotal' => 800,
            'paid_amount' => 800,
            'due_amount' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $trends = $response->viewData('page')['props']['salesTrend'];
        $currentMonthKey = now()->format('M Y');

        $this->assertNotEmpty($trends);
        $currentMonthData = collect($trends)->firstWhere('month', $currentMonthKey);

        $this->assertNotNull($currentMonthData);
        $this->assertEquals(1250, $currentMonthData['sales']);
        $this->assertEquals(800, $currentMonthData['purchases']);
    }
}
