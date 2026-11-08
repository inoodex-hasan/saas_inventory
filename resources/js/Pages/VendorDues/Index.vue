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
    vendors: [Object, Array],
    metrics: Object,
    financialAccounts: Array,
    filters: Object,
});

const vendorList = computed(() => {
    if (Array.isArray(props.vendors)) return props.vendors;
    return props.vendors?.data || [];
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
    vendor_id: '',
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
    router.get(route('vendor-dues.index'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    filter.value = 'all_with_dues';
    router.get(route('vendor-dues.index'), {}, { preserveState: false, replace: true });
}

function openPayModal(vendor, order = null) {
    selectedVendor.value = vendor;
    payForm.clearErrors();
    payForm.vendor_id = vendor.id;
    payForm.financial_account_id = defaultAccount?.id || '';
    payForm.payment_reference = '';
    payForm.notes = '';

    const dueOrders = vendor.due_orders || [];
    selectedOrder.value = order || dueOrders[0] || null;

    if (order) {
        payForm.order_id = order.id;
        payForm.amount = Number(order.due_amount);
        payForm.allocations = dueOrders.map(o => ({
            order_id: o.id,
            po_number: o.po_number || o.order_number,
            order_date: o.order_date,
            total_amount: Number(o.total_amount),
            paid_amount: Number(o.paid_amount),
            due_amount: Number(o.due_amount),
            amount: o.id === order.id ? Number(o.due_amount) : 0,
        }));
    } else {
        const totalDue = Number(vendor.outstanding_due);
        payForm.order_id = '';
        payForm.amount = totalDue;
        payForm.allocations = dueOrders.map(o => ({
            order_id: o.id,
            po_number: o.po_number || o.order_number,
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
    if (!selectedVendor.value) return;
    const baseDue = selectedOrder.value ? Number(selectedOrder.value.due_amount) : Number(selectedVendor.value.outstanding_due || 0);
    payForm.amount = Math.round((baseDue * (percent / 100)) * 100) / 100;
    autoAllocateFIFO();
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
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template v-for="v in vendorList" :key="v.id">
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

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <button
                                            v-if="v.outstanding_due > 0"
                                            type="button"
                                            @click="openPayModal(v)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs shadow-indigo-600/20 transition"
                                            title="Disburse payment across vendor purchase orders"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <span>Pay Due</span>
                                        </button>
                                        <span v-else class="text-xs text-slate-400 font-medium">Settled</span>
                                    </td>
                                </tr>

                                <!-- Expandable Unpaid Purchase Orders Sub-table -->
                                <tr v-if="expandedVendorIds.has(v.id) && v.due_orders && v.due_orders.length > 0" class="bg-slate-50/60">
                                    <td colspan="8" class="p-3 pl-12 pr-4">
                                        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs space-y-2.5">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                                <span>Unpaid Purchase Orders for {{ v.name }} ({{ v.due_orders.length }})</span>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-rose-600 font-mono font-bold">Total Outstanding: <CurrencyDisplay :amount="v.outstanding_due" /></span>
                                                    <button
                                                        v-if="v.outstanding_due > 0"
                                                        type="button"
                                                        @click="openPayModal(v)"
                                                        class="px-3 py-1 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs transition flex items-center gap-1"
                                                    >
                                                        <span>Pay Due</span>
                                                    </button>
                                                </div>
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
                                                            <th class="py-1.5 text-center">Status</th>
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

                            <tr v-if="!vendorList || vendorList.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 text-xs">
                                    No vendor accounts found matching your filter criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div v-if="vendors?.links" class="p-4 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-500 font-medium">
                        Showing <span class="font-bold text-slate-700">{{ vendors.from || 0 }}</span> to <span class="font-bold text-slate-700">{{ vendors.to || 0 }}</span> of <span class="font-bold text-slate-700">{{ vendors.total || 0 }}</span> vendor accounts
                    </p>
                    <Pagination :links="vendors.links" />
                </div>
            </div>
        </div>

        <!-- Pay Due Modal (Multi-PO Allocation) -->
        <Modal :show="isPayModalOpen" max-width="2xl" @close="isPayModalOpen = false">
            <div class="flex flex-col max-h-[90vh]">
                <!-- Modal Sticky Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center shadow-sm shadow-indigo-500/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900">Record Vendor Payment</h3>
                                <span v-if="payForm.allocations?.length > 1" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Multi-PO Settlement
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Vendor: <strong class="text-slate-800">{{ selectedVendor?.name }}</strong>
                                <span v-if="selectedVendor?.phone" class="text-slate-500 font-mono"> ({{ selectedVendor.phone }})</span>
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
                        <!-- Vendor Outstanding Due Overview Card -->
                        <div class="p-3.5 bg-gradient-to-r from-slate-50 to-slate-100/80 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="text-[11px] text-slate-500 font-medium">Total Vendor Payable Due:</span>
                                <div class="text-lg font-black text-rose-600">
                                    <CurrencyDisplay :amount="selectedVendor?.outstanding_due || 0" />
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-slate-500 font-medium">Pending Due Purchase Orders:</span>
                                <div class="text-sm font-bold text-slate-800">
                                    {{ payForm.allocations?.length || 0 }} {{ (payForm.allocations?.length || 0) === 1 ? 'PO' : 'POs' }}
                                </div>
                            </div>
                        </div>

                        <!-- Total Disbursement Amount & Quick Allocation Presets -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">
                                    Total Payment Disbursement <span class="text-rose-500">*</span>
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
                                        title="Allocate payment to oldest due POs first"
                                        class="text-[10px] font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded transition flex items-center gap-1"
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
                                :max="selectedVendor?.outstanding_due"
                                required
                                placeholder="Enter amount to pay vendor (e.g. 5000)"
                                class="w-full text-base font-bold font-mono px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 shadow-xs"
                            />
                            <div v-if="payForm.errors.amount" class="text-xs text-rose-500 mt-1 font-medium">
                                {{ payForm.errors.amount }}
                            </div>
                        </div>

                        <!-- Multi-PO Allocation Breakdown Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                            <div class="bg-slate-50 px-3.5 py-2 border-b border-slate-200 flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                    Purchase Order Payment Allocation
                                </span>
                                <span class="text-[11px] text-slate-500">
                                    Type any custom amount per PO or use Auto-FIFO
                                </span>
                            </div>

                            <div class="max-h-60 overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50/70 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200 sticky top-0">
                                        <tr>
                                            <th class="py-2.5 px-3">PO # / Date</th>
                                            <th class="py-2.5 px-3 text-right">Outstanding Due</th>
                                            <th class="py-2.5 px-3 text-center w-40">Payment Allocation</th>
                                            <th class="py-2.5 px-3 text-right">Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in payForm.allocations" :key="item.order_id" class="hover:bg-slate-50/70 transition">
                                            <td class="py-2.5 px-3">
                                                <div class="font-bold text-slate-900 font-mono">{{ item.po_number }}</div>
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
                                                        class="w-full pl-6 pr-11 py-1.5 text-xs font-mono font-bold text-right bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-1 focus:ring-indigo-500"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="fillMax(item)"
                                                        title="Allocate full PO due"
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
                                                No outstanding due purchase orders found for this vendor.
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

                        <!-- Pay From Account & Reference -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Cheque No / TrxID / Ref</label>
                                <input
                                    v-model="payForm.payment_reference"
                                    type="text"
                                    placeholder="e.g. Cheque #, Bank Ref"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Remarks / Note (Optional)</label>
                            <input
                                v-model="payForm.notes"
                                type="text"
                                placeholder="Optional notes for disbursement ledger"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
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
                            class="px-6 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span v-if="payForm.processing">Processing...</span>
                            <span v-else>Confirm Disbursement (৳{{ (Number(payForm.amount) || 0).toFixed(2) }})</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
