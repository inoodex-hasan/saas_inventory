<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ServiceTicket;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\SerialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WarrantyController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'all'); // all, active, expiring_soon, expired, in_stock, defective
        $warehouseFilter = $request->input('warehouse_id');

        $query = ProductSerial::where('team_id', $teamId)
            ->with(['product', 'warehouse', 'customer', 'salesOrder', 'purchaseOrder.supplier'])
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $warehouseFilter));

        if ($statusFilter === 'active') {
            $query->where('status', 'sold')->whereNotNull('warranty_expires_at')->where('warranty_expires_at', '>', now());
        } elseif ($statusFilter === 'expiring_soon') {
            $query->where('status', 'sold')
                ->whereNotNull('warranty_expires_at')
                ->where('warranty_expires_at', '>', now())
                ->where('warranty_expires_at', '<=', now()->addDays(30));
        } elseif ($statusFilter === 'expired') {
            $query->where('status', 'sold')->whereNotNull('warranty_expires_at')->where('warranty_expires_at', '<=', now());
        } elseif ($statusFilter === 'in_stock') {
            $query->where('status', 'in_stock');
        } elseif ($statusFilter === 'defective') {
            $query->where('status', 'defective');
        }

        $serials = $query->latest('id')->paginate(15)->withQueryString();

        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $metrics = [
            'total_serials' => ProductSerial::where('team_id', $teamId)->count(),
            'active_warranties' => ProductSerial::where('team_id', $teamId)->where('status', 'sold')->where('warranty_expires_at', '>', now())->count(),
            'expiring_soon' => ProductSerial::where('team_id', $teamId)->where('status', 'sold')->where('warranty_expires_at', '>', now())->where('warranty_expires_at', '<=', now()->addDays(30))->count(),
            'defective_count' => ProductSerial::where('team_id', $teamId)->where('status', 'defective')->count(),
            'in_stock_count' => ProductSerial::where('team_id', $teamId)->where('status', 'in_stock')->count(),
        ];

        return Inertia::render('Warranties/Index', [
            'page_title' => 'Serial Numbers & Warranty Center',
            'warranty_labels' => [
                'active' => 'Active Warranty',
                'expiring' => 'Expiring Soon',
            ],
            'serials' => $serials,
            'warehouses' => $warehouses,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'warehouse_id' => $warehouseFilter,
            ],
        ]);
    }

    public function markDefective(Request $request, ProductSerial $serial, SerialService $serialService)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($serial->team_id !== $teamId, 403);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $serialService->markDefective($serial, $validated['reason']);

        return redirect()->back()->with('success', "Serial {$serial->serial_number} marked as defective.");
    }

    /**
     * Return available in-stock serial numbers for a product to use as a warranty replacement.
     */
    public function availableReplacements(Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $serials = ProductSerial::where('team_id', $teamId)
            ->where('product_id', $product->id)
            ->where('status', 'in_stock')
            ->with('warehouse:id,name')
            ->orderBy('id', 'asc')
            ->get(['id', 'serial_number', 'warehouse_id']);

        return response()->json(['serials' => $serials]);
    }

    /**
     * Process a customer warranty claim: either create a $0 Service Ticket (repair)
     * or issue an instant replacement serial from in-stock inventory.
     */
    public function claimWarranty(Request $request, ProductSerial $serial, SerialService $serialService)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($serial->team_id !== $teamId, 403);

        if ($serial->status !== 'sold') {
            return redirect()->back()->with('error', 'Only sold serial numbers can be claimed under warranty.');
        }

        $validated = $request->validate([
            'claim_type' => 'required|in:repair,replace',
            'reason' => 'required|string|max:500',
            'replacement_serial_id' => 'required_if:claim_type,replace|nullable|integer',
            'technician_name' => 'nullable|string|max:120',
        ]);

        if ($validated['claim_type'] === 'repair') {
            $ticket = DB::transaction(function () use ($serial, $validated, $serialService, $teamId) {
                $serial->loadMissing(['product', 'customer']);
                $serialService->markDefective($serial, '[Warranty Repair] ' . $validated['reason']);

                return ServiceTicket::create([
                    'team_id' => $teamId,
                    'customer_id' => $serial->customer_id,
                    'ticket_number' => 'SRV-WRN-' . strtoupper(Str::random(6)),
                    'device_name' => $serial->product?->name ?? 'Warranty Item',
                    'serial_no' => $serial->serial_number,
                    'problem_description' => '[Warranty Claim] ' . $validated['reason'],
                    'technician_name' => $validated['technician_name'] ?? null,
                    'status' => 'received',
                    'priority' => 'high',
                    'service_charge' => 0,
                    'parts_total' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'payment_status' => 'paid',
                    'warranty_days' => (int) ($serial->warranty_days ?? 0),
                    'received_date' => now()->toDateString(),
                    'remarks' => "Warranty repair claim for Serial #{$serial->serial_number}",
                    'created_by' => Auth::id(),
                ]);
            });

            return redirect()->back()->with('success', "Warranty repair ticket {$ticket->ticket_number} created for Serial {$serial->serial_number}.");
        }

        // Instant Replacement
        $replacement = ProductSerial::where('team_id', $teamId)
            ->where('id', $validated['replacement_serial_id'])
            ->where('product_id', $serial->product_id)
            ->where('status', 'in_stock')
            ->first();

        if (!$replacement) {
            return redirect()->back()->with('error', 'Selected replacement serial is not available in stock.');
        }

        DB::transaction(function () use ($serial, $replacement, $validated, $serialService, $teamId) {
            $serialService->markDefective(
                $serial,
                "[Warranty Replaced with {$replacement->serial_number}] " . $validated['reason']
            );

            $replacement->update([
                'status' => 'sold',
                'customer_id' => $serial->customer_id,
                'sales_order_id' => $serial->sales_order_id,
                'sales_order_item_id' => $serial->sales_order_item_id,
                'sold_at' => now(),
                'warranty_days' => $serial->warranty_days,
                'warranty_expires_at' => $serial->warranty_expires_at,
                'notes' => "Warranty replacement for defective SN: {$serial->serial_number}",
            ]);

            // Deduct 1 unit from the replacement serial's warehouse stock level
            $stockLevel = StockLevel::where('team_id', $teamId)
                ->where('warehouse_id', $replacement->warehouse_id)
                ->where('product_id', $replacement->product_id)
                ->first();

            if ($stockLevel && $stockLevel->quantity > 0) {
                $stockLevel->decrement('quantity', 1);
            }
        });

        return redirect()->back()->with('success', "Replaced defective Serial {$serial->serial_number} with {$replacement->serial_number}.");
    }

    public function checkUniqueness(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $serials = $request->input('serials', []);
        if (is_string($serials)) {
            $serials = preg_split('/[\r\n,]+/', $serials, -1, PREG_SPLIT_NO_EMPTY);
        }
        $serials = array_values(array_unique(array_filter(array_map('trim', (array) $serials))));

        if (empty($serials)) {
            return response()->json(['available' => true, 'existing' => []]);
        }

        $existing = ProductSerial::where('team_id', $teamId)
            ->whereIn('serial_number', $serials)
            ->pluck('serial_number')
            ->toArray();

        return response()->json([
            'available' => empty($existing),
            'existing' => $existing,
        ]);
    }
}
