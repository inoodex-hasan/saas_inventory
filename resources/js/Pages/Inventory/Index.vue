<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    metrics: Object,
    stockItems: Object,
    movements: Object,
    warehouses: Array,
    categories: Array,
    productsList: Array,
    filters: Object,
});

const currentTab = ref(props.filters?.tab || 'stock');
const search = ref(props.filters?.search || '');
const warehouseFilter = ref(props.filters?.warehouse_id || '');
const categoryFilter = ref(props.filters?.category_id || '');

// Modals state
const isAdjustModalOpen = ref(false);
const isTransferModalOpen = ref(false);

// Adjust Form
const adjustForm = useForm({
    product_id: '',
    warehouse_id: props.warehouses[0]?.id || '',
    new_quantity: '',
    reason: '',
});

// Transfer Form
const transferForm = useForm({
    product_id: '',
    from_warehouse_id: props.warehouses[0]?.id || '',
    to_warehouse_id: props.warehouses[1]?.id || '',
    quantity: 1,
    reason: '',
});

function handleFilter() {
    router.get(route('inventory.index'), {
        tab: currentTab.value,
        search: search.value || undefined,
        warehouse_id: warehouseFilter.value || undefined,
        category_id: categoryFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function setTab(tab) {
    currentTab.value = tab;
    handleFilter();
}

function clearFilters() {
    search.value = '';
    warehouseFilter.value = '';
    categoryFilter.value = '';
    router.get(route('inventory.index'), { tab: currentTab.value }, { preserveState: false, replace: true, preserveScroll: true });
}

function openAdjustModal(product = null) {
    adjustForm.reset();
    adjustForm.clearErrors();
    if (product) {
        adjustForm.product_id = product.id;
    }
    adjustForm.warehouse_id = warehouseFilter.value || props.warehouses[0]?.id || '';
    isAdjustModalOpen.value = true;
}

function openTransferModal(product = null) {
    transferForm.reset();
    transferForm.clearErrors();
    if (product) {
        transferForm.product_id = product.id;
    }
    transferForm.from_warehouse_id = warehouseFilter.value || props.warehouses[0]?.id || '';
    transferForm.to_warehouse_id = props.warehouses.find(w => w.id !== transferForm.from_warehouse_id)?.id || '';
    transferForm.quantity = 1;
    isTransferModalOpen.value = true;
}

function submitAdjust() {
    adjustForm.post(route('inventory.adjust'), {
        preserveScroll: true,
        onSuccess: () => {
            isAdjustModalOpen.value = false;
            adjustForm.reset();
        },
    });
}

function submitTransfer() {
    transferForm.post(route('inventory.transfer'), {
        preserveScroll: true,
        onSuccess: () => {
            isTransferModalOpen.value = false;
            transferForm.reset();
        },
    });
}

const productOptions = computed(() => {
    return (props.productsList || []).map(p => ({
        id: p.id,
        name: p.name,
        sku: p.sku,
        cost_price: p.cost_price,
    }));
});
</script>

<template>
    <AppLayout>
        <Head title="Stock & Inventory Balances" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Stock & Inventory Balances</h1>
                    <p class="text-xs text-slate-500 font-medium">Real-time warehouse stock tracking, holding valuation & inventory audit reconciliation</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- 1. KPI Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Stock On Hand</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ Number(metrics?.total_units || 0).toLocaleString() }} <span class="text-xs font-normal text-slate-500">units</span>
                    </div>
                    <div v-if="metrics?.total_incoming_units > 0" class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-amber-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        <span>+{{ Number(metrics.total_incoming_units).toLocaleString() }} on order (incoming)</span>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inventory Holding Valuation</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_valuation || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Warnings</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.low_stock_count || 0 }} <span class="text-xs font-normal text-slate-500">items</span>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Out of Stock Depleted</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        {{ metrics?.out_of_stock_count || 0 }} <span class="text-xs font-normal text-slate-500">items</span>
                    </div>
                </div>
            </div>

            <!-- 2. Tabs & Toolbar Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Navigation Tabs -->
                <div class="inline-flex p-1 bg-slate-200/70 rounded-xl text-xs font-bold">
                    <button
                        type="button"
                        @click="setTab('stock')"
                        class="px-4 py-2 rounded-lg transition-all"
                        :class="currentTab === 'stock' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Stock Balances & Valuation
                    </button>
                    <button
                        type="button"
                        @click="setTab('ledger')"
                        class="px-4 py-2 rounded-lg transition-all"
                        :class="currentTab === 'ledger' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Movement Audit Ledger
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <button
                        @click="openTransferModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-all"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span>Transfer Stock</span>
                    </button>

                    <button
                        @click="openAdjustModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>
                </div>
            </div>

            <!-- 3. Search & Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="handleFilter"
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select
                        v-model="warehouseFilter"
                        @change="handleFilter"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 transition"
                    >
                        <option value="">All Warehouses</option>
                        <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                            {{ w.name }} ({{ w.code }})
                        </option>
                    </select>

                    <select
                        v-if="currentTab === 'stock'"
                        v-model="categoryFilter"
                        @change="handleFilter"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 transition"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                            {{ cat.name }}
                        </option>
                    </select>

                    <button
                        v-if="search || warehouseFilter || categoryFilter"
                        @click="clearFilters"
                        class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- 4. TAB 1: Stock Balances & Valuation Table -->
            <div v-if="currentTab === 'stock'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Item & Catalog</th>
                                <th class="py-3 px-4 whitespace-nowrap">Warehouse Distribution</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">On Hand</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Incoming</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Reserved</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Available</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Cost Price</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Holding Value</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Health</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in stockItems.data" :key="item.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 text-sm leading-snug max-w-[200px] lg:max-w-[260px] truncate" :title="item.name">{{ item.name }}</div>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400 font-mono">
                                        <span>SKU: <span class="text-slate-600 font-semibold">{{ item.sku }}</span></span>
                                        <span v-if="item.barcode">&bull; Barcode: {{ item.barcode }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span v-if="item.category" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                            {{ item.category }}
                                        </span>
                                        <span v-if="item.brand" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                            {{ item.brand }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div v-if="item.warehouses && item.warehouses.length > 0" class="flex flex-wrap gap-1.5 max-w-xs">
                                        <span
                                            v-for="wh in item.warehouses"
                                            :key="wh.warehouse_id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-50 border border-slate-200 whitespace-nowrap"
                                        >
                                            <span class="text-slate-500">{{ wh.warehouse_name }}:</span>
                                            <span class="font-bold text-slate-900">{{ wh.quantity }}</span>
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-300 font-mono text-[11px]">— No stock assigned —</span>
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-900 text-sm whitespace-nowrap">
                                    {{ item.on_hand }}
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono whitespace-nowrap">
                                    <span
                                        v-if="item.incoming > 0"
                                        class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md text-[11px] border border-amber-200/80"
                                        title="Ordered from vendor (pending warehouse receipt)"
                                    >
                                        <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        <span>+{{ item.incoming }}</span>
                                    </span>
                                    <span v-else class="text-slate-300 text-xs">0</span>
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono text-slate-400 whitespace-nowrap">
                                    {{ item.reserved > 0 ? item.reserved : '0' }}
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono font-bold text-indigo-600 text-sm whitespace-nowrap">
                                    {{ item.available }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-700 whitespace-nowrap">
                                    <CurrencyDisplay :amount="item.cost_price" />
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <CurrencyDisplay :amount="item.valuation" />
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        v-if="item.status === 'in_stock'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100"
                                    >
                                        In Stock
                                    </span>
                                    <span
                                        v-else-if="item.status === 'low_stock'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-100 animate-pulse"
                                    >
                                        Low Stock
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100"
                                    >
                                        Depleted
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="stockItems.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No inventory records match the selected filters.
                </div>

                <!-- Stock Pagination -->
                <div v-if="stockItems.links && stockItems.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ stockItems.from }} to {{ stockItems.to }} of {{ stockItems.total }} products
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in stockItems.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="link.active ? 'bg-indigo-600 text-white' : (link.url ? 'text-slate-600 hover:bg-slate-200' : 'text-slate-300 cursor-not-allowed')"
                        />
                    </div>
                </div>
            </div>

            <!-- 5. TAB 2: Movement Audit Ledger Table -->
            <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Timestamp</th>
                                <th class="py-3 px-4 whitespace-nowrap">Product Item</th>
                                <th class="py-3 px-4 whitespace-nowrap">Warehouse</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Movement Type</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Quantity</th>
                                <th class="py-3 px-4 whitespace-nowrap">Reason / Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ formatDate(m.created_at) }}
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 max-w-[180px] lg:max-w-[240px] truncate" :title="m.product?.name || 'Deleted Product'">{{ m.product?.name || 'Deleted Product' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ m.product?.sku }}</div>
                                </td>

                                <td class="py-3 px-4 font-medium text-slate-700 whitespace-nowrap">
                                    <span class="max-w-[150px] truncate block" :title="m.warehouse?.name || 'Unknown Hub'">{{ m.warehouse?.name || 'Unknown Hub' }}</span>
                                </td>

                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-100': m.type === 'in',
                                            'bg-rose-50 text-rose-700 border border-rose-100': m.type === 'out',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-100': m.type === 'transfer' || m.type === 'transfer_in' || m.type === 'transfer_out',
                                            'bg-amber-50 text-amber-700 border border-amber-100': m.type === 'adjustment',
                                        }"
                                    >
                                        {{ m.type }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center font-mono font-bold text-sm whitespace-nowrap" :class="Number(m.quantity) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ Number(m.quantity) > 0 ? '+' : '' }}{{ Number(m.quantity) }}
                                </td>

                                <td class="py-3 px-4 text-slate-600 max-w-[200px] lg:max-w-[280px] truncate" :title="m.reason || m.reference_type">
                                    {{ (m.reason || m.reference_type) ? ((m.reason || m.reference_type).length > 50 ? (m.reason || m.reference_type).slice(0, 50) + '...' : (m.reason || m.reference_type)) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="movements.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No stock movements recorded yet.
                </div>

                <!-- Ledger Pagination -->
                <div v-if="movements.links && movements.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ movements.from }} to {{ movements.to }} of {{ movements.total }} records
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in movements.links"
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

        <!-- Adjust Stock Modal -->
        <Modal :show="isAdjustModalOpen" @close="isAdjustModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Reconcile / Adjust Physical Stock</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Update physical on-hand counts with documented audit reason</p>
                    </div>
                    <button @click="isAdjustModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAdjust" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Select Product *</label>
                        <SearchableSelect
                            v-model="adjustForm.product_id"
                            :options="productOptions"
                            labelKey="name"
                            sublabelKey="sku"
                            :error="adjustForm.errors.product_id"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fulfillment Hub / Warehouse *</label>
                        <select
                            v-model="adjustForm.warehouse_id"
                            required
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }} ({{ w.code }})
                            </option>
                        </select>
                        <span v-if="adjustForm.errors.warehouse_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ adjustForm.errors.warehouse_id }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Physical Count *</label>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            v-model.number="adjustForm.new_quantity"
                            required
                            class="w-full px-3.5 py-2 rounded-xl text-xs font-mono font-bold border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                        <span v-if="adjustForm.errors.new_quantity" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ adjustForm.errors.new_quantity }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Adjustment *</label>
                        <textarea
                            v-model="adjustForm.reason"
                            required
                            rows="2"
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 resize-none"
                        ></textarea>
                        <span v-if="adjustForm.errors.reason" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ adjustForm.errors.reason }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAdjustModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="adjustForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Transfer Stock Modal -->
        <Modal :show="isTransferModalOpen" @close="isTransferModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Inter-Warehouse Stock Transfer</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Move inventory units atomically between two fulfillment hubs</p>
                    </div>
                    <button @click="isTransferModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Select Product *</label>
                        <SearchableSelect
                            v-model="transferForm.product_id"
                            :options="productOptions"
                            labelKey="name"
                            sublabelKey="sku"
                            :error="transferForm.errors.product_id"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">From Hub *</label>
                            <select
                                v-model="transferForm.from_warehouse_id"
                                required
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                            <span v-if="transferForm.errors.from_warehouse_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                                {{ transferForm.errors.from_warehouse_id }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">To Hub *</label>
                            <select
                                v-model="transferForm.to_warehouse_id"
                                required
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                            <span v-if="transferForm.errors.to_warehouse_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                                {{ transferForm.errors.to_warehouse_id }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Quantity to Transfer *</label>
                        <input
                            type="number"
                            step="any"
                            min="0.01"
                            v-model.number="transferForm.quantity"
                            required
                            class="w-full px-3.5 py-2 rounded-xl text-xs font-mono font-bold border border-slate-200 focus:border-indigo-500"
                        />
                        <span v-if="transferForm.errors.quantity" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ transferForm.errors.quantity }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Transfer Notes (Optional)</label>
                        <textarea
                            v-model="transferForm.reason"
                            rows="2"
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isTransferModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="transferForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            Execute Transfer
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
