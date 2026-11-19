<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\CatalogImportExportService;
use App\Services\SerialService;
use App\Services\StockService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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
            ->with([
                'category',
                'brand',
                'variants' => fn ($q) => $q->withSum('stockLevels as total_stock', 'quantity'),
            ])
            ->withSum('stockLevels as total_stock', 'quantity')
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $categoryId))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $brandId));

        if ($stockFilter === 'low_stock') {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.reorder_level');
        } elseif ($stockFilter === 'out_of_stock') {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= 0');
        }

        $products = $query->latest('id')->paginate(15)->withQueryString();

        $metrics = [
            'total_products' => Product::where('team_id', $teamId)->count(),
            'total_units' => (float) StockLevel::where('team_id', $teamId)->sum('quantity'),
            'low_stock_count' => Product::where('team_id', $teamId)
                ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.reorder_level')
                ->count(),
            'total_valuation' => (float) (StockLevel::where('stock_levels.team_id', $teamId)
                ->join('products', 'products.id', '=', 'stock_levels.product_id')
                ->selectRaw('SUM(stock_levels.quantity * products.cost_price) as total_val')
                ->value('total_val') ?: 0),
        ];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => fn () => ProductCategory::where('team_id', $teamId)->orderBy('name')->get(),
            'brands' => fn () => Brand::where('team_id', $teamId)->orderBy('name')->get(),
            'warehouses' => fn () => Warehouse::where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get(),
            'metrics' => $metrics,
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

    public function store(Request $request, StockService $stockService, SubscriptionService $subscriptionService, SerialService $serialService)
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $teamId = $user->current_team_id;

        if (!$subscriptionService->canAddProduct($team)) {
            return redirect()->back()->with('error', 'You have reached the maximum product limit for your subscription plan. Please upgrade your plan.');
        }

        if (is_string($request->input('variants'))) {
            $decoded = json_decode($request->input('variants'), true);
            if (is_array($decoded)) {
                $request->merge(['variants' => $decoded]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where('team_id', $teamId)],
            'barcode' => 'nullable|string|max:100',
            'category_id' => ['nullable', Rule::exists('product_categories', 'id')->where('team_id', $teamId)],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->where('team_id', $teamId)],
            'unit' => 'required|string|max:30',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'has_variants' => 'nullable|boolean',
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:3072',
            'description' => 'nullable|string|max:1000',
            'initial_warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
            'initial_stock_quantity' => 'nullable|numeric|min:0',
            'initial_serials' => 'nullable|string',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_if:has_variants,true|string|max:255',
            'variants.*.sku' => 'required_if:has_variants,true|string|max:100',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.attributes' => 'nullable|array',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
            'variants.*.unit_price' => 'nullable|numeric|min:0',
            'variants.*.selling_price' => 'nullable|numeric|min:0',
            'variants.*.is_active' => 'boolean',
            'variants.*.initial_stock_quantity' => 'nullable|numeric|min:0',
            'variants.*.initial_serials' => 'nullable|string',
        ]);

        $hasVariants = (bool) ($validated['has_variants'] ?? false);
        $variants = $hasVariants ? ($validated['variants'] ?? []) : [];

        if ($hasVariants && empty($variants)) {
            return redirect()->back()->withErrors(['variants' => 'Please configure at least one variant combination or disable variants.']);
        }

        if ($hasVariants && !empty($variants)) {
            $skus = array_filter(array_column($variants, 'sku'));
            if (count($skus) !== count(array_unique($skus))) {
                return redirect()->back()->withErrors(['variants' => 'Each product variant must have a unique SKU.']);
            }
            $existingVariant = ProductVariant::where('team_id', $teamId)->whereIn('sku', $skus)->first();
            if ($existingVariant) {
                return redirect()->back()->withErrors(['variants' => "Variant SKU '{$existingVariant->sku}' is already in use by another product."]);
            }
        }

        $validated['team_id'] = $teamId;
        $validated['unit_price'] = $validated['selling_price'];
        $validated['warranty_days'] = (int) ($validated['warranty_days'] ?? 0);
        $validated['has_variants'] = $hasVariants;
        $validated['is_serialized'] = $validated['is_serialized'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $initialWarehouseId = $validated['initial_warehouse_id'] ?? null;
        $initialStockQty = (float) ($validated['initial_stock_quantity'] ?? 0);
        $initialSerials = $validated['initial_serials'] ?? null;

        // If serial numbers are provided, enforce exact count matching opening quantity
        if ($validated['is_serialized'] && !$hasVariants && $initialWarehouseId && $initialStockQty > 0 && !empty(trim((string) $initialSerials))) {
            $serials = preg_split('/[\r\n,]+/', (string) $initialSerials, -1, PREG_SPLIT_NO_EMPTY);
            $serials = array_values(array_unique(array_filter(array_map('trim', $serials))));
            if (count($serials) !== (int) $initialStockQty) {
                return redirect()->back()->withErrors([
                    'initial_serials' => "You entered " . count($serials) . " serial numbers, but opening quantity is " . (int) $initialStockQty . ". Please provide exactly " . (int) $initialStockQty . " unique serial numbers."
                ]);
            }
        }

        if ($validated['is_serialized'] && $hasVariants && !empty($variants) && $initialWarehouseId) {
            foreach ($variants as $idx => $vData) {
                $vQty = (int) ($vData['initial_stock_quantity'] ?? 0);
                $vSerialStr = trim((string) ($vData['initial_serials'] ?? ''));
                if ($vQty > 0 && !empty($vSerialStr)) {
                    $vSerials = preg_split('/[\r\n,]+/', $vSerialStr, -1, PREG_SPLIT_NO_EMPTY);
                    $vSerials = array_values(array_unique(array_filter(array_map('trim', $vSerials))));
                    if (count($vSerials) !== $vQty) {
                        return redirect()->back()->withErrors([
                            'variants' => "Variant '{$vData['name']}' has opening quantity {$vQty}, but " . count($vSerials) . " serial numbers were provided. Please enter exactly {$vQty} unique serial numbers."
                        ]);
                    }
                }
            }
        }

        unset($validated['initial_warehouse_id'], $validated['initial_stock_quantity'], $validated['initial_serials'], $validated['variants']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store("products/{$teamId}", 'public');
        }

        $product = DB::transaction(function () use ($validated, $hasVariants, $variants, $initialWarehouseId, $initialStockQty, $initialSerials, $stockService, $serialService, $teamId) {
            $product = Product::create($validated);

            if ($hasVariants && !empty($variants)) {
                foreach ($variants as $vData) {
                    $costPrice = isset($vData['cost_price']) && $vData['cost_price'] !== '' ? (float) $vData['cost_price'] : (float) $product->cost_price;
                    $sellingPrice = isset($vData['selling_price']) && $vData['selling_price'] !== '' 
                        ? (float) $vData['selling_price'] 
                        : (isset($vData['unit_price']) ? (float) $vData['unit_price'] : (float) $product->unit_price);

                    $variant = ProductVariant::create([
                        'team_id' => $teamId,
                        'product_id' => $product->id,
                        'name' => $vData['name'],
                        'sku' => $vData['sku'],
                        'barcode' => !empty($vData['barcode']) ? $vData['barcode'] : null,
                        'attributes' => $vData['attributes'] ?? null,
                        'cost_price' => $costPrice,
                        'unit_price' => $sellingPrice,
                        'is_active' => $vData['is_active'] ?? true,
                    ]);

                    $vQty = (float) ($vData['initial_stock_quantity'] ?? 0);
                    if ($initialWarehouseId && $vQty > 0) {
                        $stockService->stockIn(
                            warehouseId: $initialWarehouseId,
                            productId: $product->id,
                            quantity: $vQty,
                            unitCost: $costPrice,
                            variantId: $variant->id,
                            referenceType: ProductVariant::class,
                            referenceId: $variant->id,
                            reason: "Initial Variant Opening Stock ({$variant->name})"
                        );

                        if ($product->is_serialized && !empty($vData['initial_serials'])) {
                            $vSerials = preg_split('/[\r\n,]+/', (string) $vData['initial_serials'], -1, PREG_SPLIT_NO_EMPTY);
                            $vSerials = array_values(array_unique(array_filter(array_map('trim', $vSerials))));
                            if (!empty($vSerials)) {
                                $serialService->ingestSerialsForInitialStock($product, (int) $initialWarehouseId, $vSerials, $variant->id);
                            }
                        }
                    }
                }
            } else {
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

                    if ($product->is_serialized && !empty($initialSerials)) {
                        $serials = preg_split('/[\r\n,]+/', (string) $initialSerials, -1, PREG_SPLIT_NO_EMPTY);
                        $serials = array_values(array_unique(array_filter(array_map('trim', $serials))));
                        if (!empty($serials)) {
                            $serialService->ingestSerialsForInitialStock($product, (int) $initialWarehouseId, $serials);
                        }
                    }
                }
            }

            return $product;
        });

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' created successfully.");
    }

    public function edit(Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $product->load(['variants' => fn ($q) => $q->withSum('stockLevels as total_stock', 'quantity')]);

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

        if (is_string($request->input('variants'))) {
            $decoded = json_decode($request->input('variants'), true);
            if (is_array($decoded)) {
                $request->merge(['variants' => $decoded]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)->where('team_id', $teamId)],
            'barcode' => 'nullable|string|max:100',
            'category_id' => ['nullable', Rule::exists('product_categories', 'id')->where('team_id', $teamId)],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->where('team_id', $teamId)],
            'unit' => 'required|string|max:30',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'has_variants' => 'nullable|boolean',
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:3072',
            'remove_image' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required_if:has_variants,true|string|max:255',
            'variants.*.sku' => 'required_if:has_variants,true|string|max:100',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.attributes' => 'nullable|array',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
            'variants.*.unit_price' => 'nullable|numeric|min:0',
            'variants.*.selling_price' => 'nullable|numeric|min:0',
            'variants.*.is_active' => 'boolean',
        ]);

        $hasVariants = (bool) ($validated['has_variants'] ?? false);
        $variants = $hasVariants ? ($validated['variants'] ?? []) : [];

        if ($hasVariants && empty($variants)) {
            return redirect()->back()->withErrors(['variants' => 'Please configure at least one variant combination or disable variants.']);
        }

        if ($hasVariants && !empty($variants)) {
            $skus = array_filter(array_column($variants, 'sku'));
            if (count($skus) !== count(array_unique($skus))) {
                return redirect()->back()->withErrors(['variants' => 'Each product variant must have a unique SKU.']);
            }
            $existingVariantIds = $product->variants()->pluck('id')->toArray();
            $existingOther = ProductVariant::where('team_id', $teamId)
                ->whereNotIn('id', $existingVariantIds)
                ->whereIn('sku', $skus)
                ->first();
            if ($existingOther) {
                return redirect()->back()->withErrors(['variants' => "Variant SKU '{$existingOther->sku}' is already in use by another product."]);
            }
        }

        $validated['unit_price'] = $validated['selling_price'];
        $validated['warranty_days'] = (int) ($validated['warranty_days'] ?? 0);
        $validated['has_variants'] = $hasVariants;
        $validated['is_serialized'] = $validated['is_serialized'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        if ($request->boolean('remove_image')) {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store("products/{$teamId}", 'public');
        } else {
            unset($validated['image']);
        }

        unset($validated['remove_image'], $validated['variants']);

        DB::transaction(function () use ($product, $validated, $hasVariants, $variants, $teamId) {
            $product->update($validated);

            if ($hasVariants) {
                $savedIds = [];
                foreach ($variants as $vData) {
                    $costPrice = isset($vData['cost_price']) && $vData['cost_price'] !== '' ? (float) $vData['cost_price'] : (float) $product->cost_price;
                    $sellingPrice = isset($vData['selling_price']) && $vData['selling_price'] !== '' 
                        ? (float) $vData['selling_price'] 
                        : (isset($vData['unit_price']) ? (float) $vData['unit_price'] : (float) $product->unit_price);

                    if (!empty($vData['id'])) {
                        $existing = ProductVariant::where('product_id', $product->id)->where('id', $vData['id'])->first();
                        if ($existing) {
                            $existing->update([
                                'name' => $vData['name'],
                                'sku' => $vData['sku'],
                                'barcode' => !empty($vData['barcode']) ? $vData['barcode'] : null,
                                'attributes' => $vData['attributes'] ?? null,
                                'cost_price' => $costPrice,
                                'unit_price' => $sellingPrice,
                                'is_active' => $vData['is_active'] ?? true,
                            ]);
                            $savedIds[] = $existing->id;
                            continue;
                        }
                    }

                    $newVariant = ProductVariant::create([
                        'team_id' => $teamId,
                        'product_id' => $product->id,
                        'name' => $vData['name'],
                        'sku' => $vData['sku'],
                        'barcode' => !empty($vData['barcode']) ? $vData['barcode'] : null,
                        'attributes' => $vData['attributes'] ?? null,
                        'cost_price' => $costPrice,
                        'unit_price' => $sellingPrice,
                        'is_active' => $vData['is_active'] ?? true,
                    ]);
                    $savedIds[] = $newVariant->id;
                }

                // Soft-delete removed variants
                ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $savedIds)
                    ->delete();
            } else {
                // If previously had variants, soft-delete them
                $product->variants()->delete();
            }
        });

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

    public function lookupBarcode(Request $request)
    {
        $code = trim($request->input('barcode', ''));
        if (!$code) {
            return response()->json(['found' => false]);
        }
        $teamId = Auth::user()->current_team_id ?? Auth::user()->currentTeam?->id;
        $excludeId = $request->input('exclude_id');

        $product = Product::where('team_id', $teamId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->with(['category', 'brand'])
            ->first();

        if ($product) {
            return response()->json([
                'found' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                    'unit_price' => (float) $product->unit_price,
                    'cost_price' => (float) $product->cost_price,
                ],
            ]);
        }

        $variant = ProductVariant::where('team_id', $teamId)
            ->when($excludeId, fn($q) => $q->where('product_id', '!=', $excludeId))
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->with('product')
            ->first();

        if ($variant) {
            return response()->json([
                'found' => true,
                'product' => [
                    'id' => $variant->product_id,
                    'name' => ($variant->product?->name ?? 'Product') . " ({$variant->name})",
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'category' => $variant->product?->category?->name,
                    'brand' => $variant->product?->brand?->name,
                    'unit_price' => (float) $variant->unit_price,
                    'cost_price' => (float) $variant->cost_price,
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }

    public function inspectStock(Product $product)
    {
        $teamId = Auth::user()->current_team_id;
        abort_if($product->team_id !== $teamId, 403);

        $levels = StockLevel::with(['warehouse', 'variant'])
            ->where('product_id', $product->id)
            ->where('team_id', $teamId)
            ->get()
            ->map(function ($level) use ($product) {
                $onHand = (float) $level->quantity;
                $reserved = (float) $level->reserved_quantity;
                $available = max(0, $onHand - $reserved);
                $cost = (float) ($level->variant?->cost_price ?: $product->cost_price);
                $warehouseName = $level->warehouse?->name ?? 'Unknown';
                if ($level->variant) {
                    $warehouseName .= " — " . $level->variant->name;
                }

                return [
                    'warehouse_name' => $warehouseName,
                    'variant_name' => $level->variant?->name,
                    'variant_sku' => $level->variant?->sku,
                    'quantity_on_hand' => $onHand,
                    'quantity_reserved' => $reserved,
                    'quantity_available' => $available,
                    'valuation' => $onHand * $cost,
                ];
            });

        $barcodeValue = $product->barcode ?: $product->sku;
        $barcodeSvg = null;
        if ($barcodeValue) {
            try {
                $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                $barcodeSvg = $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128, 2, 45);
            } catch (\Throwable $e) {
                $barcodeSvg = null;
            }
        }

        return response()->json([
            'product' => $product,
            'levels' => $levels,
            'barcode_svg' => $barcodeSvg,
            'barcode_value' => $barcodeValue,
        ]);
    }

    public function export(Request $request, CatalogImportExportService $service)
    {
        $teamId = Auth::user()->current_team_id;
        $format = strtolower($request->query('format', 'xlsx'));

        if ($format === 'csv') {
            return $service->exportCsv($teamId);
        }

        $filename = 'products-' . date('Y-m-d-His') . '.xlsx';

        return Excel::download(new ProductsExport($teamId), $filename);
    }

    public function import(Request $request, CatalogImportExportService $service)
    {
        $teamId = Auth::user()->current_team_id;

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('team_id', $teamId)],
        ]);

        $file = $request->file('file');
        $warehouseId = $request->input('warehouse_id');

        $result = $service->importCsv($file->getRealPath(), $teamId, $warehouseId);

        return redirect()->back()->with('success', "Import complete: {$result['imported']} imported, {$result['updated']} updated, {$result['failed']} failed.");
    }
}
