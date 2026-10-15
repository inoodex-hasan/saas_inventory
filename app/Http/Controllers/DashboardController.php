<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the main SaaS command dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        // Super Admin lands on platform management unless actively inspecting a client tenant
        if ($user->isSuperAdmin() && (!$team || $user->ownedTeams->contains($team))) {
            return redirect()->route('admin.dashboard');
        }

        if (!$team) {
            return redirect()->route('teams.create');
        }

        // 1. Total Inventory Valuation (sum of quantity * cost_price across all warehouses)
        $totalValuation = (float) DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->where('stock_levels.team_id', $team->id)
            ->whereNull('products.deleted_at')
            ->sum(DB::raw('stock_levels.quantity * products.cost_price'));

        $totalProducts = Product::where('team_id', $team->id)->count();

        // 2. Total Sales Volume & Orders
        $totalSalesVolume = (float) SalesOrder::where('team_id', $team->id)
            ->whereIn('status', ['confirmed', 'dispatched', 'delivered'])
            ->sum('total_amount');

        $totalSalesOrders = SalesOrder::where('team_id', $team->id)->count();

        // 3. Open Purchase Orders (pending delivery)
        $pendingPurchasesCount = PurchaseOrder::where('team_id', $team->id)
            ->whereIn('status', ['draft', 'ordered', 'partially_received'])
            ->count();

        $openPurchaseOrders = (float) PurchaseOrder::where('team_id', $team->id)
            ->whereIn('status', ['draft', 'ordered', 'partially_received'])
            ->sum('total_amount');

        // 4. Low Stock Products & Alerts
        $lowStockBaseQuery = Product::where('team_id', $team->id)
            ->where('is_active', true)
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.reorder_level');

        $lowStockCount = (clone $lowStockBaseQuery)->count();

        $lowStockProducts = (clone $lowStockBaseQuery)
            ->withSum('stockLevels as total_on_hand', 'quantity')
            ->take(10)
            ->get();

        // 5. Recent Activity
        $recentSalesOrders = SalesOrder::where('team_id', $team->id)
            ->with(['customer', 'warehouse'])
            ->latest('id')
            ->take(5)
            ->get();

        $recentPurchaseOrders = PurchaseOrder::where('team_id', $team->id)
            ->with(['supplier', 'warehouse'])
            ->latest('id')
            ->take(5)
            ->get();

        $recentStockMovements = StockMovement::where('team_id', $team->id)
            ->with(['product', 'warehouse', 'creator'])
            ->latest('created_at')
            ->take(6)
            ->get();

        // 6. Warehouse Breakdown (Total stock count & value per location)
        $warehouseStockBreakdown = Warehouse::where('team_id', $team->id)
            ->where('is_active', true)
            ->with(['stockLevels.product'])
            ->get()
            ->map(function ($warehouse) {
                $totalUnits = (float) $warehouse->stockLevels->sum('quantity');
                $warehouseValuation = (float) $warehouse->stockLevels->reduce(function ($carry, $level) {
                    return $carry + ($level->quantity * ($level->product?->cost_price ?? 0));
                }, 0.0);

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'is_default' => $warehouse->is_default,
                    'total_units' => $totalUnits,
                    'total_valuation' => $warehouseValuation,
                ];
            });

        return Inertia::render('Dashboard', [
            'totalValuation' => $totalValuation,
            'totalProducts' => $totalProducts,
            'totalSalesVolume' => $totalSalesVolume,
            'totalSalesOrders' => $totalSalesOrders,
            'pendingPurchasesCount' => $pendingPurchasesCount,
            'openPurchaseOrders' => $openPurchaseOrders,
            'lowStockCount' => $lowStockCount,
            'lowStockProducts' => $lowStockProducts,
            'recentSalesOrders' => $recentSalesOrders,
            'recentPurchaseOrders' => $recentPurchaseOrders,
            'recentStockMovements' => $recentStockMovements,
            'warehouseStockBreakdown' => $warehouseStockBreakdown,
        ]);
    }
}
