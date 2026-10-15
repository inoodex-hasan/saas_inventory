<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChartOfAccountController extends Controller
{
    public function index(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;
        $accountingService->ensureDefaultAccounts($teamId);

        $search = $request->input('search', '');
        $type = $request->input('type', '');

        $query = ChartOfAccount::where('team_id', $teamId)
            ->withCount('journalLines')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('account_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('subtype', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->orderBy('account_code');

        $accounts = $query->paginate(25)->withQueryString();

        $metrics = [
            'total_accounts' => ChartOfAccount::where('team_id', $teamId)->count(),
            'assets' => ChartOfAccount::where('team_id', $teamId)->where('type', 'asset')->count(),
            'liabilities' => ChartOfAccount::where('team_id', $teamId)->where('type', 'liability')->count(),
            'equity' => ChartOfAccount::where('team_id', $teamId)->where('type', 'equity')->count(),
            'revenue' => ChartOfAccount::where('team_id', $teamId)->where('type', 'revenue')->count(),
            'expense' => ChartOfAccount::where('team_id', $teamId)->where('type', 'expense')->count(),
        ];

        return Inertia::render('Accounting/Accounts/Index', [
            'page_title' => 'Chart of Accounts (COA)',
            'accounts' => $accounts,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'account_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $exists = ChartOfAccount::where('team_id', $teamId)
            ->where('account_code', $validated['account_code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['account_code' => 'This account code already exists in your chart of accounts.']);
        }

        ChartOfAccount::create([
            'team_id' => $teamId,
            'account_code' => $validated['account_code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'subtype' => $validated['subtype'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'is_active' => true,
        ]);

        return back()->with('success', "Account {$validated['account_code']} - {$validated['name']} created.");
    }

    public function update(Request $request, ChartOfAccount $account)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($account->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subtype' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        $account->update($validated);

        return back()->with('success', "Account {$account->account_code} updated.");
    }

    public function destroy(ChartOfAccount $account)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($account->team_id !== $teamId, 403);

        if ($account->is_system) {
            return back()->withErrors(['error' => 'Core system accounts cannot be deleted to maintain accounting ledger integrity.']);
        }

        if ($account->journalLines()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete an account with existing journal entry transactions.']);
        }

        $code = $account->account_code;
        $account->delete();

        return back()->with('success', "Account {$code} deleted.");
    }
}
