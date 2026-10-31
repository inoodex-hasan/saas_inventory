<?php

namespace Database\Seeders;

use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Team;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    /**
     * Run the database seeds for Accounting & Finance menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        // 1. Chart of Accounts
        $accountsData = [
            ['code' => '1010', 'name' => 'Cash in Hand', 'type' => 'asset', 'subtype' => 'Current Asset', 'desc' => 'Physical till and emergency petty cash'],
            ['code' => '1020', 'name' => 'Main Operating Checking Bank Account', 'type' => 'asset', 'subtype' => 'Bank', 'desc' => 'Primary corporate checking account for deposits and wires'],
            ['code' => '1030', 'name' => 'Accounts Receivable (Trade Debtors)', 'type' => 'asset', 'subtype' => 'Current Asset', 'desc' => 'Amounts owed by customers for delivered goods and services'],
            ['code' => '1040', 'name' => 'Merchandise Inventory Asset', 'type' => 'asset', 'subtype' => 'Inventory', 'desc' => 'Balance of physical warehouse stock at landed cost'],
            ['code' => '2010', 'name' => 'Accounts Payable (Trade Creditors)', 'type' => 'liability', 'subtype' => 'Current Liability', 'desc' => 'Amounts owed to hardware vendors and freight suppliers'],
            ['code' => '2020', 'name' => 'Accrued Payroll & Withholding Taxes', 'type' => 'liability', 'subtype' => 'Current Liability', 'desc' => 'Staff salary withholdings pending disbursement'],
            ['code' => '3010', 'name' => 'Owner Capital & Contributed Surplus', 'type' => 'equity', 'subtype' => 'Equity', 'desc' => 'Initial and ongoing invested equity capital'],
            ['code' => '4010', 'name' => 'Merchandise Sales Revenue', 'type' => 'revenue', 'subtype' => 'Operating Revenue', 'desc' => 'Income generated from wholesale and retail sales orders'],
            ['code' => '4020', 'name' => 'Workshop & Technical Service Income', 'type' => 'revenue', 'subtype' => 'Service Revenue', 'desc' => 'Revenue earned from hardware diagnostics and repair labor'],
            ['code' => '5010', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'subtype' => 'Direct Expense', 'desc' => 'Direct inventory acquisition costs of items sold'],
            ['code' => '5020', 'name' => 'Facility Rent & Utilities Expense', 'type' => 'expense', 'subtype' => 'Operating Expense', 'desc' => 'Warehouse building leases and electricity power'],
            ['code' => '5030', 'name' => 'Staff Wages & Compensation Expense', 'type' => 'expense', 'subtype' => 'Operating Expense', 'desc' => 'Monthly payroll, bonuses, and travel allowances'],
        ];

        $accounts = [];
        foreach ($accountsData as $acc) {
            $accounts[$acc['code']] = ChartOfAccount::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'account_code' => $acc['code'],
                ],
                [
                    'name'        => $acc['name'],
                    'type'        => $acc['type'],
                    'subtype'     => $acc['subtype'],
                    'description' => $acc['desc'],
                    'is_system'   => true,
                    'is_active'   => true,
                ]
            );
        }

        // 2. Fiscal Years (satisfies fiscal_years >= 2)
        FiscalYear::updateOrCreate(
            [
                'team_id' => $team->id,
                'title'   => 'Fiscal Year 2025',
            ],
            [
                'start_date' => '2025-01-01',
                'end_date'   => '2025-12-31',
                'is_closed'  => true,
            ]
        );

        $fiscalYear = FiscalYear::updateOrCreate(
            [
                'team_id' => $team->id,
                'title'   => 'Fiscal Year 2026',
            ],
            [
                'start_date' => '2026-01-01',
                'end_date'   => '2026-12-31',
                'is_closed'  => false,
            ]
        );

        // 3. 5 Journal Entries
        $journalData = [
            [
                'number'    => 'JE-2026-001',
                'date'      => '2026-01-05',
                'narration' => 'Initial owner seed capital injected into corporate operating checking account.',
                'lines'     => [
                    ['account' => '1020', 'debit' => 50000.00, 'credit' => 0.00, 'memo' => 'Capital injection deposit'],
                    ['account' => '3010', 'debit' => 0.00, 'credit' => 50000.00, 'memo' => 'Owner capital contribution'],
                ],
            ],
            [
                'number'    => 'JE-2026-002',
                'date'      => '2026-09-15',
                'narration' => 'Supplier invoice payment for PO-2026-001 via bank wire transfer.',
                'lines'     => [
                    ['account' => '2010', 'debit' => 13537.50, 'credit' => 0.00, 'memo' => 'Accounts Payable cleared for TechSupply'],
                    ['account' => '1020', 'debit' => 0.00, 'credit' => 13537.50, 'memo' => 'Wire transfer from checking account'],
                ],
            ],
            [
                'number'    => 'JE-2026-003',
                'date'      => '2026-09-20',
                'narration' => 'Customer remittance received via ACH for sales invoice SO-2026-001.',
                'lines'     => [
                    ['account' => '1020', 'debit' => 6350.00, 'credit' => 0.00, 'memo' => 'ACH remittance received from Apex Cybernetics'],
                    ['account' => '1030', 'debit' => 0.00, 'credit' => 6350.00, 'memo' => 'Trade debtor receivable settled'],
                ],
            ],
            [
                'number'    => 'JE-2026-004',
                'date'      => '2026-09-25',
                'narration' => 'Warehouse facility monthly commercial power and fiber connectivity settlement.',
                'lines'     => [
                    ['account' => '5020', 'debit' => 1390.00, 'credit' => 0.00, 'memo' => 'Power ($1150) + Fiber Internet ($240)'],
                    ['account' => '1020', 'debit' => 0.00, 'credit' => 1390.00, 'memo' => 'Bank direct debit'],
                ],
            ],
            [
                'number'    => 'JE-2026-005',
                'date'      => '2026-09-30',
                'narration' => 'Monthly staff net salary disbursement for September payroll.',
                'lines'     => [
                    ['account' => '5030', 'debit' => 12335.00, 'credit' => 0.00, 'memo' => 'Staff payroll gross expense'],
                    ['account' => '1020', 'debit' => 0.00, 'credit' => 12335.00, 'memo' => 'Bulk payroll ACH release'],
                ],
            ],
        ];

        foreach ($journalData as $je) {
            $entry = JournalEntry::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'entry_number' => $je['number'],
                ],
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'entry_date'     => $je['date'],
                    'narration'      => $je['narration'],
                    'is_posted'      => true,
                    'created_by'     => $team->user_id,
                ]
            );

            // Re-create lines
            $entry->lines()->delete();
            foreach ($je['lines'] as $line) {
                if (isset($accounts[$line['account']])) {
                    JournalEntryLine::create([
                        'team_id'             => $team->id,
                        'journal_entry_id'    => $entry->id,
                        'chart_of_account_id' => $accounts[$line['account']]->id,
                        'debit'               => $line['debit'],
                        'credit'              => $line['credit'],
                        'memo'                => $line['memo'],
                    ]);
                }
            }
        }

        // 4. Bank Reconciliations (satisfies bank_reconciliations >= 2)
        if (isset($accounts['1020'])) {
            BankReconciliation::updateOrCreate(
                [
                    'team_id'             => $team->id,
                    'chart_of_account_id' => $accounts['1020']->id,
                    'reconciliation_date' => '2026-08-31',
                ],
                [
                    'statement_balance' => 15000.00,
                    'ledger_balance'    => 15000.00,
                    'difference'        => 0.00,
                    'status'            => 'reconciled',
                    'notes'             => 'August 2026 monthly bank statement verified and reconciled with zero variance.',
                    'reconciled_by'     => $team->user_id,
                    'reconciled_at'     => now()->subDays(35),
                ]
            );

            BankReconciliation::updateOrCreate(
                [
                    'team_id'             => $team->id,
                    'chart_of_account_id' => $accounts['1020']->id,
                    'reconciliation_date' => '2026-09-30',
                ],
                [
                    'statement_balance' => 29087.50,
                    'ledger_balance'    => 29087.50,
                    'difference'        => 0.00,
                    'status'            => 'reconciled',
                    'notes'             => 'September 2026 monthly bank statement verified and reconciled with zero variance.',
                    'reconciled_by'     => $team->user_id,
                    'reconciled_at'     => now()->subDays(5),
                ]
            );
        }

        // 5. Financial Accounts (satisfies financial_accounts >= 2)
        $cashAcc = FinancialAccount::updateOrCreate(
            [
                'team_id' => $team->id,
                'name'    => 'Main Cash Register Till',
            ],
            [
                'type'                => 'cash',
                'account_type'        => 'cash',
                'opening_balance'     => 5000.00,
                'current_balance'     => 7500.00,
                'is_default'          => true,
                'is_active'           => true,
                'chart_of_account_id' => $accounts['1010']?->id,
                'created_by'          => $team->user_id,
            ]
        );

        $bankAcc = FinancialAccount::updateOrCreate(
            [
                'team_id' => $team->id,
                'name'    => 'BRAC Bank Corporate A/C',
            ],
            [
                'type'                => 'bank',
                'institution_name'    => 'BRAC Bank',
                'account_number'      => '1501204891001',
                'account_type'        => 'current',
                'branch'              => 'Gulshan Corporate Branch',
                'opening_balance'     => 50000.00,
                'current_balance'     => 42812.50,
                'is_default'          => false,
                'is_active'           => true,
                'chart_of_account_id' => $accounts['1020']?->id,
                'created_by'          => $team->user_id,
            ]
        );

        // 6. Financial Transactions (satisfies financial_transactions >= 2)
        FinancialTransaction::updateOrCreate(
            [
                'team_id'              => $team->id,
                'reference_number'     => 'POS-CASH-001',
            ],
            [
                'financial_account_id' => $cashAcc->id,
                'transaction_type'     => 'deposit',
                'amount'               => 2500.00,
                'balance_after'        => 7500.00,
                'transaction_date'     => '2026-09-20',
                'reference_type'       => 'manual',
                'description'          => 'Counter POS sale cash receipt',
                'created_by'           => $team->user_id,
            ]
        );

        FinancialTransaction::updateOrCreate(
            [
                'team_id'              => $team->id,
                'reference_number'     => 'WIRE-TSG-8819',
            ],
            [
                'financial_account_id' => $bankAcc->id,
                'transaction_type'     => 'withdrawal',
                'amount'               => 13537.50,
                'balance_after'        => 36462.50,
                'transaction_date'     => '2026-09-15',
                'reference_type'       => 'manual',
                'description'          => 'Vendor supplier invoice wire transfer',
                'created_by'           => $team->user_id,
            ]
        );
    }
}
