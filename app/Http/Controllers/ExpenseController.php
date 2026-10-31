<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Services\FinancialAccountService;
use App\Services\HrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request, FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Expense::where('team_id', $teamId)
            ->with(['category', 'employee', 'creator', 'financialAccount'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('vendor_or_payee', 'like', "%{$search}%")
                        ->orWhere('receipt_reference', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('expense_category_id', $categoryId))
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('expense_date', '<=', $endDate));

        $expenses = $query->latest('expense_date')->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_expenses' => (float) Expense::where('team_id', $teamId)->sum('amount'),
            'this_month' => (float) Expense::where('team_id', $teamId)->whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'),
            'count' => Expense::where('team_id', $teamId)->count(),
        ];

        $categories = ExpenseCategory::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        if ($categories->isEmpty()) {
            $defaults = [
                ['name' => 'Facility Rent & Leases', 'code' => 'EXP-RENT', 'description' => 'Warehouse, office, or showroom rentals'],
                ['name' => 'Utilities & Internet', 'code' => 'EXP-UTIL', 'description' => 'Electricity, water, gas, broadband connectivity'],
                ['name' => 'Equipment & Maintenance', 'code' => 'EXP-MAINT', 'description' => 'Machinery repair, tools, and servicing'],
                ['name' => 'Vehicle Fuel & Transport', 'code' => 'EXP-FUEL', 'description' => 'Delivery fleet fuel, tolls, and cargo transport'],
                ['name' => 'Office Supplies & Stationery', 'code' => 'EXP-OFFICE', 'description' => 'Paper, toner, packaging materials, and refreshments'],
                ['name' => 'Marketing & Advertising', 'code' => 'EXP-MKT', 'description' => 'Promotions, digital ads, and printing'],
            ];
            foreach ($defaults as $d) {
                ExpenseCategory::create([
                    'team_id' => $teamId,
                    'name' => $d['name'],
                    'code' => $d['code'],
                    'description' => $d['description'],
                    'is_active' => true,
                ]);
            }
            $categories = ExpenseCategory::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        }
        $employees = Employee::where('team_id', $teamId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code']);

        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        return Inertia::render('HR/Expenses/Index', [
            'page_title' => 'Daily Business Expenses & Outflows',
            'expenses' => $expenses,
            'metrics' => $metrics,
            'categories' => $categories,
            'employees' => $employees,
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function store(Request $request, HrService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('team_id', $teamId)],
            'employee_id' => ['nullable', Rule::exists('employees', 'id')->where('team_id', $teamId)],
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'vendor_or_payee' => 'nullable|string|max:255',
            'receipt_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $expense = $service->recordExpense($validated);

        return back()->with('success', "Expense {$expense->expense_number} recorded successfully.");
    }

    public function destroy(Expense $expense)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($expense->team_id !== $teamId, 403);

        $num = $expense->expense_number;
        $expense->delete();

        return back()->with('success', "Expense {$num} deleted.");
    }

    public function storeCategory(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories')->where('team_id', $teamId),
            ],
            'description' => 'nullable|string|max:500',
        ]);

        $category = ExpenseCategory::create([
            'team_id' => $teamId,
            'name' => $validated['name'],
            'code' => 'EXP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $validated['name']), 0, 6)),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Expense category '{$category->name}' created successfully.");
    }

    public function destroyCategory(ExpenseCategory $expenseCategory)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($expenseCategory->team_id !== $teamId, 403);

        $name = $expenseCategory->name;
        $expenseCategory->delete();

        return back()->with('success', "Expense category '{$name}' deleted.");
    }
}
