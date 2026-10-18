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
    page_title: String,
    tickets: Object,
    metrics: Object,
    technicians: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const priority = ref(props.filters.priority || '');
const technicianId = ref(props.filters.technician_id || '');
const paymentStatus = ref(props.filters.payment_status || '');

// Quick Payment Modal
const isPayModalOpen = ref(false);
const activeTicketForPay = ref(null);
const payForm = useForm({
    amount: '',
    payment_method: 'cash',
    notes: '',
});

function openPayModal(ticket) {
    activeTicketForPay.value = ticket;
    payForm.amount = ticket.due_amount;
    payForm.payment_method = 'cash';
    payForm.notes = '';
    isPayModalOpen.value = true;
}

function submitPay() {
    if (!activeTicketForPay.value) return;
    payForm.post(route('services.pay', activeTicketForPay.value.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
        },
    });
}

// Quick Status Transition Modal
const isStatusModalOpen = ref(false);
const activeTicketForStatus = ref(null);
const statusForm = useForm({
    status: '',
});

function openStatusModal(ticket) {
    activeTicketForStatus.value = ticket;
    statusForm.status = ticket.status;
    isStatusModalOpen.value = true;
}

function submitStatusChange() {
    if (!activeTicketForStatus.value) return;
    statusForm.post(route('services.transition', activeTicketForStatus.value.id), {
        onSuccess: () => {
            isStatusModalOpen.value = false;
        },
    });
}

function applyFilters() {
    router.get(route('services.index'), {
        search: search.value,
        status: status.value,
        priority: priority.value,
        technician_id: technicianId.value,
        payment_status: paymentStatus.value,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    priority.value = '';
    technicianId.value = '';
    paymentStatus.value = '';
    applyFilters();
}

function deleteTicket(ticket) {
    if (confirm(`Are you sure you want to delete service ticket #${ticket.ticket_number}?`)) {
        router.delete(route('services.destroy', ticket.id));
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>{{ page_title }}</span>
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Manage workshop repairs, equipment drop-offs, parts consumption, and customer invoices</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Jobs</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">{{ metrics.total_tickets }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">All logged repairs</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-amber-100 bg-amber-50/20 shadow-sm">
                    <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">In Progress</div>
                    <div class="text-xl font-extrabold text-amber-700 mt-1">{{ metrics.in_progress }}</div>
                    <div class="text-[10px] text-amber-500 mt-0.5">Diagnosing & repair</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-indigo-100 bg-indigo-50/20 shadow-sm">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Ready / Repaired</div>
                    <div class="text-xl font-extrabold text-indigo-700 mt-1">{{ metrics.repaired }}</div>
                    <div class="text-[10px] text-indigo-500 mt-0.5">Awaiting customer pickup</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-emerald-100 bg-emerald-50/20 shadow-sm">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Delivered</div>
                    <div class="text-xl font-extrabold text-emerald-700 mt-1">{{ metrics.delivered }}</div>
                    <div class="text-[10px] text-emerald-500 mt-0.5">Handed over & closed</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Value</div>
                    <div class="text-xl font-extrabold text-indigo-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_revenue" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Billed service charges</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-rose-100 bg-rose-50/20 shadow-sm">
                    <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Pending Dues</div>
                    <div class="text-xl font-extrabold text-rose-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_dues" />
                    </div>
                    <div class="text-[10px] text-rose-500 mt-0.5">Unsettled receivables</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-100 shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 flex-1">
                    <div>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search ticket #, customer, phone..."
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 pl-3 pr-4"
                        />
                    </div>
                    <div>
                        <select
                            v-model="status"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 pl-3 pr-8"
                        >
                            <option value="">All Statuses</option>
                            <option value="received">Received / Logged</option>
                            <option value="diagnosing">Diagnosing</option>
                            <option value="waiting_parts">Waiting for Parts</option>
                            <option value="repaired">Repaired / Ready</option>
                            <option value="delivered">Delivered / Handed Over</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="technicianId"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 pl-3 pr-8"
                        >
                            <option value="">All Technicians</option>
                            <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="paymentStatus"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 pl-3 pr-8"
                        >
                            <option value="">All Payments</option>
                            <option value="paid">Paid</option>
                            <option value="partially_paid">Partially Paid</option>
                            <option value="unpaid">Unpaid / Due</option>
                        </select>
                        <button
                            @click="resetFilters"
                            type="button"
                            class="px-2.5 py-2 text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-all shrink-0"
                            title="Reset filters"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <Link
                        :href="route('services.create')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Create Service</span>
                    </Link>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-4">Ticket / Date</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Device & Serial</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Priority</th>
                                <th class="py-3 px-4">Assigned Tech</th>
                                <th class="py-3 px-4 text-right">Bill & Due</th>
                                <th class="py-3 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4">
                                    <Link
                                        :href="route('services.show', ticket.id)"
                                        class="font-mono font-bold text-indigo-600 hover:text-indigo-800 hover:underline"
                                    >
                                        {{ ticket.ticket_number }}
                                    </Link>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ formatDate(ticket.received_date) }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800">{{ ticket.customer?.name || 'Walk-in Customer' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ ticket.customer?.phone }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800">{{ ticket.device_name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">
                                        {{ ticket.serial_no || ticket.device_brand || 'No S/N recorded' }}
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="openStatusModal(ticket)"
                                        class="cursor-pointer hover:opacity-80 transition-opacity"
                                        title="Click to change status"
                                    >
                                        <StatusBadge :status="ticket.status" />
                                    </button>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase rounded-md"
                                        :class="{
                                            'bg-rose-100 text-rose-700': ticket.priority === 'urgent',
                                            'bg-orange-100 text-orange-700': ticket.priority === 'high',
                                            'bg-blue-100 text-blue-700': ticket.priority === 'normal',
                                            'bg-slate-100 text-slate-600': ticket.priority === 'low'
                                        }"
                                    >
                                        {{ ticket.priority }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="ticket.technician" class="text-slate-700 font-medium">
                                        {{ ticket.technician.name }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">Unassigned</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="font-bold text-slate-800">
                                        <CurrencyDisplay :amount="ticket.total_amount" />
                                    </div>
                                    <div v-if="parseFloat(ticket.due_amount) > 0" class="text-[11px] font-bold text-rose-600 mt-0.5 flex items-center justify-end gap-1">
                                        <span>Due:</span>
                                        <CurrencyDisplay :amount="ticket.due_amount" />
                                        <button
                                            @click="openPayModal(ticket)"
                                            class="ml-1 text-[9px] bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 px-1.5 py-0.5 rounded font-bold"
                                        >
                                            Collect
                                        </button>
                                    </div>
                                    <div v-else class="text-[11px] font-bold text-emerald-600">
                                        Settled
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <Link
                                                :href="route('services.show', ticket.id)"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Details</span>
                                            </Link>

                                            <a
                                                :href="route('services.token', ticket.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-amber-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                                </svg>
                                                <span>Intake Token (PDF)</span>
                                            </a>

                                            <a
                                                :href="route('services.invoice', ticket.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-emerald-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Service Invoice (PDF)</span>
                                            </a>

                                            <Link
                                                :href="route('services.edit', ticket.id)"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Ticket</span>
                                            </Link>

                                            <button
                                                @click="deleteTicket(ticket)"
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
                            <tr v-if="!tickets.data || tickets.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <p class="font-medium text-slate-600">No service job tickets found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Intake a customer device repair to begin tracking.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Showing {{ tickets.from || 0 }} to {{ tickets.to || 0 }} of {{ tickets.total }} tickets
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in tickets.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-2.5 py-1 text-xs rounded-lg transition-all"
                            :class="{
                                'bg-indigo-600 text-white font-bold': link.active,
                                'text-slate-600 hover:bg-slate-100': !link.active && link.url,
                                'text-slate-300 pointer-events-none': !link.url
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Collect Payment Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-base font-bold text-slate-800 mb-1">
                    Collect Payment — #{{ activeTicketForPay?.ticket_number }}
                </h3>
                <p class="text-xs text-slate-500 mb-4">
                    Outstanding balance: <strong class="text-rose-600"><CurrencyDisplay :amount="activeTicketForPay?.due_amount || 0" /></strong>
                </p>

                <form @submit.prevent="submitPay" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Amount (৳) <span class="text-rose-500">*</span></label>
                        <input
                            v-model="payForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="activeTicketForPay?.due_amount || 9999999"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            required
                        />
                        <div class="flex gap-2 mt-1.5">
                            <button
                                type="button"
                                @click="payForm.amount = activeTicketForPay?.due_amount"
                                class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-semibold"
                            >
                                Settle Full Due
                            </button>
                            <button
                                type="button"
                                @click="payForm.amount = ((activeTicketForPay?.due_amount || 0) / 2).toFixed(2)"
                                class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-semibold"
                            >
                                Pay 50%
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="cash">Cash Counter</option>
                            <option value="mobile_banking">Mobile Banking (bKash / Nagad / Rocket)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card POS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Notes / TrxID</label>
                        <input
                            v-model="payForm.notes"
                            type="text"
                            placeholder="e.g. bKash TrxID, receipt slip #"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Status Change Modal -->
        <Modal :show="isStatusModalOpen" @close="isStatusModalOpen = false" max-width="sm">
            <div class="p-6">
                <h3 class="text-base font-bold text-slate-800 mb-1">
                    Update Ticket Status
                </h3>
                <p class="text-xs text-slate-500 mb-4">
                    Change status for #{{ activeTicketForStatus?.ticket_number }}
                </p>

                <form @submit.prevent="submitStatusChange" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status State</label>
                        <select
                            v-model="statusForm.status"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                        >
                            <option value="received">1. Received / Intake</option>
                            <option value="diagnosing">2. Diagnosing</option>
                            <option value="waiting_parts">3. Waiting for Spare Parts</option>
                            <option value="repaired">4. Repaired (Ready for pickup)</option>
                            <option value="delivered">5. Delivered / Closed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isStatusModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="statusForm.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
