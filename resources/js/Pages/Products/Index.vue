<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    brands: Array,
    warehouses: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category_id || '');
const brandFilter = ref(props.filters?.brand_id || '');
const stockFilter = ref(props.filters?.stock || 'all');

// Modals
const isInspectModalOpen = ref(false);
const inspectedProduct = ref(null);
const inspectedLevels = ref([]);
const inspectedBarcodeSvg = ref('');
const inspectedBarcodeValue = ref('');
const deletingProduct = ref(null);
const isDeleting = ref(false);
const isInspectLoading = ref(false);

const isTaxonomyModalOpen = ref(false);
const taxonomyTab = ref('categories');

const isImportModalOpen = ref(false);

// Forms for categories / brands
const categoryForm = useForm({
    name: '',
    parent_id: '',
    description: '',
});

const brandForm = useForm({
    name: '',
    description: '',
});

const importForm = useForm({
    file: null,
    warehouse_id: '',
});

function applyFilters() {
    const params = {};
    if (search.value) params.search = search.value;
    if (categoryFilter.value) params.category_id = categoryFilter.value;
    if (brandFilter.value) params.brand_id = brandFilter.value;
    if (stockFilter.value && stockFilter.value !== 'all') params.stock = stockFilter.value;
    router.get(route('products.index'), params, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    categoryFilter.value = '';
    brandFilter.value = '';
    stockFilter.value = 'all';
    router.get(route('products.index'), {}, { preserveState: false, replace: true });
}

function deleteProduct(prod) {
    deletingProduct.value = prod;
}

const optimisticallyHiddenProductIds = ref(new Set());

const displayedProducts = computed(() => {
    return (props.products?.data || []).filter(p => !optimisticallyHiddenProductIds.value.has(p.id));
});

function executeDeleteProduct() {
    if (!deletingProduct.value) return;
    const targetId = deletingProduct.value.id;
    // Optimistic UI: row vanishes instantly (0ms)
    optimisticallyHiddenProductIds.value.add(targetId);
    deletingProduct.value = null;

    router.delete(route('products.destroy', targetId), {
        preserveScroll: true,
        only: ['products', 'metrics', 'flash'],
        onError: () => {
            optimisticallyHiddenProductIds.value.delete(targetId);
        },
        onFinish: () => {
            optimisticallyHiddenProductIds.value.delete(targetId);
        },
    });
}

async function inspectStock(prod) {
    inspectedProduct.value = prod;
    isInspectModalOpen.value = true;
    isInspectLoading.value = true;
    inspectedLevels.value = [];
    inspectedBarcodeSvg.value = '';
    inspectedBarcodeValue.value = '';

    try {
        const response = await axios.get(route('products.inspect', prod.id));
        inspectedLevels.value = response.data.levels || [];
        inspectedBarcodeSvg.value = response.data.barcode_svg || '';
        inspectedBarcodeValue.value = response.data.barcode_value || '';
    } catch (e) {
        console.error('Failed to inspect product stock distribution', e);
    } finally {
        isInspectLoading.value = false;
    }
}

function printBarcode() {
    if (!inspectedBarcodeSvg.value) return;
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>Barcode - ${inspectedProduct.value?.name || 'Product'}</title>
                <style>
                    body { font-family: sans-serif; text-align: center; padding: 20px; }
                    .label { display: inline-block; border: 1px dashed #cbd5e1; padding: 16px; border-radius: 8px; max-width: 280px; }
                    .name { font-size: 13px; font-weight: bold; margin-bottom: 8px; color: #0f172a; word-break: break-word; }
                    .barcode-box { display: flex; justify-content: center; margin: 6px 0; }
                    .code { font-family: monospace; font-size: 12px; font-weight: bold; margin-top: 6px; letter-spacing: 2px; color: #334155; }
                    .price { font-size: 14px; font-weight: 800; color: #4338ca; margin-top: 4px; }
                    svg { width: 220px; height: 50px; }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="label">
                    <div class="name">${inspectedProduct.value?.name || ''}</div>
                    <div class="barcode-box">${inspectedBarcodeSvg.value}</div>
                    <div class="code">${inspectedBarcodeValue.value}</div>
                    <div class="price">৳ ${Number(inspectedProduct.value?.unit_price || inspectedProduct.value?.selling_price || 0).toFixed(2)}</div>
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
}

function submitCategory() {
    categoryForm.post(route('products.categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
        },
    });
}

const deletingCategory = ref(null);
const isDeletingCategory = ref(false);

const optimisticallyHiddenCategoryIds = ref(new Set());
const displayedCategories = computed(() => {
    return (props.categories || []).filter(c => !optimisticallyHiddenCategoryIds.value.has(c.id));
});

function deleteCategory(cat) {
    deletingCategory.value = cat;
}

function executeDeleteCategory() {
    if (!deletingCategory.value) return;
    const targetId = deletingCategory.value.id;
    optimisticallyHiddenCategoryIds.value.add(targetId);
    deletingCategory.value = null;

    router.delete(route('products.categories.destroy', targetId), {
        preserveScroll: true,
        only: ['categories', 'flash'],
        onError: () => {
            optimisticallyHiddenCategoryIds.value.delete(targetId);
        },
        onFinish: () => {
            optimisticallyHiddenCategoryIds.value.delete(targetId);
        },
    });
}

function submitBrand() {
    brandForm.post(route('products.brands.store'), {
        preserveScroll: true,
        only: ['brands', 'flash'],
        onSuccess: () => {
            brandForm.reset();
        },
    });
}

const deletingBrand = ref(null);
const isDeletingBrand = ref(false);
const optimisticallyHiddenBrandIds = ref(new Set());
const displayedBrands = computed(() => {
    return (props.brands || []).filter(b => !optimisticallyHiddenBrandIds.value.has(b.id));
});

function deleteBrand(b) {
    deletingBrand.value = b;
}

function executeDeleteBrand() {
    if (!deletingBrand.value) return;
    const targetId = deletingBrand.value.id;
    optimisticallyHiddenBrandIds.value.add(targetId);
    deletingBrand.value = null;

    router.delete(route('products.brands.destroy', targetId), {
        preserveScroll: true,
        only: ['brands', 'flash'],
        onError: () => {
            optimisticallyHiddenBrandIds.value.delete(targetId);
        },
        onFinish: () => {
            optimisticallyHiddenBrandIds.value.delete(targetId);
        },
    });
}

function handleImport(e) {
    importForm.file = e.target.files[0];
}

function submitImport() {
    importForm.post(route('products.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Products & Catalog" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Products & Catalog</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage SKUs, categories, brands, price margins, and warehouse distribution</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Products</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_products || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Stock On-Hand</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        {{ Number(metrics?.total_units || 0).toLocaleString() }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Low & Out of Stock Alerts</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.low_stock_count || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Catalog Valuation</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_valuation || 0" />
                    </div>
                </div>
            </div>

            <!-- Filter Bar & Actions -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="applyFilters"
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a 
                            :href="route('products.export', { format: 'xlsx' })" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-xs font-semibold transition-all shadow-xs"
                            title="Download formatted Excel spreadsheet"
                        >
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export Excel</span>
                        </a>

                        <a 
                            :href="route('products.export', { format: 'csv' })" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all"
                            title="Download raw CSV file"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Export CSV</span>
                        </a>

                        <button 
                            @click="isImportModalOpen = true" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Import CSV</span>
                        </button>

                        <Link 
                            :href="route('products.create')" 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Product</span>
                        </Link>
                    </div>
                </div>

                <!-- Secondary Filter Bar -->
                <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100 text-xs">
                    <!-- Category Select -->
                    <select 
                        v-model="categoryFilter" 
                        @change="applyFilters"
                        class="pl-3 pr-8 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 min-w-[130px]"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>

                    <!-- Brand Select -->
                    <select 
                        v-model="brandFilter" 
                        @change="applyFilters"
                        class="pl-3 pr-8 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 min-w-[130px]"
                    >
                        <option value="">All Brands</option>
                        <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>

                    <!-- Stock Status Pills -->
                    <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200">
                        <button 
                            @click="stockFilter = 'all'; applyFilters()" 
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="stockFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            All Stock
                        </button>
                        <button 
                            @click="stockFilter = 'low_stock'; applyFilters()" 
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="stockFilter === 'low_stock' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Low Stock
                        </button>
                        <button 
                            @click="stockFilter = 'out_of_stock'; applyFilters()" 
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="stockFilter === 'out_of_stock' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Out of Stock
                        </button>
                    </div>

                    <button 
                        v-if="search || categoryFilter || brandFilter || stockFilter !== 'all'"
                        @click="resetFilters"
                        class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs ml-auto"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Product & SKU</th>
                                <th class="py-3 px-4 whitespace-nowrap">Brand/Category</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Cost (COGS)</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Selling Price</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Available Stock</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Warranty</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="prod in displayedProducts" :key="prod.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Product & SKU -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200/80 flex items-center justify-center overflow-hidden shrink-0">
                                            <img v-if="prod.image_url" :src="prod.image_url" :alt="prod.name" class="w-full h-full object-cover" />
                                            <svg v-else class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm leading-tight max-w-[200px] lg:max-w-[280px] truncate" :title="prod.name">{{ prod.name }}</div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="font-mono text-indigo-600 font-semibold text-[11px] bg-indigo-50 px-1.5 py-0.5 rounded whitespace-nowrap">
                                                    {{ prod.sku }}
                                                </span>
                                                <span v-if="prod.barcode" class="font-mono text-slate-400 text-[10px] whitespace-nowrap">
                                                    UPC: {{ prod.barcode }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category & Brand -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span v-if="prod.category" class="inline-block px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-semibold w-max">
                                            {{ prod.category.name }}
                                        </span>
                                        <span v-if="prod.brand" class="text-[10px] text-slate-500 font-medium">
                                            Brand: <strong class="text-slate-700">{{ prod.brand.name }}</strong>
                                        </span>
                                    </div>
                                </td>

                                <!-- Cost Price -->
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-600 whitespace-nowrap">
                                    <CurrencyDisplay :amount="prod.cost_price" />
                                </td>

                                <!-- Selling Price & Margin -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="font-mono font-bold text-slate-900">
                                        <CurrencyDisplay :amount="prod.selling_price" />
                                    </div>
                                    <div class="text-[10px] font-medium text-emerald-600 mt-0.5">
                                        +<CurrencyDisplay :amount="prod.selling_price - prod.cost_price" />
                                    </div>
                                </td>

                                <!-- Available Stock -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div 
                                        class="inline-flex items-center gap-1 font-mono font-bold text-xs px-2.5 py-1 rounded-full border"
                                        :class="(prod.total_stock || 0) <= prod.reorder_level 
                                            ? ((prod.total_stock || 0) <= 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200') 
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                    >
                                        <span>{{ prod.total_stock || 0 }}</span>
                                        <span class="text-[10px] uppercase font-sans text-slate-500 font-semibold">{{ prod.unit }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Min: {{ prod.reorder_level }}
                                    </div>
                                </td>

                                <!-- Warranty & Serialization -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div v-if="prod.warranty_days > 0" class="text-xs font-semibold text-indigo-600">
                                        {{ prod.warranty_days }} Days
                                    </div>
                                    <div v-else class="text-slate-400 text-[10px]">
                                        None
                                    </div>
                                    <span v-if="prod.is_serialized" class="inline-block mt-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">
                                        Serialized
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <button 
                                                @click="inspectStock(prod)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span>Inspect Stock</span>
                                            </button>

                                            <Link 
                                                :href="route('products.edit', prod.id)" 
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Product</span>
                                            </Link>

                                            <button 
                                                @click="deleteProduct(prod)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Product</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="products.data.length === 0" class="text-center py-16">
                    <p class="text-xs text-slate-500">No products match your search or filter criteria.</p>
                </div>

                <!-- Pagination -->
                <div v-if="products.links && products.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ products.from }} to {{ products.to }} of {{ products.total }} items
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in products.links" 
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="link.active ? 'bg-indigo-600 text-white' : (link.url ? 'text-slate-600 hover:bg-slate-200' : 'text-slate-300 cursor-not-allowed')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Inspection Modal -->
        <Modal :show="isInspectModalOpen" @close="isInspectModalOpen = false" maxWidth="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Warehouse Distribution: {{ inspectedProduct?.name }}
                        </h2>
                        <p class="text-xs text-slate-500 font-mono">SKU: {{ inspectedProduct?.sku }}</p>
                    </div>
                    <button @click="isInspectModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="isInspectLoading" class="text-center py-10 text-slate-400 text-xs">
                    Loading stock distribution...
                </div>

                <template v-else>
                    <!-- Barcode Display & Print Sticker -->
                    <div v-if="inspectedBarcodeSvg" class="mb-4 p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="bg-white p-2 border border-slate-200 rounded-lg shrink-0 max-w-[160px] overflow-hidden" v-html="inspectedBarcodeSvg"></div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Product Barcode (Code-128)</span>
                                <div class="font-mono text-xs font-bold text-slate-800 tracking-wider mt-0.5 truncate">{{ inspectedBarcodeValue }}</div>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="printBarcode"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer shrink-0"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Print Sticker</span>
                        </button>
                    </div>

                    <div v-if="inspectedLevels.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px]">
                                <th class="py-2.5">Warehouse Hub</th>
                                <th class="py-2.5 text-center">On-Hand</th>
                                <th class="py-2.5 text-center">Reserved</th>
                                <th class="py-2.5 text-center">Available</th>
                                <th class="py-2.5 text-right">Capital Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(lvl, idx) in inspectedLevels" :key="idx" class="hover:bg-slate-50">
                                <td class="py-2.5 font-bold text-slate-800">{{ lvl.warehouse_name }}</td>
                                <td class="py-2.5 text-center font-mono font-bold">{{ lvl.quantity_on_hand }}</td>
                                <td class="py-2.5 text-center font-mono text-amber-600">{{ lvl.quantity_reserved }}</td>
                                <td class="py-2.5 text-center font-mono text-emerald-600 font-bold">{{ lvl.quantity_available }}</td>
                                <td class="py-2.5 text-right font-mono font-bold">
                                    <CurrencyDisplay :amount="lvl.valuation" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-10 text-slate-400 text-xs">
                    No active stock currently registered for this product across fulfillment hubs.
                </div>
                </template>
            </div>
        </Modal>

        <!-- Categories & Brands Manager Modal -->
        <Modal :show="isTaxonomyModalOpen" @close="isTaxonomyModalOpen = false" maxWidth="3xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-3">
                        <button 
                            @click="taxonomyTab = 'categories'" 
                            class="text-sm font-bold pb-1 border-b-2 transition-all"
                            :class="taxonomyTab === 'categories' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-700'"
                        >
                            Categories ({{ categories.length }})
                        </button>
                        <button 
                            @click="taxonomyTab = 'brands'" 
                            class="text-sm font-bold pb-1 border-b-2 transition-all"
                            :class="taxonomyTab === 'brands' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-700'"
                        >
                            Brands & Manufacturers ({{ brands.length }})
                        </button>
                    </div>
                    <button @click="isTaxonomyModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Categories Tab Content -->
                <div v-if="taxonomyTab === 'categories'" class="space-y-4">
                    <!-- Inline Add Category Form -->
                    <form @submit.prevent="submitCategory" class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                        <input 
                            type="text" 
                            v-model="categoryForm.name" 
class="flex-1 px-3 py-1.5 rounded-lg text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <button 
                            type="submit" 
                            :disabled="categoryForm.processing || !categoryForm.name"
                            class="px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors disabled:opacity-50"
                        >
                            Add Category
                        </button>
                    </form>

                    <!-- Categories List -->
                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl">
                        <div 
                            v-for="cat in displayedCategories" 
                            :key="cat.id" 
                            class="p-2.5 px-3.5 flex items-center justify-between hover:bg-slate-50 text-xs"
                        >
                            <div>
                                <span class="font-bold text-slate-800">{{ cat.name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono ml-2">/{{ cat.slug }}</span>
                            </div>
                            <button 
                                @click="deleteCategory(cat)" 
                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Brands Tab Content -->
                <div v-if="taxonomyTab === 'brands'" class="space-y-4">
                    <!-- Inline Add Brand Form -->
                    <form @submit.prevent="submitBrand" class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                        <input 
                            type="text" 
                            v-model="brandForm.name" 
class="flex-1 px-3 py-1.5 rounded-lg text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <button 
                            type="submit" 
                            :disabled="brandForm.processing || !brandForm.name"
                            class="px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors disabled:opacity-50"
                        >
                            Add Brand
                        </button>
                    </form>

                    <!-- Brands List -->
                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl">
                        <div 
                            v-for="b in displayedBrands" 
                            :key="b.id" 
                            class="p-2.5 px-3.5 flex items-center justify-between hover:bg-slate-50 text-xs"
                        >
                            <div>
                                <span class="font-bold text-slate-800">{{ b.name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono ml-2">/{{ b.slug }}</span>
                            </div>
                            <button 
                                @click="deleteBrand(b)" 
                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Import Modal -->
        <Modal :show="isImportModalOpen" @close="isImportModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h2 class="text-base font-bold text-slate-900">Bulk Import Products (CSV)</h2>
                    <button @click="isImportModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">CSV File</label>
                        <input 
                            type="file" 
                            accept=".csv,text/csv" 
                            @change="handleImport"
                            required
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Opening Warehouse (Optional)</label>
                        <select 
                            v-model="importForm.warehouse_id" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200"
                        >
                            <option value="">Do not allocate opening stock</option>
                            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button 
                            type="button" 
                            @click="isImportModalOpen = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="importForm.processing || !importForm.file"
                            class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 transition-colors disabled:opacity-50"
                        >
                            Start Import
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingProduct"
            title="Delete Product"
            :item-name="deletingProduct ? `${deletingProduct.name} (${deletingProduct.sku})` : ''"
            message="Are you sure you want to delete this product? All current warehouse stock records for this item will be removed from active inventory."
            confirm-text="Delete Product"
            :processing="isDeleting"
            @confirm="executeDeleteProduct"
            @close="deletingProduct = null"
        />

        <!-- Delete Category Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingCategory"
            title="Delete Category"
            :item-name="deletingCategory ? deletingCategory.name : ''"
            message="Are you sure you want to delete this category? Products assigned to it will become unassigned."
            confirm-text="Delete Category"
            :processing="isDeletingCategory"
            @confirm="executeDeleteCategory"
            @close="deletingCategory = null"
        />

        <!-- Delete Brand Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingBrand"
            title="Delete Brand"
            :item-name="deletingBrand ? deletingBrand.name : ''"
            message="Are you sure you want to delete this brand? Products assigned to it will have their brand removed."
            confirm-text="Delete Brand"
            :processing="isDeletingBrand"
            @confirm="executeDeleteBrand"
            @close="deletingBrand = null"
        />
    </AppLayout>
</template>
