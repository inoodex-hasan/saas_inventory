<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    challans: Object,
    customers: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const customerId = ref(props.filters.customer_id || '');

const isViewModalOpen = ref(false);
const viewingChallan = ref(null);

function openViewModal(challan) {
    viewingChallan.value = challan;
    isViewModalOpen.value = true;
}

function applyFilter() {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (customerId.value) params.customer_id = customerId.value;
    router.get(route('challans.index'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    customerId.value = '';
    router.get(route('challans.index'), {}, { preserveState: false, replace: true });
}

function markDelivered(challan) {
    if (confirm(`Mark Delivery Challan #${challan.challan_number} as safely delivered?`)) {
        router.post(route('challans.deliver', challan.id), {}, {
            onSuccess: () => {
                if (viewingChallan.value && viewingChallan.value.id === challan.id) {
                    viewingChallan.value.status = 'delivered';
                }
            }
        });
    }
}

function deleteChallan(challan) {
    if (confirm(`Delete delivery challan #${challan.challan_number}?`)) {
        router.delete(route('challans.destroy', challan.id), {
            onSuccess: () => {
                if (viewingChallan.value && viewingChallan.value.id === challan.id) {
                    isViewModalOpen.value = false;
                }
            }
        });
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Delivery Challans & Gate Passes" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Delivery Challans & Gate Passes</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage dispatch logistics, transport vehicles, drivers, and delivery confirmations</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Challans</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_challans || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">In Transit / Dispatched</span>
                    <div class="text-2xl font-black text-blue-600 mt-2">
                        {{ metrics?.dispatched_count || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Safely Delivered</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        {{ metrics?.delivered_count || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Preparing / Draft</span>
                    <div class="text-2xl font-black text-slate-700 mt-2">
                        {{ metrics?.draft_count || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center gap-2">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="delivered">Delivered</option>
                    </select>

                    <select
                        v-model="customerId"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[135px] max-w-xs"
                    >
                        <option value="">All Customers</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <button
                        @click="resetFilter"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>

                    <Link
                        :href="route('challans.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Issue Challan</span>
                    </Link>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 whitespace-nowrap">Challan / Gate Pass #</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Sales Order #</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Recipient Customer</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Dispatch Date</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Carrier / Vehicle</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Driver</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Status</th>
                                <th class="py-3.5 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="c in challans.data" :key="c.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900 whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openViewModal(c)"
                                        class="font-mono font-bold text-cyan-600 hover:text-cyan-800 hover:underline flex items-center gap-1.5 transition text-left group"
                                        title="Click to view delivery challan details"
                                    >
                                        <span>{{ c.challan_number }}</span>
                                        <svg class="w-3.5 h-3.5 text-cyan-400 group-hover:text-cyan-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-800 whitespace-nowrap">
                                    {{ c.sales_order?.order_number || '-' }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900 max-w-[170px] lg:max-w-[220px] truncate" :title="c.customer?.name || c.sales_order?.customer?.name || '-'">{{ c.customer?.name || c.sales_order?.customer?.name || '-' }}</div>
                                    <div class="text-xs text-slate-400 truncate max-w-[200px] lg:max-w-[260px]" :title="c.delivery_address">{{ c.delivery_address ? (c.delivery_address.length > 45 ? c.delivery_address.slice(0, 45) + '...' : c.delivery_address) : '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 font-medium whitespace-nowrap">
                                    {{ formatDate(c.dispatch_date) }}
                                </td>
                                <td class="py-3.5 px-4 text-xs whitespace-nowrap">
                                    <div class="font-semibold text-slate-800">{{ c.transport_carrier || 'Fleet' }}</div>
                                    <div class="text-slate-400">{{ c.vehicle_no || 'N/A' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs whitespace-nowrap">
                                    <div class="font-medium text-slate-800">{{ c.driver_name || '-' }}</div>
                                    <div class="text-slate-400">{{ c.driver_phone || '' }}</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <StatusBadge :status="c.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown align="right">
                                        <div class="p-1 space-y-0.5">
                                            <!-- View Details -->
                                            <button
                                                type="button"
                                                @click="openViewModal(c)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-cyan-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Details</span>
                                            </button>

                                            <!-- Edit Challan -->
                                            <Link
                                                :href="route('challans.edit', c.id)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-cyan-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Challan</span>
                                            </Link>

                                            <!-- PDF Challan -->
                                            <a
                                                :href="route('challans.pdf', c.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-cyan-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                <span>Print Gate Pass</span>
                                            </a>

                                            <!-- Mark delivered -->
                                            <button
                                                v-if="c.status !== 'delivered'"
                                                @click="markDelivered(c)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-emerald-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Confirm Delivery</span>
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                @click="deleteChallan(c)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!challans.data || challans.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 font-medium">
                                    No delivery challans issued yet. Click "Issue Challan" to dispatch orders.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Challan Details Modal -->
        <Teleport to="body">
            <div
                v-if="isViewModalOpen && viewingChallan"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
                @click.self="isViewModalOpen = false"
            >
                <div class="bg-white rounded-2xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 my-8 space-y-6">
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xl font-black text-slate-900 tracking-tight">
                                    {{ viewingChallan.challan_number }}
                                </span>
                                <StatusBadge :status="viewingChallan.status" />
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Dispatch Date: <strong class="text-slate-700">{{ formatDate(viewingChallan.dispatch_date) }}</strong>
                                <span v-if="viewingChallan.creator"> &bull; Authorized by: <strong class="text-slate-700">{{ viewingChallan.creator.name }}</strong></span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                :href="route('challans.pdf', viewingChallan.id)"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                            >
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Print Gate Pass</span>
                            </a>
                            <button
                                type="button"
                                @click="isViewModalOpen = false"
                                class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Customer & Logistics Overview Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Recipient Destination</span>
                            <div class="text-sm font-bold text-slate-900">
                                {{ viewingChallan.customer?.name || viewingChallan.sales_order?.customer?.name || 'Recipient' }}
                            </div>
                            <div class="text-xs text-slate-600">
                                Contact: {{ viewingChallan.customer?.phone || viewingChallan.sales_order?.customer?.phone || '—' }}
                            </div>
                            <div class="text-xs text-slate-500 pt-1">
                                <strong class="text-slate-700">Address:</strong> {{ viewingChallan.delivery_address || '—' }}
                            </div>
                        </div>

                        <div class="bg-cyan-50/50 p-4 rounded-xl border border-cyan-100 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-700 block mb-1">Logistics & Gate Details</span>
                            <div class="text-xs text-slate-700 flex justify-between">
                                <span class="font-medium text-slate-500">Sales Order:</span>
                                <span class="font-bold text-slate-900 font-mono">{{ viewingChallan.sales_order?.order_number || '-' }}</span>
                            </div>
                            <div class="text-xs text-slate-700 flex justify-between">
                                <span class="font-medium text-slate-500">Warehouse:</span>
                                <span class="font-semibold text-slate-800">{{ viewingChallan.warehouse?.name || viewingChallan.sales_order?.warehouse?.name || '-' }}</span>
                            </div>
                            <div class="text-xs text-slate-700 flex justify-between">
                                <span class="font-medium text-slate-500">Carrier:</span>
                                <span class="font-semibold text-slate-800">{{ viewingChallan.transport_carrier || 'Fleet' }}</span>
                            </div>
                            <div class="text-xs text-slate-700 flex justify-between">
                                <span class="font-medium text-slate-500">Vehicle #:</span>
                                <span class="font-mono font-semibold text-slate-800">{{ viewingChallan.vehicle_no || 'N/A' }}</span>
                            </div>
                            <div class="text-xs text-slate-700 flex justify-between">
                                <span class="font-medium text-slate-500">Driver:</span>
                                <span class="font-semibold text-slate-800">{{ viewingChallan.driver_name || '-' }} {{ viewingChallan.driver_phone ? `(${viewingChallan.driver_phone})` : '' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Transport Packing List Table -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Transport Packing List</h3>
                            <span class="text-xs text-slate-400 font-medium">{{ viewingChallan.items?.length || 0 }} Item(s)</span>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-4">#</th>
                                        <th class="py-2.5 px-4">Product Item</th>
                                        <th class="py-2.5 px-3">SKU / Code</th>
                                        <th class="py-2.5 px-4 text-right">Dispatch Quantity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(it, idx) in viewingChallan.items" :key="it.id || idx" class="hover:bg-slate-50/50">
                                        <td class="py-2.5 px-4 text-slate-400 font-mono text-[11px]">{{ idx + 1 }}</td>
                                        <td class="py-2.5 px-4">
                                            <div class="font-bold text-slate-900">{{ it.product?.name || 'Product Item' }}</div>
                                            <div v-if="it.packaging_description" class="text-[10px] text-slate-500 mt-0.5">{{ it.packaging_description }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 font-mono text-slate-600">
                                            {{ it.product?.sku || it.product?.code || '-' }}
                                        </td>
                                        <td class="py-2.5 px-4 text-right font-mono font-black text-slate-900 text-sm">
                                            {{ it.quantity }}
                                        </td>
                                    </tr>
                                    <tr v-if="!viewingChallan.items || viewingChallan.items.length === 0">
                                        <td colspan="4" class="text-center py-4 text-slate-400">No packing list items recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes / Remarks -->
                    <div v-if="viewingChallan.notes" class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Gate Pass Instructions & Remarks</span>
                        <p class="text-xs text-slate-700 whitespace-pre-line">{{ viewingChallan.notes }}</p>
                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <Link
                                :href="route('challans.edit', viewingChallan.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit Challan</span>
                            </Link>

                            <a
                                :href="route('challans.pdf', viewingChallan.id)"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-xl text-xs font-bold transition border border-cyan-200"
                            >
                                <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Print Gate Pass</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button
                                v-if="viewingChallan.status !== 'delivered'"
                                type="button"
                                @click="markDelivered(viewingChallan)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Confirm Delivery</span>
                            </button>

                            <button
                                type="button"
                                @click="isViewModalOpen = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
