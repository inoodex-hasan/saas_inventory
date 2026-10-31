<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\BankReconciliation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Standard default Chart of Accounts template.
     */
    public const DEFAULT_ACCOUNTS = [
        // Assets (1000s)
        ['code' => '1010', 'name' => 'Cash in Hand / Petty Cash', 'type' => 'asset', 'subtype' => 'current_asset'],
        ['code' => '1020', 'name' => 'Bank Operating Account', 'type' => 'asset', 'subtype' => 'current_asset'],
        ['code' => '1030', 'name' => 'Accounts Receivable (Customers)', 'type' => 'asset', 'subtype' => 'current_asset'],
        ['code' => '1040', 'name' => 'Inventory Merchandise Stock', 'type' => 'asset', 'subtype' => 'current_asset'],
        ['code' => '1050', 'name' => 'Prepaid Expenses & Advances', 'type' => 'asset', 'subtype' => 'current_asset'],
        ['code' => '1510', 'name' => 'Office Equipment & Hardware', 'type' => 'asset', 'subtype' => 'fixed_asset'],

        // Liabilities (2000s)
        ['code' => '2010', 'name' => 'Accounts Payable (Vendors)', 'type' => 'liability', 'subtype' => 'current_liability'],
        ['code' => '2020', 'name' => 'Customer Advance Deposits', 'type' => 'liability', 'subtype' => 'current_liability'],
        ['code' => '2030', 'name' => 'Sales Tax / VAT Payable', 'type' => 'liability', 'subtype' => 'current_liability'],
        ['code' => '2040', 'name' => 'Accrued Salaries & Wages', 'type' => 'liability', 'subtype' => 'current_liability'],

        // Equity (3000s)
        ['code' => '3010', 'name' => "Owner's / Shareholder Capital", 'type' => 'equity', 'subtype' => 'equity'],
        ['code' => '3020', 'name' => 'Retained Earnings', 'type' => 'equity', 'subtype' => 'equity'],

        // Revenue (4000s)
        ['code' => '4010', 'name' => 'Product Sales Revenue', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
        ['code' => '4020', 'name' => 'Repair & Workshop Services Revenue', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
        ['code' => '4030', 'name' => 'Project Contracting & Billing Revenue', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
        ['code' => '4040', 'name' => 'Discounts Given', 'type' => 'revenue', 'subtype' => 'contra_revenue'],

        // Expenses (5000s)
        ['code' => '5010', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'subtype' => 'cogs'],
        ['code' => '5020', 'name' => 'Staff Salaries & Wages Expense', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ['code' => '5030', 'name' => 'Field TA/DA & Conveyance Expense', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ['code' => '5040', 'name' => 'Office Rent & Facilities', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ['code' => '5050', 'name' => 'Utilities, Internet & Telecom', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ['code' => '5060', 'name' => 'Direct Project & Subcontractor Costs', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ['code' => '5070', 'name' => 'General & Administrative Overheads', 'type' => 'expense', 'subtype' => 'operating_expense'],
    ];

    /**
     * Ensure default Chart of Accounts exists for a tenant team.
     */
    public function ensureDefaultAccounts(int $teamId): void
    {
        $existingCount = ChartOfAccount::where('team_id', $teamId)->count();
        if ($existingCount > 0) {
            return;
        }

        foreach (self::DEFAULT_ACCOUNTS as $acc) {
            ChartOfAccount::create([
                'team_id' => $teamId,
                'account_code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'subtype' => $acc['subtype'],
                'is_system' => true,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Record a balanced Double-Entry Journal Entry.
     */
    public function recordJournalEntry(array $data, array $lines): JournalEntry
    {
        if (count($lines) < 2) {
            throw new \InvalidArgumentException("A journal entry requires at least two line items.");
        }

        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $preparedLines = [];

        foreach ($lines as $line) {
            $debit = max(0, round((float) ($line['debit'] ?? 0), 2));
            $credit = max(0, round((float) ($line['credit'] ?? 0), 2));

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException("A single journal line cannot contain both debit and credit amounts.");
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $preparedLines[] = [
                'chart_of_account_id' => (int) $line['chart_of_account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if ($totalDebit <= 0) {
            throw new \InvalidArgumentException("Journal entry amount must be greater than zero.");
        }

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw new \InvalidArgumentException("Journal entry is unbalanced. Total Debits: " . number_format($totalDebit, 2) . ", Total Credits: " . number_format($totalCredit, 2));
        }

        return DB::transaction(function () use ($data, $preparedLines) {
            $teamId = Auth::user()?->current_team_id;

            $prefix = 'JE-' . Carbon::now()->format('Ymd') . '-';
            $last = JournalEntry::where('team_id', $teamId)
                ->where('entry_number', 'LIKE', $prefix . '%')
                ->latest('id')
                ->first();
            $nextNum = $last ? str_pad((int) substr($last->entry_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $entryNumber = $prefix . $nextNum;

            $entry = JournalEntry::create([
                'team_id' => $teamId,
                'fiscal_year_id' => $data['fiscal_year_id'] ?? null,
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'narration' => $data['narration'],
                'is_posted' => true,
                'created_by' => Auth::id(),
            ]);

            foreach ($preparedLines as $pl) {
                JournalEntryLine::create([
                    'team_id' => $teamId,
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $pl['chart_of_account_id'],
                    'debit' => $pl['debit'],
                    'credit' => $pl['credit'],
                    'memo' => $pl['memo'],
                ]);
            }

            return $entry->load(['lines.account']);
        });
    }

    /**
     * Generate General Ledger for a specific account.
     */
    public function getGeneralLedger(int $teamId, int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = ChartOfAccount::where('team_id', $teamId)->findOrFail($accountId);

        // Opening balance before start date
        $openingQuery = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->where('chart_of_account_id', $accountId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id');

        if ($startDate) {
            $openingQuery->where('journal_entries.entry_date', '<', $startDate);
        }

        $openingDebits = (float) $openingQuery->sum('journal_entry_lines.debit');
        $openingCredits = (float) $openingQuery->sum('journal_entry_lines.credit');

        $isDebitNormal = in_array($account->type, ['asset', 'expense']);
        $openingBalance = $isDebitNormal ? ($openingDebits - $openingCredits) : ($openingCredits - $openingDebits);

        // Transactions in period
        $transQuery = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->where('chart_of_account_id', $accountId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->select(
                'journal_entry_lines.*',
                'journal_entries.entry_number',
                'journal_entries.entry_date',
                'journal_entries.narration'
            )
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id');

        if ($startDate) {
            $transQuery->where('journal_entries.entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $transQuery->where('journal_entries.entry_date', '<=', $endDate);
        }

        $records = $transQuery->get();

        $runningBalance = $openingBalance;
        $ledgerEntries = [];
        $totalPeriodDebit = 0;
        $totalPeriodCredit = 0;

        foreach ($records as $r) {
            $debit = (float) $r->debit;
            $credit = (float) $r->credit;
            $totalPeriodDebit += $debit;
            $totalPeriodCredit += $credit;

            if ($isDebitNormal) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $ledgerEntries[] = [
                'id' => $r->id,
                'entry_number' => $r->entry_number,
                'entry_date' => $r->entry_date,
                'narration' => $r->narration,
                'memo' => $r->memo,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($runningBalance, 2),
            ];
        }

        return [
            'account' => $account,
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($runningBalance, 2),
            'total_debit' => round($totalPeriodDebit, 2),
            'total_credit' => round($totalPeriodCredit, 2),
            'total_debits' => round($totalPeriodDebit, 2),
            'total_credits' => round($totalPeriodCredit, 2),
            'entries' => $ledgerEntries,
            'transactions' => $ledgerEntries,
        ];
    }

    /**
     * Generate Trial Balance.
     */
    public function getTrialBalance(int $teamId, ?string $asOfDate = null): array
    {
        $accounts = ChartOfAccount::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $balances = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->when($asOfDate, fn ($q) => $q->where('journal_entries.entry_date', '<=', $asOfDate))
            ->selectRaw('
                journal_entry_lines.chart_of_account_id,
                SUM(journal_entry_lines.debit) as total_debit,
                SUM(journal_entry_lines.credit) as total_credit
            ')
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $rows = [];
        $grandTotalDebit = 0.00;
        $grandTotalCredit = 0.00;

        foreach ($accounts as $acc) {
            $line = $balances->get($acc->id);
            $totalDebit = $line ? (float) $line->total_debit : 0.00;
            $totalCredit = $line ? (float) $line->total_credit : 0.00;

            if ($totalDebit == 0 && $totalCredit == 0) {
                continue;
            }

            $isDebitNormal = in_array($acc->type, ['asset', 'expense']);
            $net = $isDebitNormal ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);

            $debitBalance = 0.00;
            $creditBalance = 0.00;

            if ($isDebitNormal) {
                if ($net >= 0) {
                    $debitBalance = $net;
                } else {
                    $creditBalance = abs($net);
                }
            } else {
                if ($net >= 0) {
                    $creditBalance = $net;
                } else {
                    $debitBalance = abs($net);
                }
            }

            $grandTotalDebit += $debitBalance;
            $grandTotalCredit += $creditBalance;

            $rows[] = [
                'id' => $acc->id,
                'account_code' => $acc->account_code,
                'name' => $acc->name,
                'account_name' => $acc->name,
                'type' => $acc->type,
                'debit' => round($debitBalance, 2),
                'credit' => round($creditBalance, 2),
                'debit_balance' => round($debitBalance, 2),
                'credit_balance' => round($creditBalance, 2),
            ];
        }

        $grandTotalDebit = round($grandTotalDebit, 2);
        $grandTotalCredit = round($grandTotalCredit, 2);
        $isBalanced = abs($grandTotalDebit - $grandTotalCredit) < 0.01;

        return [
            'as_of_date' => $asOfDate ?? now()->toDateString(),
            'rows' => $rows,
            'total_debit' => $grandTotalDebit,
            'total_credit' => $grandTotalCredit,
            'is_balanced' => $isBalanced,
            'difference' => round(abs($grandTotalDebit - $grandTotalCredit), 2),
        ];
    }

    /**
     * Generate Profit and Loss (Income Statement).
     */
    public function getProfitAndLoss(int $teamId, string $startDate, string $endDate): array
    {
        $periodBalances = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereBetween('journal_entries.entry_date', [$startDate, $endDate])
            ->selectRaw('
                journal_entry_lines.chart_of_account_id,
                SUM(journal_entry_lines.debit) as total_debit,
                SUM(journal_entry_lines.credit) as total_credit
            ')
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        // 1. Operating Revenue
        $revenueAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'revenue')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $revenueRows = [];
        $totalRevenue = 0.00;

        foreach ($revenueAccounts as $acc) {
            $line = $periodBalances->get($acc->id);
            $credits = $line ? (float) $line->total_credit : 0.00;
            $debits = $line ? (float) $line->total_debit : 0.00;
            $amount = round($credits - $debits, 2);

            if ($amount != 0) {
                $totalRevenue += $amount;
                $revenueRows[] = [
                    'account_code' => $acc->account_code,
                    'name' => $acc->name,
                    'account_name' => $acc->name,
                    'amount' => $amount,
                    'balance' => $amount,
                ];
            }
        }

        // 2. Cost of Goods Sold (COGS)
        $cogsAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'expense')
            ->where('subtype', 'cogs')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $cogsRows = [];
        $totalCogs = 0.00;

        foreach ($cogsAccounts as $acc) {
            $line = $periodBalances->get($acc->id);
            $debits = $line ? (float) $line->total_debit : 0.00;
            $credits = $line ? (float) $line->total_credit : 0.00;
            $amount = round($debits - $credits, 2);

            if ($amount != 0) {
                $totalCogs += $amount;
                $cogsRows[] = [
                    'account_code' => $acc->account_code,
                    'name' => $acc->name,
                    'account_name' => $acc->name,
                    'amount' => $amount,
                    'balance' => $amount,
                ];
            }
        }

        $grossProfit = round($totalRevenue - $totalCogs, 2);

        // 3. Operating Expenses
        $expenseAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'expense')
            ->where('subtype', '!=', 'cogs')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $expenseRows = [];
        $totalOperatingExpenses = 0.00;

        foreach ($expenseAccounts as $acc) {
            $line = $periodBalances->get($acc->id);
            $debits = $line ? (float) $line->total_debit : 0.00;
            $credits = $line ? (float) $line->total_credit : 0.00;
            $amount = round($debits - $credits, 2);

            if ($amount != 0) {
                $totalOperatingExpenses += $amount;
                $expenseRows[] = [
                    'account_code' => $acc->account_code,
                    'name' => $acc->name,
                    'account_name' => $acc->name,
                    'amount' => $amount,
                    'balance' => $amount,
                ];
            }
        }

        $netIncome = round($grossProfit - $totalOperatingExpenses, 2);

        $totalExpenses = round($totalCogs + $totalOperatingExpenses, 2);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue_rows' => $revenueRows,
            'revenue_accounts' => $revenueRows,
            'total_revenue' => round($totalRevenue, 2),
            'cogs_rows' => $cogsRows,
            'total_cogs' => round($totalCogs, 2),
            'gross_profit' => $grossProfit,
            'expense_rows' => $expenseRows,
            'expense_accounts' => array_merge($cogsRows, $expenseRows),
            'total_operating_expenses' => round($totalOperatingExpenses, 2),
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    /**
     * Generate Balance Sheet (Statement of Financial Position).
     */
    public function getBalanceSheet(int $teamId, string $asOfDate): array
    {
        $balancesAsOf = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.entry_date', '<=', $asOfDate)
            ->selectRaw('
                journal_entry_lines.chart_of_account_id,
                SUM(journal_entry_lines.debit) as total_debit,
                SUM(journal_entry_lines.credit) as total_credit
            ')
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        // 1. Assets (Debits - Credits)
        $assetAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $assetRows = [];
        $totalAssets = 0.00;

        foreach ($assetAccounts as $acc) {
            $line = $balancesAsOf->get($acc->id);
            $debits = $line ? (float) $line->total_debit : 0.00;
            $credits = $line ? (float) $line->total_credit : 0.00;
            $amount = round($debits - $credits, 2);

            if ($amount != 0) {
                $totalAssets += $amount;
                $assetRows[] = [
                    'account_code' => $acc->account_code,
                    'name' => $acc->name,
                    'account_name' => $acc->name,
                    'subtype' => $acc->subtype,
                    'amount' => $amount,
                    'balance' => $amount,
                ];
            }
        }

        // 2. Liabilities (Credits - Debits)
        $liabilityAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'liability')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $liabilityRows = [];
        $totalLiabilities = 0.00;

        foreach ($liabilityAccounts as $acc) {
            $line = $balancesAsOf->get($acc->id);
            $credits = $line ? (float) $line->total_credit : 0.00;
            $debits = $line ? (float) $line->total_debit : 0.00;
            $amount = round($credits - $debits, 2);

            if ($amount != 0) {
                $totalLiabilities += $amount;
                $liabilityRows[] = [
                    'account_code' => $acc->account_code,
                    'name' => $acc->name,
                    'account_name' => $acc->name,
                    'subtype' => $acc->subtype,
                    'amount' => $amount,
                    'balance' => $amount,
                ];
            }
        }

        // 3. Equity (Credits - Debits)
        $equityAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'equity')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $equityRows = [];
        $totalEquityBase = 0.00;

        foreach ($equityAccounts as $acc) {
            $line = $balancesAsOf->get($acc->id);
            $credits = $line ? (float) $line->total_credit : 0.00;
            $debits = $line ? (float) $line->total_debit : 0.00;
            $amount = round($credits - $debits, 2);

            if ($amount != 0) {
                $totalEquityBase += $amount;
                $equityRows[] = [
                    'account_code' => $acc->account_code,
                    'name' => $acc->name,
                    'account_name' => $acc->name,
                    'amount' => $amount,
                    'balance' => $amount,
                ];
            }
        }

        // Calculate Retained Earnings from all prior revenue and expenses
        $revQuery = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('chart_of_accounts.type', 'revenue')
            ->where('journal_entries.entry_date', '<=', $asOfDate);

        $totalRevenueToDate = (float) $revQuery->sum('credit') - (float) $revQuery->sum('debit');

        $expQuery = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('chart_of_accounts.type', 'expense')
            ->where('journal_entries.entry_date', '<=', $asOfDate);

        $totalExpenseToDate = (float) $expQuery->sum('debit') - (float) $expQuery->sum('credit');

        $netIncomeToDate = round($totalRevenueToDate - $totalExpenseToDate, 2);

        $totalEquity = round($totalEquityBase + $netIncomeToDate, 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return [
            'as_of_date' => $asOfDate,
            'asset_rows' => $assetRows,
            'asset_accounts' => $assetRows,
            'total_assets' => round($totalAssets, 2),
            'liability_rows' => $liabilityRows,
            'liability_accounts' => $liabilityRows,
            'total_liabilities' => round($totalLiabilities, 2),
            'equity_rows' => $equityRows,
            'equity_accounts' => $equityRows,
            'net_income_to_date' => $netIncomeToDate,
            'current_period_net_income' => $netIncomeToDate,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => $isBalanced,
            'difference' => round(abs($totalAssets - $totalLiabilitiesAndEquity), 2),
        ];
    }

    /**
     * Perform Bank Statement Reconciliation.
     */
    public function reconcileBank(
        int $teamId,
        int $accountId,
        float $statementBalance,
        string $reconciliationDate,
        ?string $notes = null
    ): BankReconciliation {
        $account = ChartOfAccount::where('team_id', $teamId)->findOrFail($accountId);

        $query = JournalEntryLine::where('journal_entry_lines.team_id', $teamId)
            ->where('chart_of_account_id', $accountId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.entry_date', '<=', $reconciliationDate);

        $debits = (float) $query->sum('debit');
        $credits = (float) $query->sum('credit');
        $ledgerBalance = round($debits - $credits, 2);

        $difference = round($statementBalance - $ledgerBalance, 2);
        $status = abs($difference) < 0.01 ? 'reconciled' : 'in_progress';

        return BankReconciliation::create([
            'team_id' => $teamId,
            'chart_of_account_id' => $accountId,
            'reconciliation_date' => $reconciliationDate,
            'statement_balance' => $statementBalance,
            'ledger_balance' => $ledgerBalance,
            'difference' => $difference,
            'status' => $status,
            'notes' => $notes,
            'reconciled_by' => Auth::id(),
            'reconciled_at' => $status === 'reconciled' ? now() : null,
        ]);
    }
}
