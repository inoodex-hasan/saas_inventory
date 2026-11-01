<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\FinancialAccountService;
use App\Services\HrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index(Request $request, FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $status = $request->input('status');
        $monthYear = $request->input('month_year');

        $query = Payroll::where('team_id', $teamId)
            ->with(['employee', 'creator', 'financialAccount'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('payroll_number', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $status))
            ->when($request->filled('month_year'), fn ($q) => $q->where('month_year', $monthYear));

        $payrolls = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_payrolls' => Payroll::where('team_id', $teamId)->where('month_year', $monthYear)->count(),
            'total_disbursed' => (float) Payroll::where('team_id', $teamId)->where('month_year', $monthYear)->where('status', 'paid')->sum('net_salary'),
            'total_pending' => (float) Payroll::where('team_id', $teamId)->where('month_year', $monthYear)->whereIn('status', ['draft', 'approved'])->sum('net_salary'),
        ];

        $employees = Employee::where('team_id', $teamId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code', 'basic_salary', 'designation']);

        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        return Inertia::render('HR/Payroll/Index', [
            'page_title' => 'Monthly Staff Payroll & Salary Slips',
            'payrolls' => $payrolls,
            'metrics' => $metrics,
            'employees' => $employees,
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'month_year' => $monthYear,
            ],
        ]);
    }

    public function generate(Request $request, HrService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('team_id', $teamId)],
            'month_year' => 'required|date_format:Y-m',
            'other_allowances' => 'nullable|numeric|min:0',
            'deductions_total' => 'nullable|numeric|min:0',
        ], [
            'employee_id.exists' => 'The selected employee is invalid or does not belong to your workspace.',
        ]);

        $employee = Employee::where('team_id', $teamId)->findOrFail($validated['employee_id']);

        try {
            $payroll = $service->generatePayroll(
                $employee,
                $validated['month_year'],
                (float) ($validated['other_allowances'] ?? 0),
                (float) ($validated['deductions_total'] ?? 0)
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['employee_id' => $e->getMessage()]);
        }

        return back()->with('success', "Payroll statement {$payroll->payroll_number} for {$employee->name} generated.");
    }

    public function markPaid(Request $request, Payroll $payroll, HrService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($payroll->team_id !== $teamId, 403);

        $validated = $request->validate([
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
        ]);

        $service->markPayrollPaid(
            $payroll,
            $validated['payment_method'] ?? 'Bank Transfer',
            $validated['payment_reference'] ?? null,
            $validated['payment_date'] ?? null,
            $validated['financial_account_id'] ?? null
        );

        return back()->with('success', "Salary for {$payroll->employee->name} marked as disbursed & paid.");
    }

    public function destroy(Payroll $payroll)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($payroll->team_id !== $teamId, 403);

        if ($payroll->status === 'paid') {
            return back()->with('error', 'Cannot delete a salary slip that has already been marked as disbursed & paid.');
        }

        $num = $payroll->payroll_number;
        $payroll->delete();

        return back()->with('success', "Payroll statement {$num} deleted.");
    }
}
