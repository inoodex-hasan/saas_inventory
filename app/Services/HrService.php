<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrService
{
    /**
     * Create a new employee with unique EMP-XXXX code.
     */
    public function createEmployee(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $teamId = Auth::user()?->current_team_id;

            $code = $data['employee_code'] ?? null;
            if (empty($code)) {
                $lastEmp = Employee::where('team_id', $teamId)->latest('id')->first();
                $nextNum = $lastEmp ? str_pad($lastEmp->id + 1, 4, '0', STR_PAD_LEFT) : '0001';
                $code = 'EMP-' . $nextNum;
            }

            return Employee::create([
                'team_id' => $teamId,
                'user_id' => $data['user_id'] ?? null,
                'employee_code' => $code,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'designation' => $data['designation'],
                'department' => $data['department'] ?? null,
                'joining_date' => $data['joining_date'] ?? now()->toDateString(),
                'basic_salary' => max(0, (float) ($data['basic_salary'] ?? 0)),
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_no' => $data['bank_account_no'] ?? null,
                'mobile_banking_no' => $data['mobile_banking_no'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Update existing employee details.
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'designation' => $data['designation'],
            'department' => $data['department'] ?? null,
            'joining_date' => $data['joining_date'] ?? $employee->joining_date,
            'basic_salary' => max(0, (float) ($data['basic_salary'] ?? $employee->basic_salary)),
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_no' => $data['bank_account_no'] ?? null,
            'mobile_banking_no' => $data['mobile_banking_no'] ?? null,
            'status' => $data['status'] ?? $employee->status,
            'notes' => $data['notes'] ?? null,
        ]);

        return $employee;
    }

    /**
     * Record a general business or office expense.
     */
    public function recordExpense(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $teamId = Auth::user()?->current_team_id;

            $prefix = 'EXP-' . Carbon::now()->format('Ymd') . '-';
            $last = Expense::where('team_id', $teamId)
                ->where('expense_number', 'LIKE', $prefix . '%')
                ->latest('id')
                ->first();
            $nextNum = $last ? str_pad((int) substr($last->expense_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $expenseNumber = $prefix . $nextNum;

            $accountId = $data['financial_account_id'] ?? null;
            $account = null;
            if ($accountId) {
                $account = \App\Models\FinancialAccount::where('team_id', $teamId)->find($accountId);
            }
            $paymentMethod = $account ? $account->name : ($data['payment_method'] ?? 'Cash');

            $expense = Expense::create([
                'team_id' => $teamId,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'financial_account_id' => $account?->id,
                'expense_number' => $expenseNumber,
                'title' => $data['title'],
                'amount' => max(0.01, (float) $data['amount']),
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'payment_method' => $paymentMethod,
                'vendor_or_payee' => $data['vendor_or_payee'] ?? null,
                'receipt_reference' => $data['receipt_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            if ($account) {
                $account->withdraw($expense->amount, [
                    'reference_type' => Expense::class,
                    'reference_id' => $expense->id,
                    'reference_number' => $expenseNumber,
                    'description' => "Expense voucher #{$expenseNumber}: {$expense->title}",
                    'date' => $expense->expense_date,
                ]);
            }

            return $expense;
        });
    }

    /**
     * Generate monthly payroll statement for an employee.
     */
    public function generatePayroll(
        Employee $employee,
        string $monthYear,
        float $otherAllowances = 0,
        float $deductions = 0
    ): Payroll {
        return DB::transaction(function () use ($employee, $monthYear, $otherAllowances, $deductions) {
            $teamId = $employee->team_id;

            $exists = Payroll::where('team_id', $teamId)
                ->where('employee_id', $employee->id)
                ->where('month_year', $monthYear)
                ->exists();

            if ($exists) {
                throw new \InvalidArgumentException("Payroll for {$monthYear} has already been generated for this employee.");
            }

            $basic = (float) $employee->basic_salary;
            $allowances = max(0, $otherAllowances);
            $deductionsTotal = max(0, $deductions);
            $netSalary = max(0, round($basic + $allowances - $deductionsTotal, 2));

            $prefix = 'PAY-' . str_replace('-', '', $monthYear) . '-';
            $last = Payroll::where('team_id', $teamId)
                ->where('payroll_number', 'LIKE', $prefix . '%')
                ->latest('id')
                ->first();
            $nextNum = $last ? str_pad((int) substr($last->payroll_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $payrollNumber = $prefix . $nextNum;

            $payroll = Payroll::create([
                'team_id' => $teamId,
                'employee_id' => $employee->id,
                'payroll_number' => $payrollNumber,
                'month_year' => $monthYear,
                'basic_salary' => $basic,
                'other_allowances' => $allowances,
                'deductions_total' => $deductionsTotal,
                'net_salary' => $netSalary,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            return $payroll;
        });
    }

    /**
     * Mark salary / payroll as paid.
     */
    public function markPayrollPaid(
        Payroll $payroll,
        string $paymentMethod = 'Bank Transfer',
        ?string $reference = null,
        ?string $paymentDate = null,
        ?int $financialAccountId = null
    ): Payroll {
        $account = null;
        if ($financialAccountId) {
            $account = \App\Models\FinancialAccount::where('team_id', $payroll->team_id)->find($financialAccountId);
        }

        $methodName = $account ? $account->name : $paymentMethod;

        $payroll->update([
            'status' => 'paid',
            'payment_date' => $paymentDate ?? now()->toDateString(),
            'payment_method' => $methodName,
            'financial_account_id' => $account?->id,
            'payment_reference' => $reference,
        ]);

        if ($account) {
            $account->withdraw($payroll->net_salary, [
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'reference_number' => $payroll->payroll_number,
                'description' => "Salary disbursement #{$payroll->payroll_number} for {$payroll->employee->name}",
                'date' => $paymentDate ?? now()->toDateString(),
            ]);
        }

        return $payroll;
    }
}
