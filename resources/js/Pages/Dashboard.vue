<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import { formatDate, formatRelative } from '@/Utils/date';
import { useTranslation } from '@/Utils/translations';
import apexchart from 'vue3-apexcharts';

const { t, currentLang } = useTranslation();
import { 
    Plus, 
    ShoppingCart, 
    Truck, 
    PackagePlus, 
    TrendingUp, 
    AlertTriangle, 
    Warehouse as WarehouseIcon, 
    BarChart3, 
    PieChart, 
    History, 
    AlertCircle, 
    ArrowRight,
    Boxes
} from 'lucide-vue-next';

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

const totalCategoryValuation = computed(() => {
    if (!props.categoryDistribution || props.categoryDistribution.length === 0) return props.totalValuation || 0;
    return props.categoryDistribution.reduce((acc, cat) => acc + (Number(cat.valuation) || 0), 0);
});

const donutSlices = computed(() => {
    const total = totalCategoryValuation.value;
    if (total <= 0) return [];

    return props.categoryDistribution.map((cat, idx) => {
        const val = Number(cat.valuation) || 0;
        const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : '0';
        return {
            name: cat.name,
            valuation: val,
            totalUnits: cat.total_units,
            percentage,
            color: categoryPalette[idx % categoryPalette.length].bg,
            colorClass: categoryPalette[idx % categoryPalette.length].text,
            fillClass: categoryPalette[idx % categoryPalette.length].fill,
        };
    });
});

// ApexCharts - 6-Month Revenue & Procurement Trend
const trendChartSeries = computed(() => [
    {
        name: 'Sales Invoices',
        data: (props.salesTrend || []).map(d => Number(d.sales) || 0),
    },
    {
        name: 'Purchase POs',
        data: (props.salesTrend || []).map(d => Number(d.purchases) || 0),
    },
]);

const trendChartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 250,
        toolbar: { show: false },
        fontFamily: 'inherit',
        sparkline: { enabled: false },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 600,
        },
    },
    colors: ['#4f46e5', '#f59e0b'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 90, 100],
        },
    },
    stroke: {
        curve: 'smooth',
        width: 2.5,
    },
    dataLabels: {
        enabled: false,
    },
    grid: {
        borderColor: '#f1f5f9',
        strokeDashArray: 4,
        padding: { top: 10, right: 10, bottom: 0, left: 10 },
    },
    xaxis: {
        categories: (props.salesTrend || []).map(d => d.short_month || (d.month ? d.month.substring(0, 3) : '')),
        labels: {
            style: {
                colors: '#64748b',
                fontSize: '11px',
                fontWeight: 600,
            },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: {
                colors: '#94a3b8',
                fontSize: '10px',
                fontWeight: 600,
            },
            formatter: (val) => val >= 1000 ? `৳${(val / 1000).toFixed(0)}k` : `৳${Number(val).toFixed(0)}`,
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val) => `৳${Number(val).toLocaleString()}`,
        },
    },
    legend: {
        show: false,
    },
}));

// ApexCharts - Inventory Holding by Category Donut
const donutChartSeries = computed(() => {
    if (!props.categoryDistribution || props.categoryDistribution.length === 0) return [1];
    return props.categoryDistribution.map(cat => Number(cat.valuation) || 0);
});

const donutChartLabels = computed(() => {
    if (!props.categoryDistribution || props.categoryDistribution.length === 0) return ['No Data'];
    return props.categoryDistribution.map(cat => cat.name);
});

const donutChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'inherit',
    },
    labels: donutChartLabels.value,
    colors: ['#6366f1', '#10b981', '#f59e0b', '#06b6d4', '#8b5cf6', '#ec4899', '#3b82f6'],
    dataLabels: {
        enabled: false,
    },
    stroke: {
        width: 2,
        colors: ['#ffffff'],
    },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#64748b',
                        offsetY: -4,
                    },
                    value: {
                        show: true,
                        fontSize: '14px',
                        fontWeight: 800,
                        color: '#0f172a',
                        offsetY: 4,
                        formatter: (val) => `৳${(Number(val) / 1000).toFixed(1)}k`,
                    },
                    total: {
                        show: true,
                        label: 'Valuation',
                        fontSize: '10px',
                        fontWeight: 700,
                        color: '#94a3b8',
                        formatter: () => `৳${(totalCategoryValuation.value / 1000).toFixed(1)}k`,
                    },
                },
            },
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val) => `৳${Number(val).toLocaleString()}`,
        },
    },
    legend: {
        show: false,
    },
}));

// Total orders count across all statuses
const totalOrdersInStatus = computed(() => {
    if (!props.orderStatusDistribution || props.orderStatusDistribution.length === 0) return props.totalSalesOrders || 1;
    return props.orderStatusDistribution.reduce((acc, o) => acc + (Number(o.count) || 0), 0);
});
</script>

<template>
    <AppLayout>
        <Head :title="t('Admin Dashboard')" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>{{ t('Admin Dashboard') }}</span>
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">{{ t('Real-time inventory valuation, trends, analytics & operations') }}</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Quick Actions Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ t('Quick Operations') }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link 
                        :href="route('sales.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ t('New Sales Order') }}</span>
                    </Link>

                    <Link 
                        :href="route('purchases.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition-all hover:scale-[1.02]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>{{ t('New Purchase') }}</span>
                    </Link>

                    <Link 
                        :href="route('products.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs transition-all"
                    >
                        <PackagePlus class="w-4 h-4 text-slate-500" />
                        <span>{{ t('Add Product') }}</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Holding Valuation -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-indigo-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-indigo-50/50 rounded-full blur-xl group-hover:bg-indigo-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t('Total Inventory Value') }}</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shadow-xs font-mono">
                            ৳
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight relative">
                        <CurrencyDisplay :amount="totalValuation" />
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        {{ t('Across') }} <strong class="text-slate-800">{{ totalProducts }}</strong> {{ t('active catalog products') }}
                    </p>
                </div>

                <!-- Monthly Sales Volume -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-emerald-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-emerald-50/50 rounded-full blur-xl group-hover:bg-emerald-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t('Total Sales Dispatched') }}</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            <TrendingUp class="w-5 h-5 text-emerald-600" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight relative">
                        <CurrencyDisplay :amount="totalSalesVolume" />
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        <strong class="text-slate-800">{{ totalSalesOrders }}</strong> {{ t('total confirmed client orders') }}
                    </p>
                </div>

                <!-- Inbound Procurement -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-amber-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-amber-50/50 rounded-full blur-xl group-hover:bg-amber-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t('Inbound Purchases') }}</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            <Truck class="w-5 h-5 text-amber-600" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight relative">
                        {{ pendingPurchasesCount }}
                        <span class="text-sm font-semibold text-slate-400">{{ t('Open POs') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        {{ t('Worth') }} <strong class="text-slate-800"><CurrencyDisplay :amount="openPurchaseOrders" /></strong>
                    </p>
                </div>

                <!-- Low Stock Alert Radar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-rose-400 hover:shadow-md transition-all">
                    <div class="absolute -right-3 -top-3 w-20 h-20 bg-rose-50/50 rounded-full blur-xl group-hover:bg-rose-100/60 transition-colors"></div>
                    <div class="flex items-center justify-between mb-3 relative">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t('Low Stock Threshold') }}</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm shadow-xs">
                            <AlertTriangle class="w-5 h-5 text-rose-600" />
                        </div>
                    </div>
                    <div class="text-2xl font-black relative" :class="lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900'">
                        {{ lowStockCount }}
                        <span class="text-sm font-semibold text-slate-400">{{ t('Items Low') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium relative">
                        {{ t('Needs purchase replenishment') }}
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
                                    <BarChart3 class="w-4 h-4 text-indigo-600" />
                                    <h2 class="text-sm font-bold text-slate-900">{{ t('Revenue & Procurement Trends') }}</h2>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">{{ t('Monthly sales turnover vs. inventory procurement outlay') }}</p>
                            </div>

                            <!-- Legend Pills -->
                            <div class="flex items-center gap-3 text-xs font-semibold">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-md bg-indigo-600 shadow-xs"></span>
                                    <span class="text-slate-600">{{ t('Sales Invoices') }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-md bg-amber-500 shadow-xs"></span>
                                    <span class="text-slate-600">{{ t('Purchase POs') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive ApexCharts Area Container -->
                        <div class="pt-2">
                            <div class="h-64 w-full">
                                <apexchart
                                    type="area"
                                    height="100%"
                                    width="100%"
                                    :options="trendChartOptions"
                                    :series="trendChartSeries"
                                />
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
                                    <PieChart class="w-4 h-4 text-emerald-600" />
                                    <span>{{ t('Inventory Holding by Category') }}</span>
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">{{ t('Portfolio distribution and capital allocation') }}</p>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono">
                                {{ donutSlices.length }} {{ t('Categories') }}
                            </span>
                        </div>

                        <!-- Donut Graphic & Legend -->
                        <div class="flex flex-col sm:flex-row items-center gap-6 py-2">
                            <!-- ApexCharts Donut Chart -->
                            <div class="relative w-48 h-48 shrink-0 flex items-center justify-center">
                                <apexchart
                                    type="donut"
                                    height="100%"
                                    width="100%"
                                    :options="donutChartOptions"
                                    :series="donutChartSeries"
                                />
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
                            {{ t('View Full Catalog & Categories') }} &rarr;
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Warehouse Holding Distribution -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <WarehouseIcon class="w-4 h-4 text-indigo-600" />
                            <span>{{ t('Warehouse & Hub Holding Breakdown') }}</span>
                        </h2>
                        <p class="text-xs text-slate-500">{{ t('Capital valuation and physical inventory across active fulfillment locations') }}</p>
                    </div>
                    <Link :href="route('warehouses.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                        {{ t('Manage Hubs') }} &rarr;
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
                                {{ t('Default') }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-400 mb-2 font-mono">CODE: {{ wh.code || 'N/A' }}</div>
                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <span class="text-slate-500">{{ t('Units') }}: <strong class="text-slate-800 font-mono">{{ wh.total_units }}</strong></span>
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
                                    <AlertCircle class="w-4 h-4 text-rose-500" />
                                    <span>{{ t('Low Stock Radar') }}</span>
                                </h2>
                                <p class="text-xs text-slate-500">{{ t('Products requiring purchase requisition') }}</p>
                            </div>
                            <Link :href="route('products.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                                {{ t('View Catalog') }} &rarr;
                            </Link>
                        </div>

                        <div v-if="lowStockProducts && lowStockProducts.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px]">
                                        <th class="py-2.5">{{ t('Product') }}</th>
                                        <th class="py-2.5 text-center">{{ t('Available') }}</th>
                                        <th class="py-2.5 text-center">{{ t('Reorder Lvl') }}</th>
                                        <th class="py-2.5 text-right">{{ t('Action') }}</th>
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
                                                {{ t('Order PO') }} &rarr;
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-10 text-slate-400 text-xs">
                            {{ t('No low stock items. All inventory levels optimal.') }}
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Tabs -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 w-full">
                            <button 
                                @click="activeTab = 'sales'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all inline-flex items-center gap-1.5"
                                :class="activeTab === 'sales' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <ShoppingCart class="w-3.5 h-3.5" />
                                <span>{{ t('Recent Sales') }}</span>
                            </button>
                            <button 
                                @click="activeTab = 'purchases'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all inline-flex items-center gap-1.5"
                                :class="activeTab === 'purchases' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <Truck class="w-3.5 h-3.5" />
                                <span>{{ t('Recent Inbound') }}</span>
                            </button>
                            <button 
                                @click="activeTab = 'movements'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all inline-flex items-center gap-1.5"
                                :class="activeTab === 'movements' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <History class="w-3.5 h-3.5" />
                                <span>{{ t('Stock Ledger') }}</span>
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
                                    {{ so.customer?.name || t('Walk-in Customer') }} &bull; {{ so.warehouse?.name }} &bull; <span class="text-slate-400">{{ formatRelative(so.order_date || so.created_at) }}</span>
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
                            {{ t('No recent sales orders.') }}
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
                                    {{ po.supplier?.name || t('Direct Vendor') }} &bull; {{ po.warehouse?.name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-slate-900 text-xs">
                                    <CurrencyDisplay :amount="po.total_amount" />
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5" :title="formatDate(po.order_date || po.ordered_at)">
                                    {{ formatRelative(po.order_date || po.ordered_at) }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!recentPurchaseOrders || recentPurchaseOrders.length === 0" class="text-center py-6 text-slate-400 text-xs">
                            {{ t('No recent purchase orders.') }}
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
                                    <div class="text-[10px] text-slate-400">{{ m.warehouse?.name }} &bull; {{ m.reason || t('Ledger entry') }} &bull; {{ formatRelative(m.created_at) }}</div>
                                </div>
                            </div>
                            <div class="text-right font-mono">
                                <span 
                                    class="font-bold"
                                    :class="m.quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600'"
                                >
                                    {{ m.quantity_change > 0 ? '+' : '' }}{{ m.quantity_change }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">{{ t('After') }}: {{ m.quantity_after }}</span>
                            </div>
                        </div>
                        <div v-if="!recentStockMovements || recentStockMovements.length === 0" class="text-center py-6 text-slate-400 text-xs">
                            {{ t('No ledger movements recorded yet.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
