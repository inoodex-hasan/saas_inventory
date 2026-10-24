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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VendorDueController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = $request->input('search', '');
        $filter = $request->input('filter', 'all_with_dues'); // all_with_dues, fully_paid, all

        $query = Supplier::where('team_id', $teamId)
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'all_with_dues', function ($q) {
                $q->whereHas('purchaseOrders', fn ($po) => $po->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'));
            })
            ->when($filter === 'fully_paid', function ($q) {
                $q->whereDoesntHave('purchaseOrders', fn ($po) => $po->where('due_amount', '>', 0)->where('status', '!=', 'cancelled'))
                  ->whereHas('purchaseOrders', fn ($po) => $po->where('status', '!=', 'cancelled'));
            })
            ->withSum(['purchaseOrders as total_procured' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total_amount')
            ->withSum(['purchaseOrders as total_paid' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'paid_amount')
            ->withSum(['purchaseOrders as outstanding_due' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'due_amount')
            ->with(['purchaseOrders' => function ($q) {
                $q->where('status', '!=', 'cancelled')->where('due_amount', '>', 0)->latest('id');
            }]);

        $suppliers = $query->latest('id')->get()->map(function ($supplier) {
            $totalProcured = (float) ($supplier->total_procured ?? 0);
            $totalPaid = (float) ($supplier->total_paid ?? 0);
            $outstandingDue = (float) ($supplier->outstanding_due ?? 0);
            $dueOrders = $supplier->purchaseOrders->values();

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
            $defaultAccount = app(FinancialAccountService::class)->getDefaultAccount($teamId);
            $request->merge(['financial_account_id' => $defaultAccount->id]);
        }

        $validated = $request->validate([
            'order_id' => ['required', Rule::exists('purchase_orders', 'id')->where('team_id', $teamId)],
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = PurchaseOrder::where('team_id', $teamId)->findOrFail($validated['order_id']);

        if ((float) $validated['amount'] > (float) $order->due_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot exceed the outstanding PO balance of ' . number_format($order->due_amount, 2),
            ]);
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
