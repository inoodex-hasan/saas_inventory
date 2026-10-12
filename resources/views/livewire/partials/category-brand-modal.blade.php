@if($isCatBrandModalOpen)
    <div x-data="{ open: true }" 
         x-show="open" 
         x-cloak
         @keydown.escape.window="open = false; $wire.closeCatBrandModal()"
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                 @click="open = false; $wire.closeCatBrandModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-100"
                 x-show="open"
                 x-transition:enter="ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <!-- Modal Header with Tabs -->
                <div class="px-6 py-4 bg-white border-b border-slate-200/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-600 font-bold">
                            🏷️
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 tracking-tight leading-none">Categories & Brands</h3>
                            <p class="text-xs text-slate-500 mt-1">Manage product categories and brands for your catalog</p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="open = false; $wire.closeCatBrandModal()" 
                            class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 p-1.5 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tab Switcher & Search Bar -->
                <div class="px-6 py-3 bg-slate-50 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                wire:click="switchCatBrandTab('categories')"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $catBrandActiveTab === 'categories' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                            <span>📁 Product Categories</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $catBrandActiveTab === 'categories' ? 'bg-indigo-500/40 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $categories->count() }}</span>
                        </button>
                        <button type="button" 
                                wire:click="switchCatBrandTab('brands')"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $catBrandActiveTab === 'brands' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                            <span>🏷️ Brands</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $catBrandActiveTab === 'brands' ? 'bg-indigo-500/40 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $brands->count() }}</span>
                        </button>
                    </div>

                    <div class="relative sm:w-64">
                        <input type="text" 
                               wire:model.live.debounce.250ms="catBrandSearch"
                               placeholder="Filter by name..." 
                               class="w-full text-xs rounded-xl border border-slate-200 py-1.5 pl-3 pr-8 focus:ring-1 focus:ring-indigo-500">
                        @if($catBrandSearch)
                            <button type="button" wire:click="$set('catBrandSearch', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">✕</button>
                        @endif
                    </div>
                </div>

                <!-- Feedback Alerts -->
                @if (session()->has('cat_brand_message'))
                    <div class="mx-6 mt-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                        <span>✅</span>
                        <span>{{ session('cat_brand_message') }}</span>
                    </div>
                @endif
                @if (session()->has('cat_brand_error'))
                    <div class="mx-6 mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                        <span>⚠️</span>
                        <span>{{ session('cat_brand_error') }}</span>
                    </div>
                @endif

                <!-- Modal Body (Two-Column Layout) -->
                <div class="p-6">
                    @if($catBrandActiveTab === 'categories')
                        <!-- Categories Tab Content -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <!-- Form Column -->
                            <div class="md:col-span-5 bg-slate-50/80 p-5 rounded-2xl border border-slate-200/80 space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">
                                        {{ $editing_category_id ? '✏️ Edit Category' : '➕ Add New Category' }}
                                    </h4>
                                    @if($editing_category_id)
                                        <button type="button" wire:click="resetCategoryForm" class="text-[11px] text-slate-500 hover:text-slate-800 font-semibold">
                                            Cancel Edit
                                        </button>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Category Name *</label>
                                    <input type="text" wire:model="category_name" placeholder="e.g. Computer Accessories" class="w-full text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 py-2">
                                    @error('category_name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Parent Category (Optional)</label>
                                    <select wire:model="category_parent_id" class="w-full text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 py-2">
                                        <option value="">None (Top-Level Category)</option>
                                        @foreach($allCategories as $c)
                                            @if($c->id !== $editing_category_id)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('category_parent_id') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
                                    <textarea wire:model="category_description" rows="3" placeholder="Brief summary of category..." class="w-full text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 py-2"></textarea>
                                    @error('category_description') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <button type="button" 
                                        wire:click="saveCategory"
                                        class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                                    <span>{{ $editing_category_id ? 'Update Category' : 'Create Category' }}</span>
                                </button>
                            </div>

                            <!-- Table Column -->
                            <div class="md:col-span-7 overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
                                <div class="max-h-[420px] overflow-y-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 text-slate-500 font-semibold">
                                            <tr>
                                                <th class="py-2.5 px-3">Name / Slug</th>
                                                <th class="py-2.5 px-3 text-center">Products</th>
                                                <th class="py-2.5 px-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($categories as $cat)
                                                <tr class="hover:bg-slate-50/70 transition {{ $editing_category_id === $cat->id ? 'bg-indigo-50/50' : '' }}">
                                                    <td class="py-2.5 px-3">
                                                        <div class="font-bold text-slate-900">{{ $cat->name }}</div>
                                                        <div class="font-mono text-[10px] text-slate-400 mt-0.5">/{{ $cat->slug }}</div>
                                                        @if($cat->description)
                                                            <div class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">{{ $cat->description }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $cat->products_count > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-500' }}">
                                                            {{ $cat->products_count }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                                        <div class="inline-flex items-center gap-1">
                                                            <button type="button" 
                                                                    wire:click="editCategory({{ $cat->id }})" 
                                                                    title="Edit Category"
                                                                    class="p-1 text-slate-400 hover:text-indigo-600 rounded hover:bg-slate-100">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button type="button" 
                                                                    wire:click="deleteCategory({{ $cat->id }})" 
                                                                    wire:confirm="Are you sure you want to delete category '{{ $cat->name }}'?"
                                                                    title="Delete Category"
                                                                    class="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="py-8 text-center text-slate-400">
                                                        No categories found. Create your first category on the left!
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Brands Tab Content -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <!-- Form Column -->
                            <div class="md:col-span-5 bg-slate-50/80 p-5 rounded-2xl border border-slate-200/80 space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">
                                        {{ $editing_brand_id ? '✏️ Edit Brand' : '➕ Add New Brand' }}
                                    </h4>
                                    @if($editing_brand_id)
                                        <button type="button" wire:click="resetBrandForm" class="text-[11px] text-slate-500 hover:text-slate-800 font-semibold">
                                            Cancel Edit
                                        </button>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Name *</label>
                                    <input type="text" wire:model="brand_name" placeholder="e.g. Logitech, Sony, Apple" class="w-full text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 py-2">
                                    @error('brand_name') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
                                    <textarea wire:model="brand_description" rows="4" placeholder="Brief notes about manufacturer/brand..." class="w-full text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 py-2"></textarea>
                                    @error('brand_description') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <button type="button" 
                                        wire:click="saveBrand"
                                        class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                                    <span>{{ $editing_brand_id ? 'Update Brand' : 'Create Brand' }}</span>
                                </button>
                            </div>

                            <!-- Table Column -->
                            <div class="md:col-span-7 overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
                                <div class="max-h-[420px] overflow-y-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 text-slate-500 font-semibold">
                                            <tr>
                                                <th class="py-2.5 px-3">Brand / Slug</th>
                                                <th class="py-2.5 px-3 text-center">Products</th>
                                                <th class="py-2.5 px-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($brands as $brand)
                                                <tr class="hover:bg-slate-50/70 transition {{ $editing_brand_id === $brand->id ? 'bg-indigo-50/50' : '' }}">
                                                    <td class="py-2.5 px-3">
                                                        <div class="font-bold text-slate-900">{{ $brand->name }}</div>
                                                        <div class="font-mono text-[10px] text-slate-400 mt-0.5">/{{ $brand->slug }}</div>
                                                        @if($brand->description)
                                                            <div class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">{{ $brand->description }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $brand->products_count > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-500' }}">
                                                            {{ $brand->products_count }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                                        <div class="inline-flex items-center gap-1">
                                                            <button type="button" 
                                                                    wire:click="editBrand({{ $brand->id }})" 
                                                                    title="Edit Brand"
                                                                    class="p-1 text-slate-400 hover:text-indigo-600 rounded hover:bg-slate-100">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button type="button" 
                                                                    wire:click="deleteBrand({{ $brand->id }})" 
                                                                    wire:confirm="Are you sure you want to delete brand '{{ $brand->name }}'?"
                                                                    title="Delete Brand"
                                                                    class="p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100">
                                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="py-8 text-center text-slate-400">
                                                        No brands found. Create your first brand on the left!
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button" @click="open = false; $wire.closeCatBrandModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
@endif
