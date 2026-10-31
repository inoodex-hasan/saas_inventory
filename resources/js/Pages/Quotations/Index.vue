<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    quotations: Object,
    customers: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const customerId = ref(props.filters.customer_id || '');

const isConvertModalOpen = ref(false);
const selectedQuotation = ref(null);
const isViewModalOpen = ref(false);
const viewingQuotation = ref(null);

function openViewModal(q) {
    viewingQuotation.value = q;
    isViewModalOpen.value = true;
}

const convertForm = useForm({
    warehouse_id: '',
});

function applyFilter() {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (customerId.value) params.customer_id = customerId.value;

    router.get(route('quotations.index'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    customerId.value = '';
    router.get(route('quotations.index'), {}, { preserveState: false, replace: true });
}

function openConvertModal(q) {
    selectedQuotation.value = q;
    isConvertModalOpen.value = true;
}

function submitConvert() {
    if (!selectedQuotation.value) return;
    convertForm.post(route('quotations.convert', selectedQuotation.value.id), {
        onSuccess: () => {
            isConvertModalOpen.value = false;
            convertForm.reset();
        },
    });
}

function deleteQuotation(q) {
    if (confirm(`Are you sure you want to delete quotation #${q.quotation_number}?`)) {
        router.delete(route('quotations.destroy', q.id));
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Quotations & Estimates" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Quotations & Estimates</h1>
                    <p class="text-xs text-slate-500 font-medium">Generate formal customer quotations, proposals, and convert them to Sales Orders</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Proposals</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_quotations || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Quoted Value</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_quoted_value || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Accepted / Converted</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        {{ metrics?.accepted_quotations || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active / Pending</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.pending_proposals || 0 }}
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
                        <option value="sent">Sent</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="converted">Converted to SO</option>
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
                        :href="route('quotations.create')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Quotation</span>
                    </Link>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 whitespace-nowrap">Quotation #</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Customer</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Date Issued</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Valid Until</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Total Amount</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Status</th>
                                <th class="py-3.5 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="q in quotations.data" :key="q.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900 whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openViewModal(q)"
                                        class="font-mono font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1.5 transition text-left group"
                                        title="Click to view quotation details"
                                    >
                                        <span>{{ q.quotation_number }}</span>
                                        <svg class="w-3.5 h-3.5 text-indigo-400 group-hover:text-indigo-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-800 whitespace-nowrap">
                                    <div class="max-w-[180px] lg:max-w-[240px] truncate" :title="q.customer?.name">{{ q.customer?.name }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 font-medium whitespace-nowrap">
                                    {{ formatDate(q.quotation_date) }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 font-medium whitespace-nowrap">
                                    {{ formatDate(q.valid_until) }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                    <CurrencyDisplay :amount="q.total_amount" />
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <StatusBadge :status="q.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown align="right">
                                        <div class="p-1">
                                            <button
                                                type="button"
                                                @click="openViewModal(q)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Details</span>
                                            </button>

                                            <Link
                                                v-if="q.status !== 'converted'"
                                                :href="route('quotations.edit', q.id)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Quotation</span>
                                            </Link>

                                            <a
                                                :href="route('quotations.pdf', q.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Print / PDF</span>
                                            </a>

                                            <button
                                                v-if="q.status !== 'converted'"
                                                @click="openConvertModal(q)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-emerald-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                                <span>Convert to SO</span>
                                            </button>

                                            <button
                                                v-if="q.status !== 'converted'"
                                                @click="deleteQuotation(q)"
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

                            <tr v-if="!quotations.data || quotations.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400 font-medium">
                                    No quotations found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Convert to SO Modal -->
        <Modal :show="isConvertModalOpen" max-width="md" @close="isConvertModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Convert Quotation to Sales Order</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Convert <span class="font-semibold text-slate-800">{{ selectedQuotation?.quotation_number }}</span>
                    into an official confirmed Sales Order. Please select the fulfilling warehouse hub:
                </p>

                <form @submit.prevent="submitConvert" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Fulfilling Warehouse Hub *</label>
                        <select
                            v-model="convertForm.warehouse_id"
                            required
                            class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="" disabled>-- Select Warehouse Hub --</option>
                            <option v-for="w in $page.props.team?.warehouses || []" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isConvertModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="convertForm.processing"
                            class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition"
                        >
                            Confirm Conversion
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- View Quotation Details Modal -->
        <Modal :show="isViewModalOpen" max-width="3xl" @close="isViewModalOpen = false">
            <div v-if="viewingQuotation" class="p-6 space-y-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Formal Quotation</span>
                            <span class="font-mono text-base font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200">
                                {{ viewingQuotation.quotation_number }}
                            </span>
                            <StatusBadge :status="viewingQuotation.status" />
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Issued: <strong class="text-slate-700">{{ formatDate(viewingQuotation.quotation_date) }}</strong>
                            &bull; Valid Until: <strong class="text-slate-700">{{ formatDate(viewingQuotation.valid_until) }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            :href="route('quotations.pdf', viewingQuotation.id)"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Print PDF</span>
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

                <!-- Recipient & Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Customer Client</span>
                        <div class="text-sm font-bold text-slate-900">{{ viewingQuotation.customer?.name || 'Walk-in Customer' }}</div>
                        <div class="text-xs text-slate-600 mt-0.5">Phone: {{ viewingQuotation.customer?.phone || '—' }}</div>
                        <div v-if="viewingQuotation.customer?.address" class="text-xs text-slate-500 mt-0.5">
                            {{ viewingQuotation.customer.address }}
                        </div>
                    </div>

                    <div class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-100 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block mb-1">Total Valuation</span>
                            <div class="text-2xl font-black text-indigo-700">
                                <CurrencyDisplay :amount="viewingQuotation.total_amount" />
                            </div>
                        </div>
                        <div class="text-[11px] text-indigo-600 font-medium mt-1">
                            {{ viewingQuotation.items?.length || 0 }} Catalog line item(s) quoted
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-2.5 px-4">Item & SKU</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Unit Price</th>
                                <th class="py-2.5 px-4 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="it in viewingQuotation.items" :key="it.id" class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-4">
                                    <div class="font-bold text-slate-900">{{ it.product?.name || 'Product Item' }}</div>
                                    <div v-if="it.product?.sku" class="text-[10px] font-mono text-slate-400">SKU: {{ it.product.sku }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono font-semibold text-slate-700">
                                    {{ it.quantity }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600">
                                    <CurrencyDisplay :amount="it.unit_price" />
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                                    <CurrencyDisplay :amount="it.subtotal || (it.quantity * it.unit_price)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Financial Breakdown -->
                <div class="flex justify-end">
                    <div class="w-72 space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-3">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-semibold text-slate-900"><CurrencyDisplay :amount="viewingQuotation.subtotal" /></span>
                        </div>
                        <div v-if="Number(viewingQuotation.discount_amount) > 0" class="flex justify-between text-rose-600">
                            <span>Discount:</span>
                            <span class="font-semibold">-<CurrencyDisplay :amount="viewingQuotation.discount_amount" /></span>
                        </div>
                        <div v-if="Number(viewingQuotation.tax_amount) > 0" class="flex justify-between">
                            <span>Tax ({{ viewingQuotation.tax_rate }}%):</span>
                            <span class="font-semibold text-slate-900">+<CurrencyDisplay :amount="viewingQuotation.tax_amount" /></span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-slate-900 pt-2 border-t border-slate-200">
                            <span>Grand Total:</span>
                            <span class="text-indigo-600"><CurrencyDisplay :amount="viewingQuotation.total_amount" /></span>
                        </div>
                    </div>
                </div>

                <!-- Notes & Terms -->
                <div v-if="viewingQuotation.notes || viewingQuotation.terms" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs pt-2 border-t border-slate-100">
                    <div v-if="viewingQuotation.notes" class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="font-bold text-slate-700 block mb-1">Notes / Narration:</span>
                        <p class="text-slate-600 whitespace-pre-line">{{ viewingQuotation.notes }}</p>
                    </div>
                    <div v-if="viewingQuotation.terms" class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="font-bold text-slate-700 block mb-1">Terms & Conditions:</span>
                        <p class="text-slate-600 font-mono text-[11px] whitespace-pre-line">{{ viewingQuotation.terms }}</p>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <button
                        type="button"
                        @click="isViewModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Close
                    </button>

                    <div class="flex items-center gap-2">
                        <Link
                            v-if="viewingQuotation.status !== 'converted'"
                            :href="route('quotations.edit', viewingQuotation.id)"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Edit Quotation</span>
                        </Link>

                        <button
                            v-if="viewingQuotation.status !== 'converted'"
                            @click="isViewModalOpen = false; openConvertModal(viewingQuotation)"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-600/30 transition"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span>Convert to Sales Order</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
