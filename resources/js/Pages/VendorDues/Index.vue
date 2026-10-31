<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    vendors: Array,
    metrics: Object,
    financialAccounts: Array,
    filters: Object,
});

const defaultAccount = (props.financialAccounts || []).find(a => a.is_default) || props.financialAccounts?.[0];

const accountOptions = computed(() => {
    return (props.financialAccounts || []).map(acc => ({
        value: acc.id,
        label: acc.name,
        sublabel: acc.type === 'cash'
            ? `Balance: ৳${Number(acc.current_balance).toFixed(2)}`
            : `${acc.institution_name} (${acc.account_number}) - Bal: ৳${Number(acc.current_balance).toFixed(2)}`,
        group: acc.type === 'cash' ? 'Cash Registers' : (acc.type === 'bank' ? 'Bank Accounts' : 'MFS Wallets'),
    }));
});

const search = ref(props.filters?.search || '');
const filter = ref(props.filters?.filter || 'all_with_dues');

const expandedVendorIds = ref(new Set());

function toggleExpand(id) {
    const next = new Set(expandedVendorIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedVendorIds.value = next;
}

const isPayModalOpen = ref(false);
const selectedVendor = ref(null);
const selectedOrder = ref(null);

const payForm = useForm({
    order_id: '',
    amount: '',
    financial_account_id: defaultAccount?.id || '',
    payment_reference: '',
    notes: '',
});

function applyFilter() {
    const params = {};
    if (search.value) params.search = search.value;
    if (filter.value && filter.value !== 'all_with_dues') params.filter = filter.value;
    router.get(route('vendor-dues.index'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    filter.value = 'all_with_dues';
    router.get(route('vendor-dues.index'), {}, { preserveState: false, replace: true });
}

function openPayModal(vendor, order = null) {
    selectedVendor.value = vendor;
    const dueOrders = vendor.due_orders || [];
    const targetOrder = order || dueOrders[0];

    selectedOrder.value = targetOrder;
    payForm.order_id = targetOrder ? targetOrder.id : '';
    payForm.amount = targetOrder ? Number(targetOrder.due_amount) : Number(vendor.outstanding_due);
    payForm.financial_account_id = defaultAccount?.id || '';
    payForm.payment_reference = '';
    payForm.notes = '';
    payForm.clearErrors();
    isPayModalOpen.value = true;
}

function onModalOrderChanged() {
    const order = selectedVendor.value?.due_orders?.find(o => o.id === Number(payForm.order_id));
    if (order) {
        selectedOrder.value = order;
        payForm.amount = Number(order.due_amount);
    }
}

function setQuickPercent(percent) {
    const baseDue = selectedOrder.value ? Number(selectedOrder.value.due_amount) : Number(selectedVendor.value?.outstanding_due || 0);
    payForm.amount = Math.round((baseDue * (percent / 100)) * 100) / 100;
}

function submitPay() {
    payForm.post(route('vendor-dues.pay'), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            payForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Vendor Due Ledger" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Vendor Due Ledger</h1>
                    <p class="text-xs text-slate-500 font-medium">Track procurement liabilities, view purchase orders, and record partial/full disbursements</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Outstanding Due</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.outstanding_due || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Liabilities pending payment to suppliers</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Paid Out</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_paid || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Total supplier disbursements to date</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Procured</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_procured || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">All purchase orders logged</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Vendors in Debt</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.vendors_with_due || 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Accounts with overdue balance</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row items-center gap-2 flex-1 w-full">
                    <div class="relative flex-1 w-full max-w-sm">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilter"
                            type="text"
                            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200 text-xs shrink-0 font-medium">
                        <button
                            type="button"
                            @click="filter = 'all_with_dues'; applyFilter()"
                            :class="filter === 'all_with_dues' ? 'bg-white shadow-xs text-rose-600 font-bold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-lg transition"
                        >
                            With Dues Only
                        </button>
                        <button
                            type="button"
                            @click="filter = 'fully_paid'; applyFilter()"
                            :class="filter === 'fully_paid' ? 'bg-white shadow-xs text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-lg transition"
                        >
                            Fully Settled
                        </button>
                        <button
                            type="button"
                            @click="filter = 'all'; applyFilter()"
                            :class="filter === 'all' ? 'bg-white shadow-xs text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-lg transition"
                        >
                            All Vendors
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="resetFilter"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-3 w-10 text-center whitespace-nowrap"></th>
                                <th class="py-3 px-4 whitespace-nowrap">Vendor Account</th>
                                <th class="py-3 px-4 whitespace-nowrap">Phone Number</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Pending Orders</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Total Procured</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Total Paid</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Balance Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="v in vendors" :key="v.id">
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <!-- Expand / Collapse chevron -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <button
                                            v-if="v.due_orders && v.due_orders.length > 0"
                                            type="button"
                                            @click="toggleExpand(v.id)"
                                            class="p-1 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition"
                                            :title="expandedVendorIds.has(v.id) ? 'Collapse orders' : 'View unpaid orders'"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5 transition-transform duration-150"
                                                :class="expandedVendorIds.has(v.id) ? 'rotate-90 text-indigo-600' : ''"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </td>

                                    <!-- Vendor Details -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-sm max-w-[180px] lg:max-w-[240px] truncate" :title="v.name">{{ v.name }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[200px] lg:max-w-[260px]" :title="(v.contact_name ? `${v.contact_name} • ` : '') + (v.address || 'Address not listed')">
                                            {{ v.contact_name ? `${v.contact_name} • ` : '' }}{{ v.address ? (v.address.length > 45 ? v.address.slice(0, 45) + '...' : v.address) : 'Address not listed' }}
                                        </div>
                                    </td>

                                    <!-- Phone -->
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                        {{ v.phone || '—' }}
                                    </td>

                                    <!-- Pending Orders Count -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <button
                                            v-if="v.due_orders && v.due_orders.length > 0"
                                            type="button"
                                            @click="toggleExpand(v.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200/80 hover:bg-amber-100 transition"
                                        >
                                            <span>{{ v.due_orders.length }} Pending</span>
                                            <svg
                                                class="w-3 h-3 transition-transform"
                                                :class="expandedVendorIds.has(v.id) ? 'rotate-180' : ''"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Fully Settled
                                        </span>
                                    </td>

                                    <!-- Total Procured -->
                                    <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700">
                                        <CurrencyDisplay :amount="v.total_procured" />
                                    </td>

                                    <!-- Total Paid -->
                                    <td class="py-3.5 px-4 text-right font-mono font-semibold text-emerald-600">
                                        <CurrencyDisplay :amount="v.total_paid" />
                                    </td>

                                    <!-- Balance Due -->
                                    <td class="py-3.5 px-4 text-right font-mono font-bold">
                                        <span :class="v.outstanding_due > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                            <CurrencyDisplay :amount="v.outstanding_due" />
                                        </span>
                                    </td>
                                </tr>

                                <!-- Expandable Unpaid Purchase Orders Sub-table -->
                                <tr v-if="expandedVendorIds.has(v.id) && v.due_orders && v.due_orders.length > 0" class="bg-slate-50/60">
                                    <td colspan="8" class="p-3 pl-12 pr-4">
                                        <div class="bg-white rounded-xl border border-slate-200/80 p-3 shadow-xs space-y-2">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between border-b border-slate-100 pb-2">
                                                <span>Unpaid Purchase Orders for {{ v.name }}</span>
                                                <span class="text-rose-600 font-mono">Total Outstanding: <CurrencyDisplay :amount="v.outstanding_due" /></span>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-semibold">
                                                            <th class="py-1.5">PO / Order #</th>
                                                            <th class="py-1.5">Order Date</th>
                                                            <th class="py-1.5 text-right">PO Total</th>
                                                            <th class="py-1.5 text-right">Paid</th>
                                                            <th class="py-1.5 text-right">Due Balance</th>
                                                            <th class="py-1.5 text-right">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-50 font-mono">
                                                        <tr v-for="order in v.due_orders" :key="order.id" class="hover:bg-slate-50/80">
                                                            <td class="py-2 font-bold text-slate-800">
                                                                {{ order.order_number || order.po_number }}
                                                            </td>
                                                            <td class="py-2 text-slate-500 font-sans">
                                                                {{ formatDate(order.order_date) }}
                                                            </td>
                                                            <td class="py-2 text-right text-slate-700">
                                                                <CurrencyDisplay :amount="order.total_amount" />
                                                            </td>
                                                            <td class="py-2 text-right text-emerald-600">
                                                                <CurrencyDisplay :amount="order.paid_amount" />
                                                            </td>
                                                            <td class="py-2 text-right font-bold text-rose-600">
                                                                <CurrencyDisplay :amount="order.due_amount" />
                                                            </td>
                                                            <td class="py-2 text-right">
                                                                <button
                                                                    type="button"
                                                                    @click="openPayModal(v, order)"
                                                                    class="px-2.5 py-1 text-[11px] font-bold text-indigo-700 hover:text-white bg-indigo-50 hover:bg-indigo-600 border border-indigo-200/80 rounded-lg transition"
                                                                >
                                                                    Pay Due
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!vendors || vendors.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 text-xs">
                                    No vendor accounts found matching your filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pay Due Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="xl">
            <div class="p-6 sm:p-7">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Record Vendor Payment</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Vendor: <strong class="text-slate-800">{{ selectedVendor?.name }}</strong>
                        </p>
                    </div>
                    <button
                        @click="isPayModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 transition"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitPay" class="space-y-4 mt-5 text-xs">
                    <!-- PO Selector if vendor has multiple due orders -->
                    <div v-if="selectedVendor?.due_orders?.length > 1">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Target Purchase Order *</label>
                        <select
                            v-model="payForm.order_id"
                            @change="onModalOrderChanged"
                            required
                            class="w-full text-xs px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-medium"
                        >
                            <option
                                v-for="ord in selectedVendor.due_orders"
                                :key="ord.id"
                                :value="ord.id"
                            >
                                {{ ord.order_number || ord.po_number }} — Due: ৳{{ Number(ord.due_amount).toFixed(2) }} ({{ formatDate(ord.order_date) }})
                            </option>
                        </select>
                    </div>

                    <div class="p-3.5 bg-rose-50 rounded-xl border border-rose-200/60 flex items-center justify-between text-xs">
                        <span class="text-rose-800 font-medium">Selected PO Due:</span>
                        <span class="font-mono font-bold text-rose-900 text-sm">
                            <CurrencyDisplay :amount="selectedOrder?.due_amount || selectedVendor?.outstanding_due || 0" />
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-600 uppercase">Payment Amount *</label>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="setQuickPercent(50)"
                                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                                    >
                                        50% Due
                                    </button>
                                    <span class="text-slate-300">&bull;</span>
                                    <button 
                                        type="button" 
                                        @click="setQuickPercent(100)"
                                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Full Due
                                    </button>
                                </div>
                            </div>
                            <input
                                v-model.number="payForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="selectedOrder?.due_amount"
                                required
                                class="w-full text-sm font-bold font-mono px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 text-emerald-600"
                            />
                            <div v-if="payForm.errors.amount" class="text-xs text-rose-500 mt-1">
                                {{ payForm.errors.amount }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                Pay From Account <span class="text-rose-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="payForm.financial_account_id"
                                :options="accountOptions"
                                size="compact"
                                :error="payForm.errors.financial_account_id"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Cheque No / TrxID / Ref</label>
                            <input
                                v-model="payForm.payment_reference"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Remarks / Note</label>
                            <input
                                v-model="payForm.notes"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2.5 text-xs font-semibold border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing || payForm.amount <= 0"
                            class="px-6 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition disabled:opacity-50"
                        >
                            Confirm Settlement
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
