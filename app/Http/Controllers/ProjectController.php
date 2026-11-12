<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CostCategory;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectItem;
use App\Models\Warehouse;
use App\Services\FinancialAccountService;
use App\Services\ProjectService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request, FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $clientId = $request->input('client_id');

        $query = Project::where('team_id', $teamId)
            ->with(['client', 'creator'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('project_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $status))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $clientId));

        $projects = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_projects' => Project::where('team_id', $teamId)->count(),
            'in_progress' => Project::where('team_id', $teamId)->where('status', 'in_progress')->count(),
            'completed' => Project::where('team_id', $teamId)->where('status', 'completed')->count(),
            'total_budget' => (float) Project::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('budget'),
            'total_costs' => (float) Project::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('costs_total'),
            'total_billed' => (float) Project::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('billed_total'),
            'total_paid' => (float) Project::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('paid_amount'),
            'total_due' => (float) Project::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('due_amount'),
        ];
        $metrics['gross_margin'] = round($metrics['total_billed'] - $metrics['total_costs'], 2);

        $clients = Client::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']);

        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        return Inertia::render('Projects/Index', [
            'page_title' => 'Project Costing & Client Billing',
            'projects' => $projects,
            'metrics' => $metrics,
            'clients' => $clients,
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'client_id' => $clientId,
            ],
        ]);
    }

    public function create(FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;

        $clients = Client::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $costCategories = CostCategory::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $prefix = 'PRJ-' . Carbon::now()->format('Ymd') . '-';
        $last = Project::where('team_id', $teamId)->where('project_code', 'LIKE', $prefix . '%')->latest('id')->first();
        $nextNum = $last ? str_pad((int) substr($last->project_code, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        $nextProjectCode = $prefix . $nextNum;

        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        return Inertia::render('Projects/Create', [
            'page_title' => 'Create New Project',
            'nextProjectCode' => $nextProjectCode,
            'clients' => $clients,
            'costCategories' => $costCategories,
            'financialAccounts' => $financialAccounts,
            'products' => $products,
            'warehouses' => $warehouses,
            'financialAccounts' => $financialAccounts,
            'todayDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, ProjectService $service)
    {
        $user = Auth::user();
        $teamId = $user->current_team_id;

        if ($user->currentTeam && ! app(\App\Services\SubscriptionService::class)->canAddProject($user->currentTeam)) {
            return redirect()->back()->with('error', 'You have reached the maximum project limit for your subscription plan. Please upgrade your plan.');
        }

        $rules = [
            'client_mode' => 'required|in:existing,new',
            'name' => 'required|string|max:255',
            'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'items.*.notes' => 'nullable|string|max:255',
            'costs' => 'nullable|array',
            'costs.*.description' => 'required|string|max:255',
            'costs.*.amount' => 'required|numeric|min:0.01',
            'costs.*.cost_category_id' => ['nullable', Rule::exists('cost_categories', 'id')->where('team_id', $teamId)],
            'costs.*.cost_date' => 'nullable|date',
            'costs.*.vendor_name' => 'nullable|string|max:255',
            'costs.*.receipt_reference' => 'nullable|string|max:100',
            'costs.*.notes' => 'nullable|string|max:255',
        ];

        if ($request->input('client_mode') === 'new') {
            $rules['new_client_name'] = 'required|string|max:255';
            $rules['new_client_company'] = 'nullable|string|max:255';
            $rules['new_client_phone'] = 'nullable|string|max:50';
            $rules['new_client_email'] = 'nullable|email|max:255';
            $rules['new_client_address'] = 'nullable|string|max:500';
            $rules['new_client_tax_number'] = 'nullable|string|max:100';
        } else {
            $rules['client_id'] = ['required', Rule::exists('clients', 'id')->where('team_id', $teamId)];
        }

        $validated = $request->validate($rules);

        $project = $service->createProject(
            $validated,
            $request->input('items', []),
            $request->input('costs', [])
        );

        if ($project->paid_amount > 0 && !empty($validated['financial_account_id'])) {
            $account = FinancialAccount::where('team_id', $teamId)->find($validated['financial_account_id']);
            $account?->deposit($project->paid_amount, [
                'reference_type' => Project::class,
                'reference_id' => $project->id,
                'reference_number' => $project->project_code,
                'description' => "Advance contract payment received for project #{$project->project_code}",
            ]);
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', "Project #{$project->project_code} created successfully.");
    }

    public function show(Project $project, FinancialAccountService $accountService)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403, 'Unauthorized access to project.');

        $project->load([
            'client',
            'creator',
            'items.product',
            'items.warehouse',
            'costs.category',
            'bills' => fn ($q) => $q->latest('id'),
        ]);

        $products = Product::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();
        $costCategories = CostCategory::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $accountService->ensureDefaultAccounts($teamId);
        $financialAccounts = $accountService->getActiveAccountsForTeam($teamId);

        // Calculate material costs based on unit_cost stored or product cost
        $materialCost = $project->items->sum(function ($item) {
            $cost = $item->unit_cost > 0 ? $item->unit_cost : ($item->product?->cost_price ?? 0);
            return (float) $item->quantity * (float) $cost;
        });

        $directCosts = (float) $project->costs_total;
        $actualExpenditure = $materialCost + $directCosts;
        $budget = (float) $project->budget;
        $billedTotal = (float) $project->billed_total;
        $grossProfit = round($billedTotal - $actualExpenditure, 2);
        $profitMargin = $billedTotal > 0 ? round(($grossProfit / $billedTotal) * 100, 1) : 0;
        $budgetVariance = round($budget - $actualExpenditure, 2);
        $budgetUsedPercent = $budget > 0 ? min(200, round(($actualExpenditure / $budget) * 100, 1)) : 0;

        return Inertia::render('Projects/Show', [
            'page_title' => "Project #{$project->project_code} - {$project->name}",
            'project' => $project,
            'products' => $products,
            'warehouses' => $warehouses,
            'costCategories' => $costCategories,
            'financialAccounts' => $financialAccounts,
            'analytics' => [
                'material_cost' => round($materialCost, 2),
                'direct_costs' => round($directCosts, 2),
                'actual_expenditure' => round($actualExpenditure, 2),
                'gross_profit' => $grossProfit,
                'profit_margin' => $profitMargin,
                'budget_variance' => $budgetVariance,
                'budget_used_percent' => $budgetUsedPercent,
            ],
        ]);
    }

    public function edit(Project $project)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $clients = Client::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Projects/Edit', [
            'page_title' => "Edit Project #{$project->project_code}",
            'project' => $project->load('client'),
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Project $project, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $validated = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('team_id', $teamId)],
            'name' => 'required|string|max:255',
            'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
            'budget' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:2000',
        ]);

        $project->update($validated);
        $service->recalculateProjectTotals($project);

        return redirect()->route('projects.show', $project->id)
            ->with('success', "Project #{$project->project_code} updated successfully.");
    }

    public function addItem(Request $request, Project $project, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'product_id' => ['nullable', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'notes' => 'nullable|string|max:255',
        ]);

        $service->addItem($project, $validated);

        return back()->with('success', "Material item added and inventory adjusted successfully.");
    }

    public function removeItem(Project $project, ProjectItem $item, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId || $item->project_id !== $project->id, 403);

        $service->removeItem($project, $item);

        return back()->with('success', "Material item removed and inventory restored to warehouse.");
    }

    public function addCost(Request $request, Project $project, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $validated = $request->validate([
            'cost_category_id' => ['nullable', Rule::exists('cost_categories', 'id')->where('team_id', $teamId)],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'cost_date' => 'nullable|date',
            'vendor_name' => 'nullable|string|max:255',
            'receipt_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $service->addCost($project, $validated);

        return back()->with('success', "Direct project expense recorded successfully.");
    }

    public function removeCost(Project $project, ProjectCost $cost, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId || $cost->project_id !== $project->id, 403);

        $service->removeCost($project, $cost);

        return back()->with('success', "Project expense removed.");
    }

    public function createBill(Request $request, Project $project, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $bill = $service->createMilestoneBill($project, $validated);

        return back()->with('success', "Milestone billing claim {$bill->bill_number} generated.");
    }

    public function recordPayment(Request $request, Project $project, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $service->recordPayment(
                $project,
                (float) $validated['amount'],
                $validated['payment_method'] ?? null,
                $validated['notes'] ?? null,
                $validated['financial_account_id'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', "Payment of " . number_format($validated['amount'], 2) . " received successfully.");
    }

    public function transitionStatus(Request $request, Project $project, ProjectService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $validated = $request->validate([
            'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
        ]);

        $service->transitionStatus($project, $validated['status']);

        return back()->with('success', "Project status updated to " . str_replace('_', ' ', $validated['status']) . ".");
    }

    public function destroy(Project $project)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($project->team_id !== $teamId, 403);

        $code = $project->project_code;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project #{$code} deleted successfully.");
    }
}
