<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Team;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DoubleEntryAccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->currentTeam;
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
        $this->actingAs($this->user);

        $this->accountingService = app(AccountingService::class);
    }

    public function test_default_chart_of_accounts_provisioned_automatically(): void
    {
        $response = $this->get(route('accounts.index'));
        $response->assertStatus(200);

        $accountsCount = ChartOfAccount::where('team_id', $this->team->id)->count();
        $this->assertGreaterThanOrEqual(15, $accountsCount);

        // Verify fundamental accounts exist
        $this->assertDatabaseHas('chart_of_accounts', [
            'team_id' => $this->team->id,
            'account_code' => '1010',
            'type' => 'asset',
        ]);
        $this->assertDatabaseHas('chart_of_accounts', [
            'team_id' => $this->team->id,
            'account_code' => '2010',
            'type' => 'liability',
        ]);
        $this->assertDatabaseHas('chart_of_accounts', [
            'team_id' => $this->team->id,
            'account_code' => '3010',
            'type' => 'equity',
        ]);
        $this->assertDatabaseHas('chart_of_accounts', [
            'team_id' => $this->team->id,
            'account_code' => '4010',
            'type' => 'revenue',
        ]);
        $this->assertDatabaseHas('chart_of_accounts', [
            'team_id' => $this->team->id,
            'account_code' => '5010',
            'type' => 'expense',
        ]);
    }

    public function test_can_create_custom_chart_of_account(): void
    {
        $response = $this->post(route('accounts.store'), [
            'account_code' => '5090',
            'name' => 'Software & SaaS Subscriptions',
            'type' => 'expense',
            'subtype' => 'operating_expense',
            'description' => 'AWS, OpenAI, GitHub cloud tooling costs',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('chart_of_accounts', [
            'team_id' => $this->team->id,
            'account_code' => '5090',
            'name' => 'Software & SaaS Subscriptions',
            'type' => 'expense',
            'is_system' => false,
        ]);
    }

    public function test_cannot_create_duplicate_account_code_in_same_team(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $response = $this->post(route('accounts.store'), [
            'account_code' => '1010', // Already exists as Cash on Hand
            'name' => 'Duplicate Cash',
            'type' => 'asset',
        ]);

        $response->assertSessionHasErrors(['account_code']);
    }

    public function test_can_update_chart_of_account(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $account = ChartOfAccount::where('team_id', $this->team->id)
            ->where('account_code', '1020')
            ->firstOrFail();

        $response = $this->put(route('accounts.update', $account->id), [
            'name' => 'Main Operating Checking Bank Account',
            'subtype' => 'cash_and_equivalent',
            'description' => 'City Bank Primary Business Account',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('chart_of_accounts', [
            'id' => $account->id,
            'name' => 'Main Operating Checking Bank Account',
        ]);
    }

    public function test_cannot_delete_system_account(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $systemAccount = ChartOfAccount::where('team_id', $this->team->id)
            ->where('is_system', true)
            ->firstOrFail();

        $response = $this->delete(route('accounts.destroy', $systemAccount->id));
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('chart_of_accounts', ['id' => $systemAccount->id]);
    }

    public function test_can_record_balanced_journal_entry(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bankAccount = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $capitalAccount = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '3010')->firstOrFail();

        $response = $this->post(route('journal.store'), [
            'entry_date' => '2026-10-14',
            'narration' => 'Initial Founder Equity Cash Injection',
            'lines' => [
                [
                    'chart_of_account_id' => $bankAccount->id,
                    'debit' => 500000.00,
                    'credit' => 0,
                    'memo' => 'Bank deposit',
                ],
                [
                    'chart_of_account_id' => $capitalAccount->id,
                    'debit' => 0,
                    'credit' => 500000.00,
                    'memo' => 'Common equity issue',
                ],
            ],
        ]);

        $response->assertRedirect(route('journal.index'));

        $this->assertDatabaseHas('journal_entries', [
            'team_id' => $this->team->id,
            'narration' => 'Initial Founder Equity Cash Injection',
        ]);

        $entry = JournalEntry::where('team_id', $this->team->id)->latest()->first();
        $this->assertEquals(500000.00, $entry->total_debit);
        $this->assertEquals(500000.00, $entry->total_credit);
        $this->assertCount(2, $entry->lines);
    }

    public function test_cannot_record_unbalanced_journal_entry(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bankAccount = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $capitalAccount = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '3010')->firstOrFail();

        $response = $this->post(route('journal.store'), [
            'entry_date' => '2026-10-14',
            'narration' => 'Unbalanced illegal transaction attempt',
            'lines' => [
                [
                    'chart_of_account_id' => $bankAccount->id,
                    'debit' => 50000.00,
                    'credit' => 0,
                ],
                [
                    'chart_of_account_id' => $capitalAccount->id,
                    'debit' => 0,
                    'credit' => 45000.00, // Difference of 5000!
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['lines']);
        $this->assertDatabaseMissing('journal_entries', [
            'narration' => 'Unbalanced illegal transaction attempt',
        ]);
    }

    public function test_cannot_delete_account_with_journal_lines(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $customAccount = ChartOfAccount::create([
            'team_id' => $this->team->id,
            'account_code' => '5095',
            'name' => 'Custom Travel Account',
            'type' => 'expense',
            'is_system' => false,
            'is_active' => true,
        ]);

        $cashAccount = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1010')->firstOrFail();

        // Record entry with this custom account
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-14',
            'narration' => 'Travel expense',
        ], [
            ['chart_of_account_id' => $customAccount->id, 'debit' => 1200, 'credit' => 0],
            ['chart_of_account_id' => $cashAccount->id, 'debit' => 0, 'credit' => 1200],
        ]);

        $response = $this->delete(route('accounts.destroy', $customAccount->id));
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('chart_of_accounts', ['id' => $customAccount->id]);
    }

    public function test_general_ledger_calculates_opening_and_running_balances_correctly(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bank = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $capital = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '3010')->firstOrFail();
        $rent = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '5020')->firstOrFail();

        // Entry 1: Deposit $100,000 on 2026-10-01
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-01',
            'narration' => 'Deposit capital',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 100000, 'credit' => 0],
            ['chart_of_account_id' => $capital->id, 'debit' => 0, 'credit' => 100000],
        ]);

        // Entry 2: Pay Rent $15,000 on 2026-10-05
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-05',
            'narration' => 'Office rent',
        ], [
            ['chart_of_account_id' => $rent->id, 'debit' => 15000, 'credit' => 0],
            ['chart_of_account_id' => $bank->id, 'debit' => 0, 'credit' => 15000],
        ]);

        $ledger = $this->accountingService->getGeneralLedger($this->team->id, $bank->id, '2026-10-01', '2026-10-10');

        $this->assertEquals(0, $ledger['opening_balance']);
        $this->assertEquals(100000, $ledger['total_debits']);
        $this->assertEquals(15000, $ledger['total_credits']);
        $this->assertEquals(85000, $ledger['closing_balance']);
        $this->assertCount(2, $ledger['transactions']);
    }

    public function test_trial_balance_debits_equal_credits(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bank = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $sales = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '4010')->firstOrFail();
        $wages = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '5030')->firstOrFail();

        // Transaction 1: Sale of $60,000 received in bank
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-10',
            'narration' => 'Sales receipt',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 60000, 'credit' => 0],
            ['chart_of_account_id' => $sales->id, 'debit' => 0, 'credit' => 60000],
        ]);

        // Transaction 2: Paid wages of $20,000 from bank
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-12',
            'narration' => 'Staff payroll payout',
        ], [
            ['chart_of_account_id' => $wages->id, 'debit' => 20000, 'credit' => 0],
            ['chart_of_account_id' => $bank->id, 'debit' => 0, 'credit' => 20000],
        ]);

        $trialBalance = $this->accountingService->getTrialBalance($this->team->id, '2026-10-14');

        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals($trialBalance['total_debit'], $trialBalance['total_credit']);
        $this->assertEquals(60000, $trialBalance['total_debit']); // Bank: 40,000 Dr, Wages: 20,000 Dr vs Sales: 60,000 Cr
    }

    public function test_profit_and_loss_calculates_net_income(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bank = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $sales = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '4010')->firstOrFail();
        $cogs = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '5010')->firstOrFail();
        $rent = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '5020')->firstOrFail();

        // Revenue: $150,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-02',
            'narration' => 'Major client contract revenue',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 150000, 'credit' => 0],
            ['chart_of_account_id' => $sales->id, 'debit' => 0, 'credit' => 150000],
        ]);

        // COGS: $60,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-03',
            'narration' => 'Direct cost of goods',
        ], [
            ['chart_of_account_id' => $cogs->id, 'debit' => 60000, 'credit' => 0],
            ['chart_of_account_id' => $bank->id, 'debit' => 0, 'credit' => 60000],
        ]);

        // Rent: $20,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-04',
            'narration' => 'Facility lease',
        ], [
            ['chart_of_account_id' => $rent->id, 'debit' => 20000, 'credit' => 0],
            ['chart_of_account_id' => $bank->id, 'debit' => 0, 'credit' => 20000],
        ]);

        $pnl = $this->accountingService->getProfitAndLoss($this->team->id, '2026-10-01', '2026-10-14');

        $this->assertEquals(150000, $pnl['total_revenue']);
        $this->assertEquals(80000, $pnl['total_expenses']);
        $this->assertEquals(70000, $pnl['net_income']); // 150k - 80k = 70k profit
    }

    public function test_balance_sheet_accounting_equation_holds_assets_equal_liabilities_plus_equity(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bank = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $capital = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '3010')->firstOrFail();
        $loan = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '2020')->firstOrFail();
        $sales = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '4010')->firstOrFail();
        $expenses = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '5050')->firstOrFail();

        // 1. Initial capital: $200,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-01',
            'narration' => 'Owner capital',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 200000, 'credit' => 0],
            ['chart_of_account_id' => $capital->id, 'debit' => 0, 'credit' => 200000],
        ]);

        // 2. Bank Loan liability: $100,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-02',
            'narration' => 'Commercial bank loan disbursement',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 100000, 'credit' => 0],
            ['chart_of_account_id' => $loan->id, 'debit' => 0, 'credit' => 100000],
        ]);

        // 3. Revenue: $50,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-03',
            'narration' => 'Client revenue',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 50000, 'credit' => 0],
            ['chart_of_account_id' => $sales->id, 'debit' => 0, 'credit' => 50000],
        ]);

        // 4. Utility expense: $10,000
        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-04',
            'narration' => 'Power utility bills',
        ], [
            ['chart_of_account_id' => $expenses->id, 'debit' => 10000, 'credit' => 0],
            ['chart_of_account_id' => $bank->id, 'debit' => 0, 'credit' => 10000],
        ]);

        // Total Bank Assets = 200k + 100k + 50k - 10k = 340,000.
        // Liabilities = 100,000 (loan).
        // Equity = 200,000 (capital) + 40,000 (net income: 50k rev - 10k exp) = 240,000.
        // Liabilities + Equity = 100k + 240k = 340,000.
        $bs = $this->accountingService->getBalanceSheet($this->team->id, '2026-10-14');

        $this->assertEquals(340000, $bs['total_assets']);
        $this->assertEquals(100000, $bs['total_liabilities']);
        $this->assertEquals(240000, $bs['total_equity']);
        $this->assertTrue($bs['is_balanced']);
        $this->assertEquals($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity']);
    }

    public function test_bank_reconciliation_verifies_ledger_and_records_audit_trail(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        $bank = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->firstOrFail();
        $capital = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '3010')->firstOrFail();

        $this->accountingService->recordJournalEntry([
            'entry_date' => '2026-10-01',
            'narration' => 'Deposit',
        ], [
            ['chart_of_account_id' => $bank->id, 'debit' => 75000, 'credit' => 0],
            ['chart_of_account_id' => $capital->id, 'debit' => 0, 'credit' => 75000],
        ]);

        // Perfect reconciliation matching $75,000
        $response = $this->post(route('reconciliation.store'), [
            'chart_of_account_id' => $bank->id,
            'statement_balance' => 75000,
            'reconciliation_date' => '2026-10-14',
            'notes' => 'Month end bank statement matches exactly',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_reconciliations', [
            'team_id' => $this->team->id,
            'chart_of_account_id' => $bank->id,
            'statement_balance' => 75000,
            'ledger_balance' => 75000,
            'difference' => 0,
            'status' => 'reconciled',
        ]);

        // Discrepancy reconciliation (statement has $74,000, difference of -$1,000)
        $reconDiscrepancy = $this->accountingService->reconcileBank(
            $this->team->id,
            $bank->id,
            74000,
            '2026-10-14',
            'Bank service charge not yet recorded'
        );

        $this->assertEquals('in_progress', $reconDiscrepancy->status);
        $this->assertEquals(-1000, $reconDiscrepancy->difference);
    }

    public function test_accounting_pdf_generation_endpoints(): void
    {
        $this->accountingService->ensureDefaultAccounts($this->team->id);

        // Trial Balance PDF
        $tbPdf = $this->get(route('pdf.trial-balance', ['as_of_date' => '2026-10-14']));
        $tbPdf->assertStatus(200);
        $tbPdf->assertHeader('Content-Type', 'application/pdf');

        // Profit and Loss PDF
        $pnlPdf = $this->get(route('pdf.profit-loss', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-10-14',
        ]));
        $pnlPdf->assertStatus(200);
        $pnlPdf->assertHeader('Content-Type', 'application/pdf');

        // Balance Sheet PDF
        $bsPdf = $this->get(route('pdf.balance-sheet', ['as_of_date' => '2026-10-14']));
        $bsPdf->assertStatus(200);
        $bsPdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tenant_isolation_in_chart_of_accounts_and_journals(): void
    {
        $otherTeam = Team::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Foreign Subsidiary Ltd',
            'personal_team' => false,
        ]);

        $this->accountingService->ensureDefaultAccounts($this->team->id);
        $this->accountingService->ensureDefaultAccounts($otherTeam->id);

        $otherAccount = ChartOfAccount::withoutGlobalScopes()->where('team_id', $otherTeam->id)->firstOrFail();

        // User in this->team cannot modify account belonging to otherTeam (route model binding 404s due to tenant scope)
        $response = $this->put(route('accounts.update', $otherAccount->id), [
            'name' => 'Hacked Name',
            'subtype' => null,
            'description' => null,
            'is_active' => true,
        ]);
        $response->assertStatus(404);

        // Accounts list only contains current team's accounts
        $this->get(route('accounts.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Accounting/Accounts/Index')
                ->has('accounts.data')
                ->where('accounts.data.0.team_id', $this->team->id)
            );
    }
}
