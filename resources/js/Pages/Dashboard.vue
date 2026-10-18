<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    totalValuation: Number,
    totalProducts: Number,
    totalSalesVolume: Number,
    totalSalesOrders: Number,
    pendingPurchasesCount: Number,
    openPurchaseOrders: Number,
    lowStockCount: Number,
    lowStockProducts: Array,
    recentSalesOrders: Array,
    recentPurchaseOrders: Array,
    recentStockMovements: Array,
    warehouseStockBreakdown: Array,
    salesTrend: {
        type: Array,
        default: () => [],
    },
    categoryDistribution: {
        type: Array,
        default: () => [],
    },
    orderStatusDistribution: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('sales');
const hoveredBar = ref(null);
const hoveredCategory = ref(null);

// Chart Colors Palette
const categoryPalette = [
    { bg: '#6366f1', text: 'text-indigo-600', fill: 'bg-indigo-500', border: 'border-indigo-500' },
    { bg: '#10b981', text: 'text-emerald-600', fill: 'bg-emerald-500', border: 'border-emerald-500' },
    { bg: '#f59e0b', text: 'text-amber-600', fill: 'bg-amber-500', border: 'border-amber-500' },
    { bg: '#06b6d4', text: 'text-cyan-600', fill: 'bg-cyan-500', border: 'border-cyan-500' },
    { bg: '#8b5cf6', text: 'text-purple-600', fill: 'bg-purple-500', border: 'border-purple-500' },
    { bg: '#ec4899', text: 'text-pink-600', fill: 'bg-pink-500', border: 'border-pink-500' },
];

// Max Value for Bar Chart Scaling
const maxTrendValue = computed(() => {
    if (!props.salesTrend || props.salesTrend.length === 0) return 10000;
    const max = Math.max(
        ...props.salesTrend.map(d => Math.max(Number(d.sales) || 0, Number(d.purchases) || 0))
    );
    return max > 0 ? Math.ceil(max * 1.25) : 10000;
});

// Y-Axis Ticks
const yAxisTicks = computed(() => {
    const max = maxTrendValue.value;
    return [
        Math.round(max),
        Math.round(max * 0.75),
        Math.round(max * 0.5),
        Math.round(max * 0.25),
        0
    ];
});

// Category Donut Slices (Radius = 70, Circumference = 439.82)
const circumference = 2 * Math.PI * 70;

const totalCategoryValuation = computed(() => {
    if (!props.categoryDistribution || props.categoryDistribution.length === 0) return props.totalValuation || 0;
    return props.categoryDistribution.reduce((acc, cat) => acc + (Number(cat.valuation) || 0), 0);
});

const donutSlices = computed(() => {
    const total = totalCategoryValuation.value;
    if (total <= 0) return [];

    let currentOffset = 0;
    return props.categoryDistribution.map((cat, idx) => {
        const val = Number(cat.valuation) || 0;
        const percentage = (val / total) * 100;
        const strokeLength = (val / total) * circumference;
        const slice = {
            name: cat.name,
            valuation: val,
            totalUnits: cat.total_units,
            percentage: percentage.toFixed(1),
            color: categoryPalette[idx % categoryPalette.length].bg,
            colorClass: categoryPalette[idx % categoryPalette.length].text,
            fillClass: categoryPalette[idx % categoryPalette.length].fill,
            strokeDasharray: `${strokeLength} ${circumference - strokeLength}`,
            strokeDashoffset: -currentOffset,
        };
        currentOffset += strokeLength;
        return slice;
    });
});

// Total orders count across all statuses
const totalOrdersInStatus = computed(() => {
    if (!props.orderStatusDistribution || props.orderStatusDistribution.length === 0) return props.totalSalesOrders || 1;
    return props.orderStatusDistribution.reduce((acc, o) => acc + (Number(o.count) || 0), 0);
});
</script>

<template>
    <AppLayout>
        <Head title="Admin Dashboard" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Admin Dashboard</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                            Live Telemetry
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Real-time inventory valuation, trends, analytics & operations</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Quick Actions Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Quick Operations</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link 
                        :href="route('sales.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Sales Order</span>
                    </Link>

                    <Link 
                        :href="route('purchases.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition-all hover:scale-[1.02]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Purchase PO</span>
                    </Link>

                    <Link 
                        :href="route('products.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs transition-all"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Add Product</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Holding Valuation -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-indigo-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-indigo-50/50 rounded-full blur-xl group-hover:bg-indigo-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Inventory Value</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            ৳
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight relative">
                        <CurrencyDisplay :amount="totalValuation" />
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        Across <strong class="text-slate-800">{{ totalProducts }}</strong> active catalog products
                    </p>
                </div>

                <!-- Monthly Sales Volume -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-emerald-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-emerald-50/50 rounded-full blur-xl group-hover:bg-emerald-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Sales Dispatched</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight relative">
                        <CurrencyDisplay :amount="totalSalesVolume" />
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        <strong class="text-slate-800">{{ totalSalesOrders }}</strong> total confirmed client orders
                    </p>
                </div>

                <!-- Inbound Procurement -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-amber-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-amber-50/50 rounded-full blur-xl group-hover:bg-amber-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inbound Purchases</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight relative">
                        {{ pendingPurchasesCount }}
                        <span class="text-sm font-semibold text-slate-400">Open POs</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        Worth <strong class="text-slate-800"><CurrencyDisplay :amount="openPurchaseOrders" /></strong>
                    </p>
                </div>

                <!-- Low Stock Alert Radar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-rose-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-rose-50/50 rounded-full blur-xl group-hover:bg-rose-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Threshold</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black relative" :class="lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900'">
                        {{ lowStockCount }}
                        <span class="text-sm font-semibold text-slate-400">Items Low</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        Needs purchase replenishment
                    </p>
                </div>
            </div>

            <!-- ATTRACTIVE CHARTS SECTION: Dual Column (Trend Graph & Category Donut Pie Chart) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- 1. Monthly Revenue & Procurement Bar/Curve Graph (7 Cols) -->
                <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                    <h2 class="text-sm font-bold text-slate-900">Revenue & Procurement Trends</h2>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Monthly sales turnover vs. inventory procurement outlay</p>
                            </div>

                            <!-- Legend Pills -->
                            <div class="flex items-center gap-3 text-xs font-semibold">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-md bg-indigo-600 shadow-xs"></span>
                                    <span class="text-slate-600">Sales Invoices</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-md bg-amber-500 shadow-xs"></span>
                                    <span class="text-slate-600">Purchase POs</span>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Bar Graph Container -->
                        <div class="relative pt-4">
                            <!-- Background Grid Lines -->
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-8 text-[10px] text-slate-400 font-mono">
                                <div v-for="(tick, index) in yAxisTicks" :key="index" class="border-b border-slate-100 w-full flex items-center justify-between pb-1">
                                    <span class="bg-white pr-2 text-slate-400 select-none">৳{{ tick >= 1000 ? (tick / 1000).toFixed(0) + 'k' : tick }}</span>
                                </div>
                            </div>

                            <!-- Chart Bars Area -->
                            <div class="h-64 pl-12 pr-2 pb-8 flex items-end justify-between gap-2 sm:gap-4 relative z-10">
                                <div 
                                    v-for="(trend, i) in salesTrend" 
                                    :key="i"
                                    class="flex-1 flex flex-col items-center h-full justify-end group cursor-pointer"
                                    @mouseenter="hoveredBar = trend"
                                    @mouseleave="hoveredBar = null"
                                >
                                    <!-- Tooltip Popup -->
                                    <div 
                                        v-if="hoveredBar && hoveredBar.month === trend.month"
                                        class="absolute -top-6 bg-slate-900 text-white text-[11px] font-semibold py-1.5 px-3 rounded-xl shadow-xl z-30 pointer-events-none whitespace-nowrap border border-slate-700 animate-in fade-in zoom-in-95 duration-150"
                                    >
                                        <div class="text-[10px] text-slate-400 font-bold mb-0.5">{{ trend.month }}</div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                            <span>Sales: ৳{{ Number(trend.sales).toLocaleString() }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                            <span>Purchases: ৳{{ Number(trend.purchases).toLocaleString() }}</span>
                                        </div>
                                    </div>

                                    <!-- Dual Bars -->
                                    <div class="w-full flex items-end justify-center gap-1.5 h-full">
                                        <!-- Sales Bar -->
                                        <div 
                                            class="w-3.5 sm:w-5 bg-gradient-to-t from-indigo-700 to-indigo-500 rounded-t-lg transition-all duration-300 group-hover:brightness-110 group-hover:scale-y-[1.03] origin-bottom shadow-xs relative"
                                            :style="{ height: `${Math.max((Number(trend.sales) / maxTrendValue) * 100, 3)}%` }"
                                        >
                                            <div class="absolute -top-5 left-1/2 -translate-x-1/2 text-[9px] font-bold text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                ৳{{ (trend.sales / 1000).toFixed(1) }}k
                                            </div>
                                        </div>

                                        <!-- Purchases Bar -->
                                        <div 
                                            class="w-3.5 sm:w-5 bg-gradient-to-t from-amber-600 to-amber-400 rounded-t-lg transition-all duration-300 group-hover:brightness-110 group-hover:scale-y-[1.03] origin-bottom shadow-xs relative"
                                            :style="{ height: `${Math.max((Number(trend.purchases) / maxTrendValue) * 100, 3)}%` }"
                                        >
                                            <div class="absolute -top-5 left-1/2 -translate-x-1/2 text-[9px] font-bold text-amber-600 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                ৳{{ (trend.purchases / 1000).toFixed(1) }}k
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Month Label -->
                                    <span class="mt-2 text-[11px] font-bold text-slate-500 group-hover:text-indigo-600 transition-colors uppercase tracking-tight">
                                        {{ trend.short_month || trend.month.substring(0, 3) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graph Footer Stats -->
                    <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500">
                        <div class="flex items-center gap-1.5 font-medium">
                            <span class="text-emerald-600 font-bold flex items-center">
                                &uarr; Active Trading
                            </span>
                            <span>Historical 6-month transaction flow</span>
                        </div>
                        <div class="text-[11px] font-mono text-slate-400">
                            Updated real-time from ledger
                        </div>
                    </div>
                </div>

                <!-- 2. Inventory by Category Donut / Pie Chart (5 Cols) -->
                <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <span>Inventory Holding by Category</span>
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">Portfolio distribution and capital allocation</p>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono">
                                {{ donutSlices.length }} Categories
                            </span>
                        </div>

                        <!-- Donut Graphic & Legend -->
                        <div class="flex flex-col sm:flex-row items-center gap-6 py-2">
                            <!-- SVG Donut Chart -->
                            <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
                                <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 200 200">
                                    <!-- Background Base Ring -->
                                    <circle
                                        cx="100"
                                        cy="100"
                                        r="70"
                                        fill="transparent"
                                        stroke="#f1f5f9"
                                        stroke-width="24"
                                    />

                                    <!-- Donut Segments -->
                                    <circle
                                        v-for="(slice, sIdx) in donutSlices"
                                        :key="sIdx"
                                        cx="100"
                                        cy="100"
                                        r="70"
                                        fill="transparent"
                                        :stroke="slice.color"
                                        stroke-width="24"
                                        :stroke-dasharray="slice.strokeDasharray"
                                        :stroke-dashoffset="slice.strokeDashoffset"
                                        class="transition-all duration-500 cursor-pointer hover:opacity-90 hover:stroke-[28px]"
                                        @mouseenter="hoveredCategory = slice"
                                        @mouseleave="hoveredCategory = null"
                                    />
                                </svg>

                                <!-- Central Donut Hole Info -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 pointer-events-none">
                                    <div v-if="hoveredCategory" class="animate-in fade-in duration-150">
                                        <span class="text-[10px] font-bold text-slate-400 block truncate max-w-[90px]">
                                            {{ hoveredCategory.name }}
                                        </span>
                                        <span class="text-sm font-black text-slate-900 block font-mono">
                                            {{ hoveredCategory.percentage }}%
                                        </span>
                                    </div>
                                    <div v-else class="text-center">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">
                                            Valuation
                                        </span>
                                        <span class="text-xs font-black text-slate-900 block font-mono">
                                            ৳{{ (totalCategoryValuation / 1000).toFixed(1) }}k
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Slices Breakdown List -->
                            <div class="flex-1 w-full space-y-2.5">
                                <div 
                                    v-for="(cat, cIdx) in donutSlices" 
                                    :key="cIdx"
                                    class="p-2 rounded-xl border transition-all text-xs flex items-center justify-between cursor-pointer"
                                    :class="hoveredCategory?.name === cat.name ? 'border-slate-400 bg-slate-50 shadow-xs' : 'border-slate-100 hover:bg-slate-50/60'"
                                    @mouseenter="hoveredCategory = cat"
                                    @mouseleave="hoveredCategory = null"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: cat.color }"></span>
                                        <span class="font-semibold text-slate-800 truncate text-[11px]">{{ cat.name }}</span>
                                    </div>
                                    <div class="text-right shrink-0 flex items-center gap-2">
                                        <span class="font-bold text-slate-900 font-mono text-[11px]">
                                            ৳{{ (cat.valuation / 1000).toFixed(1) }}k
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-100 text-slate-600">
                                            {{ cat.percentage }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 text-center">
                        <Link :href="route('products.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                            View Full Catalog & Categories &rarr;
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Warehouse Holding Distribution -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Warehouse & Hub Holding Breakdown
                        </h2>
                        <p class="text-xs text-slate-500">Capital valuation and physical inventory across active fulfillment locations</p>
                    </div>
                    <Link :href="route('warehouses.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                        Manage Hubs &rarr;
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
                    <div 
                        v-for="wh in warehouseStockBreakdown" 
                        :key="wh.id" 
                        class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-indigo-300 hover:shadow-md transition-all group"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-slate-900 text-xs truncate group-hover:text-indigo-600 transition-colors">{{ wh.name }}</span>
                            <span v-if="wh.is_default" class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                                Default
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-400 mb-2 font-mono">CODE: {{ wh.code || 'N/A' }}</div>
                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <span class="text-slate-500">Units: <strong class="text-slate-800 font-mono">{{ wh.total_units }}</strong></span>
                            <span class="font-bold text-slate-900 font-mono">
                                <CurrencyDisplay :amount="wh.total_valuation" />
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout: Low Stock Radar & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Low Stock Alert Radar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    Low Stock Radar
                                </h2>
                                <p class="text-xs text-slate-500">Products requiring purchase requisition</p>
                            </div>
                            <Link :href="route('products.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                                View Catalog &rarr;
                            </Link>
                        </div>

                        <div v-if="lowStockProducts && lowStockProducts.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px]">
                                        <th class="py-2.5">Product</th>
                                        <th class="py-2.5 text-center">Available</th>
                                        <th class="py-2.5 text-center">Reorder Lvl</th>
                                        <th class="py-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="item in lowStockProducts" :key="item.id" class="hover:bg-slate-50">
                                        <td class="py-2.5">
                                            <div class="font-bold text-slate-800">{{ item.name }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ item.sku }}</div>
                                        </td>
                                        <td class="py-2.5 text-center font-bold font-mono text-rose-600">
                                            {{ item.total_on_hand || 0 }} {{ item.unit || 'pcs' }}
                                        </td>
                                        <td class="py-2.5 text-center font-mono text-slate-500">
                                            {{ item.reorder_level }}
                                        </td>
                                        <td class="py-2.5 text-right">
                                            <Link :href="route('purchases.create')" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">
                                                Order PO &rarr;
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-10 text-slate-400 text-xs">
                            No low stock items. All inventory levels optimal.
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Tabs -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 w-full">
                            <button 
                                @click="activeTab = 'sales'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                :class="activeTab === 'sales' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Recent Sales
                            </button>
                            <button 
                                @click="activeTab = 'purchases'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                :class="activeTab === 'purchases' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Recent Inbound
                            </button>
                            <button 
                                @click="activeTab = 'movements'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                :class="activeTab === 'movements' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Stock Ledger
                            </button>
                        </div>
                    </div>

                    <!-- Sales Tab -->
                    <div v-if="activeTab === 'sales'" class="space-y-3">
                        <div 
                            v-for="so in recentSalesOrders" 
                            :key="so.id" 
                            class="p-3 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 flex items-center justify-between transition-all"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800 text-xs font-mono">{{ so.order_number }}</span>
                                    <StatusBadge :status="so.status" />
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ so.customer?.name || 'Walk-in Customer' }} &bull; {{ so.warehouse?.name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-slate-900 text-xs">
                                    <CurrencyDisplay :amount="so.total_amount" />
                                </div>
                                <StatusBadge :status="so.payment_status" class="scale-90 origin-right mt-0.5" />
                            </div>
                        </div>
                        <div v-if="!recentSalesOrders || recentSalesOrders.length === 0" class="text-center py-6 text-slate-400 text-xs">
                            No recent sales orders.
                        </div>
                    </div>

                    <!-- Purchases Tab -->
                    <div v-if="activeTab === 'purchases'" class="space-y-3">
                        <div 
                            v-for="po in recentPurchaseOrders" 
                            :key="po.id" 
                            class="p-3 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 flex items-center justify-between transition-all"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800 text-xs font-mono">{{ po.order_number || po.po_number }}</span>
                                    <StatusBadge :status="po.status" />
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ po.supplier?.name || 'Direct Vendor' }} &bull; {{ po.warehouse?.name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-slate-900 text-xs">
                                    <CurrencyDisplay :amount="po.total_amount" />
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ formatDate(po.order_date || po.ordered_at, 'Draft') }}</span>
                            </div>
                        </div>
                        <div v-if="!recentPurchaseOrders || recentPurchaseOrders.length === 0" class="text-center py-6 text-slate-400 text-xs">
                            No recent purchase orders.
                        </div>
                    </div>

                    <!-- Movements Tab -->
                    <div v-if="activeTab === 'movements'" class="space-y-2.5">
                        <div 
                            v-for="m in recentStockMovements" 
                            :key="m.id" 
                            class="p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 text-xs flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <span 
                                    class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-[10px]"
                                    :class="m.type === 'in' ? 'bg-emerald-100 text-emerald-700' : (m.type === 'out' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700')"
                                >
                                    {{ m.type.toUpperCase() }}
                                </span>
                                <div>
                                    <div class="font-semibold text-slate-800">{{ m.product?.name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ m.warehouse?.name }} &bull; {{ m.reason || 'Ledger entry' }}</div>
                                </div>
                            </div>
                            <div class="text-right font-mono">
                                <span 
                                    class="font-bold"
                                    :class="m.quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600'"
                                >
                                    {{ m.quantity_change > 0 ? '+' : '' }}{{ m.quantity_change }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">After: {{ m.quantity_after }}</span>
                            </div>
                        </div>
                        <div v-if="!recentStockMovements || recentStockMovements.length === 0" class="text-center py-6 text-slate-400 text-xs">
                            No ledger movements recorded yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
