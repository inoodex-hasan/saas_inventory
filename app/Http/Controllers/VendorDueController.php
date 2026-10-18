<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\FinancialAccountService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VendorDueController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all_with_dues'); // all_with_dues, fully_paid, all

        $query = Supplier::where('team_id', $teamId)
            ->with(['purchaseOrders' => function ($q) {
                $q->where('status', '!=', 'cancelled')->latest('id');
            }])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%");
                });
            });

        $suppliers = $query->latest('id')->get()->map(function ($supplier) {
            $totalProcured = (float) $supplier->purchaseOrders->sum('total_amount');
            $totalPaid = (float) $supplier->purchaseOrders->sum('paid_amount');
            $outstandingDue = (float) $supplier->purchaseOrders->sum('due_amount');
            $dueOrders = $supplier->purchaseOrders->where('due_amount', '>', 0)->values();

            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'contact_name' => $supplier->contact_name,
                'address' => $supplier->address,
                'total_procured' => $totalProcured,
                'total_paid' => $totalPaid,
                'outstanding_due' => $outstandingDue,
                'due_orders' => $dueOrders,
            ];
        });

        if ($filter === 'all_with_dues') {
            $suppliers = $suppliers->filter(fn ($s) => $s['outstanding_due'] > 0)->values();
        } elseif ($filter === 'fully_paid') {
            $suppliers = $suppliers->filter(fn ($s) => $s['outstanding_due'] <= 0 && $s['total_procured'] > 0)->values();
        }

        // Metrics
        $totalProcuredAll = (float) PurchaseOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalPaidAll = (float) PurchaseOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('paid_amount');
        $outstandingDueAll = (float) PurchaseOrder::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('due_amount');
        $vendorsWithDueCount = Supplier::where('team_id', $teamId)
            ->whereHas('purchaseOrders', fn ($q) => $q->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'))
            ->count();

        $financialAccounts = app(FinancialAccountService::class)->getActiveAccountsForTeam($teamId);

        return Inertia::render('VendorDues/Index', [
            'vendors' => $suppliers,
            'metrics' => [
                'total_procured' => $totalProcuredAll,
                'total_paid' => $totalPaidAll,
                'outstanding_due' => $outstandingDueAll,
                'vendors_with_due' => $vendorsWithDueCount,
            ],
            'financialAccounts' => $financialAccounts,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function pay(Request $request, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        if (!$request->filled('financial_account_id')) {
            $defaultAccount = FinancialAccount::where('team_id', $teamId)->where('is_active', true)->first();
            if (!$defaultAccount) {
                $defaultAccount = FinancialAccount::create([
                    'team_id' => $teamId,
                    'name' => 'Cash in Hand',
                    'type' => 'cash',
                    'account_type' => 'cash',
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }
            $request->merge(['financial_account_id' => $defaultAccount->id]);
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:purchase_orders,id',
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => 'required|exists:financial_accounts,id',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = PurchaseOrder::where('team_id', $teamId)->findOrFail($validated['order_id']);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw new \InvalidArgumentException("Payment amount exceeds outstanding PO due amount.");
        }

        $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);

        DB::transaction(function () use ($service, $order, $account, $validated) {
            $service->recordPayment($order, (float) $validated['amount'], [
                'payment_method' => $account->type,
                'notes' => $validated['notes'] ?? null,
            ]);

            $account->withdraw((float) $validated['amount'], [
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $order->id,
                'reference_number' => $validated['payment_reference'] ?? null,
                'description' => "Vendor bill settlement for PO #{$order->po_number}" . (!empty($validated['notes']) ? " - {$validated['notes']}" : ''),
            ]);
        });

        return redirect()->back()->with('success', "Disbursement of ৳{$validated['amount']} recorded from {$account->name} for PO {$order->po_number}.");
    }
}
