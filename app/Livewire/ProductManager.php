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
        $product = Product::findOrFail($id);
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
            ['id' => $this->product_id],
            [
                'name' => $this->name,
                'sku' => $this->sku,
                'barcode' => $this->barcode,
                'category_id' => $this->category_id ?: null,
                'brand_id' => $this->brand_id ?: null,
                'unit' => $this->unit,
                'cost_price' => $this->cost_price,
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
        $product = Product::findOrFail($id);
        
        // Check if there is physical stock
        $totalStock = StockLevel::where('product_id', $product->id)->sum('quantity');
        if ($totalStock > 0) {
            session()->flash('error', "Cannot delete product '{$product->name}' because {$totalStock} units are currently on hand in warehouses.");
            return;
        }

        $product->delete();
        session()->flash('message', 'Product deleted successfully.');
    }

    public function inspectStock($productId)
    {
        $this->selectedProduct = Product::findOrFail($productId);
        $warehouses = Warehouse::orderBy('name')->get();

        $breakdown = [];
        foreach ($warehouses as $wh) {
            $stock = StockLevel::where('warehouse_id', $wh->id)
                ->where('product_id', $productId)
                ->first();

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
        $categories = ProductCategory::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('livewire.product-manager', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'warehouses' => $warehouses,
        ])->layout('layouts.app');
    }
}
