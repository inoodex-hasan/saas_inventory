<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    page_title: String,
    payrolls: Object,
    metrics: Object,
    employees: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const monthYear = ref(props.filters.month_year || new Date().toISOString().slice(0, 7));

// Generate Payroll Modal
const isGenerateModalOpen = ref(false);
const generateForm = useForm({
    employee_id: props.employees.length > 0 ? props.employees[0].id : '',
    month_year: monthYear.value,
    other_allowances: 0,
    deductions_total: 0,
    include_approved_tada: true,
});

const selectedEmployee = computed(() => {
    return props.employees.find(e => e.id === parseInt(generateForm.employee_id)) || null;
});

const previewNetSalary = computed(() => {
    const basic = selectedEmployee.value ? parseFloat(selectedEmployee.value.basic_salary) : 0;
    const allowances = parseFloat(generateForm.other_allowances) || 0;
    const deductions = parseFloat(generateForm.deductions_total) || 0;
    return Math.max(0, basic + allowances - deductions);
});

function openGenerateModal() {
    generateForm.reset();
    generateForm.month_year = monthYear.value;
    generateForm.include_approved_tada = true;
    if (props.employees.length > 0) {
        generateForm.employee_id = props.employees[0].id;
    }
    isGenerateModalOpen.value = true;
}

function submitGenerate() {
    generateForm.post(route('payroll.generate'), {
        onSuccess: () => {
            isGenerateModalOpen.value = false;
        },
    });
}

// Mark Paid Modal
const isPayModalOpen = ref(false);
const activePayrollForPay = ref(null);
const payForm = useForm({
    payment_method: 'bank_transfer',
    payment_reference: '',
    payment_date: new Date().toISOString().slice(0, 10),
});

function openPayModal(p) {
    activePayrollForPay.value = p;
    payForm.payment_method = 'bank_transfer';
    payForm.payment_reference = '';
    payForm.payment_date = new Date().toISOString().slice(0, 10);
    isPayModalOpen.value = true;
}

function submitPay() {
    if (!activePayrollForPay.value) return;
    payForm.post(route('payroll.pay', activePayrollForPay.value.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
        },
    });
}

function deletePayroll(p) {
    if (confirm(`Are you sure you want to delete payroll record #${p.payroll_number}?`)) {
        router.delete(route('payroll.destroy', p.id));
    }
}

function applyFilters() {
    router.get(route('payroll.index'), {
        search: search.value,
        status: status.value,
        month_year: monthYear.value,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    monthYear.value = new Date().toISOString().slice(0, 7);
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
                <p class="text-xs text-slate-500 font-medium">Generate monthly salary statements, consolidate approved field TA/DA claims, and issue pay slips</p>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Payrolls Generated</div>
                    <div class="text-2xl font-bold text-slate-900 mt-1">{{ metrics.total_payrolls }} Records</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Cycle: {{ monthYear }}</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Disbursed</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_disbursed" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Marked as paid</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Disbursement</div>
                    <div class="text-2xl font-bold text-amber-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_pending" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Draft or approved claims</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-2 items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                    <div class="relative w-full md:w-64">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search payroll #, staff name, employee code..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 placeholder:text-slate-400"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <input
                        v-model="monthYear"
                        @change="applyFilters"
                        type="month"
                        class="text-xs rounded-xl border border-slate-200 py-2 px-3 font-semibold text-slate-700"
                    />

                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="paid">Paid / Disbursed</option>
                    </select>

                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button>

                    <button
                        v-if="search || status || monthYear !== new Date().toISOString().slice(0, 7)"
                        @click="resetFilters"
                        class="px-3 py-2 text-slate-500 hover:text-slate-800 text-xs font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button
                        @click="openGenerateModal"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02] shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Generate Payroll Slip</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-4">Payroll #</th>
                                <th class="py-3 px-4">Staff Member</th>
                                <th class="py-3 px-4">Cycle</th>
                                <th class="py-3 px-4 text-right">Basic Salary</th>
                                <th class="py-3 px-4 text-right">TA/DA Reimbursement</th>
                                <th class="py-3 px-4 text-right">Allowances</th>
                                <th class="py-3 px-4 text-right">Deductions</th>
                                <th class="py-3 px-4 text-right">Net Salary</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in payrolls.data" :key="p.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-600">
                                    {{ p.payroll_number }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ p.employee?.name }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ p.employee?.employee_code }} - {{ p.employee?.designation }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-700">
                                    {{ p.month_year }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-slate-800">
                                    <CurrencyDisplay :amount="p.basic_salary" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-emerald-700">
                                    <CurrencyDisplay :amount="p.tada_allowance" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-emerald-700">
                                    <CurrencyDisplay :amount="p.other_allowances" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-rose-600">
                                    <CurrencyDisplay :amount="p.deductions_total" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 text-sm">
                                    <CurrencyDisplay :amount="p.net_salary" />
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <StatusBadge :status="p.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <a
                                                :href="route('payroll.pdf', p.id)"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-sky-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Salary Slip (PDF)</span>
                                            </a>

                                            <button
                                                v-if="p.status !== 'paid'"
                                                @click="openPayModal(p)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>Disburse Salary</span>
                                            </button>

                                            <button
                                                @click="deletePayroll(p)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Record</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="payrolls.data.length === 0">
                                <td colspan="10" class="py-10 text-center text-slate-400">
                                    No payroll records for this month. Click "+ Generate Payroll Slip" to calculate salaries.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Generate Payroll Modal -->
        <Modal :show="isGenerateModalOpen" @close="isGenerateModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Generate Staff Monthly Salary Slip</h3>
                    <button @click="isGenerateModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitGenerate" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Staff Member *</label>
                        <select
                            v-model="generateForm.employee_id"
                            required
                            class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                        >
                            <option v-for="e in employees" :key="e.id" :value="e.id">
                                {{ e.name }} ({{ e.employee_code }}) - Basic: {{ e.basic_salary }}
                            </option>
                        </select>
                        <div v-if="generateForm.errors.employee_id" class="text-rose-500 text-[11px] mt-1">{{ generateForm.errors.employee_id }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Salary Month / Year *</label>
                            <input
                                v-model="generateForm.month_year"
                                type="month"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Base Monthly Salary</label>
                            <input
                                :value="selectedEmployee?.basic_salary || 0"
                                disabled
                                class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3 bg-slate-50 font-bold text-slate-700"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Other Allowances / Bonus</label>
                            <input
                                v-model.number="generateForm.other_allowances"
                                type="number"
                                step="0.01"
                                min="0"
class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3 font-semibold text-emerald-700"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Advances / Deductions</label>
                            <input
                                v-model.number="generateForm.deductions_total"
                                type="number"
                                step="0.01"
                                min="0"
class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3 font-semibold text-rose-600"
                            />
                        </div>
                    </div>

                    <div class="bg-indigo-50/60 p-3 rounded-xl border border-indigo-100 flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="generateForm.include_approved_tada"
                                type="checkbox"
                                class="rounded text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="font-semibold text-indigo-900">Include Approved TA/DA Claims</span>
                        </label>
                        <span class="text-[10px] text-indigo-600 italic">Auto-marks claims as reimbursed</span>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-600">Estimated Net Salary:</span>
                        <span class="font-extrabold text-indigo-700 text-sm">
                            <CurrencyDisplay :amount="previewNetSalary" />
                        </span>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isGenerateModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="generateForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Generate Slip
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Mark as Disbursed Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Disburse Salary - #{{ activePayrollForPay?.payroll_number }}</h3>
                    <button @click="isPayModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitPay" class="mt-4 space-y-4 text-xs">
                    <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-100 flex justify-between items-center">
                        <span class="text-emerald-800 font-semibold">Net Payable:</span>
                        <span class="text-emerald-900 font-extrabold text-base">
                            <CurrencyDisplay :amount="activePayrollForPay?.net_salary || 0" />
                        </span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Disbursement Method *</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                        >
                            <option value="bank_transfer">Bank Transfer / BEFTN / NPSB</option>
                            <option value="cash">Cash in Envelope</option>
                            <option value="cheque">Company Cheque</option>
                            <option value="bkash">bKash Corporate</option>
                            <option value="nagad">Nagad Corporate</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Disbursement Date</label>
                            <input
                                v-model="payForm.payment_date"
                                type="date"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bank Reference / Txn ID</label>
                            <input
                                v-model="payForm.payment_reference"
                                type="text"
class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs py-2 px-3"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Confirm Disbursement
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
