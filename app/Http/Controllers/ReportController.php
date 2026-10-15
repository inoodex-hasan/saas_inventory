<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $team = Auth::user()->currentTeam;
        $currencySymbol = $team->currency_symbol ?: '$';

        $activeTab = $request->input('tab', 'valuation');
        $movementType = $request->input('movement_type', '');
        $movementWarehouseId = $request->input('movement_warehouse_id', '');
        $movementSearch = $request->input('movement_search', '');
        $lowStockSearch = $request->input('low_stock_search', '');

        $warehouses = Warehouse::where('team_id', $team->id)->orderBy('name')->get(['id', 'name', 'code']);
        $categories = ProductCategory::where('team_id', $team->id)->orderBy('name')->get(['id', 'name']);

        // 1. Valuation aggregates
        $valuation = $this->getValuationData($team->id);

        // 2. Low-stock alerts
        $lowStockItems = $this->getLowStockData($team->id, $lowStockSearch);

        // 3. Stock Movements (paginated)
        $movements = $this->getMovementsData($team->id, $movementType, $movementWarehouseId, $movementSearch);

        return Inertia::render('Reports/Index', [
            'currencySymbol' => $currencySymbol,
            'activeTab' => $activeTab,
            'filters' => [
                'tab' => $activeTab,
                'movement_type' => $movementType,
                'movement_warehouse_id' => $movementWarehouseId,
                'movement_search' => $movementSearch,
                'low_stock_search' => $lowStockSearch,
            ],
            'warehouses' => $warehouses,
            'categories' => $categories,
            'valuation' => $valuation,
            'lowStockItems' => $lowStockItems,
            'movements' => $movements,
        ]);
    }

    protected function getValuationData(int $teamId): array
    {
        $totals = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->where('stock_levels.team_id', $teamId)
            ->selectRaw("
                COALESCE(SUM(stock_levels.quantity), 0) as total_units,
                COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as total_cost_value,
                COALESCE(SUM(stock_levels.quantity * products.unit_price), 0) as total_retail_value
            ")
            ->first();

        $totalUnits = (float) ($totals->total_units ?? 0);
        $totalCost = (float) ($totals->total_cost_value ?? 0);
        $totalRetail = (float) ($totals->total_retail_value ?? 0);
        $potentialProfit = max(0, $totalRetail - $totalCost);
        $marginPercent = $totalRetail > 0 ? ($potentialProfit / $totalRetail) * 100 : 0;

        $warehouseBreakdown = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
            ->where('stock_levels.team_id', $teamId)
            ->groupBy('warehouses.id', 'warehouses.name', 'warehouses.code')
            ->selectRaw("
                warehouses.id,
                warehouses.name,
                warehouses.code,
                COALESCE(SUM(stock_levels.quantity), 0) as units_count,
                COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as cost_value,
                COALESCE(SUM(stock_levels.quantity * products.unit_price), 0) as retail_value
            ")
            ->orderByDesc('cost_value')
            ->get();

        $categoryBreakdown = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('stock_levels.team_id', $teamId)
            ->groupBy('product_categories.id', 'product_categories.name')
            ->selectRaw("
                COALESCE(product_categories.name, 'Uncategorized') as category_name,
                COALESCE(SUM(stock_levels.quantity), 0) as units_count,
                COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as cost_value,
                COALESCE(SUM(stock_levels.quantity * products.unit_price), 0) as retail_value
            ")
            ->orderByDesc('cost_value')
            ->get();

        return [
            'total_units' => $totalUnits,
            'total_cost' => $totalCost,
            'total_retail' => $totalRetail,
            'potential_profit' => $potentialProfit,
            'margin_percent' => $marginPercent,
            'warehouse_breakdown' => $warehouseBreakdown,
            'category_breakdown' => $categoryBreakdown,
        ];
    }

    protected function getLowStockData(int $teamId, ?string $search = null): array
    {
        $query = Product::with(['category', 'stockLevels.warehouse'])
            ->where('team_id', $teamId)
            ->where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $products = $query->get();

        return $products->filter(function ($product) {
            $totalStock = $product->total_stock;
            return $product->reorder_level > 0 && $totalStock <= $product->reorder_level;
        })->map(function ($product) {
            $totalStock = $product->total_stock;
            $deficit = max(0, $product->reorder_level - $totalStock);
            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ] : null,
                ],
                'current_stock' => (float) $totalStock,
                'reorder_level' => (float) $product->reorder_level,
                'deficit' => (float) $deficit,
                'cost_price' => (float) $product->cost_price,
                'estimated_restock_cost' => (float) ($deficit * (float) $product->cost_price),
            ];
        })->values()->all();
    }

    protected function getMovementsData(int $teamId, ?string $type = null, ?string $warehouseId = null, ?string $search = null)
    {
        $query = StockMovement::with(['product', 'warehouse', 'creator'])
            ->where('team_id', $teamId)
            ->orderByDesc('created_at');

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($warehouseId)) {
            $query->where('warehouse_id', $warehouseId);
        }

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', $term)
                  ->orWhere('reference_type', 'like', $term)
                  ->orWhereHas('product', function ($pq) use ($term) {
                      $pq->where('name', 'like', $term)
                         ->orWhere('sku', 'like', $term);
                  });
            });
        }

        return $query->paginate(15)->withQueryString();
    }
}
