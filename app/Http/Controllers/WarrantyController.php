<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Warehouse;
use App\Services\SerialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($warehouseFilter, fn ($q, $wid) => $q->where('warehouse_id', $wid));

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
}
