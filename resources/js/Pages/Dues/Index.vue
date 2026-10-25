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
    customers: Array,
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

const expandedCustomerIds = ref(new Set());

function toggleExpand(id) {
    const next = new Set(expandedCustomerIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedCustomerIds.value = next;
}

const isPayModalOpen = ref(false);
const selectedCustomer = ref(null);
const selectedOrder = ref(null);

const payForm = useForm({
    order_id: '',
    amount: '',
    financial_account_id: defaultAccount?.id || '',
    payment_reference: '',
    notes: '',
});

function applyFilter() {
    router.get(route('dues.index'), {
        search: search.value,
        filter: filter.value,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    filter.value = 'all_with_dues';
    applyFilter();
}

function openPayModal(customer, order = null) {
    selectedCustomer.value = customer;
    const dueOrders = customer.due_orders || [];
    const targetOrder = order || dueOrders[0];

    selectedOrder.value = targetOrder;
    payForm.order_id = targetOrder ? targetOrder.id : '';
    payForm.amount = targetOrder ? Number(targetOrder.due_amount) : Number(customer.total_due);
    payForm.financial_account_id = defaultAccount?.id || '';
    payForm.payment_reference = '';
    payForm.notes = '';
    payForm.clearErrors();
    isPayModalOpen.value = true;
}

function onModalOrderChanged() {
    const order = selectedCustomer.value?.due_orders?.find(o => o.id === Number(payForm.order_id));
    if (order) {
        selectedOrder.value = order;
        payForm.amount = Number(order.due_amount);
    }
}

function submitPay() {
    payForm.post(route('dues.pay'), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            payForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Customer Receivables & Dues Ledger" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Customer Receivables & Dues Ledger</h1>
                    <p class="text-xs text-slate-500 font-medium">Track outstanding customer balances, payment recovery, and credit aging</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Outstanding Due</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_receivables || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Pending customer collections</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Collections Paid</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_collections || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Settled receivables</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Invoiced Amount</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_billed || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">All sales orders logged</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Customers in Debt</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.customers_with_due || 0 }}
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
                            placeholder="Search customer by name, mobile, address..."
                            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400"
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
                            All Clients
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-3 w-10 text-center"></th>
                                <th class="py-3 px-4">Customer Account</th>
                                <th class="py-3 px-4">Phone Number</th>
                                <th class="py-3 px-4 text-center">Pending Invoices</th>
                                <th class="py-3 px-4 text-right">Total Invoiced</th>
                                <th class="py-3 px-4 text-right">Total Paid</th>
                                <th class="py-3 px-4 text-right">Balance Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="c in customers" :key="c.id">
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <!-- Expand / Collapse chevron -->
                                    <td class="py-3.5 px-3 text-center">
                                        <button
                                            v-if="c.due_orders && c.due_orders.length > 0"
                                            type="button"
                                            @click="toggleExpand(c.id)"
                                            class="p-1 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition"
                                            :title="expandedCustomerIds.has(c.id) ? 'Collapse invoices' : 'View unpaid invoices'"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5 transition-transform duration-150"
                                                :class="expandedCustomerIds.has(c.id) ? 'rotate-90 text-indigo-600' : ''"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </td>

                                    <!-- Customer Details -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 text-sm">{{ c.name }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-xs">
                                            {{ c.address || c.email || 'Address not listed' }}
                                        </div>
                                    </td>

                                    <!-- Phone -->
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                        {{ c.phone || '—' }}
                                    </td>

                                    <!-- Pending Invoices Count -->
                                    <td class="py-3.5 px-4 text-center">
                                        <button
                                            v-if="c.due_orders && c.due_orders.length > 0"
                                            type="button"
                                            @click="toggleExpand(c.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200/80 hover:bg-amber-100 transition"
                                        >
                                            <span>{{ c.due_orders.length }} Pending</span>
                                            <svg
                                                class="w-3 h-3 transition-transform"
                                                :class="expandedCustomerIds.has(c.id) ? 'rotate-180' : ''"
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

                                    <!-- Total Invoiced -->
                                    <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700">
                                        <CurrencyDisplay :amount="c.total_billed" />
                                    </td>

                                    <!-- Total Paid -->
                                    <td class="py-3.5 px-4 text-right font-mono font-semibold text-emerald-600">
                                        <CurrencyDisplay :amount="c.total_paid" />
                                    </td>

                                    <!-- Balance Due -->
                                    <td class="py-3.5 px-4 text-right font-mono font-bold">
                                        <span :class="c.total_due > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                            <CurrencyDisplay :amount="c.total_due" />
                                        </span>
                                    </td>
                                </tr>

                                <!-- Expandable Unpaid Invoices Sub-table -->
                                <tr v-if="expandedCustomerIds.has(c.id) && c.due_orders && c.due_orders.length > 0" class="bg-slate-50/60">
                                    <td colspan="8" class="p-3 pl-12 pr-4">
                                        <div class="bg-white rounded-xl border border-slate-200/80 p-3 shadow-xs space-y-2">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between border-b border-slate-100 pb-2">
                                                <span>Unpaid Invoices for {{ c.name }}</span>
                                                <span class="text-rose-600 font-mono">Total Outstanding: <CurrencyDisplay :amount="c.total_due" /></span>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-semibold">
                                                            <th class="py-1.5">Invoice #</th>
                                                            <th class="py-1.5">Invoice Date</th>
                                                            <th class="py-1.5 text-right">Invoice Total</th>
                                                            <th class="py-1.5 text-right">Paid</th>
                                                            <th class="py-1.5 text-right">Due Balance</th>
                                                            <th class="py-1.5 text-right">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-50 font-mono">
                                                        <tr v-for="order in c.due_orders" :key="order.id" class="hover:bg-slate-50/80">
                                                            <td class="py-2 font-bold text-slate-800">
                                                                {{ order.order_number }}
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
                                                                    @click="openPayModal(c, order)"
                                                                    class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 hover:text-white bg-emerald-50 hover:bg-emerald-600 border border-emerald-200/80 rounded-lg transition"
                                                                >
                                                                    Collect
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

                            <tr v-if="!customers || customers.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 text-xs">
                                    No customer accounts found matching your filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collect Payment Modal -->
        <Modal :show="isPayModalOpen" max-width="md" @close="isPayModalOpen = false">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Collect Due Payment</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Customer: <strong class="text-slate-800">{{ selectedCustomer?.name }}</strong>
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

                <form @submit.prevent="submitPay" class="space-y-4 mt-4">
                    <!-- Invoice Selector if customer has multiple due orders -->
                    <div v-if="selectedCustomer?.due_orders?.length > 1">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Target Invoice *</label>
                        <select
                            v-model="payForm.order_id"
                            @change="onModalOrderChanged"
                            required
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 font-medium"
                        >
                            <option
                                v-for="ord in selectedCustomer.due_orders"
                                :key="ord.id"
                                :value="ord.id"
                            >
                                {{ ord.order_number }} — Due: ৳{{ Number(ord.due_amount).toFixed(2) }} ({{ formatDate(ord.order_date) }})
                            </option>
                        </select>
                    </div>

                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200/60 flex items-center justify-between text-xs">
                        <span class="text-rose-800 font-medium">Selected Invoice Due:</span>
                        <span class="font-mono font-bold text-rose-900 text-sm">
                            <CurrencyDisplay :amount="selectedOrder?.due_amount || selectedCustomer?.total_due || 0" />
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Collection Amount *</label>
                        <input
                            v-model.number="payForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="selectedOrder?.due_amount"
                            required
class="w-full text-sm font-bold font-mono px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500"
                        />
                        <div v-if="payForm.errors.amount" class="text-xs text-rose-500 mt-1">
                            {{ payForm.errors.amount }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                            Deposit Account <span class="text-rose-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="payForm.financial_account_id"
                            :options="accountOptions"
                            placeholder="Select deposit account..."
                            searchPlaceholder="Type account name or bank..."
                            :error="payForm.errors.financial_account_id"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Ref / TrxID / Receipt #</label>
                        <input
                            v-model="payForm.payment_reference"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 font-mono placeholder:font-sans placeholder:text-slate-400"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Remarks / Note</label>
                        <input
                            v-model="payForm.notes"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-400"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing || payForm.amount <= 0"
                            class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition disabled:opacity-50"
                        >
                            Confirm Collection
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
