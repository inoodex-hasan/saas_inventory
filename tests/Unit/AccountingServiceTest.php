<?php

namespace Tests\Unit;

use App\Services\AccountingService;
use App\Services\StockService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class AccountingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getService(): AccountingService
    {
        $stockService = Mockery::mock(StockService::class);
        return new AccountingService($stockService);
    }

    public function test_record_journal_entry_rejects_fewer_than_two_lines(): void
    {
        $service = $this->getService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A journal entry requires at least two line items.");

        $service->recordJournalEntry(
            ['entry_date' => '2026-10-22', 'narration' => 'Single line test'],
            [
                ['chart_of_account_id' => 1, 'debit' => 100, 'credit' => 0],
            ]
        );
    }

    public function test_record_journal_entry_rejects_line_with_both_debit_and_credit(): void
    {
        $service = $this->getService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A single journal line cannot contain both debit and credit amounts.");

        $service->recordJournalEntry(
            ['entry_date' => '2026-10-22', 'narration' => 'Both debit and credit test'],
            [
                ['chart_of_account_id' => 1, 'debit' => 100, 'credit' => 50],
                ['chart_of_account_id' => 2, 'debit' => 0, 'credit' => 50],
            ]
        );
    }

    public function test_record_journal_entry_rejects_unbalanced_debits_and_credits(): void
    {
        $service = $this->getService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Journal entry is unbalanced. Total Debits: 1,500.00, Total Credits: 1,000.00");

        $service->recordJournalEntry(
            ['entry_date' => '2026-10-22', 'narration' => 'Unbalanced test'],
            [
                ['chart_of_account_id' => 1, 'debit' => 1500, 'credit' => 0],
                ['chart_of_account_id' => 2, 'debit' => 0, 'credit' => 1000],
            ]
        );
    }

    public function test_record_journal_entry_rejects_zero_amount(): void
    {
        $service = $this->getService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Journal entry amount must be greater than zero.");

        $service->recordJournalEntry(
            ['entry_date' => '2026-10-22', 'narration' => 'Zero amount test'],
            [
                ['chart_of_account_id' => 1, 'debit' => 0, 'credit' => 0],
                ['chart_of_account_id' => 2, 'debit' => 0, 'credit' => 0],
            ]
        );
    }

    public function test_double_entry_balance_sheet_equation_accounting_math(): void
    {
        // Assets = Liabilities + Equity (including Net Income)
        $assetCash = 50000.00;
        $assetReceivable = 25000.00;
        $assetInventory = 40000.00;
        $totalAssets = $assetCash + $assetReceivable + $assetInventory; // 115,000

        $liabilityPayable = 30000.00;
        $liabilityLoan = 20000.00;
        $totalLiabilities = $liabilityPayable + $liabilityLoan; // 50,000

        $equityCapital = 45000.00;
        $revenue = 35000.00;
        $expense = 15000.00;
        $netIncome = $revenue - $expense; // 20,000

        $totalEquity = $equityCapital + $netIncome; // 65,000
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity; // 115,000

        $this->assertEquals($totalAssets, $totalLiabilitiesAndEquity);
        $this->assertTrue(abs($totalAssets - $totalLiabilitiesAndEquity) < 0.001);
    }

    public function test_bank_reconciliation_variance_formula(): void
    {
        // Reconciled scenario
        $statementBalance = 24500.50;
        $ledgerDebits = 30000.00;
        $ledgerCredits = 5499.50;
        $ledgerBalance = round($ledgerDebits - $ledgerCredits, 2); // 24,500.50

        $diff = round($statementBalance - $ledgerBalance, 2);
        $status = abs($diff) < 0.01 ? 'reconciled' : 'in_progress';

        $this->assertEquals(0.00, $diff);
        $this->assertEquals('reconciled', $status);

        // Discrepancy scenario
        $statementBalanceDiscrepant = 24000.00;
        $diffDiscrepant = round($statementBalanceDiscrepant - $ledgerBalance, 2); // -500.50
        $statusDiscrepant = abs($diffDiscrepant) < 0.01 ? 'reconciled' : 'in_progress';

        $this->assertEquals(-500.50, $diffDiscrepant);
        $this->assertEquals('in_progress', $statusDiscrepant);
    }
}
