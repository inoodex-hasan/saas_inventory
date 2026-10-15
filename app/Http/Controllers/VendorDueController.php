<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('VendorDues/Index', [
            'vendors' => $suppliers,
            'metrics' => [
                'total_procured' => $totalProcuredAll,
                'total_paid' => $totalPaidAll,
                'outstanding_due' => $outstandingDueAll,
                'vendors_with_due' => $vendorsWithDueCount,
            ],
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function pay(Request $request, PurchaseOrderService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'order_id' => 'required|exists:purchase_orders,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = PurchaseOrder::where('team_id', $teamId)->findOrFail($validated['order_id']);

        $service->recordPayment($order, (float) $validated['amount'], [
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', "Payment recorded successfully against PO {$order->po_number}.");
    }
}
