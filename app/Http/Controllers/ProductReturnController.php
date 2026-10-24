<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductReturn;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductReturnController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $status = $request->input('status', '');

        $query = ProductReturn::where('team_id', $teamId)
            ->with(['salesOrder', 'customer', 'warehouse', 'items.product', 'creator'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', fn ($so) => $so->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status));

        $returns = $query->latest('id')->paginate(15)->withQueryString();

        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $eligibleOrders = SalesOrder::where('team_id', $teamId)
            ->whereIn('status', ['confirmed', 'dispatched', 'completed'])
            ->with(['customer', 'items.product'])
            ->latest('id')
            ->take(50)
            ->get();

        $metrics = [
            'total_returns' => ProductReturn::where('team_id', $teamId)->count(),
            'total_refunded' => (float) ProductReturn::where('team_id', $teamId)->where('status', 'completed')->sum('refund_amount'),
            'pending_requests' => ProductReturn::where('team_id', $teamId)->where('status', 'pending')->count(),
            'approved_count' => ProductReturn::where('team_id', $teamId)->where('status', 'approved')->count(),
        ];

        return Inertia::render('Returns/Index', [
            'returns' => $returns,
            'warehouses' => $warehouses,
            'eligibleOrders' => $eligibleOrders,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request, ReturnService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'sales_order_id' => ['required', Rule::exists('sales_orders', 'id')->where('team_id', $teamId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'reason' => 'required|string|max:500',
            'refund_type' => 'required|in:credit_note,cash_refund,bank_refund,replacement',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|integer',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.condition' => 'required|in:sellable,damaged,defective',
            'items.*.action_taken' => 'required|in:restock,scrap,repair',
            'items.*.serial_number' => 'nullable|string',
        ]);

        $order = SalesOrder::where('team_id', $teamId)->findOrFail($validated['sales_order_id']);

        $returnData = [
            'sales_order_id' => $order->id,
            'warehouse_id' => $validated['warehouse_id'] ?? $order->warehouse_id,
            'reason' => $validated['reason'],
            'refund_type' => $validated['refund_type'],
            'notes' => $validated['notes'] ?? null,
        ];

        $itemsData = array_map(function ($it) {
            return [
                'sales_order_item_id' => $it['sales_order_item_id'],
                'product_id' => $it['product_id'],
                'quantity' => (float) $it['quantity'],
                'unit_price' => (float) $it['unit_price'],
                'condition' => $it['condition'],
                'action_taken' => $it['action_taken'],
                'serial_number' => $it['serial_number'] ?? null,
            ];
        }, $validated['items']);

        $return = $service->createReturn($returnData, $itemsData);

        return redirect()->back()->with('success', "Return request {$return->return_number} logged successfully.");
    }

    public function approve(ProductReturn $return, ReturnService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($return->team_id !== $teamId, 403);

        $service->approveReturn($return);

        return redirect()->back()->with('success', "Return {$return->return_number} approved.");
    }

    public function reject(Request $request, ProductReturn $return, ReturnService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($return->team_id !== $teamId, 403);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $service->rejectReturn($return, Auth::id(), $validated['reason']);

        return redirect()->back()->with('success', "Return {$return->return_number} rejected.");
    }

    public function complete(ProductReturn $return, ReturnService $service)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($return->team_id !== $teamId, 403);

        $service->completeReturn($return);

        return redirect()->back()->with('success', "Return {$return->return_number} processed and items restocked.");
    }
}
