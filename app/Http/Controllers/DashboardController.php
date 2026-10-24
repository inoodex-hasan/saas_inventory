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

        // 6. Warehouse Breakdown (Total stock count & value per location via SQL aggregation)
        $warehouseValuations = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->where('stock_levels.team_id', $team->id)
            ->groupBy('stock_levels.warehouse_id')
            ->select(
                'stock_levels.warehouse_id',
                DB::raw('SUM(stock_levels.quantity) as total_units'),
                DB::raw('SUM(stock_levels.quantity * products.cost_price) as total_valuation')
            )
            ->get()
            ->keyBy('warehouse_id');

        $warehouseStockBreakdown = Warehouse::where('team_id', $team->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($warehouse) use ($warehouseValuations) {
                $stat = $warehouseValuations->get($warehouse->id);

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'is_default' => $warehouse->is_default,
                    'total_units' => (float) ($stat->total_units ?? 0),
                    'total_valuation' => (float) ($stat->total_valuation ?? 0),
                ];
            });

        // 7. Monthly Sales vs Procurement Trend (Last 6 Months)
        $trendStartDate = Carbon::now()->subMonths(5)->startOfMonth()->toDateString();
        $trendEndDate = Carbon::now()->endOfMonth()->toDateString();

        $monthlySales = SalesOrder::where('team_id', $team->id)
            ->whereBetween('order_date', [$trendStartDate, $trendEndDate])
            ->selectRaw('SUBSTR(order_date, 1, 7) as month_key, SUM(total_amount) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $monthlyPurchases = PurchaseOrder::where('team_id', $team->id)
            ->whereBetween('order_date', [$trendStartDate, $trendEndDate])
            ->selectRaw('SUBSTR(order_date, 1, 7) as month_key, SUM(total_amount) as total')
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $salesTrend = collect([]);
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $salesSum = (float) ($monthlySales->get($monthKey) ?? 0);
            $purchasesSum = (float) ($monthlyPurchases->get($monthKey) ?? 0);

            $salesTrend->push([
                'month'       => $monthDate->format('M Y'),
                'short_month' => $monthDate->format('M'),
                'sales'       => $salesSum,
                'purchases'   => $purchasesSum,
            ]);
        }

        // 8. Inventory Valuation Breakdown by Category (for Donut / Pie Chart)
        $categoryDistribution = DB::table('product_categories')
            ->join('products', 'product_categories.id', '=', 'products.category_id')
            ->join('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('product_categories.team_id', $team->id)
            ->whereNull('products.deleted_at')
            ->select(
                'product_categories.id',
                'product_categories.name',
                DB::raw('CAST(SUM(stock_levels.quantity * products.cost_price) AS DECIMAL(15,2)) as valuation'),
                DB::raw('CAST(SUM(stock_levels.quantity) AS UNSIGNED) as total_units')
            )
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('valuation')
            ->get();

        // 9. Order Fulfillment Status Breakdown
        $orderStatusDistribution = SalesOrder::where('team_id', $team->id)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get();

        return Inertia::render('Dashboard', [
            'totalValuation'          => $totalValuation,
            'totalProducts'           => $totalProducts,
            'totalSalesVolume'        => $totalSalesVolume,
            'totalSalesOrders'        => $totalSalesOrders,
            'pendingPurchasesCount'   => $pendingPurchasesCount,
            'openPurchaseOrders'      => $openPurchaseOrders,
            'lowStockCount'           => $lowStockCount,
            'lowStockProducts'        => $lowStockProducts,
            'recentSalesOrders'       => $recentSalesOrders,
            'recentPurchaseOrders'    => $recentPurchaseOrders,
            'recentStockMovements'    => $recentStockMovements,
            'warehouseStockBreakdown' => $warehouseStockBreakdown,
            'salesTrend'              => $salesTrend,
            'categoryDistribution'    => $categoryDistribution,
            'orderStatusDistribution' => $orderStatusDistribution,
        ]);
    }
}
