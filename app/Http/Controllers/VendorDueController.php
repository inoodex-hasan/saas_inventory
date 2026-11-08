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
            ->when($request->filled('search'), function ($q) use ($search) {
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

        $suppliers = $query->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($supplier) {
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
            if ($defaultAccount) {
                $request->merge(['financial_account_id' => $defaultAccount->id]);
            }
        }

        $validated = $request->validate([
            'vendor_id' => ['nullable', Rule::exists('suppliers', 'id')->where('team_id', $teamId)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('team_id', $teamId)],
            'order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('team_id', $teamId)],
            'amount' => 'required|numeric|min:0.01',
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('team_id', $teamId)],
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'allocations' => 'nullable|array',
            'allocations.*.order_id' => ['required_with:allocations', Rule::exists('purchase_orders', 'id')->where('team_id', $teamId)],
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0',
        ]);

        $totalPayment = round((float) $validated['amount'], 2);

        // Normalize allocations: support both multi-allocation array and single order fallback
        $allocations = [];
        if (!empty($validated['allocations'])) {
            $allocations = array_values(array_filter($validated['allocations'], function ($a) {
                return (float) ($a['amount'] ?? 0) > 0;
            }));
        } elseif (!empty($validated['order_id'])) {
            $allocations = [
                [
                    'order_id' => $validated['order_id'],
                    'amount' => $totalPayment,
                ],
            ];
        }

        if (empty($allocations)) {
            throw ValidationException::withMessages([
                'amount' => 'Please allocate the payment amount to at least one purchase order.',
            ]);
        }

        $totalAllocated = round(array_sum(array_column($allocations, 'amount')), 2);
        if (abs($totalAllocated - $totalPayment) > 0.01) {
            throw ValidationException::withMessages([
                'amount' => "Total allocated amount (৳" . number_format($totalAllocated, 2) . ") must match the payment disbursement amount (৳" . number_format($totalPayment, 2) . ").",
            ]);
        }

        $orderIds = array_column($allocations, 'order_id');
        $orders = PurchaseOrder::where('team_id', $teamId)
            ->whereIn('id', $orderIds)
            ->get()
            ->keyBy('id');

        foreach ($allocations as $alloc) {
            $order = $orders->get($alloc['order_id']);
            if (!$order) {
                throw ValidationException::withMessages([
                    'amount' => "Purchase order ID {$alloc['order_id']} not found.",
                ]);
            }
            if ((float) $alloc['amount'] > (float) $order->due_amount + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => "Allocated amount of ৳" . number_format($alloc['amount'], 2) . " exceeds outstanding balance for PO {$order->po_number} (৳" . number_format($order->due_amount, 2) . ").",
                ]);
            }
        }

        $account = FinancialAccount::where('team_id', $teamId)->findOrFail($validated['financial_account_id']);

        $supplierId = $validated['vendor_id'] ?? $validated['supplier_id'] ?? null;
        $vendor = null;
        if (!empty($supplierId)) {
            $vendor = Supplier::where('team_id', $teamId)->find($supplierId);
        }
        if (!$vendor && $orders->isNotEmpty()) {
            $vendor = $orders->first()->supplier;
        }

        $processedOrders = [];

        DB::transaction(function () use ($service, $orders, $allocations, $account, $validated, $totalPayment, $vendor, &$processedOrders) {
            foreach ($allocations as $alloc) {
                $order = $orders->get($alloc['order_id']);
                $allocatedAmount = round((float) $alloc['amount'], 2);
                $service->recordPayment($order, $allocatedAmount, [
                    'payment_method' => $account->type,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $processedOrders[] = "{$order->po_number} (৳" . number_format($allocatedAmount, 2) . ")";
            }

            $vendorName = $vendor?->name ?? 'Vendor';
            $description = count($processedOrders) === 1
                ? "Vendor bill settlement to {$vendorName} - PO {$processedOrders[0]}"
                : "Multi-PO bill settlement to {$vendorName} across " . count($processedOrders) . " purchase orders: " . implode(', ', $processedOrders);

            if (!empty($validated['notes'])) {
                $description .= " - {$validated['notes']}";
            }

            $account->withdraw(
                $totalPayment,
                [
                    'description' => $description,
                    'reference_type' => $vendor ? Supplier::class : PurchaseOrder::class,
                    'reference_id' => $vendor ? $vendor->id : $orders->first()->id,
                    'reference_number' => $validated['payment_reference'] ?? null,
                ]
            );
        });

        $vendorDisplayName = $vendor?->name ?? 'Vendor';
        $successMsg = count($processedOrders) === 1
            ? "Disbursement of ৳" . number_format($totalPayment, 2) . " recorded from {$account->name} for PO {$processedOrders[0]}."
            : "Disbursement of ৳" . number_format($totalPayment, 2) . " successfully distributed across " . count($processedOrders) . " purchase orders to {$vendorDisplayName}.";

        return redirect()->back()->with('success', $successMsg);
    }
}
