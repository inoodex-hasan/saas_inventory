<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DeliveryChallanController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');

        $query = DeliveryChallan::where('team_id', $teamId)
            ->with(['salesOrder', 'warehouse', 'customer', 'items.product', 'creator'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('challan_number', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', fn ($so) => $so->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $customerId));

        $challans = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_challans' => DeliveryChallan::where('team_id', $teamId)->count(),
            'dispatched_count' => DeliveryChallan::where('team_id', $teamId)->where('status', 'dispatched')->count(),
            'delivered_count' => DeliveryChallan::where('team_id', $teamId)->where('status', 'delivered')->count(),
            'draft_count' => DeliveryChallan::where('team_id', $teamId)->where('status', 'draft')->count(),
        ];

        return Inertia::render('Challans/Index', [
            'page_title' => 'Delivery Challans & Gate Passes',
            'challans' => $challans,
            'customers' => fn () => Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get(),
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'customer_id' => $customerId,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $orders = SalesOrder::where('team_id', $teamId)
            ->whereIn('status', ['confirmed', 'dispatched', 'draft'])
            ->with(['customer', 'warehouse', 'items.product'])
            ->latest('id')
            ->get();

        $selectedOrderId = $request->input('order_id');

        $prefix = 'DC-' . Carbon::now()->format('Ymd') . '-';
        $last = DeliveryChallan::where('team_id', $teamId)->where('challan_number', 'LIKE', $prefix . '%')->latest('id')->first();
        $nextNum = $last ? str_pad((int) substr($last->challan_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
        $challanNumber = $prefix . $nextNum;

        return Inertia::render('Challans/Create', [
            'page_title' => 'Generate Delivery Challan',
            'orders' => $orders,
            'selectedOrderId' => $selectedOrderId ? (int) $selectedOrderId : null,
            'challanNumber' => $challanNumber,
            'todayDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'sales_order_id' => ['required', Rule::exists('sales_orders', 'id')->where('team_id', $teamId)],
            'dispatch_date' => 'required|date',
            'transport_carrier' => 'nullable|string|max:100',
            'vehicle_no' => 'nullable|string|max:100',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'delivery_address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|integer',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $order = SalesOrder::where('team_id', $teamId)->with('items.product')->findOrFail($validated['sales_order_id']);

        foreach ($validated['items'] as $it) {
            $soItem = $order->items->firstWhere('id', $it['sales_order_item_id']);
            if (!$soItem) {
                return back()->withErrors(['items' => 'One or more items do not belong to the selected sales order.']);
            }

            if ((int) $it['product_id'] !== (int) $soItem->product_id) {
                return back()->withErrors(['items' => "Selected product does not match sales order line item #{$soItem->id}."]);
            }

            $alreadyDispatched = (float) DeliveryChallanItem::where('sales_order_item_id', $soItem->id)
                ->whereHas('deliveryChallan', fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->sum('quantity');

            $remaining = (float) $soItem->quantity - $alreadyDispatched;
            if ((float) $it['quantity'] > $remaining) {
                $productName = $soItem->product?->name ?? 'item';
                return back()->withErrors(['items' => "Dispatched quantity for {$productName} ({$it['quantity']}) exceeds remaining balance ({$remaining})."]);
            }
        }

        return DB::transaction(function () use ($validated, $order, $teamId) {
            $prefix = 'DC-' . Carbon::now()->format('Ymd') . '-';
            $last = DeliveryChallan::where('team_id', $teamId)->where('challan_number', 'LIKE', $prefix . '%')->latest('id')->first();
            $nextNum = $last ? str_pad((int) substr($last->challan_number, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $challanNumber = $prefix . $nextNum;

            $challan = DeliveryChallan::create([
                'team_id' => $teamId,
                'challan_number' => $challanNumber,
                'sales_order_id' => $order->id,
                'warehouse_id' => $order->warehouse_id,
                'customer_id' => $order->customer_id,
                'dispatch_date' => $validated['dispatch_date'],
                'transport_carrier' => $validated['transport_carrier'] ?? 'In-house Fleet',
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_phone' => $validated['driver_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'],
                'status' => 'dispatched',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $it) {
                DeliveryChallanItem::create([
                    'team_id' => $teamId,
                    'delivery_challan_id' => $challan->id,
                    'sales_order_item_id' => $it['sales_order_item_id'],
                    'product_id' => $it['product_id'],
                    'quantity' => (float) $it['quantity'],
                ]);
            }

            return redirect()->route('challans.index')->with('success', "Delivery Challan {$challan->challan_number} issued successfully.");
        });
    }

    public function edit(DeliveryChallan $challan)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($challan->team_id !== $teamId, 403);

        $challan->load(['salesOrder.customer', 'salesOrder.warehouse', 'customer', 'warehouse', 'items.product']);

        $orders = SalesOrder::where('team_id', $teamId)
            ->where(function ($q) use ($challan) {
                $q->whereIn('status', ['confirmed', 'dispatched', 'draft', 'delivered', 'completed'])
                    ->orWhere('id', $challan->sales_order_id);
            })
            ->with(['customer', 'warehouse', 'items.product'])
            ->latest('id')
            ->get();

        return Inertia::render('Challans/Edit', [
            'page_title' => 'Edit Delivery Challan ' . $challan->challan_number,
            'challan' => $challan,
            'orders' => $orders,
        ]);
    }

    public function update(Request $request, DeliveryChallan $challan)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($challan->team_id !== $teamId, 403);

        $validated = $request->validate([
            'dispatch_date' => 'required|date',
            'status' => 'required|in:draft,dispatched,delivered',
            'transport_carrier' => 'nullable|string|max:100',
            'vehicle_no' => 'nullable|string|max:100',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'delivery_address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('team_id', $teamId)],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.packaging_description' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $challan, $teamId) {
            $challan->update([
                'dispatch_date' => $validated['dispatch_date'],
                'status' => $validated['status'],
                'transport_carrier' => $validated['transport_carrier'] ?? 'In-house Fleet',
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_phone' => $validated['driver_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $challan->items()->delete();
            foreach ($validated['items'] as $it) {
                DeliveryChallanItem::create([
                    'team_id' => $teamId,
                    'delivery_challan_id' => $challan->id,
                    'product_id' => $it['product_id'],
                    'quantity' => (float) $it['quantity'],
                    'packaging_description' => $it['packaging_description'] ?? null,
                    'notes' => $it['notes'] ?? null,
                ]);
            }

            return redirect()->route('challans.index')->with('success', "Delivery Challan {$challan->challan_number} updated successfully.");
        });
    }

    public function markDelivered(DeliveryChallan $challan)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($challan->team_id !== $teamId, 403);

        $challan->update(['status' => 'delivered']);

        return redirect()->back()->with('success', "Challan {$challan->challan_number} marked as safely delivered.");
    }

    public function destroy(DeliveryChallan $challan)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($challan->team_id !== $teamId, 403);

        $challan->items()->delete();
        $challan->delete();

        return redirect()->back()->with('success', "Delivery Challan deleted successfully.");
    }
}
