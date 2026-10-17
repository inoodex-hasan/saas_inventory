<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

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
});

const activeTab = ref('sales');
</script>

<template>
    <AppLayout>
        <Head title="Admin Dashboard" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Admin Dashboard</h1>
                    <p class="text-xs text-slate-500 font-medium">Real-time inventory valuation, stock movements & fulfillments</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Quick Actions Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Quick Actions</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link 
                        :href="route('sales.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Sale</span>
                    </Link>

                    <Link 
                        :href="route('purchases.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Purchase</span>
                    </Link>

                    <Link 
                        :href="route('products.create')" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-sm transition-all"
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
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Holding Valuation</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                            ৳
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        <CurrencyDisplay :amount="totalValuation" />
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        Across {{ totalProducts }} active catalog items
                    </p>
                </div>

                <!-- Monthly Sales Volume -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Sales Volume</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        <CurrencyDisplay :amount="totalSalesVolume" />
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        {{ totalSalesOrders }} total confirmed orders
                    </p>
                </div>

                <!-- Inbound Procurement -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inbound Orders</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ pendingPurchasesCount }}
                        <span class="text-sm font-semibold text-slate-400">Pending</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        Worth <CurrencyDisplay :amount="openPurchaseOrders" />
                    </p>
                </div>

                <!-- Low Stock Alert Radar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-rose-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Radar</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black" :class="lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900'">
                        {{ lowStockCount }}
                        <span class="text-sm font-semibold text-slate-400">Items Low</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-medium">
                        At or below reorder threshold
                    </p>
                </div>
            </div>

            <!-- Warehouse Holding Distribution -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Warehouse & Hub Holding Breakdown</h2>
                        <p class="text-xs text-slate-500">Capital valuation and physical inventory across active fulfillment locations</p>
                    </div>
                    <Link :href="route('warehouses.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                        Manage Hubs &rarr;
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="wh in warehouseStockBreakdown" 
                        :key="wh.id" 
                        class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition-all"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-slate-900 text-sm truncate">{{ wh.name }}</span>
                            <span v-if="wh.is_default" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                                Default
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 mb-2 font-mono">Code: {{ wh.code || 'N/A' }}</div>
                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <span class="text-slate-500">Units: <strong class="text-slate-800 font-mono">{{ wh.total_units }}</strong></span>
                            <span class="font-bold text-slate-900">
                                <CurrencyDisplay :amount="wh.total_valuation" />
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout: Low Stock Radar & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Low Stock Alert Radar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
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
                            No low stock items.
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Tabs -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 w-full">
                            <button 
                                @click="activeTab = 'sales'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                :class="activeTab === 'sales' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Recent Sales
                            </button>
                            <button 
                                @click="activeTab = 'purchases'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                :class="activeTab === 'purchases' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Recent Inbound
                            </button>
                            <button 
                                @click="activeTab = 'movements'" 
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                :class="activeTab === 'movements' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
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
                                    <span class="font-bold text-slate-800 text-xs font-mono">{{ po.po_number }}</span>
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
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ po.ordered_at ? new Date(po.ordered_at).toLocaleDateString() : 'Draft' }}</span>
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
