<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    claims: Object,
    metrics: Object,
    employees: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const employeeId = ref(props.filters.employee_id || '');

// Submit Claim Modal
const isCreateModalOpen = ref(false);
const createForm = useForm({
    employee_id: props.employees.length > 0 ? props.employees[0].id : '',
    travel_date: new Date().toISOString().slice(0, 10),
    destination: '',
    purpose: '',
    travel_allowance: 0,
    daily_allowance: 0,
    other_expenses: 0,
    notes: '',
});

function openCreateModal() {
    createForm.reset();
    createForm.travel_date = new Date().toISOString().slice(0, 10);
    if (props.employees.length > 0) {
        createForm.employee_id = props.employees[0].id;
    }
    isCreateModalOpen.value = true;
}

function submitClaim() {
    createForm.post(route('tada.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
        },
    });
}

// Reject Modal
const isRejectModalOpen = ref(false);
const activeClaimForReject = ref(null);
const rejectForm = useForm({
    rejection_reason: '',
});

function openRejectModal(claim) {
    activeClaimForReject.value = claim;
    rejectForm.rejection_reason = '';
    isRejectModalOpen.value = true;
}

function submitReject() {
    if (!activeClaimForReject.value) return;
    rejectForm.post(route('tada.reject', activeClaimForReject.value.id), {
        onSuccess: () => {
            isRejectModalOpen.value = false;
        },
    });
}

function approveClaim(claim) {
    if (confirm(`Approve claim #${claim.claim_number} for ${claim.employee?.name}?`)) {
        router.post(route('tada.approve', claim.id));
    }
}

function deleteClaim(claim) {
    if (confirm(`Delete claim #${claim.claim_number}?`)) {
        router.delete(route('tada.destroy', claim.id));
    }
}

function applyFilters() {
    router.get(route('tada.index'), {
        search: search.value,
        status: status.value,
        employee_id: employeeId.value,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    employeeId.value = '';
    applyFilters();
}
</script>

<template>
    <AppLayout>
        <Head :title="page_title" />

        <template #header>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                    {{ page_title }}
                </h1>
                <p class="text-xs text-slate-500 font-medium">Manage field technician travel allowances, per diem fooding, conveyance, and manager approvals</p>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Claims</div>
                    <div class="text-2xl font-bold text-slate-900 mt-1">{{ metrics.total_claims }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">All field travel logs</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Approval</div>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ metrics.pending_approval }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Awaiting manager review</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Approved Unreimbursed</div>
                    <div class="text-2xl font-bold text-indigo-600 mt-1">
                        <CurrencyDisplay :amount="metrics.approved_unreimbursed" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Ready for payroll cycle</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Reimbursed</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">
                        <CurrencyDisplay :amount="metrics.reimbursed_total" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Disbursed with salary</div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-2 items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                    <div class="relative w-full md:w-64">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search claim #, staff, destination, purpose..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 placeholder:text-slate-400"
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
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="reimbursed">Reimbursed</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <select
                        v-model="employeeId"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[135px] max-w-xs"
                    >
                        <option value="">All Staff</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">
                            {{ e.name }} ({{ e.employee_code }})
                        </option>
                    </select>

                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button>

                    <button
                        v-if="search || status || employeeId"
                        @click="resetFilters"
                        class="px-3 py-2 text-slate-500 hover:text-slate-800 text-xs font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02] shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Submit TA/DA Claim</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-4">Claim #</th>
                                <th class="py-3 px-4">Staff Member</th>
                                <th class="py-3 px-4">Date & Destination</th>
                                <th class="py-3 px-4">Purpose</th>
                                <th class="py-3 px-4 text-right">Fare / TA</th>
                                <th class="py-3 px-4 text-right">Per Diem / DA</th>
                                <th class="py-3 px-4 text-right">Total Claim</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="claim in claims.data" :key="claim.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-600">
                                    {{ claim.claim_number }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ claim.employee?.name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ claim.employee?.designation }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <div class="font-semibold">{{ claim.destination }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">
                                        {{ formatDate(claim.travel_date) }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-xs">
                                    <div>{{ claim.purpose }}</div>
                                    <div v-if="claim.rejection_reason" class="text-rose-600 text-[11px] font-semibold mt-0.5">
                                        Rejected: {{ claim.rejection_reason }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-slate-700">
                                    <CurrencyDisplay :amount="claim.travel_allowance" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-slate-700">
                                    <CurrencyDisplay :amount="claim.daily_allowance" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-indigo-700">
                                    <CurrencyDisplay :amount="claim.total_amount" />
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <StatusBadge :status="claim.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <button
                                                v-if="claim.status === 'pending'"
                                                @click="approveClaim(claim)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Approve Claim</span>
                                            </button>

                                            <button
                                                v-if="claim.status === 'pending'"
                                                @click="openRejectModal(claim)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>Reject Claim</span>
                                            </button>

                                            <button
                                                @click="deleteClaim(claim)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Claim</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="claims.data.length === 0">
                                <td colspan="9" class="py-10 text-center text-slate-400">
                                    No TA/DA claims logged. Click "+ Submit TA/DA Claim" to file an allowance request.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit TA/DA Modal -->
        <Modal :show="isCreateModalOpen" @close="isCreateModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Submit Travel Allowance Claim</h3>
                    <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitClaim" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Staff Member *</label>
                        <select
                            v-model="createForm.employee_id"
                            required
                            class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                        >
                            <option v-for="e in employees" :key="e.id" :value="e.id">
                                {{ e.name }} ({{ e.employee_code }}) - {{ e.designation }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Travel Date *</label>
                            <input
                                v-model="createForm.travel_date"
                                type="date"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Destination / Site *</label>
                            <input
                                v-model="createForm.destination"
                                type="text"
                                required
class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Purpose of Visit *</label>
                        <textarea
                            v-model="createForm.purpose"
                            rows="2"
                            required
class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Travel Fare (TA)</label>
                            <input
                                v-model.number="createForm.travel_allowance"
                                type="number"
                                step="0.01"
                                min="0"
class="w-full text-xs font-bold rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Daily / Food (DA)</label>
                            <input
                                v-model.number="createForm.daily_allowance"
                                type="number"
                                step="0.01"
                                min="0"
class="w-full text-xs font-bold rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Other Overheads</label>
                            <input
                                v-model.number="createForm.other_expenses"
                                type="number"
                                step="0.01"
                                min="0"
class="w-full text-xs font-bold rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Submit Claim
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Reject Reason Modal -->
        <Modal :show="isRejectModalOpen" @close="isRejectModalOpen = false" max-width="sm">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Reject TA/DA Claim</h3>
                    <button @click="isRejectModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitReject" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Rejection Reason *</label>
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            rows="3"
                            required
class="w-full text-xs rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isRejectModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
