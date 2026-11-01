<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FinancialAccountTest extends TestCase
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
        $this->accountingService->ensureDefaultAccounts($this->team->id);
    }

    public function test_user_can_access_bank_accounts_index(): void
    {
        FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'current_balance' => 15000.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('bank-accounts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Accounting/BankAccounts/Index')
            ->has('accounts', 1)
            ->where('accounts.0.name', 'Main Cash Drawer')
            ->has('metrics')
        );
    }

    public function test_user_can_create_bank_account_with_opening_balance_and_journal_entry(): void
    {
        $response = $this->post(route('bank-accounts.store'), [
            'name' => 'BRAC Bank Corporate',
            'type' => 'bank',
            'institution_name' => 'BRAC Bank PLC',
            'account_number' => '1501204567890001',
            'branch' => 'Gulshan 1 Branch',
            'routing_number' => '060261234',
            'opening_balance' => 50000.00,
            'is_default' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $account = FinancialAccount::where('team_id', $this->team->id)->where('name', 'BRAC Bank Corporate')->first();
        $this->assertNotNull($account);
        $this->assertEquals('bank', $account->type);
        $this->assertEquals(50000.00, (float) $account->current_balance);
        $this->assertTrue($account->is_default);

        // Verify initial transaction ledger
        $this->assertDatabaseHas('financial_transactions', [
            'team_id' => $this->team->id,
            'financial_account_id' => $account->id,
            'reference_number' => 'INIT-BAL',
            'amount' => 50000.00,
        ]);

        // Verify double-entry journal entry was posted
        $journal = JournalEntry::where('team_id', $this->team->id)
            ->where('reference_type', FinancialAccount::class)
            ->where('reference_id', $account->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals(2, $journal->lines()->count());

        $bankCoa = ChartOfAccount::where('team_id', $this->team->id)->where('account_code', '1020')->first();
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $bankCoa->id,
            'debit' => 50000.00,
            'credit' => 0.00,
        ]);
    }

    public function test_user_can_create_cash_and_mfs_accounts(): void
    {
        $response1 = $this->post(route('bank-accounts.store'), [
            'name' => 'Outlet Cash Counter 1',
            'type' => 'cash',
            'opening_balance' => 10000.00,
        ]);
        $response1->assertRedirect();

        $response2 = $this->post(route('bank-accounts.store'), [
            'name' => 'bKash Merchant Wallet',
            'type' => 'mfs',
            'institution_name' => 'bKash',
            'account_number' => '01700000000',
            'opening_balance' => 20000.00,
        ]);
        $response2->assertRedirect();

        $this->assertDatabaseHas('financial_accounts', [
            'team_id' => $this->team->id,
            'name' => 'Outlet Cash Counter 1',
            'type' => 'cash',
        ]);

        $this->assertDatabaseHas('financial_accounts', [
            'team_id' => $this->team->id,
            'name' => 'bKash Merchant Wallet',
            'type' => 'mfs',
        ]);
    }

    public function test_user_can_update_financial_account(): void
    {
        $account = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'City Bank Principal',
            'type' => 'bank',
            'institution_name' => 'City Bank',
            'account_number' => '2200112233',
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);

        $response = $this->put(route('bank-accounts.update', $account->id), [
            'name' => 'City Bank VIP Corporate',
            'type' => 'bank',
            'institution_name' => 'City Bank PLC',
            'account_number' => '2200112233',
            'branch' => 'Motijheel Branch',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $account->refresh();
        $this->assertEquals('City Bank VIP Corporate', $account->name);
        $this->assertEquals('Motijheel Branch', $account->branch);
    }

    public function test_user_can_set_account_as_default(): void
    {
        $acc1 = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Account 1',
            'type' => 'cash',
            'is_default' => true,
            'is_active' => true,
        ]);

        $acc2 = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Account 2',
            'type' => 'bank',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->post(route('bank-accounts.set-default', $acc2->id));
        $response->assertRedirect();

        $acc1->refresh();
        $acc2->refresh();

        $this->assertFalse($acc1->is_default);
        $this->assertTrue($acc2->is_default);
    }

    public function test_user_can_toggle_account_active_status(): void
    {
        $account = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Nagad Account',
            'type' => 'mfs',
            'is_active' => true,
        ]);

        $response = $this->post(route('bank-accounts.toggle-active', $account->id));
        $response->assertRedirect();

        $account->refresh();
        $this->assertFalse($account->is_active);

        $response2 = $this->post(route('bank-accounts.toggle-active', $account->id));
        $response2->assertRedirect();

        $account->refresh();
        $this->assertTrue($account->is_active);
    }

    public function test_cannot_delete_account_with_transaction_history(): void
    {
        $account = FinancialAccount::create([
            'team_id' => $this->team->id,
            'name' => 'Operational Bank',
            'type' => 'bank',
            'current_balance' => 5000.00,
        ]);

        FinancialTransaction::create([
            'team_id' => $this->team->id,
            'financial_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 5000.00,
            'balance_after' => 5000.00,
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'TRX-OPERATIONAL-99',
            'description' => 'Regular customer deposit',
        ]);

        $response = $this->delete(route('bank-accounts.destroy', $account->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('financial_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_delete_account_with_only_initial_balance_and_cascades_journal(): void
    {
        $this->post(route('bank-accounts.store'), [
            'name' => 'Temporary Test Account',
            'type' => 'bank',
            'opening_balance' => 15000.00,
        ]);

        $account = FinancialAccount::where('team_id', $this->team->id)->where('name', 'Temporary Test Account')->first();
        $journal = JournalEntry::where('reference_type', FinancialAccount::class)->where('reference_id', $account->id)->first();
        $this->assertNotNull($journal);

        $delResponse = $this->delete(route('bank-accounts.destroy', $account->id));
        $delResponse->assertRedirect();
        $delResponse->assertSessionHas('success');

        $this->assertSoftDeleted('financial_accounts', ['id' => $account->id]);
        $this->assertDatabaseMissing('financial_transactions', ['financial_account_id' => $account->id]);
        $this->assertDatabaseMissing('journal_entries', ['id' => $journal->id]);
        $this->assertDatabaseMissing('journal_entry_lines', ['journal_entry_id' => $journal->id]);
    }

    public function test_tenant_isolation_in_financial_accounts(): void
    {
        $otherTeam = Team::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Other Business Entity',
            'personal_team' => false,
        ]);

        $otherAccount = FinancialAccount::create([
            'team_id' => $otherTeam->id,
            'name' => 'Secret Vault Account',
            'type' => 'cash',
            'current_balance' => 999999.00,
            'is_active' => true,
        ]);

        // Attempt to update other tenant's account (Route Model Binding scopes by tenant, returning 404)
        $response = $this->put(route('bank-accounts.update', $otherAccount->id), [
            'name' => 'Hijacked Account',
            'type' => 'cash',
        ]);
        $response->assertStatus(404);

        // Attempt to toggle active
        $responseToggle = $this->post(route('bank-accounts.toggle-active', $otherAccount->id));
        $responseToggle->assertStatus(404);

        // Attempt to delete
        $responseDelete = $this->delete(route('bank-accounts.destroy', $otherAccount->id));
        $responseDelete->assertStatus(404);
    }
}
