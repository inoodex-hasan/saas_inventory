<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = JournalEntry::where('team_id', $teamId)
            ->with(['lines.account', 'creator'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('entry_number', 'like', "%{$search}%")
                        ->orWhere('narration', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('entry_date', '<=', $endDate));

        $entries = $query->latest('entry_date')->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_entries' => JournalEntry::where('team_id', $teamId)->count(),
            'total_debit_volume' => (float) \App\Models\JournalEntryLine::where('team_id', $teamId)->sum('debit'),
        ];

        return Inertia::render('Accounting/Journal/Index', [
            'page_title' => 'General Journal Entries',
            'entries' => $entries,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $accounts = ChartOfAccount::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $fiscalYears = FiscalYear::where('team_id', $teamId)
            ->where('is_closed', false)
            ->get();

        return Inertia::render('Accounting/Journal/Create', [
            'page_title' => 'Post Manual Journal Entry',
            'accounts' => $accounts,
            'fiscalYears' => $fiscalYears,
            'todayDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, AccountingService $accountingService)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'narration' => 'required|string|max:1000',
            'fiscal_year_id' => ['nullable', Rule::exists('fiscal_years', 'id')->where('team_id', $teamId)],
            'lines' => 'required|array|min:2',
            'lines.*.chart_of_account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('team_id', $teamId)],
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:255',
        ]);

        try {
            $entry = $accountingService->recordJournalEntry($validated, $validated['lines']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('journal.index')
            ->with('success', "Journal Entry {$entry->entry_number} posted successfully.");
    }

    public function show(JournalEntry $entry)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($entry->team_id !== $teamId, 403);

        $entry->load(['lines.account', 'creator', 'fiscalYear']);

        return Inertia::render('Accounting/Journal/Show', [
            'page_title' => "Journal Voucher #{$entry->entry_number}",
            'entry' => $entry,
        ]);
    }
}
