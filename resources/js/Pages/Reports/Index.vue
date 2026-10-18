<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    currencySymbol: String,
    activeTab: {
        type: String,
        default: 'valuation',
    },
    filters: Object,
    warehouses: Array,
    categories: Array,
    valuation: Object,
    lowStockItems: Array,
    movements: Object,
});

const currentTab = ref(props.filters?.tab || props.activeTab || 'valuation');
const movementType = ref(props.filters?.movement_type || '');
const movementWarehouseId = ref(props.filters?.movement_warehouse_id || '');
const movementSearch = ref(props.filters?.movement_search || '');
const lowStockSearch = ref(props.filters?.low_stock_search || '');

function setTab(tab) {
    currentTab.value = tab;
    applyFilter();
}

let searchTimeout = null;
function handleSearchDebounced() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilter();
    }, 350);
}

function applyFilter() {
    router.get(route('reports.index'), {
        tab: currentTab.value,
        movement_type: movementType.value,
        movement_warehouse_id: movementWarehouseId.value,
        movement_search: movementSearch.value,
        low_stock_search: lowStockSearch.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function resetMovementsFilter() {
    movementType.value = '';
    movementWarehouseId.value = '';
    movementSearch.value = '';
    applyFilter();
}

function printReport() {
    window.print();
}
</script>

<template>
    <AppLayout>
        <Head title="Inventory Analytics & Reports" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Inventory Analytics &amp; Reports</h1>
                    <p class="text-xs text-slate-500 font-medium">Real-time asset valuation, low-stock reorder alerts, and comprehensive movement audit trails</p>
                </div>
                <button
                    type="button"
                    @click="printReport"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Print Report</span>
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Selector Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
                <div class="inline-flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button
                        type="button"
                        @click="setTab('valuation')"
                        :class="currentTab === 'valuation' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Stock Valuation</span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('low_stock')"
                        :class="currentTab === 'low_stock' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Low Stock Radar</span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('movements')"
                        :class="currentTab === 'movements' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span>Stock Movements</span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: STOCK VALUATION -->
            <div v-if="currentTab === 'valuation'" class="space-y-6">
                <!-- 4 Top KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Units -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Units On Hand</span>
                            <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 mt-3">
                            {{ Number(valuation?.total_units || 0).toLocaleString() }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1">Across all active facilities</div>
                    </div>

                    <!-- Total Cost Valuation -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Asset Value (At Cost)</span>
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-indigo-600 mt-3">
                            <CurrencyDisplay :amount="valuation?.total_cost || 0" />
                        </div>
                        <div class="text-xs text-slate-400 mt-1">Capital invested in physical stock</div>
                    </div>

                    <!-- Retail Value -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Potential Sales Value</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-emerald-600 mt-3">
                            <CurrencyDisplay :amount="valuation?.total_retail || 0" />
                        </div>
                        <div class="text-xs text-slate-400 mt-1">Based on active selling prices</div>
                    </div>

                    <!-- Profit & Margin -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Projected Gross Margin</span>
                            <div class="w-8 h-8 rounded-lg bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-purple-600 mt-3">
                            {{ Number(valuation?.margin_percent || 0).toFixed(1) }}%
                        </div>
                        <div class="text-xs text-slate-400 mt-1">
                            +<CurrencyDisplay :amount="valuation?.potential_profit || 0" /> profit potential
                        </div>
                    </div>
                </div>

                <!-- Breakdown Tables Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Warehouse Breakdown -->
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">Valuation by Warehouse</h2>
                                <p class="text-xs text-slate-500">Inventory assets distributed across storage hubs.</p>
                            </div>
                            <span class="text-xs font-mono text-indigo-600 font-semibold">
                                {{ valuation?.warehouse_breakdown?.length || 0 }} Hubs
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                                    <tr>
                                        <th class="px-5 py-3">Warehouse Hub</th>
                                        <th class="px-5 py-3 text-center">Units</th>
                                        <th class="px-5 py-3 text-right">Cost Value</th>
                                        <th class="px-5 py-3 text-right">Retail Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="wh in valuation?.warehouse_breakdown || []" :key="wh.id" class="hover:bg-slate-50 transition">
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-slate-900 text-sm">{{ wh.name }}</div>
                                            <div class="text-[10px] font-mono text-indigo-500">{{ wh.code }}</div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-slate-700">
                                            {{ Number(wh.units_count || 0).toLocaleString() }}
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-bold text-indigo-600">
                                            <CurrencyDisplay :amount="wh.cost_value || 0" />
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">
                                            <CurrencyDisplay :amount="wh.retail_value || 0" />
                                        </td>
                                    </tr>
                                    <tr v-if="!valuation?.warehouse_breakdown?.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-400">
                                            No warehouse stock data recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">Valuation by Category</h2>
                                <p class="text-xs text-slate-500">Capital allocation across product categories.</p>
                            </div>
                            <span class="text-xs font-mono text-purple-600 font-semibold">
                                {{ valuation?.category_breakdown?.length || 0 }} Categories
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                                    <tr>
                                        <th class="px-5 py-3">Category</th>
                                        <th class="px-5 py-3 text-center">Units</th>
                                        <th class="px-5 py-3 text-right">Cost Value</th>
                                        <th class="px-5 py-3 text-right">Retail Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="cat in valuation?.category_breakdown || []" :key="cat.category_name" class="hover:bg-slate-50 transition">
                                        <td class="px-5 py-3.5 font-bold text-slate-900 text-sm">
                                            {{ cat.category_name }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-slate-700">
                                            {{ Number(cat.units_count || 0).toLocaleString() }}
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-bold text-indigo-600">
                                            <CurrencyDisplay :amount="cat.cost_value || 0" />
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">
                                            <CurrencyDisplay :amount="cat.retail_value || 0" />
                                        </td>
                                    </tr>
                                    <tr v-if="!valuation?.category_breakdown?.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-400">
                                            No category stock data recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: LOW-STOCK ALERTS -->
            <div v-if="currentTab === 'low_stock'" class="space-y-6">
                <!-- Search & Actions Bar -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            v-model="lowStockSearch"
                            @input="handleSearchDebounced"
                            class="w-full bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 transition"
                            placeholder="Search low stock SKU or title..."
                        >
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                        <span class="text-xs text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl font-medium flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            {{ lowStockItems?.length || 0 }} Items Require Restocking
                        </span>
                        <Link
                            :href="route('purchases.create')"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shadow-sm inline-flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Create Purchase Order</span>
                        </Link>
                    </div>
                </div>

                <!-- Low Stock Table -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5">Product &amp; SKU</th>
                                    <th class="px-5 py-3.5">Category</th>
                                    <th class="px-5 py-3.5 text-center">Current Stock</th>
                                    <th class="px-5 py-3.5 text-center">Reorder Threshold</th>
                                    <th class="px-5 py-3.5 text-center">Shortage / Deficit</th>
                                    <th class="px-5 py-3.5 text-right">Unit Cost</th>
                                    <th class="px-5 py-3.5 text-right">Estimated Restock Cost</th>
                                    <th class="px-5 py-3.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in lowStockItems || []" :key="item.product.id" class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900 text-sm">{{ item.product.name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ item.product.sku }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-500">
                                        {{ item.product.category?.name || '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span
                                            :class="item.current_stock <= 0 ? 'bg-rose-100 text-rose-700 border-rose-200' : 'bg-amber-100 text-amber-800 border-amber-200'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border"
                                        >
                                            {{ Number(item.current_stock).toLocaleString() }} {{ item.product.unit || 'units' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center font-semibold text-slate-600">
                                        {{ Number(item.reorder_level).toLocaleString() }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="font-bold text-rose-600">
                                            -{{ Number(item.deficit).toLocaleString() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-medium text-slate-500">
                                        <CurrencyDisplay :amount="item.cost_price || 0" />
                                    </td>
                                    <td class="px-5 py-4 text-right font-bold text-amber-600">
                                        <CurrencyDisplay :amount="item.estimated_restock_cost || 0" />
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <Link
                                            :href="route('purchases.create')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition"
                                        >
                                            <span>Restock</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!lowStockItems?.length">
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                            <div class="text-slate-800 font-bold text-sm">All Inventory Levels Healthy</div>
                                            <div class="text-xs text-slate-400">No products are currently at or below their reorder threshold.</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: STOCK MOVEMENT AUDIT LEDGER -->
            <div v-if="currentTab === 'movements'" class="space-y-6">
                <!-- Filter Toolbar -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-2">
                    <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                        <!-- Search input -->
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                v-model="movementSearch"
                                @input="handleSearchDebounced"
                                class="w-full bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 transition"
                                placeholder="Search reason or SKU..."
                            >
                        </div>

                        <!-- Type Filter -->
                        <select
                            v-model="movementType"
                            @change="applyFilter"
                            class="w-full sm:w-44 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[150px] py-2 text-xs text-slate-700 transition"
                        >
                            <option value="">All Movement Types</option>
                            <option value="in">Inbound (Stock In)</option>
                            <option value="out">Outbound (Stock Out)</option>
                            <option value="transfer">Warehouse Transfer</option>
                            <option value="adjustment">Manual Adjustment</option>
                        </select>

                        <!-- Warehouse Filter -->
                        <select
                            v-model="movementWarehouseId"
                            @change="applyFilter"
                            class="w-full sm:w-44 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[150px] py-2 text-xs text-slate-700 transition"
                        >
                            <option value="">All Warehouses</option>
                            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                {{ wh.name }}
                            </option>
                        </select>

                        <!-- Reset Filter -->
                        <button
                            v-if="movementType || movementWarehouseId || movementSearch"
                            @click="resetMovementsFilter"
                            type="button"
                            class="text-xs text-slate-500 hover:text-slate-800 underline transition"
                        >
                            Reset
                        </button>
                    </div>

                    <div class="text-xs text-slate-400 font-mono">
                        Showing {{ movements?.total || 0 }} recorded audit events
                    </div>
                </div>

                <!-- Ledger Table -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5">Date</th>
                                    <th class="px-5 py-3.5 text-center">Type</th>
                                    <th class="px-5 py-3.5">Product &amp; SKU</th>
                                    <th class="px-5 py-3.5">Warehouse Hub</th>
                                    <th class="px-5 py-3.5 text-center">Quantity</th>
                                    <th class="px-5 py-3.5 text-right">Unit Cost</th>
                                    <th class="px-5 py-3.5">Reference / Reason</th>
                                    <th class="px-5 py-3.5">Operator</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="m in movements?.data || []" :key="m.id" class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                                        {{ formatDate(m.created_at) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span
                                            v-if="m.type === 'in'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            + Stock In
                                        </span>
                                        <span
                                            v-else-if="m.type === 'out'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-600 border border-rose-200"
                                        >
                                            - Stock Out
                                        </span>
                                        <span
                                            v-else-if="m.type === 'transfer'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-50 text-sky-600 border border-sky-200"
                                        >
                                            Transfer
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Adjustment
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900 text-sm">{{ m.product?.name || 'Product' }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ m.product?.sku || '—' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-medium">
                                        {{ m.warehouse?.name || '—' }} ({{ m.warehouse?.code || 'WH' }})
                                    </td>
                                    <td
                                        :class="m.type === 'in' ? 'text-emerald-600' : (m.type === 'out' ? 'text-rose-600' : 'text-slate-700')"
                                        class="px-5 py-3.5 text-center font-extrabold text-sm"
                                    >
                                        {{ m.type === 'in' ? '+' : (m.type === 'out' ? '-' : '') }}{{ Number(m.quantity).toLocaleString() }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-400">
                                        <CurrencyDisplay v-if="m.unit_cost" :amount="m.unit_cost" />
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-xs truncate">
                                        <div class="text-slate-800 font-medium truncate">{{ m.reason || 'Standard stock transaction' }}</div>
                                        <div v-if="m.reference_type" class="text-[10px] text-slate-400 font-mono">
                                            {{ m.reference_type.split('\\').pop() }} #{{ m.reference_id }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-400 text-[11px] whitespace-nowrap">
                                        {{ m.creator?.name || 'System' }}
                                    </td>
                                </tr>
                                <tr v-if="!movements?.data?.length">
                                    <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                        No stock movements recorded matching your filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="movements?.links?.length > 3" class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                        <span class="text-xs text-slate-500">
                            Showing {{ movements.from || 0 }} to {{ movements.to || 0 }} of {{ movements.total || 0 }}
                        </span>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, i) in movements.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="link.active ? 'bg-indigo-600 text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                                    class="px-3 py-1.5 text-xs rounded-lg transition"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="px-3 py-1.5 text-xs rounded-lg text-slate-300 border border-slate-100 cursor-not-allowed"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
