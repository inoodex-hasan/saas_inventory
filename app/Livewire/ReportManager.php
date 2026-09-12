<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReportManager extends Component
{
    use WithPagination;

    public string $activeTab = 'valuation'; // valuation, low_stock, movements

    // Filters for Stock Movements
    public string $movementType = '';
    public string $movementWarehouseId = '';
    public string $movementSearch = '';

    // Filters for Low Stock
    public string $lowStockWarehouseId = '';
    public string $lowStockSearch = '';

    protected $queryString = [
        'activeTab' => ['except' => 'valuation'],
        'movementType' => ['except' => ''],
        'movementWarehouseId' => ['except' => ''],
        'lowStockWarehouseId' => ['except' => ''],
    ];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingMovementSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMovementType(): void
    {
        $this->resetPage();
    }

    public function updatingMovementWarehouseId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $team = Auth::user()->currentTeam;
        $currencySymbol = $team->currency_symbol ?: '$';

        $warehouses = Warehouse::orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();

        // 1. Valuation Report Metrics & Aggregates
        $valuationData = $this->getValuationData($team->id);

        // 2. Low-Stock Alerts
        $lowStockItems = $this->getLowStockData($team->id);

        // 3. Stock Movements (Paginated)
        $movements = $this->getMovementsData($team->id);

        return view('livewire.report-manager', [
            'team' => $team,
            'currencySymbol' => $currencySymbol,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'valuation' => $valuationData,
            'lowStockItems' => $lowStockItems,
            'movements' => $movements,
        ])->layout('layouts.app');
    }

    protected function getValuationData(int $teamId): array
    {
        // Overall Totals
        $totals = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->where('stock_levels.team_id', $teamId)
            ->selectRaw('
                COALESCE(SUM(stock_levels.quantity), 0) as total_units,
                COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as total_cost_value,
                COALESCE(SUM(stock_levels.quantity * products.unit_price), 0) as total_retail_value
            ')
            ->first();

        $totalUnits = (float) ($totals->total_units ?? 0);
        $totalCost = (float) ($totals->total_cost_value ?? 0);
        $totalRetail = (float) ($totals->total_retail_value ?? 0);
        $potentialProfit = max(0, $totalRetail - $totalCost);
        $marginPercent = $totalRetail > 0 ? ($potentialProfit / $totalRetail) * 100 : 0;

        // Warehouse Breakdown
        $warehouseBreakdown = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
            ->where('stock_levels.team_id', $teamId)
            ->groupBy('warehouses.id', 'warehouses.name', 'warehouses.code')
            ->selectRaw('
                warehouses.id,
                warehouses.name,
                warehouses.code,
                COALESCE(SUM(stock_levels.quantity), 0) as units_count,
                COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as cost_value,
                COALESCE(SUM(stock_levels.quantity * products.unit_price), 0) as retail_value
            ')
            ->orderByDesc('cost_value')
            ->get();

        // Category Breakdown
        $categoryBreakdown = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('stock_levels.team_id', $teamId)
            ->groupBy('product_categories.id', 'product_categories.name')
            ->selectRaw('
                COALESCE(product_categories.name, "Uncategorized") as category_name,
                COALESCE(SUM(stock_levels.quantity), 0) as units_count,
                COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as cost_value,
                COALESCE(SUM(stock_levels.quantity * products.unit_price), 0) as retail_value
            ')
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

    protected function getLowStockData(int $teamId)
    {
        $query = Product::with(['category', 'stockLevels.warehouse'])
            ->where('is_active', true);

        if ($this->lowStockSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->lowStockSearch . '%')
                  ->orWhere('sku', 'like', '%' . $this->lowStockSearch . '%');
            });
        }

        $products = $query->get();

        return $products->filter(function ($product) {
            $totalStock = $product->total_stock;
            // Item is low stock if reorder_level is set and current stock is <= reorder_level
            return $product->reorder_level > 0 && $totalStock <= $product->reorder_level;
        })->map(function ($product) {
            $totalStock = $product->total_stock;
            $deficit = max(0, $product->reorder_level - $totalStock);
            return [
                'product' => $product,
                'current_stock' => $totalStock,
                'reorder_level' => $product->reorder_level,
                'deficit' => $deficit,
                'cost_price' => $product->cost_price,
                'estimated_restock_cost' => $deficit * (float) $product->cost_price,
            ];
        });
    }

    protected function getMovementsData(int $teamId)
    {
        $query = StockMovement::with(['product', 'warehouse', 'creator'])
            ->orderByDesc('created_at');

        if ($this->movementType) {
            $query->where('type', $this->movementType);
        }

        if ($this->movementWarehouseId) {
            $query->where('warehouse_id', $this->movementWarehouseId);
        }

        if ($this->movementSearch) {
            $search = '%' . $this->movementSearch . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', $search)
                  ->orWhere('reference_type', 'like', $search)
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', $search)
                         ->orWhere('sku', 'like', $search);
                  });
            });
        }

        return $query->paginate(15);
    }
}
