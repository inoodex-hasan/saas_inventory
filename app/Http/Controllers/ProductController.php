<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\CatalogImportExportService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $search = $request->input('search', '');
        $categoryId = $request->input('category_id');
        $brandId = $request->input('brand_id');
        $stockFilter = $request->input('stock', 'all');

        $query = Product::where('team_id', $teamId)
            ->with(['category', 'brand'])
            ->withSum('stockLevels as total_stock', 'quantity')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn ($q, $cat) => $q->where('category_id', $cat))
            ->when($brandId, fn ($q, $brand) => $q->where('brand_id', $brand));

        if ($stockFilter === 'low_stock') {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.reorder_level');
        } elseif ($stockFilter === 'out_of_stock') {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= 0');
        }

        $products = $query->latest('id')->paginate(15)->withQueryString();

        $categories = ProductCategory::where('team_id', $teamId)->orderBy('name')->get();
        $brands = Brand::where('team_id', $teamId)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'stock' => $stockFilter,
            ],
        ]);
    }

    public function create()
    {
        $teamId = Auth::user()->current_team_id;

        $categories = ProductCategory::where('team_id', $teamId)->orderBy('name')->get();
        $brands = Brand::where('team_id', $teamId)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        $defaultWarehouse = Warehouse::where('team_id', $teamId)->where('is_default', true)->first()
            ?? Warehouse::where('team_id', $teamId)->first();

        $initialSku = 'PROD-' . strtoupper(Str::random(5));

        return Inertia::render('Products/Create', [
            'categories' => $categories,
            'brands' => $brands,
            'warehouses' => $warehouses,
            'defaultWarehouseId' => $defaultWarehouse?->id,
            'initialSku' => $initialSku,
        ]);
    }

    public function store(Request $request, StockService $stockService)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,NULL,id,team_id,' . $teamId,
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:product_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit' => 'required|string|max:30',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'initial_warehouse_id' => 'nullable|exists:warehouses,id',
            'initial_stock_quantity' => 'nullable|numeric|min:0',
        ]);

        $validated['team_id'] = $teamId;
        $validated['unit_price'] = $validated['selling_price'];
        $validated['warranty_days'] = (int) ($validated['warranty_days'] ?? 0);
        $validated['is_serialized'] = $validated['is_serialized'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $initialWarehouseId = $validated['initial_warehouse_id'] ?? null;
        $initialStockQty = (float) ($validated['initial_stock_quantity'] ?? 0);
        unset($validated['initial_warehouse_id'], $validated['initial_stock_quantity']);

        $product = Product::create($validated);

        if ($initialWarehouseId && $initialStockQty > 0) {
            $stockService->stockIn(
                warehouseId: $initialWarehouseId,
                productId: $product->id,
                quantity: $initialStockQty,
                unitCost: (float) $product->cost_price,
                referenceType: Product::class,
                referenceId: $product->id,
                reason: "Initial Opening Stock Allocation"
            );
        }

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' created successfully.");
    }

    public function edit(Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $categories = ProductCategory::where('team_id', $teamId)->orderBy('name')->get();
        $brands = Brand::where('team_id', $teamId)->orderBy('name')->get();
        $warehouses = Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id . ',id,team_id,' . $teamId,
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:product_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit' => 'required|string|max:30',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['unit_price'] = $validated['selling_price'];
        $validated['warranty_days'] = (int) ($validated['warranty_days'] ?? 0);
        $validated['is_serialized'] = $validated['is_serialized'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $product->update($validated);

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $hasStock = StockLevel::where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->exists();

        if ($hasStock) {
            return redirect()->back()->with('error', "Cannot delete '{$product->name}' because active stock exists in one or more warehouses.");
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' deleted successfully.");
    }

    public function inspectStock(Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $levels = StockLevel::with('warehouse')
            ->where('product_id', $product->id)
            ->where('team_id', $teamId)
            ->get()
            ->map(function ($level) use ($product) {
                $onHand = (float) $level->quantity;
                $reserved = (float) $level->reserved_quantity;
                $available = max(0, $onHand - $reserved);
                $cost = (float) $product->cost_price;

                return [
                    'warehouse_name' => $level->warehouse?->name ?? 'Unknown',
                    'quantity_on_hand' => $onHand,
                    'quantity_reserved' => $reserved,
                    'quantity_available' => $available,
                    'valuation' => $onHand * $cost,
                ];
            });

        return response()->json([
            'product' => $product,
            'levels' => $levels,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:500',
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'cat-' . Str::random(5);
        $slug = $baseSlug;
        $counter = 1;
        while (ProductCategory::where('team_id', $teamId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $category = ProductCategory::create([
            'team_id' => $teamId,
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Category '{$category->name}' created.");
    }

    public function updateCategory(Request $request, ProductCategory $category)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($category->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:500',
        ]);

        $category->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Category '{$category->name}' updated.");
    }

    public function destroyCategory(ProductCategory $category)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($category->team_id !== $teamId, 403);

        if ($category->products()->exists()) {
            return redirect()->back()->with('error', "Cannot delete category '{$category->name}' because products are assigned to it.");
        }

        $category->delete();

        return redirect()->back()->with('success', "Category '{$category->name}' deleted.");
    }

    public function storeBrand(Request $request)
    {
        $teamId = Auth::user()->current_team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'brand-' . Str::random(5);
        $slug = $baseSlug;
        $counter = 1;
        while (Brand::where('team_id', $teamId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $brand = Brand::create([
            'team_id' => $teamId,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Brand '{$brand->name}' created.");
    }

    public function updateBrand(Request $request, Brand $brand)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($brand->team_id !== $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $brand->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Brand '{$brand->name}' updated.");
    }

    public function destroyBrand(Brand $brand)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($brand->team_id !== $teamId, 403);

        if ($brand->products()->exists()) {
            return redirect()->back()->with('error', "Cannot delete brand '{$brand->name}' because products are assigned to it.");
        }

        $brand->delete();

        return redirect()->back()->with('success', "Brand '{$brand->name}' deleted.");
    }

    public function export(Request $request, CatalogImportExportService $service)
    {
        $teamId = Auth::user()->current_team_id;
        $filename = 'catalog-' . date('Y-m-d-His') . '.csv';
        $path = $service->exportCsv($teamId);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function import(Request $request, CatalogImportExportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $teamId = Auth::user()->current_team_id;
        $file = $request->file('file');
        $warehouseId = $request->input('warehouse_id');

        $result = $service->importCsv($file->getRealPath(), $teamId, $warehouseId);

        return redirect()->back()->with('success', "Import complete: {$result['imported']} imported, {$result['updated']} updated, {$result['failed']} failed.");
    }
}
