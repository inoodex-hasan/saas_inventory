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
use Inertia\Inertia;

class DeliveryChallanController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $customerId = $request->input('customer_id');

        $query = DeliveryChallan::where('team_id', $teamId)
            ->with(['salesOrder', 'warehouse', 'customer', 'items.product'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('challan_number', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', fn ($so) => $so->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($customerId, fn ($q, $id) => $q->where('customer_id', $id));

        $challans = $query->latest('id')->paginate(15)->withQueryString();

        $customers = Customer::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $metrics = [
            'total_challans' => DeliveryChallan::where('team_id', $teamId)->count(),
            'dispatched_count' => DeliveryChallan::where('team_id', $teamId)->where('status', 'dispatched')->count(),
            'delivered_count' => DeliveryChallan::where('team_id', $teamId)->where('status', 'delivered')->count(),
            'draft_count' => DeliveryChallan::where('team_id', $teamId)->where('status', 'draft')->count(),
        ];

        return Inertia::render('Challans/Index', [
            'page_title' => 'Delivery Challans & Gate Passes',
            'challans' => $challans,
            'customers' => $customers,
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
            'sales_order_id' => 'required|exists:sales_orders,id',
            'dispatch_date' => 'required|date',
            'transport_carrier' => 'nullable|string|max:100',
            'vehicle_no' => 'nullable|string|max:100',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'delivery_address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $order = SalesOrder::where('team_id', $teamId)->findOrFail($validated['sales_order_id']);

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
