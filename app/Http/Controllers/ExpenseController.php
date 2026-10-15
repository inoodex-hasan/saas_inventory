<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\HrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $categoryId = $request->input('category_id', '');
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');

        $query = Expense::where('team_id', $teamId)
            ->with(['category', 'employee', 'creator'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('vendor_or_payee', 'like', "%{$search}%")
                        ->orWhere('receipt_reference', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== '', fn ($q) => $q->where('expense_category_id', $categoryId))
            ->when($startDate !== '', fn ($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate !== '', fn ($q) => $q->whereDate('expense_date', '<=', $endDate));

        $expenses = $query->latest('expense_date')->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_expenses' => (float) Expense::where('team_id', $teamId)->sum('amount'),
            'this_month' => (float) Expense::where('team_id', $teamId)->whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'),
            'count' => Expense::where('team_id', $teamId)->count(),
        ];

        $categories = ExpenseCategory::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $employees = Employee::where('team_id', $teamId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code']);

        return Inertia::render('HR/Expenses/Index', [
            'page_title' => 'Daily Business Expenses & Outflows',
            'expenses' => $expenses,
            'metrics' => $metrics,
            'categories' => $categories,
            'employees' => $employees,
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'employee_id' => 'nullable|exists:employees,id',
            'expense_date' => 'required|date',
            'payment_method' => 'required|string|max:50',
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
}
