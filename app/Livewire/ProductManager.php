<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\CatalogImportExportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filter_category = '';
    public $filter_brand = '';
    public $filter_stock = ''; // all, low_stock, out_of_stock

    public $isModalOpen = false;
    public $product_id = null;

    // Import Modal State
    public bool $isImportModalOpen = false;
    public $importFile = null;
    public ?int $initialWarehouseId = null;
    public ?array $importResults = null;

    // Category & Brand Management State
    public bool $isCatBrandModalOpen = false;
    public string $catBrandActiveTab = 'categories'; // 'categories' | 'brands'
    public string $catBrandSearch = '';

    // Category Form State
    public ?int $editing_category_id = null;
    public string $category_name = '';
    public ?int $category_parent_id = null;
    public string $category_description = '';
    public bool $showQuickAddCategory = false;
    public string $quick_category_name = '';

    // Brand Form State
    public ?int $editing_brand_id = null;
    public string $brand_name = '';
    public string $brand_description = '';
    public bool $showQuickAddBrand = false;
    public string $quick_brand_name = '';

    // Form fields
    public $name = '';
    public $sku = '';
    public $barcode = '';
    public $category_id = null;
    public $brand_id = null;
    public $unit = 'pcs';
    public $cost_price = 0.00;
    public $selling_price = 0.00;
    public $reorder_level = 10;
    public $reorder_quantity = 50;
    public $description = '';
    public $is_active = true;

    // Stock breakdown modal
    public $selectedProduct = null;
    public $productWarehouseStock = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'sku' => 'required|string|max:100',
        'barcode' => 'nullable|string|max:100',
        'category_id' => 'nullable|exists:product_categories,id',
        'brand_id' => 'nullable|exists:brands,id',
        'unit' => 'required|string|max:30',
        'cost_price' => 'required|numeric|min:0',
        'selling_price' => 'required|numeric|min:0',
        'reorder_level' => 'required|integer|min:0',
        'reorder_quantity' => 'required|integer|min:0',
        'description' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
    }

    public function updatingFilterBrand()
    {
        $this->resetPage();
    }

    public function updatingFilterStock()
    {
        $this->resetPage();
    }

    public function generateSku()
    {
        $prefix = 'SKU';
        if ($this->name) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->name), 0, 4));
        }
        $this->sku = $prefix . '-' . strtoupper(Str::random(5));
    }

    public function create()
    {
        $this->resetForm();
        $this->generateSku();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $teamId = auth()->user()->current_team_id;
        $product = Product::where('team_id', $teamId)->findOrFail($id);
        $this->product_id = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode;
        $this->category_id = $product->category_id;
        $this->brand_id = $product->brand_id;
        $this->unit = $product->unit ?? 'pcs';
        $this->cost_price = $product->cost_price;
        $this->selling_price = $product->selling_price;
        $this->reorder_level = $product->reorder_level;
        $this->reorder_quantity = $product->reorder_quantity;
        $this->description = $product->description;
        $this->is_active = (bool) $product->is_active;

        $this->isModalOpen = true;
    }

    public function save()
    {
        $teamId = auth()->user()->current_team_id;

        // Ensure unique SKU per team
        $this->validate([
            'sku' => 'required|string|max:100|unique:products,sku,' . $this->product_id . ',id,team_id,' . $teamId,
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:30',
        ]);

        Product::updateOrCreate(
            ['id' => $this->product_id, 'team_id' => $teamId],
            [
                'name' => $this->name,
                'sku' => $this->sku,
                'barcode' => $this->barcode,
                'category_id' => $this->category_id ?: null,
                'brand_id' => $this->brand_id ?: null,
                'unit' => $this->unit,
                'cost_price' => $this->cost_price,
                'unit_price' => $this->selling_price,
                'selling_price' => $this->selling_price,
                'reorder_level' => $this->reorder_level,
                'reorder_quantity' => $this->reorder_quantity,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]
        );

        session()->flash('message', $this->product_id ? 'Product updated successfully.' : 'Product created successfully.');
        $this->closeModal();
    }

    public function delete($id)
    {
        $teamId = auth()->user()->current_team_id;
        $product = Product::where('team_id', $teamId)->findOrFail($id);
        
        // Check if there is physical stock
        $totalStock = StockLevel::where('team_id', $teamId)->where('product_id', $product->id)->sum('quantity');
        if ($totalStock > 0) {
            session()->flash('error', "Cannot delete product '{$product->name}' because {$totalStock} units are currently on hand in warehouses.");
            return;
        }

        $product->delete();
        session()->flash('message', 'Product deleted successfully.');
    }

    public function inspectStock($productId)
    {
        $teamId = auth()->user()->current_team_id;
        $this->selectedProduct = Product::where('team_id', $teamId)->findOrFail($productId);
        $warehouses = Warehouse::where('team_id', $teamId)->orderBy('name')->get();
        $stockLevels = StockLevel::where('team_id', $teamId)
            ->where('product_id', $productId)
            ->get()
            ->keyBy('warehouse_id');

        $breakdown = [];
        foreach ($warehouses as $wh) {
            $stock = $stockLevels->get($wh->id);

            $onHand = $stock ? (float) $stock->quantity : 0;
            $reserved = $stock ? (float) $stock->reserved_quantity : 0;
            $available = max(0, $onHand - $reserved);

            $breakdown[] = [
                'warehouse_name' => $wh->name,
                'warehouse_code' => $wh->code,
                'is_default' => $wh->is_default,
                'on_hand' => $onHand,
                'reserved' => $reserved,
                'available' => $available,
                'value' => $onHand * (float) $this->selectedProduct->cost_price,
            ];
        }

        $this->productWarehouseStock = $breakdown;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->product_id = null;
        $this->name = '';
        $this->sku = '';
        $this->barcode = '';
        $this->category_id = null;
        $this->brand_id = null;
        $this->unit = 'pcs';
        $this->cost_price = 0.00;
        $this->selling_price = 0.00;
        $this->reorder_level = 10;
        $this->reorder_quantity = 50;
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function openImportModal()
    {
        $this->importFile = null;
        $this->initialWarehouseId = null;
        $this->importResults = null;
        $this->isImportModalOpen = true;
    }

    public function closeImportModal()
    {
        $this->isImportModalOpen = false;
        $this->importFile = null;
        $this->importResults = null;
    }

    public function downloadSampleTemplate(CatalogImportExportService $service)
    {
        return $service->generateSampleCsv();
    }

    public function exportCsv(CatalogImportExportService $service)
    {
        $teamId = Auth::user()->currentTeam->id;
        return $service->exportCsv($teamId);
    }

    public function importCsv(CatalogImportExportService $service)
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:10240',
            'initialWarehouseId' => 'nullable|exists:warehouses,id',
        ]);

        $team = Auth::user()->currentTeam;
        $userId = Auth::id();

        $path = $this->importFile->getRealPath();
        $this->importResults = $service->importCsv($path, $team->id, $this->initialWarehouseId, $userId);

        if ($this->importResults['created'] > 0 || $this->importResults['updated'] > 0) {
            session()->flash('success', "Import Complete! {$this->importResults['created']} products created, {$this->importResults['updated']} updated.");
        }
    }

    // ==========================================
    // Category & Brand Management Actions
    // ==========================================

    public function openCatBrandModal(string $tab = 'categories')
    {
        $this->catBrandActiveTab = $tab;
        $this->catBrandSearch = '';
        $this->resetCategoryForm();
        $this->resetBrandForm();
        $this->isCatBrandModalOpen = true;
    }

    public function closeCatBrandModal()
    {
        $this->isCatBrandModalOpen = false;
        $this->resetCategoryForm();
        $this->resetBrandForm();
    }

    public function switchCatBrandTab(string $tab)
    {
        $this->catBrandActiveTab = $tab;
        $this->resetCategoryForm();
        $this->resetBrandForm();
    }

    public function resetCategoryForm()
    {
        $this->editing_category_id = null;
        $this->category_name = '';
        $this->category_parent_id = null;
        $this->category_description = '';
        $this->resetValidation(['category_name', 'category_parent_id', 'category_description']);
    }

    public function editCategory(int $id)
    {
        $teamId = auth()->user()->current_team_id;
        $category = ProductCategory::where('team_id', $teamId)->findOrFail($id);
        $this->editing_category_id = $category->id;
        $this->category_name = $category->name;
        $this->category_parent_id = $category->parent_id;
        $this->category_description = $category->description ?? '';
    }

    public function saveCategory()
    {
        $teamId = auth()->user()->current_team_id;
        $this->validate([
            'category_name' => 'required|string|max:255',
            'category_parent_id' => 'nullable|exists:product_categories,id',
            'category_description' => 'nullable|string|max:1000',
        ]);

        $baseSlug = Str::slug($this->category_name);
        $slug = $baseSlug ?: 'cat-' . Str::random(5);
        $counter = 1;
        while (ProductCategory::where('team_id', $teamId)->where('slug', $slug)->where('id', '!=', $this->editing_category_id)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        ProductCategory::updateOrCreate(
            ['id' => $this->editing_category_id, 'team_id' => $teamId],
            [
                'name' => $this->category_name,
                'slug' => $slug,
                'parent_id' => $this->category_parent_id ?: null,
                'description' => $this->category_description ?: null,
            ]
        );

        session()->flash('cat_brand_message', $this->editing_category_id ? 'Category updated successfully.' : 'Category created successfully.');
        $this->resetCategoryForm();
    }

    public function deleteCategory(int $id)
    {
        $teamId = auth()->user()->current_team_id;
        $category = ProductCategory::where('team_id', $teamId)->findOrFail($id);

        $productCount = $category->products()->count();
        if ($productCount > 0) {
            session()->flash('cat_brand_error', "Cannot delete category '{$category->name}' because {$productCount} product(s) are assigned to it.");
            return;
        }

        $childCount = $category->children()->count();
        if ($childCount > 0) {
            session()->flash('cat_brand_error', "Cannot delete category '{$category->name}' because it has {$childCount} sub-category(ies).");
            return;
        }

        $category->delete();
        session()->flash('cat_brand_message', "Category '{$category->name}' deleted successfully.");
        if ($this->editing_category_id === $id) {
            $this->resetCategoryForm();
        }
    }

    public function toggleQuickAddCategory()
    {
        $this->showQuickAddCategory = !$this->showQuickAddCategory;
        $this->quick_category_name = '';
    }

    public function quickAddCategory()
    {
        $teamId = auth()->user()->current_team_id;
        $this->validate([
            'quick_category_name' => 'required|string|max:255',
        ]);

        $baseSlug = Str::slug($this->quick_category_name);
        $slug = $baseSlug ?: 'cat-' . Str::random(5);
        $counter = 1;
        while (ProductCategory::where('team_id', $teamId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $category = ProductCategory::create([
            'team_id' => $teamId,
            'name' => $this->quick_category_name,
            'slug' => $slug,
        ]);

        $this->category_id = $category->id;
        $this->quick_category_name = '';
        $this->showQuickAddCategory = false;
        session()->flash('product_form_message', "Category '{$category->name}' created and selected!");
    }

    public function resetBrandForm()
    {
        $this->editing_brand_id = null;
        $this->brand_name = '';
        $this->brand_description = '';
        $this->resetValidation(['brand_name', 'brand_description']);
    }

    public function editBrand(int $id)
    {
        $teamId = auth()->user()->current_team_id;
        $brand = Brand::where('team_id', $teamId)->findOrFail($id);
        $this->editing_brand_id = $brand->id;
        $this->brand_name = $brand->name;
        $this->brand_description = $brand->description ?? '';
    }

    public function saveBrand()
    {
        $teamId = auth()->user()->current_team_id;
        $this->validate([
            'brand_name' => 'required|string|max:255',
            'brand_description' => 'nullable|string|max:1000',
        ]);

        $baseSlug = Str::slug($this->brand_name);
        $slug = $baseSlug ?: 'brand-' . Str::random(5);
        $counter = 1;
        while (Brand::where('team_id', $teamId)->where('slug', $slug)->where('id', '!=', $this->editing_brand_id)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        Brand::updateOrCreate(
            ['id' => $this->editing_brand_id, 'team_id' => $teamId],
            [
                'name' => $this->brand_name,
                'slug' => $slug,
                'description' => $this->brand_description ?: null,
            ]
        );

        session()->flash('cat_brand_message', $this->editing_brand_id ? 'Brand updated successfully.' : 'Brand created successfully.');
        $this->resetBrandForm();
    }

    public function deleteBrand(int $id)
    {
        $teamId = auth()->user()->current_team_id;
        $brand = Brand::where('team_id', $teamId)->findOrFail($id);

        $productCount = $brand->products()->count();
        if ($productCount > 0) {
            session()->flash('cat_brand_error', "Cannot delete brand '{$brand->name}' because {$productCount} product(s) are assigned to it.");
            return;
        }

        $brand->delete();
        session()->flash('cat_brand_message', "Brand '{$brand->name}' deleted successfully.");
        if ($this->editing_brand_id === $id) {
            $this->resetBrandForm();
        }
    }

    public function toggleQuickAddBrand()
    {
        $this->showQuickAddBrand = !$this->showQuickAddBrand;
        $this->quick_brand_name = '';
    }

    public function quickAddBrand()
    {
        $teamId = auth()->user()->current_team_id;
        $this->validate([
            'quick_brand_name' => 'required|string|max:255',
        ]);

        $baseSlug = Str::slug($this->quick_brand_name);
        $slug = $baseSlug ?: 'brand-' . Str::random(5);
        $counter = 1;
        while (Brand::where('team_id', $teamId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $brand = Brand::create([
            'team_id' => $teamId,
            'name' => $this->quick_brand_name,
            'slug' => $slug,
        ]);

        $this->brand_id = $brand->id;
        $this->quick_brand_name = '';
        $this->showQuickAddBrand = false;
        session()->flash('product_form_message', "Brand '{$brand->name}' created and selected!");
    }

    public function render()
    {
        $query = Product::with(['category', 'brand', 'stockLevels'])
            ->withSum('stockLevels as total_on_hand', 'quantity')
            ->withSum('stockLevels as total_reserved', 'reserved_quantity');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%");
            });
        }

        if ($this->filter_category) {
            $query->where('category_id', $this->filter_category);
        }

        if ($this->filter_brand) {
            $query->where('brand_id', $this->filter_brand);
        }

        if ($this->filter_stock === 'out_of_stock') {
            $query->having('total_on_hand', '<=', 0);
        } elseif ($this->filter_stock === 'low_stock') {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM stock_levels WHERE stock_levels.product_id = products.id AND stock_levels.team_id = products.team_id) <= products.reorder_level');
        }

        $products = $query->latest()->paginate(15);

        // Filterable lists for Management Modal
        $categoriesQuery = ProductCategory::withCount('products')->orderBy('name');
        if ($this->catBrandSearch && $this->catBrandActiveTab === 'categories') {
            $categoriesQuery->where('name', 'like', "%{$this->catBrandSearch}%");
        }
        $categories = $categoriesQuery->get();

        $brandsQuery = Brand::withCount('products')->orderBy('name');
        if ($this->catBrandSearch && $this->catBrandActiveTab === 'brands') {
            $brandsQuery->where('name', 'like', "%{$this->catBrandSearch}%");
        }
        $brands = $brandsQuery->get();

        // Complete lists for dropdown selectors
        $allCategories = ProductCategory::orderBy('name')->get();
        $allBrands = Brand::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('livewire.product-manager', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'allCategories' => $allCategories,
            'allBrands' => $allBrands,
            'warehouses' => $warehouses,
        ])->layout('layouts.app');
    }
}
