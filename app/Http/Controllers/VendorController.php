<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = $request->input('search');
        $status = $request->input('status');

        $vendors = Supplier::where('team_id', $teamId)
            ->when($request->filled('search'), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('contact_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->withCount('purchaseOrders')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_vendors' => Supplier::where('team_id', $teamId)->count(),
            'active_vendors' => Supplier::where('team_id', $teamId)->where('is_active', true)->count(),
            'pending_orders' => PurchaseOrder::where('team_id', $teamId)
                ->whereIn('status', ['draft', 'ordered', 'partially_received'])
                ->count(),
            'total_payables' => (float) PurchaseOrder::where('team_id', $teamId)->sum('due_amount'),
        ];

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'The vendor / supplier name is required.',
            'phone.required' => 'The vendor phone / mobile number is required.',
            'address.required' => 'The vendor address is required.',
        ]);

        $openingBalance = round((float) ($request->input('opening_balance', 0)), 2);
        $validated['opening_balance'] = $openingBalance;
        $validated['team_id'] = $teamId;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $vendor = DB::transaction(function () use ($validated, $openingBalance, $teamId) {
            $vendor = Supplier::create($validated);

            if ($openingBalance > 0) {
                $warehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
                    ?? Warehouse::where('team_id', $teamId)->first();

                if (!$warehouse) {
                    $warehouse = Warehouse::create([
                        'team_id' => $teamId,
                        'name' => 'Main Warehouse',
                        'code' => 'WH-01',
                        'is_default' => true,
                        'is_active' => true,
                    ]);
                }

                $orderNumber = 'PO-OPEN-' . str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT);
                while (PurchaseOrder::where('team_id', $teamId)->where('order_number', $orderNumber)->exists()) {
                    $orderNumber .= '-' . rand(10, 99);
                }

                $purchaseOrder = PurchaseOrder::create([
                    'team_id' => $teamId,
                    'supplier_id' => $vendor->id,
                    'warehouse_id' => $warehouse->id,
                    'order_number' => $orderNumber,
                    'status' => 'received',
                    'payment_status' => 'unpaid',
                    'order_date' => now()->toDateString(),
                    'subtotal' => $openingBalance,
                    'tax_amount' => 0.00,
                    'shipping_cost' => 0.00,
                    'discount_amount' => 0.00,
                    'total_amount' => $openingBalance,
                    'paid_amount' => 0.00,
                    'due_amount' => $openingBalance,
                    'notes' => 'Opening balance due recorded on vendor registration.',
                    'created_by' => Auth::id(),
                ]);

                $this->recordVendorOpeningJournalEntry($teamId, $vendor, $purchaseOrder, $openingBalance);
            }

            return $vendor;
        });

        if ($request->wantsJson()) {
            return response()->json($vendor);
        }

        $successMsg = "Vendor '{$vendor->name}' created successfully" . ($openingBalance > 0 ? " with opening payable due of ৳" . number_format($openingBalance, 2) : "") . ".";

        return redirect()->back()->with('success', $successMsg);
    }

    protected function recordVendorOpeningJournalEntry(int $teamId, Supplier $vendor, PurchaseOrder $po, float $amount): void
    {
        try {
            /** @var AccountingService $accountingService */
            $accountingService = app(AccountingService::class);
            $accountingService->ensureDefaultAccounts($teamId);

            $apAccount = ChartOfAccount::where('team_id', $teamId)->where('account_code', '2010')->first();
            $equityAccount = ChartOfAccount::where('team_id', $teamId)->whereIn('account_code', ['3020', '3010'])->orderBy('account_code', 'desc')->first();

            if ($apAccount && $equityAccount) {
                $fiscalYear = FiscalYear::where('team_id', $teamId)->where('is_closed', false)->first();

                $accountingService->recordJournalEntry([
                    'fiscal_year_id' => $fiscalYear?->id,
                    'entry_date' => now()->toDateString(),
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $po->id,
                    'narration' => "Opening payable due balance for Vendor: {$vendor->name} (#{$po->order_number})",
                ], [
                    [
                        'chart_of_account_id' => $equityAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "Opening Balance Equity - Vendor Payable for {$vendor->name}",
                    ],
                    [
                        'chart_of_account_id' => $apAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Accounts Payable - Opening Due for {$vendor->name}",
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Could not record journal entry for vendor opening balance: " . $e->getMessage());
        }
    }

    public function update(Request $request, Supplier $vendor)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($vendor->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'The vendor / supplier name is required.',
            'phone.required' => 'The vendor phone / mobile number is required.',
            'address.required' => 'The vendor address is required.',
        ]);

        $oldOpeningBalance = round((float) ($vendor->opening_balance ?? 0), 2);
        $newOpeningBalance = $request->has('opening_balance')
            ? round((float) $request->input('opening_balance', 0), 2)
            : $oldOpeningBalance;
        $validated['opening_balance'] = $newOpeningBalance;

        DB::transaction(function () use ($vendor, $validated, $oldOpeningBalance, $newOpeningBalance, $teamId) {
            $prefix = 'PO-OPEN-' . str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT);
            $po = PurchaseOrder::where('team_id', $teamId)
                ->where('supplier_id', $vendor->id)
                ->where('order_number', 'like', $prefix . '%')
                ->first();

            if ($po && $newOpeningBalance < (float) $po->paid_amount) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Opening balance cannot be less than already settled amount of ' . number_format($po->paid_amount, 2),
                ]);
            }

            $vendor->update($validated);

            if ($oldOpeningBalance != $newOpeningBalance) {
                if ($po) {
                    if ($newOpeningBalance <= 0 && (float) $po->paid_amount <= 0) {
                        JournalEntry::where('reference_type', PurchaseOrder::class)
                            ->where('reference_id', $po->id)
                            ->delete();
                        $po->delete();
                    } else {
                        $po->subtotal = $newOpeningBalance;
                        $po->total_amount = $newOpeningBalance;
                        $po->due_amount = max(0, $newOpeningBalance - (float) $po->paid_amount);
                        if ($po->due_amount <= 0) {
                            $po->payment_status = 'paid';
                        } elseif ((float) $po->paid_amount > 0) {
                            $po->payment_status = 'partially_paid';
                        } else {
                            $po->payment_status = 'unpaid';
                        }
                        $po->save();

                        JournalEntry::where('reference_type', PurchaseOrder::class)
                            ->where('reference_id', $po->id)
                            ->delete();
                        if ($newOpeningBalance > 0) {
                            $this->recordVendorOpeningJournalEntry($teamId, $vendor, $po, $newOpeningBalance);
                        }
                    }
                } elseif ($newOpeningBalance > 0) {
                    $warehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
                        ?? Warehouse::where('team_id', $teamId)->first();

                    if (!$warehouse) {
                        $warehouse = Warehouse::create([
                            'team_id' => $teamId,
                            'name' => 'Main Warehouse',
                            'code' => 'WH-01',
                            'is_default' => true,
                            'is_active' => true,
                        ]);
                    }

                    $orderNumber = $prefix;
                    while (PurchaseOrder::where('team_id', $teamId)->where('order_number', $orderNumber)->exists()) {
                        $orderNumber .= '-' . rand(10, 99);
                    }

                    $purchaseOrder = PurchaseOrder::create([
                        'team_id' => $teamId,
                        'supplier_id' => $vendor->id,
                        'warehouse_id' => $warehouse->id,
                        'order_number' => $orderNumber,
                        'status' => 'received',
                        'payment_status' => 'unpaid',
                        'order_date' => now()->toDateString(),
                        'subtotal' => $newOpeningBalance,
                        'tax_amount' => 0.00,
                        'shipping_cost' => 0.00,
                        'discount_amount' => 0.00,
                        'total_amount' => $newOpeningBalance,
                        'paid_amount' => 0.00,
                        'due_amount' => $newOpeningBalance,
                        'notes' => 'Opening balance due recorded on vendor update.',
                        'created_by' => Auth::id(),
                    ]);

                    $this->recordVendorOpeningJournalEntry($teamId, $vendor, $purchaseOrder, $newOpeningBalance);
                }
            }
        });

        return redirect()->back()->with('success', "Vendor '{$vendor->name}' updated successfully.");
    }

    public function toggleActive(Supplier $vendor)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($vendor->team_id !== $teamId, 403);

        $vendor->update(['is_active' => !$vendor->is_active]);

        $statusStr = $vendor->is_active ? 'active' : 'inactive';
        return redirect()->back()->with('success', "Vendor marked as {$statusStr}.");
    }

    public function destroy(Supplier $vendor)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($vendor->team_id !== $teamId, 403);

        if ($vendor->purchaseOrders()->exists()) {
            return redirect()->back()->with('error', "Cannot delete vendor '{$vendor->name}' because purchase orders are associated with them.");
        }

        $vendor->delete();

        return redirect()->back()->with('success', "Vendor '{$vendor->name}' deleted successfully.");
    }
}
