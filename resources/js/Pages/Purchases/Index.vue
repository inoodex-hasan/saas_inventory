<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    orders: Object,
    suppliers: Array,
    warehouses: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const supplierId = ref(props.filters?.supplier_id || '');

// Receive Modal State
const isReceiveModalOpen = ref(false);
const receivingPo = ref(null);
const receiveForm = useForm({
    items: [],
});

function applyFilters() {
    router.get(route('purchases.index'), {
        search: search.value,
        status: status.value,
        supplier_id: supplierId.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    supplierId.value = '';
    applyFilters();
}

function markOrdered(order) {
    if (confirm(`Mark PO ${order.po_number} as Ordered / Sent to Vendor?`)) {
        router.post(route('purchases.mark-ordered', order.id), {}, {
            preserveScroll: true,
        });
    }
}

function cancelOrder(order) {
    if (confirm(`Cancel Purchase Order ${order.po_number}?`)) {
        router.post(route('purchases.cancel', order.id), {}, {
            preserveScroll: true,
        });
    }
}

function openReceiveModal(order) {
    receivingPo.value = order;
    receiveForm.items = (order.items || []).map(item => {
        const remaining = (item.quantity || 0) - (item.received_quantity || 0);
        return {
            item_id: item.id,
            product_name: item.product?.name || `Product #${item.product_id}`,
            sku: item.product?.sku || '',
            unit: item.product?.unit || 'pcs',
            is_serialized: item.product?.is_serialized || false,
            quantity_ordered: item.quantity,
            received_quantity: item.received_quantity || 0,
            remaining_quantity: remaining,
            quantity: remaining > 0 ? remaining : 0,
            batch_number: item.batch_number || '',
            bin_location: '',
            serial_input: '',
        };
    });
    isReceiveModalOpen.value = true;
}

function submitReceive() {
    const payload = receiveForm.items.map(it => {
        const serials = it.serial_input
            ? it.serial_input.split(/[\n,]+/).map(s => s.trim()).filter(Boolean)
            : [];
        return {
            item_id: it.item_id,
            quantity: Number(it.quantity) || 0,
            batch_number: it.batch_number,
            bin_location: it.bin_location,
            serials: serials,
        };
    });

    router.post(route('purchases.receive', receivingPo.value.id), {
        items: payload,
    }, {
        onSuccess: () => {
            isReceiveModalOpen.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Purchases & Inbound" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Procurement & Purchase Orders</h1>
                    <p class="text-xs text-slate-500 font-medium">Inbound shipment tracking, supplier procurement sheets & dock receiving</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Purchase Orders</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_inbound || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inbound Orders Pending</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.pending_orders || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Payables</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_payables || 0" />
                    </div>
                </div>
            </div>

            <!-- Filter & Action Header -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="applyFilters"
                            placeholder="Search PO #, vendor, phone..."
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 placeholder:text-slate-400"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select 
                        v-model="status" 
                        @change="applyFilters"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="ordered">Ordered</option>
                        <option value="partially_received">Partially Received</option>
                        <option value="received">Received & Stocked</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <select 
                        v-model="supplierId" 
                        @change="applyFilters"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Vendors</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>

                    <button 
                        v-if="search || status || supplierId" 
                        @click="resetFilters" 
                        class="px-2.5 py-2 text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <Link 
                        :href="route('purchases.create')" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Purchase Order</span>
                    </Link>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4">PO Number</th>
                                <th class="py-3 px-4">Vendor</th>
                                <th class="py-3 px-4">Destination Hub</th>
                                <th class="py-3 px-4">Order Date</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Order Total</th>
                                <th class="py-3 px-4 text-right">Payable Due</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ order.po_number }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ order.supplier?.name || 'Direct Vendor' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ order.supplier?.phone }}</div>
                                </td>

                                <td class="py-3.5 px-4 text-slate-700 font-medium">
                                    {{ order.warehouse?.name }}
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ order.order_date ? new Date(order.order_date).toLocaleDateString() : '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <StatusBadge :status="order.status" />
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    <CurrencyDisplay :amount="order.total_amount" />
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div 
                                        class="font-mono font-bold"
                                        :class="order.due_amount > 0 ? 'text-rose-600' : 'text-emerald-600'"
                                    >
                                        <CurrencyDisplay :amount="order.due_amount" />
                                    </div>
                                    <StatusBadge :status="order.payment_status" class="scale-90 origin-right mt-0.5" />
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Receive Dock Button -->
                                        <button 
                                            v-if="['ordered', 'partially_received'].includes(order.status)"
                                            @click="openReceiveModal(order)"
                                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm"
                                        >
                                            Receive Dock
                                        </button>

                                        <!-- Mark Ordered Button -->
                                        <button 
                                            v-if="order.status === 'draft'"
                                            @click="markOrdered(order)"
                                            class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold transition-colors"
                                        >
                                            Send / Order
                                        </button>

                                        <!-- PDF Dropdown / Actions -->
                                        <a 
                                            :href="route('purchases.pdf', order.id)" 
                                            target="_blank"
                                            title="Print Procurement Sheet"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a>

                                        <!-- Cancel PO -->
                                        <button 
                                            v-if="['draft', 'ordered'].includes(order.status)"
                                            @click="cancelOrder(order)"
                                            title="Cancel Order"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No purchase orders found.
                </div>

                <!-- Pagination -->
                <div v-if="orders.links && orders.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in orders.links" 
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

        <!-- Receive Goods Modal -->
        <Modal :show="isReceiveModalOpen" @close="isReceiveModalOpen = false" maxWidth="3xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Loading Dock Goods Receiving</span>
                            <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                {{ receivingPo?.po_number }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500">
                            Fulfillment Destination: <strong>{{ receivingPo?.warehouse?.name }}</strong>
                        </p>
                    </div>
                    <button @click="isReceiveModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitReceive" class="space-y-4">
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-bold">
                                    <th class="py-2.5">Product & SKU</th>
                                    <th class="py-2.5 text-center">Remaining</th>
                                    <th class="py-2.5 text-center">Receive Units *</th>
                                    <th class="py-2.5">Batch / Lot #</th>
                                    <th class="py-2.5">Bin Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(it, idx) in receiveForm.items" :key="idx" class="hover:bg-slate-50">
                                    <td class="py-3">
                                        <div class="font-bold text-slate-800">{{ it.product_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ it.sku }}</div>
                                        <div v-if="it.is_serialized" class="mt-1">
                                            <input 
                                                type="text" 
                                                v-model="it.serial_input"
                                                placeholder="Enter serial numbers comma separated..."
                                                class="w-full px-2 py-1 rounded text-[11px] border border-amber-300 bg-amber-50/50"
                                            />
                                        </div>
                                    </td>
                                    <td class="py-3 text-center font-mono font-bold text-slate-600">
                                        {{ it.remaining_quantity }} {{ it.unit }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            :max="it.remaining_quantity"
                                            v-model="it.quantity"
                                            class="w-20 px-2 py-1 text-center rounded-lg border border-slate-200 font-mono font-bold text-xs"
                                        />
                                    </td>
                                    <td class="py-3">
                                        <input 
                                            type="text" 
                                            v-model="it.batch_number"
                                            placeholder="LOT-XXXX"
                                            class="w-24 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono"
                                        />
                                    </td>
                                    <td class="py-3">
                                        <input 
                                            type="text" 
                                            v-model="it.bin_location"
                                            placeholder="e.g. A-12"
                                            class="w-20 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="isReceiveModalOpen = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm"
                        >
                            Confirm Stock-In
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
