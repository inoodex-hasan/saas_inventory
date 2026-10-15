<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinancialReportController extends Controller
{
    /**
     * Trial Balance Report
     */
    public function trialBalance(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $data = $accountingService->getTrialBalance($teamId, $asOfDate);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'page_title' => 'Trial Balance Statement',
            'report' => $data,
        ]);
    }

    /**
     * Profit & Loss / Income Statement
     */
    public function profitAndLoss(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = $accountingService->getProfitAndLoss($teamId, $startDate, $endDate);

        return Inertia::render('Accounting/Reports/ProfitAndLoss', [
            'page_title' => 'Profit & Loss Statement (P&L)',
            'report' => $data,
        ]);
    }

    /**
     * Balance Sheet
     */
    public function balanceSheet(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $data = $accountingService->getBalanceSheet($teamId, $asOfDate);

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'page_title' => 'Balance Sheet Statement',
            'report' => $data,
        ]);
    }

    /**
     * General Ledger Drilldown Report
     */
    public function generalLedger(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;
        $accountingService->ensureDefaultAccounts($teamId);

        $accounts = ChartOfAccount::where('team_id', $teamId)->where('is_active', true)->orderBy('account_code')->get();
        $selectedAccountId = $request->input('account_id', $accounts->first()?->id);
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $ledgerData = null;
        if ($selectedAccountId) {
            $ledgerData = $accountingService->getGeneralLedger($teamId, (int) $selectedAccountId, $startDate, $endDate);
        }

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'page_title' => 'General Ledger Statement',
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId ? (int) $selectedAccountId : null,
            'ledger' => $ledgerData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Bank Reconciliation Workspace
     */
    public function reconciliation(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;
        $accountingService->ensureDefaultAccounts($teamId);

        $bankAccounts = ChartOfAccount::where('team_id', $teamId)
            ->where('type', 'asset')
            ->whereIn('subtype', ['current_asset'])
            ->where(function ($q) {
                $q->where('name', 'like', '%bank%')
                    ->orWhere('name', 'like', '%cash%');
            })
            ->get();

        $selectedAccountId = $request->input('account_id', $bankAccounts->first()?->id);
        $reconciliationDate = $request->input('reconciliation_date', now()->toDateString());

        $history = BankReconciliation::where('team_id', $teamId)
            ->with(['account', 'reconciledBy'])
            ->latest('reconciliation_date')
            ->paginate(10);

        return Inertia::render('Accounting/Reconciliation/Index', [
            'page_title' => 'Bank & Cash Reconciliation',
            'bankAccounts' => $bankAccounts,
            'selectedAccountId' => $selectedAccountId ? (int) $selectedAccountId : null,
            'reconciliationDate' => $reconciliationDate,
            'history' => $history,
        ]);
    }

    /**
     * Store Bank Reconciliation
     */
    public function storeReconciliation(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'statement_balance' => 'required|numeric',
            'reconciliation_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $recon = $accountingService->reconcileBank(
            $teamId,
            (int) $validated['chart_of_account_id'],
            (float) $validated['statement_balance'],
            $validated['reconciliation_date'],
            $validated['notes'] ?? null
        );

        $msg = $recon->status === 'reconciled'
            ? "Bank account reconciled successfully. Difference: 0.00"
            : "Reconciliation record saved. Discrepancy difference: " . number_format($recon->difference, 2);

        return back()->with('success', $msg);
    }
}
