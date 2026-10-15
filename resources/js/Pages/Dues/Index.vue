<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    customers: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const filter = ref(props.filters.filter || 'all_with_dues');

const isPayModalOpen = ref(false);
const selectedCustomer = ref(null);
const selectedOrder = ref(null);

const payForm = useForm({
    order_id: '',
    amount: '',
    payment_method: 'cash',
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

function openPayModal(customer, order) {
    selectedCustomer.value = customer;
    selectedOrder.value = order;
    payForm.order_id = order.id;
    payForm.amount = order.due_amount;
    payForm.payment_method = 'cash';
    payForm.notes = '';
    isPayModalOpen.value = true;
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
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Customer Receivables & Dues Ledger</h1>
                    <p class="text-xs text-slate-500 font-medium">Track outstanding customer balances, payment recovery, and credit aging</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Outstanding Due</p>
                        <p class="text-xl font-bold text-rose-600 mt-0.5"><CurrencyDisplay :amount="metrics.total_receivables" /></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Collections Paid</p>
                        <p class="text-xl font-bold text-emerald-600 mt-0.5"><CurrencyDisplay :amount="metrics.total_collections" /></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Invoiced Amount</p>
                        <p class="text-xl font-bold text-slate-900 mt-0.5"><CurrencyDisplay :amount="metrics.total_billed" /></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Customers in Debt</p>
                        <p class="text-xl font-bold text-amber-600 mt-0.5">{{ metrics.customers_with_due }}</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-2">
                <div class="flex flex-col sm:flex-row items-center gap-2 flex-1 w-full">
                    <div class="relative flex-1 w-full">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilter"
                            type="text"
                            placeholder="Search customer by name, mobile, address..."
                            class="w-full pl-10 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold shrink-0">
                        <button
                            type="button"
                            @click="filter = 'all_with_dues'; applyFilter()"
                            :class="filter === 'all_with_dues' ? 'bg-white shadow text-rose-600' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-lg transition"
                        >
                            Has Dues Only
                        </button>
                        <button
                            type="button"
                            @click="filter = 'fully_paid'; applyFilter()"
                            :class="filter === 'fully_paid' ? 'bg-white shadow text-emerald-600' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-lg transition"
                        >
                            Fully Cleared
                        </button>
                        <button
                            type="button"
                            @click="filter = 'all'; applyFilter()"
                            :class="filter === 'all' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-lg transition"
                        >
                            All Clients
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <!-- <button
                        @click="applyFilter"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button> -->
                    <Link
                        :href="route('sales.create')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-rose-600/30 transition-all hover:scale-[1.02] shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Sale</span>
                    </Link>
                </div>
            </div>

            <!-- Customer Due Cards / List -->
            <div class="space-y-4">
                <div
                    v-for="c in customers"
                    :key="c.id"
                    class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">{{ c.name }}</h2>
                            <p class="text-xs text-slate-500">Phone: {{ c.phone }} • {{ c.address || 'Address not listed' }}</p>
                        </div>

                        <div class="flex items-center gap-6">
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase block">Total Billed</span>
                                <span class="text-sm font-semibold text-slate-700"><CurrencyDisplay :amount="c.total_billed" /></span>
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase block">Total Paid</span>
                                <span class="text-sm font-semibold text-emerald-600"><CurrencyDisplay :amount="c.total_paid" /></span>
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase block">Balance Due</span>
                                <span class="text-base font-bold text-rose-600"><CurrencyDisplay :amount="c.total_due" /></span>
                            </div>
                        </div>
                    </div>

                    <!-- Due Orders Breakdown -->
                    <div v-if="c.due_orders && c.due_orders.length > 0" class="space-y-2">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Unpaid / Partially Paid Invoices:</span>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="order in c.due_orders"
                                :key="order.id"
                                class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3"
                            >
                                <div>
                                    <div class="font-bold text-xs text-slate-900">{{ order.order_number }}</div>
                                    <div class="text-[11px] text-slate-400">{{ order.order_date }}</div>
                                    <div class="text-xs text-rose-600 font-semibold mt-1">Due: <CurrencyDisplay :amount="order.due_amount" /></div>
                                </div>
                                <button
                                    type="button"
                                    @click="openPayModal(c, order)"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                                >
                                    Collect
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-emerald-600 font-medium bg-emerald-50/50 p-2.5 rounded-xl border border-emerald-100 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        All invoices are fully paid and reconciled. No outstanding dues.
                    </div>
                </div>

                <div v-if="!customers || customers.length === 0" class="bg-white p-12 text-center rounded-2xl border border-slate-200 text-slate-400 font-medium">
                    No customer accounts found matching your filter criteria.
                </div>
            </div>
        </div>

        <!-- Collect Payment Modal -->
        <Modal :show="isPayModalOpen" max-width="md" @close="isPayModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Collect Due Payment</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Client: <span class="font-semibold text-slate-800">{{ selectedCustomer?.name }}</span><br>
                    Invoice: <span class="font-semibold text-slate-800">{{ selectedOrder?.order_number }}</span>
                    (Due: <span class="text-rose-600 font-bold"><CurrencyDisplay :amount="selectedOrder?.due_amount" /></span>)
                </p>

                <form @submit.prevent="submitPay" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Collection Amount *</label>
                        <input
                            v-model.number="payForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="selectedOrder?.due_amount"
                            required
                            class="w-full text-base font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Payment Method</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="cash">Cash in Hand</option>
                            <option value="bank_transfer">Bank Transfer (EFT / RTGS)</option>
                            <option value="bkash">bKash Merchant</option>
                            <option value="nagad">Nagad</option>
                            <option value="cheque">Cheque Clearing</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Remarks / Note</label>
                        <input
                            v-model="payForm.notes"
                            type="text"
                            placeholder="e.g. Money receipt #MR-902, Bank slip #8829"
                            class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
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
                            :disabled="payForm.processing"
                            class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition"
                        >
                            Confirm Collection
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
