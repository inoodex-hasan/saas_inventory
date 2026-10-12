<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header & Primary Action -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Products & Catalog</h1>
                <p class="text-sm text-slate-500 mt-1">Manage SKUs, barcodes, multi-warehouse stock levels, reorder alerts, and pricing.</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="openCatBrandModal('categories')" 
                        class="inline-flex items-center justify-center px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-xl shadow-xs transition gap-1.5 border border-slate-200">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Categories & Brands</span>
                </button>

                <button wire:click="exportCsv" 
                        class="inline-flex items-center justify-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-xl shadow-xs transition gap-1.5 border border-slate-200">
                    <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Export CSV</span>
                </button>

                <button wire:click="openImportModal" 
                        class="inline-flex items-center justify-center px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs rounded-xl shadow-xs transition gap-1.5">
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span>Import Catalog</span>
                </button>

                <button wire:click="create" 
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm hover:shadow transition gap-1.5 focus:ring-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Product</span>
                </button>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                
                <!-- Search bar -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" 
                           type="text" 
                           placeholder="Search product name, SKU, barcode..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                </div>

                <!-- Category Filter -->
                <div>
                    <select wire:model.live="filter_category" class="w-full py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Brand Filter -->
                <div>
                    <select wire:model.live="filter_brand" class="w-full py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Brands</option>
                        @foreach($allBrands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Stock Level Filter -->
                <div>
                    <select wire:model.live="filter_stock" class="w-full py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Stock Status</option>
                        <option value="low_stock">⚠️ Low Stock Alerts</option>
                        <option value="out_of_stock">⛔ Out of Stock</option>
                    </select>
                </div>

            </div>
        </div>

        <!-- Flash Notices -->
        @if (session()->has('message'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Products Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-4">Item & Code</th>
                            <th class="py-3.5 px-4">Category & Brand</th>
                            <th class="py-3.5 px-4 text-right">Cost Price</th>
                            <th class="py-3.5 px-4 text-right">Selling Price</th>
                            <th class="py-3.5 px-4 text-right">Physical On Hand</th>
                            <th class="py-3.5 px-4 text-right">Available Qty</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            @php
                                $onHand = (float) ($product->total_on_hand ?? 0);
                                $reserved = (float) ($product->total_reserved ?? 0);
                                $available = max(0, $onHand - $reserved);
                                $isLowStock = $onHand <= $product->reorder_level;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900 text-sm">{{ $product->name }}</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="font-mono text-[11px] text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $product->sku }}</span>
                                        @if($product->barcode)
                                            <span class="font-mono text-[11px] text-slate-400">{{ $product->barcode }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-800 font-medium">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ $product->brand->name ?? 'No brand' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-600 font-medium">
                                    ${{ number_format($product->cost_price, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900">
                                    ${{ number_format($product->selling_price, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-slate-700">
                                    {{ number_format($onHand) }} <span class="text-slate-400 text-[11px]">{{ $product->unit }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button wire:click="inspectStock({{ $product->id }})" 
                                            class="inline-flex items-center gap-1 font-bold px-2 py-0.5 rounded-md hover:bg-slate-100 transition {{ $available <= 0 ? 'text-rose-600 bg-rose-50' : ($isLowStock ? 'text-amber-600 bg-amber-50' : 'text-emerald-700 bg-emerald-50') }}">
                                        <span>{{ number_format($available) }} {{ $product->unit }}</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($available <= 0)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">Out of Stock</span>
                                    @elseif($isLowStock)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">Low Stock</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">In Stock</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="inspectStock({{ $product->id }})" title="View Stock Distribution" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </button>
                                        <button wire:click="edit({{ $product->id }})" title="Edit" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="delete({{ $product->id }})" wire:confirm="Are you sure you want to delete this product?" title="Delete" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-semibold text-slate-800">No Products Found</h3>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Create your first catalog item with auto-generated SKU to begin managing inventory.</p>
                                    <button wire:click="create" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Product
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

        <!-- Create / Edit Product Modal -->
        @if($isModalOpen)
            <div x-data="{ open: true }" 
                 x-show="open" 
                 x-cloak
                 @keydown.escape.window="open = false; $wire.closeModal()"
                 class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                         @click="open = false; $wire.closeModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200"
                         x-show="open"
                         x-transition:enter="ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <form wire:submit.prevent="save">
                            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900">
                                    {{ $product_id ? 'Edit Product Item' : 'New Product Item' }}
                                </h3>
                                <button type="button" @click="open = false; $wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                @if (session()->has('product_form_message'))
                                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                                        <span>✅</span>
                                        <span class="font-semibold">{{ session('product_form_message') }}</span>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
                                    <input type="text" wire:model="name" placeholder="e.g. Wireless Ergonomic Keyboard" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                    @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-xs font-semibold text-slate-700">SKU Code *</label>
                                            <button type="button" wire:click="generateSku" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold">
                                                🎲 Auto-Generate
                                            </button>
                                        </div>
                                        <input type="text" wire:model="sku" placeholder="PROD-001" class="w-full font-mono text-sm rounded-xl border border-slate-200 uppercase focus:ring-2 focus:ring-indigo-500">
                                        @error('sku') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Barcode / UPC</label>
                                        <input type="text" wire:model="barcode" placeholder="890123456789" class="w-full font-mono text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('barcode') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-slate-700">Category</label>
                                            <button type="button" wire:click="toggleQuickAddCategory" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-0.5">
                                                <span>{{ $showQuickAddCategory ? '✕ Close' : '+ Quick Add' }}</span>
                                            </button>
                                        </div>

                                        @if($showQuickAddCategory)
                                            <div class="p-2.5 mb-2 bg-indigo-50/70 border border-indigo-200/80 rounded-xl space-y-2">
                                                <input type="text" wire:model="quick_category_name" placeholder="New Category Name" class="w-full text-xs rounded-lg border-slate-200 focus:ring-1 focus:ring-indigo-500 py-1.5 px-2">
                                                @error('quick_category_name') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button" wire:click="toggleQuickAddCategory" class="text-[11px] text-slate-500 px-2 py-1 rounded hover:bg-slate-200">Cancel</button>
                                                    <button type="button" wire:click="quickAddCategory" class="text-[11px] bg-indigo-600 text-white font-semibold px-2.5 py-1 rounded-lg hover:bg-indigo-700">Save & Select</button>
                                                </div>
                                            </div>
                                        @endif

                                        <select wire:model="category_id" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select Category</option>
                                            @foreach($allCategories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-slate-700">Brand</label>
                                            <button type="button" wire:click="toggleQuickAddBrand" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-0.5">
                                                <span>{{ $showQuickAddBrand ? '✕ Close' : '+ Quick Add' }}</span>
                                            </button>
                                        </div>

                                        @if($showQuickAddBrand)
                                            <div class="p-2.5 mb-2 bg-indigo-50/70 border border-indigo-200/80 rounded-xl space-y-2">
                                                <input type="text" wire:model="quick_brand_name" placeholder="New Brand Name" class="w-full text-xs rounded-lg border-slate-200 focus:ring-1 focus:ring-indigo-500 py-1.5 px-2">
                                                @error('quick_brand_name') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button" wire:click="toggleQuickAddBrand" class="text-[11px] text-slate-500 px-2 py-1 rounded hover:bg-slate-200">Cancel</button>
                                                    <button type="button" wire:click="quickAddBrand" class="text-[11px] bg-indigo-600 text-white font-semibold px-2.5 py-1 rounded-lg hover:bg-indigo-700">Save & Select</button>
                                                </div>
                                            </div>
                                        @endif

                                        <select wire:model="brand_id" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select Brand</option>
                                            @foreach($allBrands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('brand_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Unit of Measure *</label>
                                        <input type="text" wire:model="unit" placeholder="pcs, kg, box" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('unit') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cost Price ($) *</label>
                                        <input type="number" step="0.01" wire:model="cost_price" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('cost_price') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price ($) *</label>
                                        <input type="number" step="0.01" wire:model="selling_price" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('selling_price') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Reorder Alert Level *</label>
                                        <input type="number" wire:model="reorder_level" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('reorder_level') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Suggested Reorder Qty *</label>
                                        <input type="number" wire:model="reorder_quantity" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('reorder_quantity') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                                    <textarea wire:model="description" rows="2" placeholder="Specifications, dimensions, notes..." class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>

                                <div class="pt-2">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-semibold text-slate-700">Active for Sale & Purchase</span>
                                    </label>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="button" @click="open = false; $wire.closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/60 rounded-xl transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition shadow-sm">
                                    {{ $product_id ? 'Update Product' : 'Save Product' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Stock Inspection Modal (Multi-Warehouse breakdown) -->
        @if($selectedProduct)
            <div x-data="{ open: true }" 
                 x-show="open" 
                 x-cloak
                 @keydown.escape.window="open = false; $wire.set('selectedProduct', null)"
                 class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                         @click="open = false; $wire.set('selectedProduct', null)"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200"
                         x-show="open"
                         x-transition:enter="ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ $selectedProduct->name }}</h3>
                                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $selectedProduct->sku }}</p>
                            </div>
                            <button type="button" @click="open = false; $wire.set('selectedProduct', null)" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-200/60">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 max-h-[60vh] overflow-y-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                        <th class="pb-3">Warehouse Hub</th>
                                        <th class="pb-3 text-right">On Hand</th>
                                        <th class="pb-3 text-right">Reserved</th>
                                        <th class="pb-3 text-right">Available</th>
                                        <th class="pb-3 text-right">Holding Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($productWarehouseStock as $row)
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3">
                                                <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                                                    <span>{{ $row['warehouse_name'] }}</span>
                                                    @if($row['is_default'])
                                                        <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.2 rounded">DEFAULT</span>
                                                    @endif
                                                </div>
                                                <div class="text-slate-400 text-[11px] font-mono">{{ $row['warehouse_code'] }}</div>
                                            </td>
                                            <td class="py-3 text-right font-medium text-slate-700">{{ number_format($row['on_hand']) }}</td>
                                            <td class="py-3 text-right text-amber-600 font-medium">{{ number_format($row['reserved']) }}</td>
                                            <td class="py-3 text-right font-bold {{ $row['available'] <= 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                {{ number_format($row['available']) }}
                                            </td>
                                            <td class="py-3 text-right font-semibold text-slate-900">${{ number_format($row['value'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                            <button type="button" @click="open = false; $wire.set('selectedProduct', null)" class="px-4 py-2 text-xs font-semibold bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bulk CSV Import Modal -->
        @if($isImportModalOpen)
            <div x-data="{ open: true }" 
                 x-show="open" 
                 x-cloak
                 @keydown.escape.window="open = false; $wire.closeImportModal()"
                 class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="import-modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                         @click="open = false; $wire.closeImportModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200"
                         x-show="open"
                         x-transition:enter="ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="import-modal-title">Bulk Import Product Catalog</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Upload a CSV file to create or update thousands of products in one click.</p>
                            </div>
                            <button type="button" @click="open = false; $wire.closeImportModal()" class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-200/60">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form wire:submit.prevent="importCsv" class="p-6 space-y-5">
                            
                            <!-- Sample Template Notice -->
                            <div class="p-4 bg-indigo-50/70 border border-indigo-100 rounded-xl flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-indigo-950">Need the correct column format?</div>
                                    <div class="text-[11px] text-indigo-700">Download our sample CSV with pre-filled headers and instructions.</div>
                                </div>
                                <button type="button" wire:click="downloadSampleTemplate" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-bold text-indigo-600 hover:bg-indigo-50 shadow-2xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>Sample Template</span>
                                </button>
                            </div>

                            <!-- Warehouse for Initial Stock -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Initial Stock Warehouse <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <select wire:model="initialWarehouseId" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    <option value="">No initial stock allocation</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1">If your CSV contains an <code>initial_stock</code> column, units will be added to this warehouse.</p>
                            </div>

                            <!-- File Upload Drag Box -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Upload CSV File <span class="text-red-500">*</span>
                                </label>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-indigo-400 transition bg-slate-50/50">
                                    <input type="file" wire:model="importFile" accept=".csv,text/csv" class="hidden" id="catalog-file-upload">
                                    <label for="catalog-file-upload" class="cursor-pointer">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 mb-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-bold text-indigo-600 hover:text-indigo-500">Click to select CSV file</span>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Maximum file size: 10MB (.csv format)</p>
                                    </label>
                                    @if($importFile)
                                        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-700 font-medium">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ $importFile->getClientOriginalName() }}</span>
                                        </div>
                                    @endif
                                </div>
                                @error('importFile') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Import Results Summary -->
                            @if($importResults)
                                <div class="p-4 bg-slate-900 text-white rounded-xl text-xs space-y-2">
                                    <div class="font-bold text-emerald-400 flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Import Results:</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center py-2 bg-slate-800 rounded-lg">
                                        <div>
                                            <div class="text-slate-400 text-[10px] uppercase">Created</div>
                                            <div class="text-emerald-400 font-bold text-base">{{ $importResults['created'] }}</div>
                                        </div>
                                        <div>
                                            <div class="text-slate-400 text-[10px] uppercase">Updated</div>
                                            <div class="text-indigo-400 font-bold text-base">{{ $importResults['updated'] }}</div>
                                        </div>
                                        <div>
                                            <div class="text-slate-400 text-[10px] uppercase">Skipped</div>
                                            <div class="text-amber-400 font-bold text-base">{{ $importResults['skipped'] }}</div>
                                        </div>
                                    </div>

                                    @if(count($importResults['errors']) > 0)
                                        <div class="text-[11px] text-red-400 max-h-24 overflow-y-auto space-y-1 pt-1">
                                            @foreach($importResults['errors'] as $err)
                                                <div>&bull; {{ $err }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Modal Footer Actions -->
                            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                                <button type="button" @click="open = false; $wire.closeImportModal()" class="px-4 py-2 text-xs font-semibold bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition">
                                    <span wire:loading.remove wire:target="importCsv">Upload & Process</span>
                                    <span wire:loading wire:target="importCsv">Processing CSV...</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Categories & Brands Management Modal -->
        @include('livewire.partials.category-brand-modal')

    </div>
</div>
