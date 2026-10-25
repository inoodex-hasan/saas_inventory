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
    projects: Object,
    metrics: Object,
    clients: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const paymentStatus = ref(props.filters.payment_status || '');
const clientId = ref(props.filters.client_id || '');

// Quick Payment Modal
const isPayModalOpen = ref(false);
const activeProjectForPay = ref(null);
const payForm = useForm({
    amount: '',
    payment_method: 'cash',
    notes: '',
});

function openPayModal(project) {
    activeProjectForPay.value = project;
    payForm.amount = project.due_amount;
    payForm.payment_method = 'cash';
    payForm.notes = '';
    isPayModalOpen.value = true;
}

function submitPay() {
    if (!activeProjectForPay.value) return;
    payForm.post(route('projects.pay', activeProjectForPay.value.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
        },
    });
}

// Quick Status Transition Modal
const isStatusModalOpen = ref(false);
const activeProjectForStatus = ref(null);
const statusForm = useForm({
    status: '',
});

function openStatusModal(project) {
    activeProjectForStatus.value = project;
    statusForm.status = project.status;
    isStatusModalOpen.value = true;
}

function submitStatusChange() {
    if (!activeProjectForStatus.value) return;
    statusForm.post(route('projects.transition', activeProjectForStatus.value.id), {
        onSuccess: () => {
            isStatusModalOpen.value = false;
        },
    });
}

function applyFilters() {
    router.get(route('projects.index'), {
        search: search.value,
        status: status.value,
        payment_status: paymentStatus.value,
        client_id: clientId.value,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    paymentStatus.value = '';
    clientId.value = '';
    applyFilters();
}

function deleteProject(project) {
    if (confirm(`Are you sure you want to delete project #${project.project_code} - "${project.name}"?`)) {
        router.delete(route('projects.destroy', project.id));
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
                    <p class="text-xs text-slate-500 font-medium">Track contract budgets, inventory bills of materials, direct expenses, and milestone billings</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Projects</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">{{ metrics.total_projects }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ metrics.in_progress }} in execution</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Progress</div>
                    <div class="text-xl font-extrabold text-blue-600 mt-1">{{ metrics.in_progress }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ metrics.completed }} completed</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Contract Budget</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">
                        <CurrencyDisplay :amount="metrics.total_budget" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Agreed contract values</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Billed</div>
                    <div class="text-xl font-extrabold text-indigo-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_billed" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Invoiced to clients</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Direct Costs</div>
                    <div class="text-xl font-extrabold text-amber-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_costs" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Subcontractor & expenses</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Dues</div>
                    <div class="text-xl font-extrabold text-rose-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_due" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Margin: <CurrencyDisplay :amount="metrics.gross_margin" /></div>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                    <div class="relative w-full md:w-64">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search project code, name, client..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="planning">Planning</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <select
                        v-model="paymentStatus"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[135px]"
                    >
                        <option value="">All Billing Statuses</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="paid">Paid</option>
                    </select>

                    <select
                        v-model="clientId"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[135px] max-w-xs"
                    >
                        <option value="">All Clients</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">
                            {{ c.name }} {{ c.company_name ? `(${c.company_name})` : '' }}
                        </option>
                    </select>

                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button>

                    <button
                        v-if="search || status || paymentStatus || clientId"
                        @click="resetFilters"
                        class="px-3 py-2 text-slate-500 hover:text-slate-800 text-xs font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto shrink-0">
                    <Link
                        :href="route('projects.create')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Project</span>
                    </Link>
                </div>
            </div>

            <!-- Projects Data Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-4">Project</th>
                                <th class="py-3 px-4">Client</th>
                                <th class="py-3 px-4">Timeline</th>
                                <th class="py-3 px-4 text-right">Budget / Billed</th>
                                <th class="py-3 px-4 text-right">Costs</th>
                                <th class="py-3 px-4 text-right">Paid / Due</th>
                                <th class="py-3 px-4 text-center">Lifecycle</th>
                                <th class="py-3 px-4 text-center">Billing</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="project in projects.data"
                                :key="project.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-3.5 px-4">
                                    <Link :href="route('projects.show', project.id)" class="group">
                                        <div class="font-bold text-slate-900 group-hover:text-indigo-600 transition">
                                            {{ project.name }}
                                        </div>
                                        <div class="font-mono text-[11px] text-indigo-600 font-semibold">
                                            #{{ project.project_code }}
                                        </div>
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ project.client?.name || 'Walk-in / Direct' }}
                                    </div>
                                    <div v-if="project.client?.company_name" class="text-[11px] text-slate-400">
                                        {{ project.client.company_name }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div v-if="project.start_date" class="text-xs font-medium text-slate-600">
                                        <span>{{ formatDate(project.start_date) }}</span>
                                        <span v-if="project.end_date"> - {{ formatDate(project.end_date) }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-xs">Not scheduled</span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="font-bold text-slate-900">
                                        <CurrencyDisplay :amount="project.billed_total" />
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        Budget: <CurrencyDisplay :amount="project.budget" />
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-amber-700">
                                    <CurrencyDisplay :amount="project.costs_total" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="font-bold text-emerald-600">
                                        <CurrencyDisplay :amount="project.paid_amount" />
                                    </div>
                                    <div v-if="project.due_amount > 0" class="text-[11px] font-bold text-rose-600">
                                        Due: <CurrencyDisplay :amount="project.due_amount" />
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <StatusBadge :status="project.status" />
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <StatusBadge :status="project.payment_status" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <Link
                                                :href="route('projects.show', project.id)"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>Project Workbench</span>
                                            </Link>

                                            <a
                                                :href="route('projects.pdf', project.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-sky-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Statement (PDF)</span>
                                            </a>

                                            <button
                                                v-if="project.due_amount > 0"
                                                @click="openPayModal(project)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>Collect Payment</span>
                                            </button>

                                            <button
                                                @click="openStatusModal(project)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Update Status</span>
                                            </button>

                                            <button
                                                @click="deleteProject(project)"
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

                            <tr v-if="projects.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">                                    <p class="font-semibold text-slate-700">No projects found</p>
                                    <p class="text-xs text-slate-400 mt-1">Start by creating your first client project or job contract.</p>
                                    <div class="mt-4">
                                        <Link
                                            :href="route('projects.create')"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 transition"
                                        >
                                            Create Project
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="projects.links && projects.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Showing {{ projects.from || 0 }} to {{ projects.to || 0 }} of {{ projects.total }} projects
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, key) in projects.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="px-3 py-1.5 text-xs text-slate-400 border border-slate-200 rounded-lg"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="px-3 py-1.5 text-xs rounded-lg border transition-colors"
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'text-slate-600 border-slate-200 hover:bg-slate-50'"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Payment Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        Collect Payment - Project #{{ activeProjectForPay?.project_code }}
                    </h3>
                    <button @click="isPayModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitPay" class="mt-4 space-y-4">
                    <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 flex justify-between items-center text-xs">
                        <span class="text-indigo-700 font-medium">Outstanding Balance:</span>
                        <span class="text-indigo-900 font-bold text-sm">
                            <CurrencyDisplay :amount="activeProjectForPay?.due_amount || 0" />
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount to Receive *</label>
                        <input
                            v-model.number="payForm.amount"
                            type="number"
                            step="0.01"
                            :max="activeProjectForPay?.due_amount"
                            required
                            class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                        <div v-if="payForm.errors.amount" class="text-rose-500 text-[11px] mt-1">{{ payForm.errors.amount }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online / Card</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Notes / Reference</label>
                        <textarea
                            v-model="payForm.notes"
                            rows="2"
class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Quick Status Change Modal -->
        <Modal :show="isStatusModalOpen" @close="isStatusModalOpen = false" max-width="sm">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        Update Lifecycle Status
                    </h3>
                    <button @click="isStatusModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitStatusChange" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">New Project Status</label>
                        <select
                            v-model="statusForm.status"
                            class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="planning">Planning</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isStatusModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="statusForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Save Status
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
