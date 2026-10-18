<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    returns: Object,
    warehouses: Array,
    eligibleOrders: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const isCreateModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const selectedReturn = ref(null);

const returnForm = useForm({
    sales_order_id: '',
    warehouse_id: '',
    reason: '',
    refund_type: 'credit_note',
    notes: '',
    items: [],
});

const rejectForm = useForm({
    reason: '',
});

function applyFilter() {
    router.get(route('returns.index'), {
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    applyFilter();
}

function onOrderSelected() {
    const order = props.eligibleOrders.find(o => o.id === parseInt(returnForm.sales_order_id));
    if (order) {
        returnForm.warehouse_id = order.warehouse_id || '';
        returnForm.items = (order.items || []).map(item => ({
            selected: false,
            sales_order_item_id: item.id,
            product_id: item.product_id,
            product_name: item.product?.name || `Product #${item.product_id}`,
            quantity: item.quantity,
            unit_price: item.unit_price,
            condition: 'sellable',
            action_taken: 'restock',
            serial_number: '',
        }));
    }
}

function openCreateModal() {
    returnForm.reset();
    returnForm.items = [];
    isCreateModalOpen.value = true;
}

function submitReturn() {
    const selectedItems = returnForm.items.filter(it => it.selected);
    if (selectedItems.length === 0) {
        alert('Please select at least one line item to return.');
        return;
    }

    returnForm.transform(data => ({
        ...data,
        items: selectedItems,
    })).post(route('returns.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            returnForm.reset();
        },
    });
}

function approveReturn(ret) {
    if (confirm(`Approve return request #${ret.return_number}?`)) {
        router.post(route('returns.approve', ret.id));
    }
}

function openRejectModal(ret) {
    selectedReturn.value = ret;
    rejectForm.reason = '';
    isRejectModalOpen.value = true;
}

function submitReject() {
    if (!selectedReturn.value) return;
    rejectForm.post(route('returns.reject', selectedReturn.value.id), {
        onSuccess: () => {
            isRejectModalOpen.value = false;
            rejectForm.reset();
        },
    });
}

function completeReturn(ret) {
    if (confirm(`Complete and restock return #${ret.return_number}? Sellable inventory will be added back into warehouse.`)) {
        router.post(route('returns.complete', ret.id));
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Product Returns & RMA Workflows" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Product Returns & RMA Workflows</h1>
                    <p class="text-xs text-slate-500 font-medium">Process customer return merchandise authorizations, restock depot inventory, and issue credit notes</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Returns</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_returns || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending Review</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.pending_requests || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Approved RMA</span>
                    <div class="text-2xl font-black text-blue-600 mt-2">
                        {{ metrics?.approved_count || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Refunded</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_refunded || 0" />
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
                        placeholder="Search return #, order #, customer..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 placeholder:text-slate-400 placeholder:text-xs transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 min-w-[140px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved</option>
                        <option value="completed">Completed / Restocked</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <button
                        @click="resetFilter"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>

                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-rose-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Create Return Request</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Return #</th>
                                <th class="py-3.5 px-4">Original Sales Order</th>
                                <th class="py-3.5 px-4">Customer</th>
                                <th class="py-3.5 px-4">Refund Amount</th>
                                <th class="py-3.5 px-4">Refund Method</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="ret in returns.data" :key="ret.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ ret.return_number }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-800">
                                    {{ ret.sales_order?.order_number }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-900">
                                    {{ ret.customer?.name }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <CurrencyDisplay :amount="ret.refund_amount" />
                                </td>
                                <td class="py-3.5 px-4 text-xs capitalize text-slate-600">
                                    {{ (ret.refund_type || '').replace('_', ' ') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="ret.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <!-- Credit Note PDF -->
                                            <a
                                                :href="route('returns.credit-note', ret.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-purple-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                <span>Print / PDF</span>
                                            </a>

                                            <!-- Approve -->
                                            <button
                                                v-if="ret.status === 'pending'"
                                                @click="approveReturn(ret)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Approve</span>
                                            </button>

                                            <!-- Complete / Restock -->
                                            <button
                                                v-if="ret.status === 'approved'"
                                                @click="completeReturn(ret)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Restock & Refund</span>
                                            </button>

                                            <!-- Reject -->
                                            <button
                                                v-if="ret.status === 'pending'"
                                                @click="openRejectModal(ret)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>Reject</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!returns.data || returns.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400 font-medium">
                                    No product returns logged yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Log Return Modal -->
        <Modal :show="isCreateModalOpen" max-width="2xl" @close="isCreateModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Log Customer Return / RMA</h3>
                <p class="text-sm text-slate-500 mb-4">Select dispatched sales order and specify items to be returned:</p>

                <form @submit.prevent="submitReturn" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Select Sales Order *</label>
                            <select
                                v-model="returnForm.sales_order_id"
                                @change="onOrderSelected"
                                required
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="" disabled>-- Select Dispatched Order --</option>
                                <option v-for="o in eligibleOrders" :key="o.id" :value="o.id">
                                    {{ o.order_number }} - {{ o.customer?.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Refund Method *</label>
                            <select
                                v-model="returnForm.refund_type"
                                required
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="credit_note">Credit Note (Ledger Balance)</option>
                                <option value="cash_refund">Cash Refund</option>
                                <option value="bank_refund">Bank Transfer</option>
                                <option value="replacement">Item Replacement</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Return Reason *</label>
                        <input
                            v-model="returnForm.reason"
                            type="text"
                            required
                            placeholder="e.g. Defective item or wrong model"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-rose-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                    </div>

                    <!-- Items checklist -->
                    <div v-if="returnForm.items.length > 0" class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-600 uppercase">Select Items to Return *</label>
                        <div class="space-y-2 max-h-60 overflow-y-auto border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                            <div
                                v-for="(it, idx) in returnForm.items"
                                :key="idx"
                                class="p-3 bg-white border border-slate-200 rounded-lg flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="it.selected"
                                        class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4"
                                    />
                                    <div>
                                        <div class="font-bold text-slate-900">{{ it.product_name }}</div>
                                        <div class="text-slate-400">Unit: <CurrencyDisplay :amount="it.unit_price" /></div>
                                    </div>
                                </div>

                                <div v-if="it.selected" class="flex items-center gap-3">
                                    <div>
                                        <label class="text-[10px] text-slate-400 block">Qty</label>
                                        <input
                                            v-model.number="it.quantity"
                                            type="number"
                                            min="0.01"
                                            step="any"
                                            class="w-16 px-1.5 py-1 border border-slate-200 rounded text-xs"
                                        />
                                    </div>

                                    <div>
                                        <label class="text-[10px] text-slate-400 block">Condition</label>
                                        <select v-model="it.condition" class="px-1.5 py-1 border border-slate-200 rounded text-xs">
                                            <option value="sellable">Sellable</option>
                                            <option value="damaged">Damaged</option>
                                            <option value="defective">Defective</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-[10px] text-slate-400 block">Action</label>
                                        <select v-model="it.action_taken" class="px-1.5 py-1 border border-slate-200 rounded text-xs">
                                            <option value="restock">Restock</option>
                                            <option value="scrap">Scrap</option>
                                            <option value="repair">Repair</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="returnForm.processing"
                            class="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition"
                        >
                            Submit Return Request
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Reject Modal -->
        <Modal :show="isRejectModalOpen" max-width="md" @close="isRejectModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Reject Return Request</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Please provide an official reason for rejecting return #{{ selectedReturn?.return_number }}:
                </p>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <textarea
                            v-model="rejectForm.reason"
                            required
                            rows="3"
                            placeholder="e.g. Past warranty policy window"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-rose-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isRejectModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition"
                        >
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
