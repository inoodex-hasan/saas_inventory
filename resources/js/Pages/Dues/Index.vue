<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    customers: [Object, Array],
    metrics: Object,
    financialAccounts: Array,
    filters: Object,
});

const customerList = computed(() => {
    if (Array.isArray(props.customers)) return props.customers;
    return props.customers?.data || [];
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
    customer_id: '',
    order_id: '',
    amount: '',
    financial_account_id: defaultAccount?.id || '',
    payment_reference: '',
    notes: '',
    allocations: [],
});

const totalAllocated = computed(() => {
    return (payForm.allocations || []).reduce((acc, it) => acc + (Number(it.amount) || 0), 0);
});

const unallocatedAmount = computed(() => {
    const total = Number(payForm.amount) || 0;
    return Number((total - totalAllocated.value).toFixed(2));
});

function applyFilter() {
    const params = {};
    if (search.value) params.search = search.value;
    if (filter.value && filter.value !== 'all_with_dues') params.filter = filter.value;
    router.get(route('dues.index'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    filter.value = 'all_with_dues';
    router.get(route('dues.index'), {}, { preserveState: false, replace: true });
}

function openPayModal(customer, order = null) {
    selectedCustomer.value = customer;
    payForm.clearErrors();
    payForm.customer_id = customer.id;
    payForm.financial_account_id = defaultAccount?.id || '';
    payForm.payment_reference = '';
    payForm.notes = '';

    const dueOrders = customer.due_orders || [];
    selectedOrder.value = order || dueOrders[0] || null;

    if (order) {
        payForm.order_id = order.id;
        payForm.amount = Number(order.due_amount);
        payForm.allocations = dueOrders.map(o => ({
            order_id: o.id,
            order_number: o.order_number,
            order_date: o.order_date,
            total_amount: Number(o.total_amount),
            paid_amount: Number(o.paid_amount),
            due_amount: Number(o.due_amount),
            amount: o.id === order.id ? Number(o.due_amount) : 0,
        }));
    } else {
        const totalDue = Number(customer.total_due);
        payForm.order_id = '';
        payForm.amount = totalDue;
        payForm.allocations = dueOrders.map(o => ({
            order_id: o.id,
            order_number: o.order_number,
            order_date: o.order_date,
            total_amount: Number(o.total_amount),
            paid_amount: Number(o.paid_amount),
            due_amount: Number(o.due_amount),
            amount: 0,
        }));
        autoAllocateFIFO();
    }

    isPayModalOpen.value = true;
}

function autoAllocateFIFO() {
    let remaining = Number(payForm.amount) || 0;
    const sorted = [...(payForm.allocations || [])].sort((a, b) => {
        if (a.order_date && b.order_date) {
            return new Date(a.order_date) - new Date(b.order_date);
        }
        return a.order_id - b.order_id;
    });

    const allocMap = {};
    for (const ord of sorted) {
        if (remaining <= 0) {
            allocMap[ord.order_id] = 0;
        } else {
            const take = Math.min(remaining, ord.due_amount);
            allocMap[ord.order_id] = Number(take.toFixed(2));
            remaining -= take;
        }
    }

    payForm.allocations.forEach(it => {
        it.amount = allocMap[it.order_id] || 0;
    });
}

function autoAllocateEvenly() {
    const total = Number(payForm.amount) || 0;
    if (total <= 0 || !payForm.allocations?.length) return;

    const totalDue = payForm.allocations.reduce((sum, i) => sum + i.due_amount, 0);
    if (totalDue <= 0) return;

    let remaining = total;
    payForm.allocations.forEach((it, idx) => {
        if (idx === payForm.allocations.length - 1) {
            it.amount = Math.min(Number(remaining.toFixed(2)), it.due_amount);
        } else {
            const ratio = it.due_amount / totalDue;
            const take = Math.min(Number((total * ratio).toFixed(2)), it.due_amount);
            it.amount = take;
            remaining -= take;
        }
    });
}

function clearAllocations() {
    (payForm.allocations || []).forEach(it => {
        it.amount = 0;
    });
}

function fillMax(item) {
    item.amount = Number(item.due_amount.toFixed(2));
}

function syncTotalWithAllocated() {
    payForm.amount = Number(totalAllocated.value.toFixed(2));
}

function onTotalAmountInput() {
    autoAllocateFIFO();
}

function setQuickPercent(percent) {
    if (!selectedCustomer.value) return;
    const baseDue = selectedOrder.value ? Number(selectedOrder.value.due_amount) : Number(selectedCustomer.value.total_due || 0);
    payForm.amount = Math.round((baseDue * (percent / 100)) * 100) / 100;
    autoAllocateFIFO();
}

function submitPay() {
    payForm.post(route('dues.pay'), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            payForm.reset();
        },
    });
}

// Add / Edit Customer with Opening Balance
const isAddCustomerModalOpen = ref(false);
const editingCustomer = ref(null);
const customerForm = useForm({
    name: '',
    phone: '',
    company_name: '',
    email: '',
    address: '',
    opening_balance: 0,
});

function openAddCustomerModal() {
    editingCustomer.value = null;
    customerForm.reset();
    customerForm.clearErrors();
    customerForm.opening_balance = 0;
    isAddCustomerModalOpen.value = true;
}

function openEditCustomerModal(c) {
    editingCustomer.value = c;
    customerForm.clearErrors();
    customerForm.name = c.name || '';
    customerForm.phone = c.phone || '';
    customerForm.company_name = c.company_name || '';
    customerForm.email = c.email || '';
    customerForm.address = c.address || '';
    customerForm.opening_balance = Number(c.opening_balance) || 0;
    isAddCustomerModalOpen.value = true;
}

function submitAddCustomer() {
    if (editingCustomer.value) {
        customerForm.put(route('dues.customer.update', editingCustomer.value.id), {
            onSuccess: () => {
                isAddCustomerModalOpen.value = false;
                customerForm.reset();
                editingCustomer.value = null;
            },
        });
    } else {
        customerForm.post(route('dues.customer.store'), {
            onSuccess: () => {
                isAddCustomerModalOpen.value = false;
                customerForm.reset();
            },
        });
    }
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
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openAddCustomerModal"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Customer</span>
                    </button>
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
                                <th class="py-3 px-3 w-10 text-center whitespace-nowrap"></th>
                                <th class="py-3 px-4 whitespace-nowrap">Customer Account</th>
                                <th class="py-3 px-4 whitespace-nowrap">Phone Number</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Pending Invoices</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Total Invoiced</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Total Paid</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Balance Due</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="c in customerList" :key="c.id">
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <!-- Expand / Collapse chevron -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
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
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-sm max-w-[180px] lg:max-w-[240px] truncate" :title="c.name">{{ c.name }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[200px] lg:max-w-[260px]" :title="c.address || c.email || 'Address not listed'">
                                            {{ (c.address || c.email) ? ((c.address || c.email).length > 45 ? (c.address || c.email).slice(0, 45) + '...' : (c.address || c.email)) : 'Address not listed' }}
                                        </div>
                                    </td>

                                    <!-- Phone -->
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                        {{ c.phone || '—' }}
                                    </td>

                                    <!-- Pending Invoices Count -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
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

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                v-if="c.total_due > 0"
                                                type="button"
                                                @click="openPayModal(c)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs shadow-emerald-600/20 transition"
                                                title="Collect payment across customer invoices"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>Collect Due</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openEditCustomerModal(c)"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                                                title="Edit customer & opening balance"
                                            >
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Expandable Unpaid Invoices Sub-table -->
                                <tr v-if="expandedCustomerIds.has(c.id) && c.due_orders && c.due_orders.length > 0" class="bg-slate-50/60">
                                    <td colspan="8" class="p-3 pl-12 pr-4">
                                        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs space-y-2.5">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                                <span>Unpaid Invoices for {{ c.name }} ({{ c.due_orders.length }})</span>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-rose-600 font-mono font-bold">Total Outstanding: <CurrencyDisplay :amount="c.total_due" /></span>
                                                    <button
                                                        v-if="c.total_due > 0"
                                                        type="button"
                                                        @click="openPayModal(c)"
                                                        class="px-3 py-1 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition flex items-center gap-1"
                                                    >
                                                        <span>Collect Due</span>
                                                    </button>
                                                </div>
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
                                                            <th class="py-1.5 text-center">Status</th>
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
                                                            <td class="py-2 text-center font-sans">
                                                                <span
                                                                    class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                                    :class="Number(order.paid_amount) > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                                                >
                                                                    {{ Number(order.paid_amount) > 0 ? 'Partial' : 'Unpaid' }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!customerList || customerList.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 text-xs">
                                    No customer accounts found matching your filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div v-if="customers?.links" class="p-4 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-500 font-medium">
                        Showing <span class="font-bold text-slate-700">{{ customers.from || 0 }}</span> to <span class="font-bold text-slate-700">{{ customers.to || 0 }}</span> of <span class="font-bold text-slate-700">{{ customers.total || 0 }}</span> customer accounts
                    </p>
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <!-- Collect Payment Modal (Multi-Invoice Allocation) -->
        <Modal :show="isPayModalOpen" max-width="2xl" @close="isPayModalOpen = false">
            <div class="flex flex-col max-h-[90vh]">
                <!-- Modal Sticky Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-sm shadow-emerald-500/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900">Collect Due Payment</h3>
                                <span v-if="payForm.allocations?.length > 1" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Multi-Invoice Settlement
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Customer: <strong class="text-slate-800">{{ selectedCustomer?.name }}</strong>
                                <span v-if="selectedCustomer?.phone" class="text-slate-500 font-mono"> ({{ selectedCustomer.phone }})</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isPayModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-200/60"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitPay" class="flex flex-col flex-1 min-h-0">
                    <!-- Scrollable Modal Body -->
                    <div class="p-6 space-y-4 text-xs overflow-y-auto flex-1">
                        <!-- Customer Due Overview Card -->
                        <div class="p-3.5 bg-gradient-to-r from-slate-50 to-slate-100/80 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="text-[11px] text-slate-500 font-medium">Total Customer Outstanding:</span>
                                <div class="text-lg font-black text-rose-600">
                                    <CurrencyDisplay :amount="selectedCustomer?.total_due || 0" />
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-slate-500 font-medium">Pending Due Invoices:</span>
                                <div class="text-sm font-bold text-slate-800">
                                    {{ payForm.allocations?.length || 0 }} {{ (payForm.allocations?.length || 0) === 1 ? 'Invoice' : 'Invoices' }}
                                </div>
                            </div>
                        </div>

                        <!-- Total Collection Amount & Quick Allocation Presets -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">
                                    Total Collection Amount <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button
                                        type="button"
                                        @click="setQuickPercent(100)"
                                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded transition"
                                    >
                                        Full Due (100%)
                                    </button>
                                    <button
                                        type="button"
                                        @click="setQuickPercent(50)"
                                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded transition"
                                    >
                                        50% Due
                                    </button>
                                    <button
                                        type="button"
                                        @click="autoAllocateFIFO"
                                        title="Allocate payment to oldest due invoices first"
                                        class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded transition flex items-center gap-1"
                                    >
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                        Auto-Allocate (FIFO)
                                    </button>
                                    <button
                                        type="button"
                                        @click="clearAllocations"
                                        class="text-[10px] font-semibold text-slate-500 hover:text-rose-600 px-1.5 py-0.5 rounded hover:bg-slate-100 transition"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                            <input
                                v-model.number="payForm.amount"
                                @input="onTotalAmountInput"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="selectedCustomer?.total_due"
                                required
                                placeholder="Enter amount customer is paying (e.g. 5000)"
                                class="w-full text-base font-bold font-mono px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-slate-900 shadow-xs"
                            />
                            <div v-if="payForm.errors.amount" class="text-xs text-rose-500 mt-1 font-medium">
                                {{ payForm.errors.amount }}
                            </div>
                        </div>

                        <!-- Multi-Invoice Allocation Breakdown Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                            <div class="bg-slate-50 px-3.5 py-2 border-b border-slate-200 flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                    Invoice Payment Allocation
                                </span>
                                <span class="text-[11px] text-slate-500">
                                    Type custom amount per invoice or use Auto-FIFO
                                </span>
                            </div>

                            <div class="max-h-56 overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50/70 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200 sticky top-0">
                                        <tr>
                                            <th class="py-2.5 px-3">Invoice # / Date</th>
                                            <th class="py-2.5 px-3 text-right">Outstanding Due</th>
                                            <th class="py-2.5 px-3 text-center w-40">Payment Allocation</th>
                                            <th class="py-2.5 px-3 text-right">Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in payForm.allocations" :key="item.order_id" class="hover:bg-slate-50/70 transition">
                                            <td class="py-2.5 px-3">
                                                <div class="font-bold text-slate-900 font-mono">{{ item.order_number }}</div>
                                                <div class="text-[11px] text-slate-400">{{ formatDate(item.order_date) }} • Total: ৳{{ item.total_amount?.toFixed(2) }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-bold text-rose-600 font-mono">
                                                ৳{{ Number(item.due_amount).toFixed(2) }}
                                            </td>
                                            <td class="py-2 px-3 text-center">
                                                <div class="relative flex items-center">
                                                    <span class="absolute left-2.5 text-slate-400 font-bold">৳</span>
                                                    <input
                                                        v-model.number="item.amount"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        :max="item.due_amount"
                                                        class="w-full pl-6 pr-11 py-1.5 text-xs font-mono font-bold text-right bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-1 focus:ring-emerald-500"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="fillMax(item)"
                                                        title="Allocate full invoice due"
                                                        class="absolute right-1 px-1.5 py-0.5 text-[9px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded transition"
                                                    >
                                                        MAX
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-3 text-right whitespace-nowrap font-mono text-[11px]">
                                                <span v-if="(item.due_amount - (Number(item.amount) || 0)) <= 0.001" class="text-emerald-600 font-bold">
                                                    ✓ Settled (৳0)
                                                </span>
                                                <span v-else class="text-slate-600 font-semibold">
                                                    ৳{{ (item.due_amount - (Number(item.amount) || 0)).toFixed(2) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="!payForm.allocations || payForm.allocations.length === 0">
                                            <td colspan="4" class="py-6 text-center text-slate-400 text-xs">
                                                No outstanding due invoices found for this customer.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Live Allocation Reconciliation Bar -->
                            <div
                                class="p-2.5 border-t flex flex-wrap items-center justify-between text-xs font-medium"
                                :class="{
                                    'bg-emerald-50/80 border-emerald-200 text-emerald-800': Math.abs(unallocatedAmount) <= 0.01 && totalAllocated > 0,
                                    'bg-amber-50/80 border-amber-200 text-amber-800': unallocatedAmount > 0.01,
                                    'bg-rose-50/80 border-rose-200 text-rose-800': unallocatedAmount < -0.01,
                                    'bg-slate-50 border-slate-200 text-slate-600': totalAllocated === 0
                                }"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">Allocated: ৳{{ totalAllocated.toFixed(2) }} of ৳{{ (Number(payForm.amount) || 0).toFixed(2) }}</span>
                                    <span v-if="Math.abs(unallocatedAmount) <= 0.01 && totalAllocated > 0" class="text-[11px] font-bold bg-emerald-200/60 text-emerald-900 px-2 py-0.5 rounded-full">
                                        ✓ Balanced
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="unallocatedAmount > 0.01" class="text-[11px] font-bold text-amber-700">
                                        ৳{{ unallocatedAmount.toFixed(2) }} unallocated
                                    </span>
                                    <span v-else-if="unallocatedAmount < -0.01" class="text-[11px] font-bold text-rose-700">
                                        Overallocated by ৳{{ Math.abs(unallocatedAmount).toFixed(2) }}
                                    </span>
                                    <button
                                        v-if="Math.abs(unallocatedAmount) > 0.01 && totalAllocated > 0"
                                        type="button"
                                        @click="syncTotalWithAllocated"
                                        class="text-[10px] font-bold underline hover:no-underline ml-1"
                                    >
                                        Set Total to ৳{{ totalAllocated.toFixed(2) }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit Financial Account & Reference -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                    Deposit Financial Account <span class="text-rose-500">*</span>
                                </label>
                                <SearchableSelect
                                    v-model="payForm.financial_account_id"
                                    :options="accountOptions"
                                    size="compact"
                                    :error="payForm.errors.financial_account_id"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Ref / TrxID / Receipt #</label>
                                <input
                                    v-model="payForm.payment_reference"
                                    type="text"
                                    placeholder="e.g. Bank TrxID, Cheque #"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Remarks / Note (Optional)</label>
                            <input
                                v-model="payForm.notes"
                                type="text"
                                placeholder="Optional notes for accounting ledger"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <!-- Modal Sticky Footer -->
                    <div class="px-6 py-4 bg-slate-50/90 border-t border-slate-200/80 flex items-center justify-end gap-3 shrink-0">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2.5 text-xs font-semibold border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing || payForm.amount <= 0 || totalAllocated <= 0 || Math.abs(unallocatedAmount) > 0.01"
                            class="px-6 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span v-if="payForm.processing">Processing...</span>
                            <span v-else>Confirm Collection (৳{{ (Number(payForm.amount) || 0).toFixed(2) }})</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Add / Edit Customer Modal -->
        <Modal :show="isAddCustomerModalOpen" max-width="xl" @close="isAddCustomerModalOpen = false">
            <div class="p-6 sm:p-7">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ editingCustomer ? 'Update customer details and opening due balance' : 'Register customer account and optional opening due balance' }}</p>
                    </div>
                    <button
                        @click="isAddCustomerModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 transition"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAddCustomer" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Name *</label>
                            <input
                                v-model="customerForm.name"
                                type="text"
                                required
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            />
                            <div v-if="customerForm.errors.name" class="text-xs text-rose-500 mt-1">
                                {{ customerForm.errors.name }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone / Mobile *</label>
                            <input
                                v-model="customerForm.phone"
                                type="text"
                                required
                                class="w-full text-xs font-mono font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            />
                            <div v-if="customerForm.errors.phone" class="text-xs text-rose-500 mt-1">
                                {{ customerForm.errors.phone }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Company / Organization</label>
                            <input
                                v-model="customerForm.company_name"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input
                                v-model="customerForm.email"
                                type="email"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            />
                            <div v-if="customerForm.errors.email" class="text-xs text-rose-500 mt-1">
                                {{ customerForm.errors.email }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery / Billing Address</label>
                        <textarea
                            v-model="customerForm.address"
                            rows="2"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="bg-amber-50/70 p-3.5 rounded-xl border border-amber-200/80">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-amber-900">Opening Receivable Due Balance (৳)</label>
                            <span class="text-[10px] text-amber-700 font-medium">Optional initial debt</span>
                        </div>
                        <input
                            v-model.number="customerForm.opening_balance"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full text-xs font-mono font-bold text-slate-800 px-3 py-2 bg-white border border-amber-300 rounded-xl focus:ring-1 focus:ring-amber-500"
                        />
                        <p class="text-[11px] text-amber-800/80 mt-1.5 leading-relaxed">
                            If this customer has an outstanding balance from prior purchases before using this software, enter it here. An opening receivable invoice will be recorded or adjusted in the ledger.
                        </p>
                        <div v-if="customerForm.errors.opening_balance" class="text-xs text-rose-500 mt-1">
                            {{ customerForm.errors.opening_balance }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddCustomerModalOpen = false"
                            class="px-4 py-2.5 text-xs font-semibold border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="customerForm.processing"
                            class="px-6 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition disabled:opacity-50"
                        >
                            {{ editingCustomer ? 'Update Customer' : 'Save Customer' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
