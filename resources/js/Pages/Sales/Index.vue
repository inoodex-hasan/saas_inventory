<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    orders: Object,
    customers: Array,
    warehouses: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const paymentStatus = ref(props.filters.payment_status || '');
const customerId = ref(props.filters.customer_id || '');

const isPaymentModalOpen = ref(false);
const selectedOrder = ref(null);

const paymentForm = useForm({
    amount: '',
});

function applyFilter() {
    router.get(route('sales.index'), {
        search: search.value,
        status: status.value,
        payment_status: paymentStatus.value,
        customer_id: customerId.value,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    paymentStatus.value = '';
    customerId.value = '';
    applyFilter();
}

function confirmOrder(order) {
    if (confirm(`Confirm Order #${order.order_number}? Warehouse inventory will be reserved.`)) {
        router.post(route('sales.confirm', order.id));
    }
}

function dispatchOrder(order) {
    if (confirm(`Dispatch Order #${order.order_number}? Warehouse stocks will be deducted.`)) {
        router.post(route('sales.dispatch', order.id));
    }
}

function completeOrder(order) {
    if (confirm(`Mark Order #${order.order_number} as completed / fulfilled?`)) {
        router.post(route('sales.complete', order.id));
    }
}

function cancelOrder(order) {
    if (confirm(`Cancel Order #${order.order_number}? Any inventory reservations will be released.`)) {
        router.post(route('sales.cancel', order.id));
    }
}

function openPaymentModal(order) {
    selectedOrder.value = order;
    paymentForm.amount = order.due_amount > 0 ? order.due_amount : '';
    isPaymentModalOpen.value = true;
}

function submitPayment() {
    if (!selectedOrder.value) return;
    paymentForm.post(route('sales.pay', selectedOrder.value.id), {
        onSuccess: () => {
            isPaymentModalOpen.value = false;
            paymentForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Sales Orders & Terminal" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Sales Orders & Commercial Invoices</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage outbound customer billing, fulfillment pipeline, and receivables</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Sales Invoiced</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_sales || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Receivables (Due)</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_receivables || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending Fulfillment</span>
                    <div class="text-2xl font-black text-blue-600 mt-2">
                        {{ metrics?.pending_dispatch || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Orders Logged</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_orders || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center gap-2">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        placeholder="Search order #, customer name, mobile..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <select
                        v-model="paymentStatus"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 min-w-[125px]"
                    >
                        <option value="">All Payments</option>
                        <option value="paid">Fully Paid</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>

                    <select
                        v-model="customerId"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 min-w-[135px] max-w-xs"
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
                        :href="route('sales.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Sale</span>
                    </Link>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Order / Invoice #</th>
                                <th class="py-3.5 px-4">Customer</th>
                                <th class="py-3.5 px-4">Warehouse</th>
                                <th class="py-3.5 px-4">Total Amount</th>
                                <th class="py-3.5 px-4">Paid / Due</th>
                                <th class="py-3.5 px-4">Order Status</th>
                                <th class="py-3.5 px-4">Payment</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900">{{ order.order_number }}</div>
                                    <div class="text-xs text-slate-400">{{ order.order_date }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900">{{ order.customer?.name || 'Walk-in Customer' }}</div>
                                    <div class="text-xs text-slate-500">{{ order.customer?.phone || '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ order.warehouse?.name }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <CurrencyDisplay :amount="order.total_amount" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-xs text-emerald-600 font-medium">Paid: <CurrencyDisplay :amount="order.paid_amount" /></div>
                                    <div v-if="order.due_amount > 0" class="text-xs text-rose-600 font-semibold">Due: <CurrencyDisplay :amount="order.due_amount" /></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="order.status" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="order.payment_status" />
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1">
                                    <!-- Print PDF buttons -->
                                    <a
                                        :href="route('sales.invoice', order.id)"
                                        target="_blank"
                                        title="Print Invoice"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>

                                    <a
                                        :href="route('sales.pos', order.id)"
                                        target="_blank"
                                        title="Print POS Thermal Slip"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                        </svg>
                                    </a>

                                    <!-- Status Transitions -->
                                    <button
                                        v-if="order.status === 'draft'"
                                        @click="confirmOrder(order)"
                                        title="Confirm Order"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button
                                        v-if="order.status === 'confirmed'"
                                        @click="dispatchOrder(order)"
                                        title="Dispatch Goods"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-purple-600 rounded-lg hover:bg-purple-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>

                                    <button
                                        v-if="order.status === 'dispatched'"
                                        @click="completeOrder(order)"
                                        title="Mark Fulfilled"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    <!-- Record Payment -->
                                    <button
                                        v-if="order.due_amount > 0 && order.status !== 'cancelled'"
                                        @click="openPaymentModal(order)"
                                        title="Collect Payment"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </button>

                                    <!-- Cancel Order -->
                                    <button
                                        v-if="['draft', 'confirmed'].includes(order.status)"
                                        @click="cancelOrder(order)"
                                        title="Cancel Order"
                                        class="inline-flex p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!orders.data || orders.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 font-medium">
                                    No sales orders found matching your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.links && orders.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>Showing {{ orders.from || 0 }} to {{ orders.to || 0 }} of {{ orders.total }} orders</div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in orders.links"
                            :key="idx"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition',
                                link.active ? 'bg-emerald-600 text-white font-bold' : 'hover:bg-slate-100 text-slate-700',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <Modal :show="isPaymentModalOpen" max-width="md" @close="isPaymentModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Record Customer Payment</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Invoice: <span class="font-semibold text-slate-800">{{ selectedOrder?.order_number }}</span>
                    (Outstanding Due: <span class="text-rose-600 font-bold"><CurrencyDisplay :amount="selectedOrder?.due_amount" /></span>)
                </p>

                <form @submit.prevent="submitPayment" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Amount Paid *</label>
                        <input
                            v-model="paymentForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="selectedOrder?.due_amount"
                            required
                            class="w-full text-base font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPaymentModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="paymentForm.processing"
                            class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition"
                        >
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
