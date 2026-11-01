<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Services\FinancialAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FinancialAccountController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $type = $request->input('type');

        $query = FinancialAccount::where('team_id', $teamId)
            ->with(['transactions' => function ($q) {
                $q->latest('id')->take(10);
            }])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('institution_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('branch', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $type));

        $accounts = $query->orderByDesc('is_default')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $allAccounts = FinancialAccount::where('team_id', $teamId)->get();

        $metrics = [
            'total_liquidity' => (float) $allAccounts->where('is_active', true)->sum('current_balance'),
            'bank_balance' => (float) $allAccounts->where('is_active', true)->where('type', 'bank')->sum('current_balance'),
            'mfs_balance' => (float) $allAccounts->where('is_active', true)->where('type', 'mfs')->sum('current_balance'),
            'cash_balance' => (float) $allAccounts->where('is_active', true)->where('type', 'cash')->sum('current_balance'),
            'active_accounts_count' => $allAccounts->where('is_active', true)->count(),
        ];

        return Inertia::render('Accounting/BankAccounts/Index', [
            'page_title' => 'Bank & MFS Accounts',
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
            'name' => 'required|string|max:100',
            'type' => 'required|in:bank,mfs,cash',
            'institution_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'account_type' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($validated, $teamId) {
            $isDefault = (bool) ($validated['is_default'] ?? false);
            if ($isDefault) {
                FinancialAccount::where('team_id', $teamId)->update(['is_default' => false]);
            }

            $opening = (float) ($validated['opening_balance'] ?? 0.00);

            $account = FinancialAccount::create([
                'team_id' => $teamId,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'institution_name' => $validated['institution_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'account_type' => $validated['account_type'] ?? null,
                'branch' => $validated['branch'] ?? null,
                'routing_number' => $validated['routing_number'] ?? null,
                'swift_code' => $validated['swift_code'] ?? null,
                'opening_balance' => $opening,
                'current_balance' => $opening,
                'is_default' => $isDefault,
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            if ($opening > 0) {
                FinancialTransaction::create([
                    'team_id' => $teamId,
                    'financial_account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $opening,
                    'balance_after' => $opening,
                    'transaction_date' => now()->toDateString(),
                    'reference_number' => 'INIT-BAL',
                    'description' => 'Opening balance recorded upon account creation',
                    'created_by' => Auth::id(),
                ]);

                $this->recordAccountOpeningJournalEntry($teamId, $account, $opening);
            }

            return redirect()->back()->with('success', "Account '{$account->name}' created successfully.");
        });
    }

    public function update(Request $request, FinancialAccount $account)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($account->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:bank,mfs,cash',
            'institution_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'account_type' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($validated, $account, $teamId) {
            $isDefault = (bool) ($validated['is_default'] ?? false);
            if ($isDefault && !$account->is_default) {
                FinancialAccount::where('team_id', $teamId)->where('id', '!=', $account->id)->update(['is_default' => false]);
            }

            $account->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'institution_name' => $validated['institution_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'account_type' => $validated['account_type'] ?? null,
                'branch' => $validated['branch'] ?? null,
                'routing_number' => $validated['routing_number'] ?? null,
                'swift_code' => $validated['swift_code'] ?? null,
                'is_default' => $isDefault,
                'is_active' => $validated['is_active'] ?? $account->is_active,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->back()->with('success', "Account '{$account->name}' updated successfully.");
        });
    }

    public function toggleActive(FinancialAccount $account)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($account->team_id !== $teamId, 403);

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Account '{$account->name}' {$status}.");
    }

    public function setDefault(FinancialAccount $account)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($account->team_id !== $teamId, 403);

        DB::transaction(function () use ($account, $teamId) {
            FinancialAccount::where('team_id', $teamId)->update(['is_default' => false]);
            $account->update(['is_default' => true]);
        });

        return redirect()->back()->with('success', "Account '{$account->name}' set as primary default.");
    }

    public function destroy(FinancialAccount $account)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($account->team_id !== $teamId, 403);

        return DB::transaction(function () use ($account, $teamId) {
            if ($account->transactions()->where('reference_number', '!=', 'INIT-BAL')->exists()) {
                return redirect()->back()->with('error', "Cannot delete account '{$account->name}' because it contains transaction history. You can deactivate it instead.");
            }

            // Remove associated initial journal entries
            JournalEntry::where('team_id', $teamId)
                ->where('reference_type', FinancialAccount::class)
                ->where('reference_id', $account->id)
                ->each(fn ($entry) => $entry->delete());

            $account->transactions()->delete();
            $account->delete();

            return redirect()->back()->with('success', "Account deleted successfully.");
        });
    }

    protected function recordAccountOpeningJournalEntry(int $teamId, FinancialAccount $account, float $amount): void
    {
        try {
            /** @var AccountingService $accountingService */
            $accountingService = app(AccountingService::class);
            $accountingService->ensureDefaultAccounts($teamId);

            $assetCode = $account->type === 'cash' ? '1010' : '1020';
            $assetAccount = ChartOfAccount::where('team_id', $teamId)->where('account_code', $assetCode)->first();
            if (!$assetAccount) {
                $assetAccount = ChartOfAccount::where('team_id', $teamId)
                    ->whereIn('account_code', ['1010', '1020'])
                    ->first();
            }

            $equityAccount = ChartOfAccount::where('team_id', $teamId)
                ->whereIn('account_code', ['3020', '3010'])
                ->orderBy('account_code', 'desc')
                ->first();

            if ($assetAccount && $equityAccount) {
                $fiscalYear = FiscalYear::where('team_id', $teamId)->where('is_closed', false)->first();

                $typeLabel = strtoupper($account->type);
                $accountingService->recordJournalEntry([
                    'fiscal_year_id' => $fiscalYear?->id,
                    'entry_date' => now()->toDateString(),
                    'reference_type' => FinancialAccount::class,
                    'reference_id' => $account->id,
                    'narration' => "Opening balance for Financial Account: {$account->name} ({$typeLabel})",
                ], [
                    [
                        'chart_of_account_id' => $assetAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "{$assetAccount->account_name} - Opening balance for {$account->name}",
                    ],
                    [
                        'chart_of_account_id' => $equityAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Opening Balance Equity - Financial Account {$account->name}",
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Could not record journal entry for financial account opening balance: " . $e->getMessage());
        }
    }
}
