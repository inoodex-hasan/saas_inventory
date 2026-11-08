<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import { formatDate } from '@/Utils/date';
import { useTranslation } from '@/Utils/translations';

const { t } = useTranslation();

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
    expiryData: Object,
    agingData: Object,
    profitabilityData: Object,
});

const currentTab = ref(props.filters?.tab || props.activeTab || 'valuation');
const movementType = ref(props.filters?.movement_type || '');
const movementWarehouseId = ref(props.filters?.movement_warehouse_id || '');
const movementSearch = ref(props.filters?.movement_search || '');
const lowStockSearch = ref(props.filters?.low_stock_search || '');
const datePreset = ref(props.filters?.date_preset || 'all_time');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const expiryStatus = ref(props.filters?.expiry_status || 'all');
const expiryWarehouseId = ref(props.filters?.expiry_warehouse_id || '');
const expirySearch = ref(props.filters?.expiry_search || '');

const agingBracket = ref(props.filters?.aging_bracket || 'all');
const agingCategoryId = ref(props.filters?.aging_category_id || '');
const agingSearch = ref(props.filters?.aging_search || '');

const profitabilityPerformance = ref(props.filters?.profitability_performance || 'all');
const profitabilityCategoryId = ref(props.filters?.profitability_category_id || '');
const profitabilitySearch = ref(props.filters?.profitability_search || '');

const datePresets = [
    { id: 'all_time', label: 'All Time' },
    { id: 'today', label: 'Today' },
    { id: 'this_week', label: 'This Week' },
    { id: 'this_month', label: 'This Month' },
    { id: 'last_month', label: 'Last Month' },
    { id: 'this_year', label: 'This Year' },
    { id: 'custom', label: 'Custom' },
];

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
    const params = { tab: currentTab.value };
    if (movementType.value) params.movement_type = movementType.value;
    if (movementWarehouseId.value) params.movement_warehouse_id = movementWarehouseId.value;
    if (movementSearch.value) params.movement_search = movementSearch.value;
    if (lowStockSearch.value) params.low_stock_search = lowStockSearch.value;
    if (datePreset.value && datePreset.value !== 'all_time') params.date_preset = datePreset.value;
    if (datePreset.value === 'custom') {
        if (startDate.value) params.start_date = startDate.value;
        if (endDate.value) params.end_date = endDate.value;
    }
    if (expiryStatus.value && expiryStatus.value !== 'all') params.expiry_status = expiryStatus.value;
    if (expiryWarehouseId.value) params.expiry_warehouse_id = expiryWarehouseId.value;
    if (expirySearch.value) params.expiry_search = expirySearch.value;
    if (agingBracket.value && agingBracket.value !== 'all') params.aging_bracket = agingBracket.value;
    if (agingCategoryId.value) params.aging_category_id = agingCategoryId.value;
    if (agingSearch.value) params.aging_search = agingSearch.value;
    if (profitabilityPerformance.value && profitabilityPerformance.value !== 'all') params.profitability_performance = profitabilityPerformance.value;
    if (profitabilityCategoryId.value) params.profitability_category_id = profitabilityCategoryId.value;
    if (profitabilitySearch.value) params.profitability_search = profitabilitySearch.value;

    router.get(route('reports.index'), params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function resetMovementsFilter() {
    movementType.value = '';
    movementWarehouseId.value = '';
    movementSearch.value = '';
    datePreset.value = 'all_time';
    startDate.value = '';
    endDate.value = '';
    router.get(route('reports.index'), { tab: currentTab.value }, { preserveState: false, replace: true, preserveScroll: true });
}

function resetExpiryFilter() {
    expiryStatus.value = 'all';
    expiryWarehouseId.value = '';
    expirySearch.value = '';
    applyFilter();
}

function resetAgingFilter() {
    agingBracket.value = 'all';
    agingCategoryId.value = '';
    agingSearch.value = '';
    applyFilter();
}

function resetProfitabilityFilter() {
    profitabilityPerformance.value = 'all';
    profitabilityCategoryId.value = '';
    profitabilitySearch.value = '';
    datePreset.value = 'all_time';
    startDate.value = '';
    endDate.value = '';
    applyFilter();
}

function buildExportParams() {
    const params = new URLSearchParams();
    params.append('tab', currentTab.value);
    if (movementType.value) params.append('movement_type', movementType.value);
    if (movementWarehouseId.value) params.append('movement_warehouse_id', movementWarehouseId.value);
    if (movementSearch.value) params.append('movement_search', movementSearch.value);
    if (lowStockSearch.value) params.append('low_stock_search', lowStockSearch.value);
    if (datePreset.value && datePreset.value !== 'all_time') params.append('date_preset', datePreset.value);
    if (datePreset.value === 'custom') {
        if (startDate.value) params.append('start_date', startDate.value);
        if (endDate.value) params.append('end_date', endDate.value);
    }
    if (expiryStatus.value && expiryStatus.value !== 'all') params.append('expiry_status', expiryStatus.value);
    if (expiryWarehouseId.value) params.append('expiry_warehouse_id', expiryWarehouseId.value);
    if (expirySearch.value) params.append('expiry_search', expirySearch.value);
    if (agingBracket.value && agingBracket.value !== 'all') params.append('aging_bracket', agingBracket.value);
    if (agingCategoryId.value) params.append('aging_category_id', agingCategoryId.value);
    if (agingSearch.value) params.append('aging_search', agingSearch.value);
    if (profitabilityPerformance.value && profitabilityPerformance.value !== 'all') params.append('profitability_performance', profitabilityPerformance.value);
    if (profitabilityCategoryId.value) params.append('profitability_category_id', profitabilityCategoryId.value);
    if (profitabilitySearch.value) params.append('profitability_search', profitabilitySearch.value);
    return params.toString();
}

function downloadPdf() {
    window.open(route('reports.pdf') + '?' + buildExportParams(), '_blank');
}

function downloadExcel() {
    window.open(route('reports.excel') + '?' + buildExportParams(), '_blank');
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

                    <button
                        type="button"
                        @click="setTab('expiry')"
                        :class="currentTab === 'expiry' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ t('Batch Expiry') }}</span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('aging')"
                        :class="currentTab === 'aging' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>{{ t('Stock Aging') }}</span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('profitability')"
                        :class="currentTab === 'profitability' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>{{ t('Sales & Profit Margin') }}</span>
                    </button>
                </div>

                <!-- Export Actions: Excel & PDF -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="downloadExcel"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-600/30 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
                        :title="t('Download Excel')"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ t('Download Excel') }}</span>
                    </button>

                    <button
                        type="button"
                        @click="downloadPdf"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
                        :title="t('Download PDF')"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>{{ t('Download PDF') }}</span>
                    </button>
                </div>
            </div>

            <!-- Date Range Filter Toolbar (Visible when Movements or Profitability tab active) -->
            <div v-if="currentTab === 'movements' || currentTab === 'profitability'" class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-500 mr-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ t('Date Range') }}:
                    </span>
                    <button
                        v-for="preset in datePresets"
                        :key="preset.id"
                        type="button"
                        @click="datePreset = preset.id; if (preset.id !== 'custom') applyFilter();"
                        :class="datePreset === preset.id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                    >
                        {{ t(preset.label) }}
                    </button>
                </div>

                <div v-if="datePreset === 'custom'" class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-slate-400 font-medium">{{ t('From Date') }}:</span>
                        <input
                            type="date"
                            v-model="startDate"
                            class="bg-white border border-slate-300 rounded-xl px-2.5 py-1 text-xs text-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-slate-400 font-medium">{{ t('To Date') }}:</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="bg-white border border-slate-300 rounded-xl px-2.5 py-1 text-xs text-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <button
                        type="button"
                        @click="applyFilter"
                        class="px-3 py-1 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs cursor-pointer"
                    >
                        {{ t('Apply') }}
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
                                    <th class="px-5 py-3.5 whitespace-nowrap">Date</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Type</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Product &amp; SKU</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Warehouse Hub</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Quantity</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Unit Cost</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Reference / Reason</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Operator</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="m in movements?.data || []" :key="m.id" class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                                        {{ formatDate(m.created_at) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
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
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-sm max-w-[180px] lg:max-w-[240px] truncate" :title="m.product?.name || 'Product'">{{ m.product?.name || 'Product' }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ m.product?.sku || '—' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                        {{ m.warehouse?.name || '—' }} ({{ m.warehouse?.code || 'WH' }})
                                    </td>
                                    <td
                                        :class="m.type === 'in' ? 'text-emerald-600' : (m.type === 'out' ? 'text-rose-600' : 'text-slate-700')"
                                        class="px-5 py-3.5 text-center font-extrabold text-sm whitespace-nowrap"
                                    >
                                        {{ m.type === 'in' ? '+' : (m.type === 'out' ? '-' : '') }}{{ Number(m.quantity).toLocaleString() }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-400 whitespace-nowrap">
                                        <CurrencyDisplay v-if="m.unit_cost" :amount="m.unit_cost" />
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-[200px] lg:max-w-[260px] truncate" :title="m.reason || 'Standard stock transaction'">
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

            <!-- Tab 4: Batch & Expiry Radar -->
            <div v-if="currentTab === 'expiry'" class="space-y-6">
                <!-- Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-rose-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-rose-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">{{ t('Expired Batches') }}</span>
                                <div class="text-2xl font-black text-rose-700 mt-1">
                                    {{ (expiryData?.summary?.expired_count || 0).toLocaleString() }}
                                </div>
                                <span class="text-[11px] text-rose-500 font-medium">Critical immediate action</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-rose-100 text-rose-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-amber-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">{{ t('Expiring (< 30 Days)') }}</span>
                                <div class="text-2xl font-black text-amber-800 mt-1">
                                    {{ (expiryData?.summary?.expiring_30_count || 0).toLocaleString() }}
                                </div>
                                <span class="text-[11px] text-amber-600 font-medium">Discount or prioritize sales</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-amber-100 text-amber-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-sky-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-sky-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700">{{ t('Warning (< 90 Days)') }}</span>
                                <div class="text-2xl font-black text-sky-800 mt-1">
                                    {{ (expiryData?.summary?.expiring_90_count || 0).toLocaleString() }}
                                </div>
                                <span class="text-[11px] text-sky-600 font-medium">Monitor movement rate</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-sky-100 text-sky-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-indigo-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-indigo-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">{{ t('Capital at Risk') }}</span>
                                <div class="text-2xl font-black text-indigo-900 mt-1">
                                    <CurrencyDisplay :amount="expiryData?.summary?.at_risk_cost || 0" />
                                </div>
                                <span class="text-[11px] text-indigo-500 font-medium">Expired &amp; Urgent inventory cost</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-indigo-100 text-indigo-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expiry Filter Toolbar & Table Card -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                            <!-- Search -->
                            <div class="relative flex-1 sm:max-w-xs">
                                <input
                                    type="text"
                                    v-model="expirySearch"
                                    @input="handleSearchDebounced"
                                    placeholder="Search batch #, product or SKU..."
                                    class="w-full bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 transition"
                                />
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <!-- Expiry Status Filter -->
                            <select
                                v-model="expiryStatus"
                                @change="applyFilter"
                                class="w-full sm:w-44 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[150px] py-2 text-xs text-slate-700 transition"
                            >
                                <option value="all">{{ t('All Batches') }}</option>
                                <option value="expired">Expired</option>
                                <option value="expiring_30">Urgent (&lt; 30 Days)</option>
                                <option value="expiring_90">Warning (&lt; 90 Days)</option>
                                <option value="healthy">Healthy</option>
                            </select>

                            <!-- Warehouse Filter -->
                            <select
                                v-model="expiryWarehouseId"
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
                                v-if="(expiryStatus && expiryStatus !== 'all') || expiryWarehouseId || expirySearch"
                                @click="resetExpiryFilter"
                                type="button"
                                class="text-xs text-slate-500 hover:text-slate-800 underline transition cursor-pointer"
                            >
                                Reset
                            </button>
                        </div>

                        <div class="text-xs text-slate-400 font-mono">
                            Showing {{ expiryData?.items?.length || 0 }} recorded batches
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="px-5 py-3.5 whitespace-nowrap">{{ t('Batch #') }}</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Product &amp; SKU</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Warehouse Hub</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Quantity</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Unit Cost</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Total Cost</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">{{ t('Expiry Date') }}</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">{{ t('Days Remaining') }}</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in expiryData?.items || []" :key="item.id" class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap font-mono font-bold text-slate-900">
                                        {{ item.batch_number }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-sm max-w-[180px] lg:max-w-[240px] truncate" :title="item.product_name">{{ item.product_name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ item.sku }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                        {{ item.warehouse_name }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                                        {{ Number(item.quantity).toLocaleString() }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-500 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.unit_cost" />
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.total_cost" />
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[11px] text-slate-600">
                                        {{ formatDate(item.expiry_date) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap font-mono font-extrabold text-xs">
                                        <span v-if="item.days_remaining < 0" class="text-rose-600">
                                            {{ Math.abs(item.days_remaining) }}d overdue
                                        </span>
                                        <span v-else-if="item.days_remaining <= 30" class="text-amber-600">
                                            {{ item.days_remaining }}d left
                                        </span>
                                        <span v-else class="text-slate-700">
                                            {{ item.days_remaining }}d left
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span
                                            v-if="item.status_key === 'expired'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200"
                                        >
                                            Expired
                                        </span>
                                        <span
                                            v-else-if="item.status_key === 'expiring_30'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Urgent (&lt; 30d)
                                        </span>
                                        <span
                                            v-else-if="item.status_key === 'expiring_90'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-50 text-sky-700 border border-sky-200"
                                        >
                                            Warning (&lt; 90d)
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            Healthy
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!expiryData?.items?.length">
                                    <td colspan="9" class="px-5 py-8 text-center text-slate-400">
                                        No batch expiry records matching your filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Stock Aging & Dead Stock -->
            <div v-if="currentTab === 'aging'" class="space-y-6">
                <!-- Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-emerald-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">{{ t('Fresh (0-30 Days)') }}</span>
                                <div class="text-2xl font-black text-emerald-800 mt-1">
                                    {{ (agingData?.summary?.fresh_count || 0).toLocaleString() }}
                                </div>
                                <span class="text-[11px] text-emerald-600 font-medium">Fast-moving active inventory</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-emerald-100 text-emerald-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-sky-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-sky-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700">{{ t('Moderate (31-60 Days)') }}</span>
                                <div class="text-2xl font-black text-sky-800 mt-1">
                                    {{ (agingData?.summary?.moderate_count || 0).toLocaleString() }}
                                </div>
                                <span class="text-[11px] text-sky-600 font-medium">Healthy turnover range</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-sky-100 text-sky-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-amber-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">{{ t('Slow-Moving (61-90 Days)') }}</span>
                                <div class="text-2xl font-black text-amber-800 mt-1">
                                    {{ (agingData?.summary?.slow_count || 0).toLocaleString() }}
                                </div>
                                <span class="text-[11px] text-amber-600 font-medium">Requires marketing push</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-amber-100 text-amber-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-rose-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-rose-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">{{ t('Dead Stock (90+ Days)') }}</span>
                                <div class="text-2xl font-black text-rose-700 mt-1">
                                    {{ (agingData?.summary?.dead_count || 0).toLocaleString() }}
                                </div>
                                <div class="text-[11px] text-rose-600 font-medium flex items-center gap-1 mt-0.5">
                                    <span>{{ t('Frozen Capital') }}:</span>
                                    <span class="font-bold"><CurrencyDisplay :amount="agingData?.summary?.dead_stock_cost || 0" /></span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-rose-100 text-rose-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aging Filter Toolbar & Table Card -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                            <!-- Search -->
                            <div class="relative flex-1 sm:max-w-xs">
                                <input
                                    type="text"
                                    v-model="agingSearch"
                                    @input="handleSearchDebounced"
                                    placeholder="Search product name or SKU..."
                                    class="w-full bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 transition"
                                />
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <!-- Aging Bracket Filter -->
                            <select
                                v-model="agingBracket"
                                @change="applyFilter"
                                class="w-full sm:w-44 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[150px] py-2 text-xs text-slate-700 transition"
                            >
                                <option value="all">{{ t('All Brackets') }}</option>
                                <option value="fresh">Fresh (0-30 Days)</option>
                                <option value="moderate">Moderate (31-60 Days)</option>
                                <option value="slow">Slow-Moving (61-90 Days)</option>
                                <option value="dead">Dead Stock (90+ Days)</option>
                            </select>

                            <!-- Category Filter -->
                            <select
                                v-model="agingCategoryId"
                                @change="applyFilter"
                                class="w-full sm:w-44 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[150px] py-2 text-xs text-slate-700 transition"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>

                            <!-- Reset Filter -->
                            <button
                                v-if="(agingBracket && agingBracket !== 'all') || agingCategoryId || agingSearch"
                                @click="resetAgingFilter"
                                type="button"
                                class="text-xs text-slate-500 hover:text-slate-800 underline transition cursor-pointer"
                            >
                                Reset
                            </button>
                        </div>

                        <div class="text-xs text-slate-400 font-mono">
                            Showing {{ agingData?.items?.length || 0 }} tracked items
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Product &amp; SKU</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Category</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">Stock on Hand</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Unit Cost</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">{{ t('Frozen Capital') }}</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">{{ t('Days Inactive') }}</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Last Outbound / Sale</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">{{ t('Aging Bracket') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in agingData?.items || []" :key="item.id" class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-sm max-w-[200px] lg:max-w-[260px] truncate" :title="item.name">{{ item.name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ item.sku }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                        {{ item.category_name || 'Uncategorized' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                                        {{ Number(item.total_stock).toLocaleString() }} <span class="text-[10px] text-slate-400 font-normal">{{ item.unit || '' }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-500 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.cost_price" />
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.total_cost" />
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap font-mono font-extrabold text-xs">
                                        <span v-if="item.days_inactive > 90" class="text-rose-600">
                                            {{ item.days_inactive }}d
                                        </span>
                                        <span v-else-if="item.days_inactive > 60" class="text-amber-600">
                                            {{ item.days_inactive }}d
                                        </span>
                                        <span v-else class="text-slate-700">
                                            {{ item.days_inactive }}d
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[11px] text-slate-600">
                                        {{ item.last_activity_date ? formatDate(item.last_activity_date) : 'No recorded movement' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span
                                            v-if="item.bracket_key === 'dead'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200"
                                        >
                                            Dead (90+d)
                                        </span>
                                        <span
                                            v-else-if="item.bracket_key === 'slow'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Slow (61-90d)
                                        </span>
                                        <span
                                            v-else-if="item.bracket_key === 'moderate'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-50 text-sky-700 border border-sky-200"
                                        >
                                            Moderate (31-60d)
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            Fresh (0-30d)
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!agingData?.items?.length">
                                    <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                        No stock aging records matching your filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 6: Product Sales & Gross Margin Profitability -->
            <div v-if="currentTab === 'profitability'" class="space-y-6">
                <!-- Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-emerald-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">{{ t('Total Revenue') }}</span>
                                <div class="text-2xl font-black text-emerald-800 mt-1">
                                    <CurrencyDisplay :amount="profitabilityData?.summary?.total_revenue || 0" />
                                </div>
                                <span class="text-[11px] text-emerald-600 font-medium">Across active sales orders</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-emerald-100 text-emerald-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-slate-100 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">{{ t('Total COGS') }}</span>
                                <div class="text-2xl font-black text-slate-800 mt-1">
                                    <CurrencyDisplay :amount="profitabilityData?.summary?.total_cogs || 0" />
                                </div>
                                <span class="text-[11px] text-slate-500 font-medium">Acquisition cost basis</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-100 text-slate-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-indigo-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-indigo-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">{{ t('Gross Profit') }}</span>
                                <div class="text-2xl font-black text-indigo-900 mt-1">
                                    <CurrencyDisplay :amount="profitabilityData?.summary?.total_gross_profit || 0" />
                                </div>
                                <span class="text-[11px] text-indigo-500 font-medium">Net trading surplus</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-indigo-100 text-indigo-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-sky-100 shadow-xs relative overflow-hidden">
                        <div class="absolute -right-3 -top-3 w-16 h-16 bg-sky-50 rounded-full opacity-60"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700">{{ t('Gross Margin') }}</span>
                                <div class="text-2xl font-black text-sky-900 mt-1">
                                    {{ profitabilityData?.summary?.overall_margin_percent || 0 }}%
                                </div>
                                <div class="text-[11px] text-sky-600 font-medium">
                                    {{ profitabilityData?.summary?.high_count || 0 }} high margin, {{ profitabilityData?.summary?.loss_count || 0 }} loss
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-sky-100 text-sky-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profitability Filter Toolbar & Table Card -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                            <!-- Search -->
                            <div class="relative flex-1 sm:max-w-xs">
                                <input
                                    type="text"
                                    v-model="profitabilitySearch"
                                    @input="handleSearchDebounced"
                                    placeholder="Search product name or SKU..."
                                    class="w-full bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 transition"
                                />
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <!-- Performance Level Filter -->
                            <select
                                v-model="profitabilityPerformance"
                                @change="applyFilter"
                                class="w-full sm:w-48 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[160px] py-2 text-xs text-slate-700 transition"
                            >
                                <option value="all">{{ t('All Performance Levels') }}</option>
                                <option value="high">High Margin (&gt;= 40%)</option>
                                <option value="healthy">Healthy (20-39%)</option>
                                <option value="low">Low Margin (1-19%)</option>
                                <option value="loss">Loss Making (&lt;= 0%)</option>
                            </select>

                            <!-- Category Filter -->
                            <select
                                v-model="profitabilityCategoryId"
                                @change="applyFilter"
                                class="w-full sm:w-44 bg-white border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-3 pr-8 min-w-[150px] py-2 text-xs text-slate-700 transition"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>

                            <!-- Reset Filter -->
                            <button
                                v-if="(profitabilityPerformance && profitabilityPerformance !== 'all') || profitabilityCategoryId || profitabilitySearch || (datePreset && datePreset !== 'all_time')"
                                @click="resetProfitabilityFilter"
                                type="button"
                                class="text-xs text-slate-500 hover:text-slate-800 underline transition cursor-pointer"
                            >
                                Reset
                            </button>
                        </div>

                        <div class="text-xs text-slate-400 font-mono">
                            Showing {{ profitabilityData?.items?.length || 0 }} product lines
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Product &amp; SKU</th>
                                    <th class="px-5 py-3.5 whitespace-nowrap">Category</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">{{ t('Units Sold') }}</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">{{ t('Avg Selling Price') }}</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">{{ t('Revenue') }}</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">{{ t('COGS') }}</th>
                                    <th class="px-5 py-3.5 text-right whitespace-nowrap">{{ t('Gross Profit') }}</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap" style="min-width: 140px;">{{ t('Margin %') }}</th>
                                    <th class="px-5 py-3.5 text-center whitespace-nowrap">{{ t('Performance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in profitabilityData?.items || []" :key="item.id" class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-sm max-w-[200px] lg:max-w-[260px] truncate" :title="item.product_name">{{ item.product_name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ item.sku }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                        {{ item.category_name }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                                        {{ Number(item.units_sold).toLocaleString() }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-500 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.avg_selling_price" />
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.total_revenue" />
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-500 whitespace-nowrap">
                                        <CurrencyDisplay :amount="item.total_cogs" />
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold whitespace-nowrap" :class="item.gross_profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                        <CurrencyDisplay :amount="item.gross_profit" />
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center gap-2 justify-center">
                                            <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :class="item.margin_percent >= 40 ? 'bg-emerald-500' : (item.margin_percent >= 20 ? 'bg-sky-500' : (item.margin_percent > 0 ? 'bg-amber-500' : 'bg-rose-500'))"
                                                    :style="{ width: `${Math.min(100, Math.max(0, item.margin_percent))}%` }"
                                                ></div>
                                            </div>
                                            <span class="font-mono font-bold text-xs" :class="item.margin_percent >= 40 ? 'text-emerald-700' : (item.margin_percent >= 20 ? 'text-sky-700' : (item.margin_percent > 0 ? 'text-amber-700' : 'text-rose-700'))">
                                                {{ item.margin_percent }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span
                                            v-if="item.perf_key === 'high'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            High (&gt;= 40%)
                                        </span>
                                        <span
                                            v-else-if="item.perf_key === 'healthy'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-50 text-sky-700 border border-sky-200"
                                        >
                                            Healthy (20-39%)
                                        </span>
                                        <span
                                            v-else-if="item.perf_key === 'low'"
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Low (1-19%)
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200"
                                        >
                                            Loss Making
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!profitabilityData?.items?.length">
                                    <td colspan="9" class="px-5 py-8 text-center text-slate-400">
                                        No product sales records matching your selected date range and filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
