<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    page_title: String,
    expenses: Object,
    metrics: Object,
    categories: Array,
    employees: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const isModalOpen = ref(false);

const form = useForm({
    title: '',
    amount: '',
    expense_category_id: props.categories.length > 0 ? props.categories[0].id : '',
    employee_id: '',
    expense_date: new Date().toISOString().slice(0, 10),
    payment_method: 'cash',
    vendor_or_payee: '',
    receipt_reference: '',
    notes: '',
});

function openModal() {
    form.reset();
    form.expense_date = new Date().toISOString().slice(0, 10);
    form.payment_method = 'cash';
    if (props.categories.length > 0) {
        form.expense_category_id = props.categories[0].id;
    }
    isModalOpen.value = true;
}

function submitExpense() {
    form.post(route('expenses.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
        },
    });
}

function deleteExpense(exp) {
    if (confirm(`Are you sure you want to delete expense #${exp.expense_number} - "${exp.title}"?`)) {
        router.delete(route('expenses.destroy', exp.id));
    }
}

function applyFilters() {
    router.get(route('expenses.index'), {
        search: search.value,
        category_id: categoryId.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    categoryId.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
}
</script>

<template>
    <AppLayout>
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>💸</span>
                        <span>{{ page_title }}</span>
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Track operational overheads, office rent, utilities, petty cash, and vendor disbursements</p>
                </div>
                <button
                    @click="openModal"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Record Expense</span>
                </button>
            </div>
        </template>

        <div class="space-y-6">

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Recorded</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">
                        <CurrencyDisplay :amount="metrics.total_expenses" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Across {{ metrics.count }} vouchers</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">This Month</div>
                    <div class="text-xl font-extrabold text-rose-600 mt-1">
                        <CurrencyDisplay :amount="metrics.this_month" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Current calendar cycle</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Vouchers</div>
                    <div class="text-xl font-extrabold text-indigo-600 mt-1">{{ metrics.count }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Logged expense claims</div>
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
                            placeholder="Search expense #, title, payee, receipt..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select
                        v-model="categoryId"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    >
                        <option value="">All Categories</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <input
                        v-model="startDate"
                        @change="applyFilters"
                        type="date"
                        class="text-xs rounded-xl border border-slate-200 py-2 px-3"
                    />

                    <input
                        v-model="endDate"
                        @change="applyFilters"
                        type="date"
                        class="text-xs rounded-xl border border-slate-200 py-2 px-3"
                    />

                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button>

                    <button
                        v-if="search || categoryId || startDate || endDate"
                        @click="resetFilters"
                        class="px-3 py-2 text-slate-500 hover:text-slate-800 text-xs font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button
                        @click="openModal"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02] shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Record Expense</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-4">Expense Voucher</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4">Payee / Vendor</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Payment Method</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ exp.title }}</div>
                                    <div class="font-mono text-[11px] text-indigo-600 font-semibold">
                                        #{{ exp.expense_number }}
                                        <span v-if="exp.receipt_reference" class="text-slate-400 font-normal ml-1">
                                            (Ref: {{ exp.receipt_reference }})
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-medium text-[11px]">
                                        {{ exp.category?.name || 'General' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <div class="font-medium">{{ exp.vendor_or_payee || '-' }}</div>
                                    <div v-if="exp.employee" class="text-[11px] text-slate-400">
                                        Claimed by: {{ exp.employee.name }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ exp.expense_date ? new Date(exp.expense_date).toLocaleDateString() : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 capitalize">
                                    {{ exp.payment_method ? exp.payment_method.replace('_', ' ') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-rose-600">
                                    <CurrencyDisplay :amount="exp.amount" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button
                                        @click="deleteExpense(exp)"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                        title="Delete"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="expenses.data.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    No expense records found. Click "+ Record Expense" to log overheads.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: Record Expense -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Record Business Expense</h3>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitExpense" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Expense Title / Description *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="e.g. Office Internet Bill / Electricity Voucher"
                            class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Amount *</label>
                            <input
                                v-model.number="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3 font-bold"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Expense Date</label>
                            <input
                                v-model="form.expense_date"
                                type="date"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Category</label>
                            <select
                                v-model="form.expense_category_id"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            >
                                <option value="">General</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                            <select
                                v-model="form.payment_method"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            >
                                <option value="cash">Cash / Petty Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online / Card</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payee / Vendor Name</label>
                            <input
                                v-model="form.vendor_or_payee"
                                type="text"
                                placeholder="Vendor / Store"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Receipt Reference #</label>
                            <input
                                v-model="form.receipt_reference"
                                type="text"
                                placeholder="Invoice or Voucher #"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Disbursed To Employee (Optional)</label>
                        <select
                            v-model="form.employee_id"
                            class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        >
                            <option value="">None / Company direct</option>
                            <option v-for="e in employees" :key="e.id" :value="e.id">
                                {{ e.name }} ({{ e.employee_code }})
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Save Expense Voucher
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
