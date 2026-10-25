<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Pagination from '@/Components/Pagination.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    payments: Object,
    metrics: Object,
    filters: Object,
});

const statusFilter = ref(props.filters?.status || 'pending');
const search = ref(props.filters?.search || '');

function applyFilters() {
    router.get(route('admin.payments.index'), {
        status: statusFilter.value,
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function approvePayment(payment) {
    if (confirm(`Approve payment ${payment.payment_number} (TrxID: ${payment.trx_id}) for ${payment.team?.name}? This will immediately activate/extend their subscription.`)) {
        router.post(route('admin.payments.approve', payment.id));
    }
}

// Rejection Modal
const isRejectModalOpen = ref(false);
const rejectingPayment = ref(null);
const rejectForm = useForm({
    rejection_reason: '',
});

function openRejectModal(payment) {
    rejectingPayment.value = payment;
    rejectForm.rejection_reason = '';
    rejectForm.clearErrors();
    isRejectModalOpen.value = true;
}

function submitReject() {
    if (!rejectingPayment.value) return;
    rejectForm.post(route('admin.payments.reject', rejectingPayment.value.id), {
        onSuccess: () => {
            isRejectModalOpen.value = false;
            rejectingPayment.value = null;
        },
    });
}

function paymentStatusClass(status) {
    switch (status) {
        case 'approved': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'rejected': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
}
</script>

<template>
    <AppLayout title="Payment Verification Hub">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Payment Verification Hub</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Audit bKash, Nagad, Rocket, and Bank TrxIDs to activate tenant subscriptions</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Highlights -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Pending Approvals</p>
                        <p class="text-3xl font-extrabold text-amber-950 mt-1">{{ metrics.pending }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Approved This Month</p>
                        <p class="text-3xl font-extrabold text-emerald-950 mt-1">{{ metrics.approved_this_month }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-indigo-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Subscription Revenue (Month)</p>
                        <p class="text-2xl font-extrabold text-indigo-950 mt-1">
                            <CurrencyDisplay :amount="metrics.revenue_this_month_bdt" />
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row gap-2 items-stretch sm:items-center justify-between">
                <div class="flex-1 flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by TrxID, receipt #, sender number, or tenant name..."
                            class="w-full pl-10 pr-4 py-2 text-xs bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl pl-3 pr-8 min-w-[150px] py-2 font-semibold"
                    >
                        <option value="pending">Pending Approvals</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="all">All Payments</option>
                    </select>
                </div>

                <button
                    @click="applyFilters"
                    class="px-4 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all shrink-0"
                >
                    Filter
                </button>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Receipt #</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Tenant Workspace</th>
                                <th class="py-3 px-4">Plan & Cycle</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Sender Mobile</th>
                                <th class="py-3 px-4">Transaction ID (TrxID)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="pmt in payments.data"
                                :key="pmt.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-bold text-slate-900">{{ pmt.payment_number }}</td>
                                <td class="py-3 px-4 font-sans text-slate-600">{{ formatDate(pmt.created_at) }}</td>
                                <td class="py-3 px-4 font-sans font-bold text-slate-900">
                                    {{ pmt.team?.name }}
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-700">
                                    {{ pmt.plan?.name }} ({{ pmt.billing_cycle }})
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <CurrencyDisplay :amount="pmt.amount_bdt" />
                                </td>
                                <td class="py-3 px-4 uppercase text-slate-600 font-sans font-bold">{{ pmt.payment_method }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-800">{{ pmt.sender_number || '—' }}</td>
                                <td class="py-3 px-4 font-bold text-amber-700 bg-amber-50/40 rounded px-2">
                                    {{ pmt.trx_id || '—' }}
                                </td>
                                <td class="py-3 px-4 text-center font-sans">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase', paymentStatusClass(pmt.status)]">
                                        {{ pmt.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-sans">
                                    <div class="flex items-center justify-end">
                                        <ActionDropdown align="right">
                                            <template v-if="pmt.status === 'pending'">
                                                <button
                                                    @click="approvePayment(pmt)"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-emerald-600 hover:bg-emerald-50 font-semibold transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Approve Payment</span>
                                                </button>
                                                <button
                                                    @click="openRejectModal(pmt)"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50 font-semibold transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    <span>Reject Payment</span>
                                                </button>
                                            </template>
                                            <div v-else class="px-3 py-2 text-xs text-slate-400 italic">
                                                {{ pmt.reviewer ? `Processed by ${pmt.reviewer.name}` : 'Processed' }}
                                            </div>
                                            <button
                                                v-if="pmt.trx_id"
                                                @click="navigator.clipboard?.writeText(pmt.trx_id)"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span>Copy TrxID ({{ pmt.trx_id }})</span>
                                            </button>
                                        </ActionDropdown>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="10" class="py-12 text-center text-slate-400 font-sans">
                                    No payment receipts matching this status.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payments.links && payments.links.length > 3" class="p-4 border-t border-slate-100">
                    <Pagination :links="payments.links" />
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <Modal :show="isRejectModalOpen" @close="isRejectModalOpen = false" max-width="md">
            <div class="p-6 space-y-4 text-xs">
                <h3 class="text-base font-bold text-slate-900">
                    Reject Payment: {{ rejectingPayment?.payment_number }}
                </h3>
                <p class="text-slate-500">Provide an explanation for rejecting this payment (e.g. invalid TrxID, incorrect amount).</p>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reason for Rejection *</label>
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            rows="3"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-rose-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        ></textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="text-rose-500 text-[11px] mt-1">{{ rejectForm.errors.rejection_reason }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isRejectModalOpen = false"
                            class="px-4 py-2 font-medium text-slate-600 hover:text-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-all shadow-sm"
                        >
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
