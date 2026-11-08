<?php

namespace App\Http\Controllers;

use App\Exports\BatchExpiryExport;
use App\Exports\LowStockExport;
use App\Exports\ProductProfitabilityExport;
use App\Exports\StockAgingExport;
use App\Exports\StockMovementsExport;
use App\Exports\StockValuationExport;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

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

        $expiryStatus = $request->input('expiry_status', 'all');
        $expiryWarehouseId = $request->input('expiry_warehouse_id', '');
        $expirySearch = $request->input('expiry_search', '');

        $agingBracket = $request->input('aging_bracket', 'all');
        $agingCategoryId = $request->input('aging_category_id', '');
        $agingSearch = $request->input('aging_search', '');

        $profitabilityPerformance = $request->input('profitability_performance', 'all');
        $profitabilityCategoryId = $request->input('profitability_category_id', '');
        $profitabilitySearch = $request->input('profitability_search', '');

        [$startDate, $endDate, $datePreset, $formattedStart, $formattedEnd] = $this->resolveDateRange($request);

        $warehouses = Warehouse::where('team_id', $team->id)->orderBy('name')->get(['id', 'name', 'code']);
        $categories = ProductCategory::where('team_id', $team->id)->orderBy('name')->get(['id', 'name']);

        // 1. Valuation aggregates
        $valuation = $this->getValuationData($team->id);

        // 2. Low-stock alerts
        $lowStockItems = $this->getLowStockData($team->id, $lowStockSearch);

        // 3. Stock Movements (paginated)
        $movements = $this->getMovementsData($team->id, $movementType, $movementWarehouseId, $movementSearch, $startDate, $endDate);

        // 4. Batch & Expiry Radar
        $expiryData = $this->getExpiryData($team->id, $expiryStatus, $expiryWarehouseId, $expirySearch);

        // 5. Stock Aging & Dead Stock
        $agingData = $this->getAgingData($team->id, $agingBracket, $agingCategoryId, $agingSearch);

        // 6. Product Sales & Gross Margin Profitability
        $profitabilityData = $this->getProfitabilityData($team->id, $startDate, $endDate, $profitabilityPerformance, $profitabilityCategoryId, $profitabilitySearch);

        return Inertia::render('Reports/Index', [
            'currencySymbol' => $currencySymbol,
            'activeTab' => $activeTab,
            'filters' => [
                'tab' => $activeTab,
                'movement_type' => $movementType,
                'movement_warehouse_id' => $movementWarehouseId,
                'movement_search' => $movementSearch,
                'low_stock_search' => $lowStockSearch,
                'expiry_status' => $expiryStatus,
                'expiry_warehouse_id' => $expiryWarehouseId,
                'expiry_search' => $expirySearch,
                'aging_bracket' => $agingBracket,
                'aging_category_id' => $agingCategoryId,
                'aging_search' => $agingSearch,
                'profitability_performance' => $profitabilityPerformance,
                'profitability_category_id' => $profitabilityCategoryId,
                'profitability_search' => $profitabilitySearch,
                'date_preset' => $datePreset,
                'start_date' => $formattedStart,
                'end_date' => $formattedEnd,
            ],
            'warehouses' => $warehouses,
            'categories' => $categories,
            'valuation' => $valuation,
            'lowStockItems' => $lowStockItems,
            'movements' => $movements,
            'expiryData' => $expiryData,
            'agingData' => $agingData,
            'profitabilityData' => $profitabilityData,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $team = Auth::user()->currentTeam;
        $currencySymbol = $team->currency_symbol ?: '৳';
        $tab = $request->input('tab', 'valuation');

        $movementType = $request->input('movement_type', '');
        $movementWarehouseId = $request->input('movement_warehouse_id', '');
        $movementSearch = $request->input('movement_search', '');
        $lowStockSearch = $request->input('low_stock_search', '');

        $expiryStatus = $request->input('expiry_status', 'all');
        $expiryWarehouseId = $request->input('expiry_warehouse_id', '');
        $expirySearch = $request->input('expiry_search', '');

        $agingBracket = $request->input('aging_bracket', 'all');
        $agingCategoryId = $request->input('aging_category_id', '');
        $agingSearch = $request->input('aging_search', '');

        $profitabilityPerformance = $request->input('profitability_performance', 'all');
        $profitabilityCategoryId = $request->input('profitability_category_id', '');
        $profitabilitySearch = $request->input('profitability_search', '');

        [$startDate, $endDate, $datePreset] = $this->resolveDateRange($request);

        $valuation = null;
        $lowStockItems = null;
        $movements = null;
        $expiryData = null;
        $agingData = null;
        $profitabilityData = null;

        if ($tab === 'valuation') {
            $valuation = $this->getValuationData($team->id);
            $reportTitle = 'Stock Valuation & Inventory Assets Report';
        } elseif ($tab === 'low_stock') {
            $lowStockItems = $this->getLowStockData($team->id, $lowStockSearch);
            $reportTitle = 'Low Stock Reorder Radar Report';
        } elseif ($tab === 'expiry') {
            $expiryData = $this->getExpiryData($team->id, $expiryStatus, $expiryWarehouseId, $expirySearch);
            $reportTitle = 'Batch Expiry & Shelf Life Radar Report';
        } elseif ($tab === 'aging') {
            $agingData = $this->getAgingData($team->id, $agingBracket, $agingCategoryId, $agingSearch);
            $reportTitle = 'Stock Aging & Dead Stock Analysis Report';
        } elseif ($tab === 'profitability') {
            $profitabilityData = $this->getProfitabilityData($team->id, $startDate, $endDate, $profitabilityPerformance, $profitabilityCategoryId, $profitabilitySearch);
            $reportTitle = 'Product Sales & Profit Margin Analysis Report';
        } else {
            // Movements
            $query = StockMovement::with(['product', 'warehouse', 'creator'])
                ->where('team_id', $team->id)
                ->orderByDesc('created_at');

            if (!empty($movementType)) {
                $query->where('type', $movementType);
            }
            if (!empty($movementWarehouseId)) {
                $query->where('warehouse_id', $movementWarehouseId);
            }
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            if (!empty($movementSearch)) {
                $term = '%' . $movementSearch . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('reason', 'like', $term)
                      ->orWhere('reference_type', 'like', $term)
                      ->orWhereHas('product', function ($pq) use ($term) {
                          $pq->where('name', 'like', $term)
                             ->orWhere('sku', 'like', $term);
                      });
                });
            }
            $movements = $query->limit(200)->get();
            $reportTitle = 'Inventory Stock Movement Audit Log';
        }

        $marginTop = $team->report_bg_path ? ($team->report_margin_top ?? 42) : 12;
        $marginBottom = $team->report_bg_path ? ($team->report_margin_bottom ?? 25) : 12;

        $pdf = PDF::loadView('pdf.inventory-report', [
            'team' => $team,
            'tab' => $tab,
            'reportTitle' => $reportTitle,
            'currencySymbol' => $currencySymbol,
            'valuation' => $valuation,
            'lowStockItems' => $lowStockItems,
            'movements' => $movements,
            'expiryData' => $expiryData,
            'agingData' => $agingData,
            'profitabilityData' => $profitabilityData,
            'generatedAt' => now()->format('d M Y, h:i A'),
        ], [], [
            'title' => $reportTitle,
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $safeSlug = str_replace(' ', '-', strtolower($reportTitle));
        $filename = "{$safeSlug}-" . now()->format('Y-m-d') . ".pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function downloadExcel(Request $request)
    {
        $team = Auth::user()->currentTeam;
        $tab = $request->input('tab', 'valuation');

        $movementType = $request->input('movement_type', '');
        $movementWarehouseId = $request->input('movement_warehouse_id', '');
        $movementSearch = $request->input('movement_search', '');
        $lowStockSearch = $request->input('low_stock_search', '');
        $warehouseId = $request->input('warehouse_id', '');
        $categoryId = $request->input('category_id', '');

        $expiryStatus = $request->input('expiry_status', 'all');
        $expiryWarehouseId = $request->input('expiry_warehouse_id', '');
        $expirySearch = $request->input('expiry_search', '');

        $agingBracket = $request->input('aging_bracket', 'all');
        $agingCategoryId = $request->input('aging_category_id', '');
        $agingSearch = $request->input('aging_search', '');

        $profitabilityPerformance = $request->input('profitability_performance', 'all');
        $profitabilityCategoryId = $request->input('profitability_category_id', '');
        $profitabilitySearch = $request->input('profitability_search', '');

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $dateSuffix = now()->format('Y-m-d');

        if ($tab === 'valuation') {
            $filename = "stock-valuation-{$dateSuffix}.xlsx";
            return Excel::download(new StockValuationExport($team->id, $warehouseId, $categoryId), $filename);
        } elseif ($tab === 'low_stock') {
            $filename = "low-stock-radar-{$dateSuffix}.xlsx";
            return Excel::download(new LowStockExport($team->id, $lowStockSearch), $filename);
        } elseif ($tab === 'expiry') {
            $filename = "batch-expiry-radar-{$dateSuffix}.xlsx";
            return Excel::download(new BatchExpiryExport($team->id, $expiryStatus, $expiryWarehouseId, $expirySearch), $filename);
        } elseif ($tab === 'aging') {
            $filename = "stock-aging-deadstock-{$dateSuffix}.xlsx";
            return Excel::download(new StockAgingExport($team->id, $agingBracket, $agingCategoryId, $agingSearch), $filename);
        } elseif ($tab === 'profitability') {
            $filename = "sales-profitability-report-{$dateSuffix}.xlsx";
            return Excel::download(new ProductProfitabilityExport($team->id, $profitabilityPerformance, $profitabilityCategoryId, $profitabilitySearch, $startDate, $endDate), $filename);
        } else {
            $filename = "stock-movements-{$dateSuffix}.xlsx";
            return Excel::download(new StockMovementsExport(
                $team->id,
                $movementType,
                $movementWarehouseId,
                $movementSearch,
                $startDate,
                $endDate
            ), $filename);
        }
    }

    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->input('date_preset', 'all_time');
        $startInput = $request->input('start_date');
        $endInput = $request->input('end_date');

        $startDate = null;
        $endDate = null;

        switch ($preset) {
            case 'today':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                break;
            case 'this_week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            case 'custom':
                if ($startInput) {
                    $startDate = Carbon::parse($startInput)->startOfDay();
                }
                if ($endInput) {
                    $endDate = Carbon::parse($endInput)->endOfDay();
                }
                break;
            case 'all_time':
            default:
                $preset = 'all_time';
                $startDate = null;
                $endDate = null;
                break;
        }

        return [
            $startDate,
            $endDate,
            $preset,
            $startDate ? $startDate->toDateString() : ($startInput ?: ''),
            $endDate ? $endDate->toDateString() : ($endInput ?: ''),
        ];
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
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.reorder_level');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $products = $query->get();

        return $products->map(function ($product) {
            $totalStock = (float) $product->stockLevels->sum('quantity');
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

    protected function getMovementsData(int $teamId, ?string $type = null, ?string $warehouseId = null, ?string $search = null, $startDate = null, $endDate = null)
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

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
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

    protected function getExpiryData(int $teamId, ?string $status = null, ?string $warehouseId = null, ?string $search = null): array
    {
        $query = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
            ->leftJoin('warehouses', 'purchase_orders.warehouse_id', '=', 'warehouses.id')
            ->where('purchase_orders.team_id', $teamId)
            ->whereNotNull('purchase_order_items.expiry_date')
            ->select([
                'purchase_order_items.id',
                'purchase_order_items.batch_number',
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('COALESCE(purchase_order_items.received_quantity, purchase_order_items.quantity, 0) as quantity_received'),
                'purchase_order_items.unit_cost',
                'purchase_order_items.manufacture_date',
                'purchase_order_items.expiry_date',
            ])
            ->orderBy('purchase_order_items.expiry_date');

        if (!empty($warehouseId)) {
            $query->where('purchase_orders.warehouse_id', $warehouseId);
        }

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_order_items.batch_number', 'like', $term)
                  ->orWhere('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $allRows = $query->get();
        $today = now()->startOfDay();

        $expiredCount = 0;
        $expiring30Count = 0;
        $expiring90Count = 0;
        $healthyCount = 0;
        $totalAtRiskCost = 0.0;

        $processed = $allRows->map(function ($row) use ($today, &$expiredCount, &$expiring30Count, &$expiring90Count, &$healthyCount, &$totalAtRiskCost) {
            $expiry = Carbon::parse($row->expiry_date)->startOfDay();
            $diff = (int) $today->diffInDays($expiry, false);

            $costValue = (float) $row->quantity_received * (float) $row->unit_cost;

            if ($diff < 0) {
                $statusKey = 'expired';
                $statusLabel = 'Expired';
                $expiredCount++;
                $totalAtRiskCost += $costValue;
            } elseif ($diff <= 30) {
                $statusKey = 'expiring_30';
                $statusLabel = 'Urgent (< 30 days)';
                $expiring30Count++;
                $totalAtRiskCost += $costValue;
            } elseif ($diff <= 90) {
                $statusKey = 'expiring_90';
                $statusLabel = 'Warning (< 90 days)';
                $expiring90Count++;
            } else {
                $statusKey = 'healthy';
                $statusLabel = 'Healthy';
                $healthyCount++;
            }

            return [
                'id' => $row->id,
                'batch_number' => $row->batch_number ?: 'N/A',
                'product_id' => $row->product_id,
                'sku' => $row->sku,
                'product_name' => $row->product_name,
                'warehouse_id' => $row->warehouse_id,
                'warehouse_name' => $row->warehouse_name ?: '—',
                'quantity' => (float) $row->quantity_received,
                'unit_cost' => (float) $row->unit_cost,
                'total_cost' => round($costValue, 2),
                'manufacture_date' => $row->manufacture_date,
                'expiry_date' => $row->expiry_date,
                'days_remaining' => $diff,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
            ];
        });

        $filtered = $processed;
        if (!empty($status) && $status !== 'all') {
            $filtered = $processed->filter(fn ($item) => $item['status_key'] === $status)->values();
        }

        return [
            'items' => $filtered->values()->all(),
            'summary' => [
                'total_batches' => $allRows->count(),
                'expired_count' => $expiredCount,
                'expiring_30_count' => $expiring30Count,
                'expiring_90_count' => $expiring90Count,
                'healthy_count' => $healthyCount,
                'at_risk_cost' => round($totalAtRiskCost, 2),
            ],
        ];
    }

    protected function getAgingData(int $teamId, ?string $bracket = null, ?string $categoryId = null, ?string $search = null): array
    {
        $productsQuery = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->join('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.team_id', $teamId)
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit', 'products.cost_price', 'products.unit_price', 'products.created_at', 'product_categories.name')
            ->selectRaw("
                products.id,
                products.sku,
                products.name,
                products.unit,
                products.cost_price,
                products.unit_price,
                products.created_at,
                product_categories.name as category_name,
                SUM(stock_levels.quantity) as total_stock,
                (
                    SELECT MAX(sm.created_at)
                    FROM stock_movements sm
                    WHERE sm.product_id = products.id
                      AND sm.team_id = {$teamId}
                      AND sm.type = 'out'
                ) as last_sale_at,
                (
                    SELECT MAX(sm2.created_at)
                    FROM stock_movements sm2
                    WHERE sm2.product_id = products.id
                      AND sm2.team_id = {$teamId}
                ) as last_movement_at
            ")
            ->havingRaw('SUM(stock_levels.quantity) > 0')
            ->orderBy('products.name');

        if (!empty($categoryId)) {
            $productsQuery->where('products.category_id', $categoryId);
        }

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $productsQuery->where(function ($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $items = $productsQuery->get();
        $now = now();

        $freshCount = 0;
        $moderateCount = 0;
        $slowCount = 0;
        $deadCount = 0;
        $deadStockCost = 0.0;
        $totalHoldingCost = 0.0;

        $processed = $items->map(function ($item) use ($now, &$freshCount, &$moderateCount, &$slowCount, &$deadCount, &$deadStockCost, &$totalHoldingCost) {
            $referenceDate = $item->last_sale_at
                ? Carbon::parse($item->last_sale_at)
                : ($item->last_movement_at ? Carbon::parse($item->last_movement_at) : Carbon::parse($item->created_at));

            $daysInactive = (int) $referenceDate->diffInDays($now);
            $qty = (float) $item->total_stock;
            $cost = (float) $item->cost_price;
            $retail = (float) $item->unit_price;
            $totalCost = round($qty * $cost, 2);
            $totalHoldingCost += $totalCost;

            if ($daysInactive <= 30) {
                $bracketKey = 'fresh';
                $bracketLabel = 'Fresh (0-30 days)';
                $freshCount++;
            } elseif ($daysInactive <= 60) {
                $bracketKey = 'moderate';
                $bracketLabel = 'Moderate (31-60 days)';
                $moderateCount++;
            } elseif ($daysInactive <= 90) {
                $bracketKey = 'slow';
                $bracketLabel = 'Slow-Moving (61-90 days)';
                $slowCount++;
            } else {
                $bracketKey = 'dead';
                $bracketLabel = 'Dead Stock (90+ days)';
                $deadCount++;
                $deadStockCost += $totalCost;
            }

            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'category_name' => $item->category_name ?: 'Uncategorized',
                'unit' => $item->unit ?: 'pcs',
                'total_stock' => $qty,
                'cost_price' => $cost,
                'retail_price' => $retail,
                'total_cost_value' => $totalCost,
                'total_retail_value' => round($qty * $retail, 2),
                'days_inactive' => $daysInactive,
                'last_activity_date' => $referenceDate->format('Y-m-d'),
                'bracket_key' => $bracketKey,
                'bracket_label' => $bracketLabel,
            ];
        });

        $filtered = $processed;
        if (!empty($bracket) && $bracket !== 'all') {
            $filtered = $processed->filter(fn ($item) => $item['bracket_key'] === $bracket)->values();
        }

        return [
            'items' => $filtered->values()->all(),
            'summary' => [
                'total_skus' => $items->count(),
                'fresh_count' => $freshCount,
                'moderate_count' => $moderateCount,
                'slow_count' => $slowCount,
                'dead_count' => $deadCount,
                'dead_stock_cost' => round($deadStockCost, 2),
                'total_holding_cost' => round($totalHoldingCost, 2),
            ],
        ];
    }

    protected function getProfitabilityData(
        int $teamId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $performance = null,
        ?string $categoryId = null,
        ?string $search = null
    ): array {
        $query = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('sales_orders.team_id', $teamId)
            ->whereNotIn('sales_orders.status', ['cancelled', 'draft'])
            ->groupBy('products.id', 'products.sku', 'products.name', 'product_categories.name')
            ->selectRaw("
                products.id,
                products.sku,
                products.name as product_name,
                COALESCE(product_categories.name, 'Uncategorized') as category_name,
                SUM(sales_order_items.quantity) as units_sold,
                SUM(sales_order_items.subtotal) as total_revenue,
                SUM(sales_order_items.quantity * COALESCE(sales_order_items.unit_cost, products.cost_price, 0)) as total_cogs
            ")
            ->orderByDesc('total_revenue');

        if ($startDate && $endDate) {
            $query->whereBetween('sales_orders.order_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);
        }

        if (!empty($categoryId)) {
            $query->where('products.category_id', $categoryId);
        }

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $records = $query->get();

        $totalRevenue = 0.0;
        $totalCogs = 0.0;
        $totalGrossProfit = 0.0;
        $highCount = 0;
        $healthyCount = 0;
        $lowCount = 0;
        $lossCount = 0;

        $processed = $records->map(function ($row) use (&$totalRevenue, &$totalCogs, &$totalGrossProfit, &$highCount, &$healthyCount, &$lowCount, &$lossCount) {
            $revenue = (float) $row->total_revenue;
            $cogs = (float) $row->total_cogs;
            $profit = round($revenue - $cogs, 2);
            $marginPercent = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0.0;
            $unitsSold = (float) $row->units_sold;
            $avgSellingPrice = $unitsSold > 0 ? round($revenue / $unitsSold, 2) : 0.0;

            $totalRevenue += $revenue;
            $totalCogs += $cogs;
            $totalGrossProfit += $profit;

            if ($marginPercent >= 40) {
                $perfKey = 'high';
                $perfLabel = 'High Margin (>= 40%)';
                $highCount++;
            } elseif ($marginPercent >= 20) {
                $perfKey = 'healthy';
                $perfLabel = 'Healthy (20-39%)';
                $healthyCount++;
            } elseif ($marginPercent > 0) {
                $perfKey = 'low';
                $perfLabel = 'Low Margin (1-19%)';
                $lowCount++;
            } else {
                $perfKey = 'loss';
                $perfLabel = 'Loss Making (<= 0%)';
                $lossCount++;
            }

            return [
                'id' => $row->id,
                'sku' => $row->sku,
                'product_name' => $row->product_name,
                'category_name' => $row->category_name,
                'units_sold' => $unitsSold,
                'avg_selling_price' => $avgSellingPrice,
                'total_revenue' => $revenue,
                'total_cogs' => $cogs,
                'gross_profit' => $profit,
                'margin_percent' => $marginPercent,
                'perf_key' => $perfKey,
                'perf_label' => $perfLabel,
            ];
        });

        $overallMarginPercent = $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 2) : 0.0;

        $filtered = $processed;
        if (!empty($performance) && $performance !== 'all') {
            $filtered = $processed->filter(fn ($item) => $item['perf_key'] === $performance)->values();
        }

        return [
            'items' => $filtered->values()->all(),
            'summary' => [
                'total_products' => $records->count(),
                'total_revenue' => round($totalRevenue, 2),
                'total_cogs' => round($totalCogs, 2),
                'total_gross_profit' => round($totalGrossProfit, 2),
                'overall_margin_percent' => $overallMarginPercent,
                'high_count' => $highCount,
                'healthy_count' => $healthyCount,
                'low_count' => $lowCount,
                'loss_count' => $lossCount,
            ],
        ];
    }
}
