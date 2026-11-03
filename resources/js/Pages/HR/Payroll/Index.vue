<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    page_title: String,
    payrolls: Object,
    metrics: Object,
    employees: Array,
    financialAccounts: Array,
    filters: Object,
});

const defaultAccount = computed(() => {
    return (props.financialAccounts || []).find(a => a.is_default) || props.financialAccounts?.[0];
});

const accountOptions = computed(() => {
    return (props.financialAccounts || []).map(acc => ({
        id: acc.id,
        value: acc.id,
        label: acc.name,
        sublabel: acc.type === 'cash'
            ? `Balance: ৳${Number(acc.current_balance).toFixed(2)}`
            : `${acc.institution_name || ''} (${acc.account_number || ''}) • Bal: ৳${Number(acc.current_balance).toFixed(2)}`,
        group: acc.type === 'cash' ? 'Cash Registers' : (acc.type === 'bank' ? 'Bank Accounts' : 'MFS Wallets'),
    }));
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
    financial_account_id: defaultAccount.value?.id || '',
    payment_method: '',
    payment_reference: '',
    payment_date: new Date().toISOString().slice(0, 10),
});

function openPayModal(p) {
    activePayrollForPay.value = p;
    payForm.financial_account_id = defaultAccount.value?.id || '';
    payForm.payment_method = '';
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

const deletingPayroll = ref(null);
const isDeletingPayroll = ref(false);

function deletePayroll(p) {
    deletingPayroll.value = p;
}

function executeDeletePayroll() {
    if (!deletingPayroll.value) return;
    isDeletingPayroll.value = true;
    router.delete(route('payroll.destroy', deletingPayroll.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeletingPayroll.value = false;
            deletingPayroll.value = null;
        },
    });
}

function applyFilters() {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (monthYear.value) params.month_year = monthYear.value;
    router.get(route('payroll.index'), params, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    monthYear.value = new Date().toISOString().slice(0, 7);
    router.get(route('payroll.index'), {}, { preserveState: false, replace: true });
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
                                <th class="py-3 px-4 whitespace-nowrap">Payroll #</th>
                                <th class="py-3 px-4 whitespace-nowrap">Staff Member</th>
                                <th class="py-3 px-4 whitespace-nowrap">Cycle</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Basic Salary</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Allowances</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Deductions</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Net Salary</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in payrolls.data" :key="p.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-indigo-600 whitespace-nowrap">
                                    {{ p.payroll_number }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 max-w-[160px] lg:max-w-[200px] truncate" :title="p.employee?.name">{{ p.employee?.name }}</div>
                                    <div class="text-[11px] text-slate-400 max-w-[180px] truncate" :title="`${p.employee?.employee_code || ''} - ${p.employee?.designation || ''}`">
                                        {{ p.employee?.employee_code }} - {{ p.employee?.designation }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                    {{ p.month_year }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-slate-800 whitespace-nowrap">
                                    <CurrencyDisplay :amount="p.basic_salary" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-emerald-700 whitespace-nowrap">
                                    <CurrencyDisplay :amount="p.other_allowances" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-rose-600 whitespace-nowrap">
                                    <CurrencyDisplay :amount="p.deductions_total" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 text-sm whitespace-nowrap">
                                    <CurrencyDisplay :amount="p.net_salary" />
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <StatusBadge :status="p.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
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
                                <td colspan="9" class="py-10 text-center text-slate-400">
                                    No payroll records for this month. Click "+ Generate Payroll Slip" to calculate salaries.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Generate Payroll Modal -->
        <Modal :show="isGenerateModalOpen" @close="isGenerateModalOpen = false" max-width="2xl">
            <div class="p-6 sm:p-7">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Generate Staff Monthly Salary Slip</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Calculate monthly base salary, bonuses and deductions</p>
                    </div>
                    <button @click="isGenerateModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitGenerate" class="mt-5 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Staff Member *</label>
                        <select
                            v-model="generateForm.employee_id"
                            required
                            class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                        >
                            <option v-for="e in employees" :key="e.id" :value="e.id">
                                {{ e.name }} ({{ e.employee_code }}) - Basic: {{ e.basic_salary }}
                            </option>
                        </select>
                        <div v-if="generateForm.errors.employee_id" class="text-rose-500 text-[11px] mt-1">{{ generateForm.errors.employee_id }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Salary Month / Year *</label>
                            <input
                                v-model="generateForm.month_year"
                                type="month"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Base Monthly Salary</label>
                            <input
                                :value="selectedEmployee?.basic_salary || 0"
                                disabled
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 bg-slate-50 font-bold text-slate-700"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Other Allowances / Bonus</label>
                            <input
                                v-model.number="generateForm.other_allowances"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3 font-semibold text-emerald-700"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Advances / Deductions</label>
                            <input
                                v-model.number="generateForm.deductions_total"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3 font-semibold text-rose-600"
                            />
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-600">Estimated Net Salary:</span>
                        <span class="font-extrabold text-indigo-700 text-sm">
                            <CurrencyDisplay :amount="previewNetSalary" />
                        </span>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isGenerateModalOpen = false"
                            class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="generateForm.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Generate Slip
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Mark as Disbursed Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="xl">
            <div class="p-6 sm:p-7">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Disburse Salary - #{{ activePayrollForPay?.payroll_number }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Settle net salary payment and debit selected account</p>
                    </div>
                    <button @click="isPayModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitPay" class="mt-5 space-y-4 text-xs">
                    <div class="bg-emerald-50 p-3.5 rounded-xl border border-emerald-100 flex justify-between items-center">
                        <span class="text-emerald-800 font-semibold">Net Payable:</span>
                        <span class="text-emerald-900 font-extrabold text-base">
                            <CurrencyDisplay :amount="activePayrollForPay?.net_salary || 0" />
                        </span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Disbursement Account / Method *</label>
                        <SearchableSelect
                            v-model="payForm.financial_account_id"
                            :options="accountOptions"
                            size="compact"
                            :error="payForm.errors.financial_account_id"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Disbursement Date</label>
                            <input
                                v-model="payForm.payment_date"
                                type="date"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bank Reference / Txn ID</label>
                            <input
                                v-model="payForm.payment_reference"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Confirm Disbursement
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Payroll Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingPayroll"
            title="Delete Payroll Record"
            :item-name="deletingPayroll ? `#${deletingPayroll.payroll_number} - ${deletingPayroll.employee?.name || 'Employee'}` : ''"
            message="Are you sure you want to delete this payroll record? Any associated salary expense records will be permanently removed."
            confirm-text="Delete Payroll"
            :processing="isDeletingPayroll"
            @confirm="executeDeletePayroll"
            @close="deletingPayroll = null"
        />
    </AppLayout>
</template>
