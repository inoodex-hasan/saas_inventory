<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Team;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds for Staff Payroll menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $employees = Employee::where('team_id', $team->id)->get();
        if ($employees->isEmpty()) {
            return;
        }

        $monthYear = '2026-09';

        $payrollsData = [
            [
                'number'         => 'PAY-2026-001',
                'emp_idx'        => 0, // Daniel Cooper
                'basic'          => 4500.00,
                'tada'           => 0.00,
                'allowances'     => 250.00,
                'deductions'     => 180.00,
                'status'         => 'paid',
                'payment_date'   => '2026-09-30',
                'method'         => 'bank_transfer',
                'ref'            => 'ACH-SAL-882101',
                'notes'          => 'Direct deposit processed to primary checking account.',
            ],
            [
                'number'         => 'PAY-2026-002',
                'emp_idx'        => 1, // Rachel Green
                'basic'          => 3200.00,
                'tada'           => 275.00,
                'allowances'     => 150.00,
                'deductions'     => 110.00,
                'status'         => 'paid',
                'payment_date'   => '2026-09-30',
                'method'         => 'bank_transfer',
                'ref'            => 'ACH-SAL-882102',
                'notes'          => 'Includes reimbursed field diagnostic claim CLM-2026-002.',
            ],
            [
                'number'         => 'PAY-2026-003',
                'emp_idx'        => 2, // Brandon Murphy
                'basic'          => 3800.00,
                'tada'           => 755.00,
                'allowances'     => 300.00,
                'deductions'     => 140.00,
                'status'         => 'approved',
                'payment_date'   => null,
                'method'         => 'bank_transfer',
                'ref'            => null,
                'notes'          => 'Approved by finance. Scheduled for direct wire release.',
            ],
            [
                'number'         => 'PAY-2026-004',
                'emp_idx'        => 3, // Priya Sharma
                'basic'          => 4200.00,
                'tada'           => 0.00,
                'allowances'     => 200.00,
                'deductions'     => 160.00,
                'status'         => 'paid',
                'payment_date'   => '2026-09-30',
                'method'         => 'bank_transfer',
                'ref'            => 'ACH-SAL-882104',
                'notes'          => 'Monthly salary slip sent to corporate email.',
            ],
            [
                'number'         => 'PAY-2026-005',
                'emp_idx'        => 4, // Kevin O'Connor
                'basic'          => 2800.00,
                'tada'           => 245.00,
                'allowances'     => 100.00,
                'deductions'     => 95.00,
                'status'         => 'draft',
                'payment_date'   => null,
                'method'         => null,
                'ref'            => null,
                'notes'          => 'Draft calculations pending attendance log reconciliation.',
            ],
        ];

        foreach ($payrollsData as $p) {
            $emp = $employees[$p['emp_idx'] % $employees->count()];
            $net = $p['basic'] + $p['tada'] + $p['allowances'] - $p['deductions'];

            Payroll::updateOrCreate(
                [
                    'team_id'     => $team->id,
                    'employee_id' => $emp->id,
                    'month_year'  => $monthYear,
                ],
                [
                    'payroll_number'   => $p['number'],
                    'basic_salary'     => $p['basic'],
                    'tada_allowance'   => $p['tada'],
                    'other_allowances' => $p['allowances'],
                    'deductions_total' => $p['deductions'],
                    'net_salary'       => $net,
                    'status'           => $p['status'],
                    'payment_date'     => $p['payment_date'],
                    'payment_method'   => $p['method'],
                    'payment_reference'=> $p['ref'],
                    'notes'            => $p['notes'],
                    'created_by'       => $team->user_id,
                ]
            );
        }
    }
}
