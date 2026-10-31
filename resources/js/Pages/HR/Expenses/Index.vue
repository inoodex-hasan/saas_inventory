<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    expenses: Object,
    metrics: Object,
    categories: Array,
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
const categoryId = ref(props.filters.category_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const isModalOpen = ref(false);
const isCategoryModalOpen = ref(false);
const isCreatingInlineCategory = ref(false);

const categoryForm = useForm({
    name: '',
    description: '',
});

const form = useForm({
    title: '',
    amount: '',
    expense_category_id: props.categories.length > 0 ? props.categories[0].id : '',
    financial_account_id: defaultAccount.value?.id || '',
    employee_id: '',
    expense_date: new Date().toISOString().slice(0, 10),
    payment_method: '',
    vendor_or_payee: '',
    receipt_reference: '',
    notes: '',
});

function openModal() {
    form.reset();
    form.expense_date = new Date().toISOString().slice(0, 10);
    form.financial_account_id = defaultAccount.value?.id || '';
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

function openCategoryModal() {
    categoryForm.reset();
    isCategoryModalOpen.value = true;
}

function submitCategory() {
    categoryForm.post(route('expense-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
            isCategoryModalOpen.value = false;
        },
    });
}

function submitInlineCategory() {
    if (!categoryForm.name.trim()) return;
    categoryForm.post(route('expense-categories.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            const newlyCreated = (page.props.categories || []).find(c => c.name.toLowerCase() === categoryForm.name.trim().toLowerCase());
            if (newlyCreated) {
                form.expense_category_id = newlyCreated.id;
            }
            categoryForm.reset();
            isCreatingInlineCategory.value = false;
        },
    });
}

function deleteCategory(cat) {
    if (confirm(`Are you sure you want to delete expense category "${cat.name}"?`)) {
        router.delete(route('expense-categories.destroy', cat.id), {
            preserveScroll: true,
        });
    }
}

function applyFilters() {
    const params = {};
    if (search.value) params.search = search.value;
    if (categoryId.value) params.category_id = categoryId.value;
    if (startDate.value) params.start_date = startDate.value;
    if (endDate.value) params.end_date = endDate.value;
    router.get(route('expenses.index'), params, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    categoryId.value = '';
    startDate.value = '';
    endDate.value = '';
    router.get(route('expenses.index'), {}, { preserveState: false, replace: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                        {{ page_title }}
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Track operational overheads, office rent, utilities, petty cash, and vendor disbursements</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Recorded</div>
                    <div class="text-2xl font-bold text-slate-900 mt-1">
                        <CurrencyDisplay :amount="metrics.total_expenses" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Across {{ metrics.count }} vouchers</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">This Month</div>
                    <div class="text-2xl font-bold text-rose-600 mt-1">
                        <CurrencyDisplay :amount="metrics.this_month" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Current calendar cycle</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Vouchers</div>
                    <div class="text-2xl font-bold text-indigo-600 mt-1">{{ metrics.count }}</div>
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
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 placeholder:text-slate-400"
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
                        type="button"
                        @click="openCategoryModal"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-semibold transition shrink-0"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Categories</span>
                    </button>

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
                                <th class="py-3 px-4 whitespace-nowrap">Expense Voucher</th>
                                <th class="py-3 px-4 whitespace-nowrap">Category</th>
                                <th class="py-3 px-4 whitespace-nowrap">Payee / Vendor</th>
                                <th class="py-3 px-4 whitespace-nowrap">Date</th>
                                <th class="py-3 px-4 whitespace-nowrap">Payment Method</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Amount</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 max-w-[200px] lg:max-w-[260px] truncate" :title="exp.title">{{ exp.title }}</div>
                                    <div class="font-mono text-[11px] text-indigo-600 font-semibold">
                                        #{{ exp.expense_number }}
                                        <span v-if="exp.receipt_reference" class="text-slate-400 font-normal ml-1">
                                            (Ref: {{ exp.receipt_reference }})
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-medium text-[11px]">
                                        {{ exp.category?.name || 'General' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 whitespace-nowrap">
                                    <div class="font-medium max-w-[160px] lg:max-w-[200px] truncate" :title="exp.vendor_or_payee || '-'">{{ exp.vendor_or_payee || '-' }}</div>
                                    <div v-if="exp.employee" class="text-[11px] text-slate-400 max-w-[160px] truncate" :title="exp.employee.name">
                                        Claimed by: {{ exp.employee.name }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 font-medium whitespace-nowrap">
                                    {{ formatDate(exp.expense_date) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 whitespace-nowrap">
                                    <div class="font-medium text-xs">{{ exp.payment_method || exp.financial_account?.name || '-' }}</div>
                                    <div v-if="exp.financial_account?.account_number" class="text-[10px] text-slate-400 font-mono">
                                        {{ exp.financial_account.institution_name }} ({{ exp.financial_account.account_number }})
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-rose-600 whitespace-nowrap">
                                    <CurrencyDisplay :amount="exp.amount" />
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <button
                                                @click="deleteExpense(exp)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Expense</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
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
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="2xl">
            <div class="p-6 sm:p-7">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Record Business Expense</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Log an overhead or operational expense with dynamic disbursement tracking</p>
                    </div>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitExpense" class="mt-5 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Expense Title / Description *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Amount *</label>
                            <input
                                v-model.number="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full text-xs font-bold rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Expense Date *</label>
                            <input
                                v-model="form.expense_date"
                                type="date"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-semibold text-slate-700">Category</label>
                                <button
                                    type="button"
                                    @click="isCreatingInlineCategory = !isCreatingInlineCategory"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800"
                                >
                                    {{ isCreatingInlineCategory ? 'Select Existing' : '+ New' }}
                                </button>
                            </div>

                            <div v-if="isCreatingInlineCategory" class="flex gap-1.5">
                                <input
                                    v-model="categoryForm.name"
                                    type="text"
                                    class="flex-1 text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-2.5 font-medium"
                                    @keyup.enter.prevent="submitInlineCategory"
                                />
                                <button
                                    type="button"
                                    :disabled="!categoryForm.name.trim() || categoryForm.processing"
                                    @click="submitInlineCategory"
                                    class="px-3 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 disabled:opacity-50 transition shrink-0"
                                >
                                    Add
                                </button>
                            </div>

                            <select
                                v-else
                                v-model="form.expense_category_id"
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            >
                                <option value="">General</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payment Method / Account *</label>
                            <SearchableSelect
                                v-model="form.financial_account_id"
                                :options="accountOptions"
                                size="compact"
                                :error="form.errors.financial_account_id"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payee / Vendor Name</label>
                            <input
                                v-model="form.vendor_or_payee"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Receipt Reference #</label>
                            <input
                                v-model="form.receipt_reference"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Disbursed To Employee (Optional)</label>
                        <select
                            v-model="form.employee_id"
                            class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2.5 px-3"
                        >
                            <option value="">None / Company direct</option>
                            <option v-for="e in employees" :key="e.id" :value="e.id">
                                {{ e.name }} ({{ e.employee_code }})
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Save Expense Voucher
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Manage Categories Modal -->
        <Modal :show="isCategoryModalOpen" @close="isCategoryModalOpen = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Manage Expense Categories</h2>
                        <p class="text-xs text-slate-500">Add or manage categories for daily business expense classification</p>
                    </div>
                    <button @click="isCategoryModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">
                        &times;
                    </button>
                </div>

                <!-- Add Category Form -->
                <form @submit.prevent="submitCategory" class="mt-4 p-4 bg-slate-50 rounded-2xl space-y-3 border border-slate-100">
                    <div class="text-xs font-bold text-slate-800">New Category</div>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Category Name *</label>
                            <input
                                v-model="categoryForm.name"
                                type="text"
                                required
                                class="w-full text-xs rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3 font-medium"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Description (Optional)</label>
                            <input
                                v-model="categoryForm.description"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-2 px-3"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            :disabled="!categoryForm.name.trim() || categoryForm.processing"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition disabled:opacity-50"
                        >
                            Create Category
                        </button>
                    </div>
                </form>

                <!-- Category List -->
                <div class="mt-4 space-y-2 max-h-60 overflow-y-auto">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-1">
                        Categories ({{ categories.length }})
                    </div>
                    <div v-if="categories.length === 0" class="text-center py-4 text-xs text-slate-400 italic">
                        No categories defined yet.
                    </div>
                    <div
                        v-for="cat in categories"
                        :key="cat.id"
                        class="flex items-center justify-between p-2.5 rounded-xl border border-slate-100 bg-white hover:bg-slate-50 transition text-xs"
                    >
                        <div class="min-w-0 pr-2">
                            <div class="font-bold text-slate-800 truncate">{{ cat.name }}</div>
                            <div v-if="cat.description" class="text-[11px] text-slate-400 mt-0.5 truncate">
                                {{ cat.description }}
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="deleteCategory(cat)"
                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition shrink-0"
                            title="Delete category"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-5 flex justify-end border-t border-slate-100 pt-3">
                    <button
                        type="button"
                        @click="isCategoryModalOpen = false"
                        class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50 transition"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
