<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;
        $search = trim($request->query('search', ''));
        $warehouseId = $request->query('warehouse_id');
        $categoryId = $request->query('category_id');
        $status = $request->query('status');
        $activeTab = $request->query('tab', 'stock');

        // Warehouses & Categories for filters & modals
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $categories = ProductCategory::where('team_id', $teamId)->orderBy('name')->get(['id', 'name']);
        $productsList = Product::where('team_id', $teamId)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price']);

        // Global KPI Metrics across all inventory
        $totalUnits = (float) StockLevel::where('team_id', $teamId)->sum('quantity');

        // Total valuation: join stock_levels with products
        $totalValuation = (float) DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->where('stock_levels.team_id', $teamId)
            ->whereNull('products.deleted_at')
            ->sum(DB::raw('stock_levels.quantity * products.cost_price'));

        // Low stock count (threshold <= alert_quantity or default 5)
        $lowStockCount = Product::where('team_id', $teamId)
            ->whereHas('stockLevels')
            ->get()
            ->filter(function ($p) {
                $stock = (float) $p->stockLevels->sum('quantity');
                $threshold = (float) ($p->alert_quantity ?: 5);
                return $stock > 0 && $stock <= $threshold;
            })
            ->count();

        // Out of stock count
        $outOfStockCount = Product::where('team_id', $teamId)
            ->whereDoesntHave('stockLevels', function ($q) {
                $q->where('quantity', '>', 0);
            })
            ->count();

        // Total Incoming Units from pending purchase orders
        $totalIncomingUnits = (float) DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.team_id', $teamId)
            ->whereIn('purchase_orders.status', ['ordered', 'partially_received'])
            ->sum(DB::raw('purchase_order_items.quantity - purchase_order_items.received_quantity'));

        $metrics = [
            'total_units' => $totalUnits,
            'total_valuation' => $totalValuation,
            'total_incoming_units' => $totalIncomingUnits,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];

        // 1. Stock Balances Query
        $stockQuery = Product::query()
            ->where('team_id', $teamId)
            ->with([
                'category:id,name',
                'brand:id,name',
                'stockLevels' => function ($q) use ($warehouseId) {
                    if ($warehouseId) {
                        $q->where('warehouse_id', $warehouseId);
                    }
                    $q->with('warehouse:id,name,code');
                },
            ]);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $stockQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $stockQuery->where('category_id', $categoryId);
        }

        if ($request->filled('warehouse_id')) {
            $stockQuery->whereHas('stockLevels', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        $paginatedProducts = $stockQuery->orderBy('name')->paginate(15);
        $productIds = $paginatedProducts->pluck('id')->all();

        $incomingMap = empty($productIds) ? collect() : DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.team_id', $teamId)
            ->whereIn('purchase_orders.status', ['ordered', 'partially_received'])
            ->when($warehouseId, function ($q) use ($warehouseId) {
                $q->where('purchase_orders.warehouse_id', $warehouseId);
            })
            ->whereIn('purchase_order_items.product_id', $productIds)
            ->groupBy('purchase_order_items.product_id')
            ->select(
                'purchase_order_items.product_id',
                DB::raw('SUM(purchase_order_items.quantity - purchase_order_items.received_quantity) as incoming_quantity')
            )
            ->pluck('incoming_quantity', 'product_id');

        $stockItems = $paginatedProducts
            ->through(function ($product) use ($incomingMap) {
                $onHand = (float) $product->stockLevels->sum('quantity');
                $reserved = (float) $product->stockLevels->sum('reserved_quantity');
                $incoming = (float) ($incomingMap[$product->id] ?? 0);
                $available = max(0, $onHand - $reserved);
                $cost = (float) $product->cost_price;
                $valuation = $onHand * $cost;
                $alertLevel = (float) ($product->alert_quantity ?: 5);

                $stockStatus = 'in_stock';
                if ($onHand <= 0) {
                    $stockStatus = 'out_of_stock';
                } elseif ($onHand <= $alertLevel) {
                    $stockStatus = 'low_stock';
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                    'cost_price' => $cost,
                    'selling_price' => (float) $product->selling_price,
                    'on_hand' => $onHand,
                    'incoming' => $incoming,
                    'reserved' => $reserved,
                    'available' => $available,
                    'valuation' => $valuation,
                    'status' => $stockStatus,
                    'warehouses' => $product->stockLevels->map(fn ($sl) => [
                        'warehouse_id' => $sl->warehouse_id,
                        'warehouse_name' => $sl->warehouse?->name ?? 'Unknown',
                        'quantity' => (float) $sl->quantity,
                        'reserved' => (float) $sl->reserved_quantity,
                        'available' => max(0, (float) $sl->quantity - (float) $sl->reserved_quantity),
                    ]),
                ];
            })
            ->withQueryString();

        // 2. Stock Movements Query (Audit Ledger)
        $movementQuery = StockMovement::query()
            ->where('team_id', $teamId)
            ->with([
                'product:id,name,sku',
                'warehouse:id,name,code',
            ]);

        if ($request->filled('warehouse_id')) {
            $movementQuery->where('warehouse_id', $warehouseId);
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $movementQuery->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $movements = $movementQuery->orderByDesc('id')
            ->paginate(20, ['*'], 'movement_page')
            ->withQueryString();

        return Inertia::render('Inventory/Index', [
            'metrics' => $metrics,
            'stockItems' => $stockItems,
            'movements' => $movements,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'productsList' => $productsList,
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId ?? '',
                'category_id' => $categoryId ?? '',
                'status' => $status ?? '',
                'tab' => $activeTab,
            ],
        ]);
    }

    public function adjust(Request $request, StockService $stockService)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'new_quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        // Security check for tenant
        $product = Product::where('team_id', $teamId)->findOrFail($validated['product_id']);
        $warehouse = Warehouse::where('team_id', $teamId)->findOrFail($validated['warehouse_id']);

        try {
            $stockService->adjust(
                warehouseId: $warehouse->id,
                productId: $product->id,
                newPhysicalCount: (float) $validated['new_quantity'],
                reason: $validated['reason']
            );

            return redirect()->back()->with('success', "Stock for '{$product->name}' at '{$warehouse->name}' adjusted to {$validated['new_quantity']} units.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function transfer(Request $request, StockService $stockService)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $product = Product::where('team_id', $teamId)->findOrFail($validated['product_id']);
        $fromWh = Warehouse::where('team_id', $teamId)->findOrFail($validated['from_warehouse_id']);
        $toWh = Warehouse::where('team_id', $teamId)->findOrFail($validated['to_warehouse_id']);

        try {
            $stockService->transfer(
                fromWarehouseId: $fromWh->id,
                toWarehouseId: $toWh->id,
                productId: $product->id,
                quantity: (float) $validated['quantity'],
                reason: $validated['reason'] ?? 'Inter-warehouse inventory transfer'
            );

            return redirect()->back()->with('success', "Transferred {$validated['quantity']} units of '{$product->name}' from '{$fromWh->name}' to '{$toWh->name}'.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
