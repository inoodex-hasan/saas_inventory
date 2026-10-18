<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    page_title: String,
    accounts: Object,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');

function applyFilters() {
    router.get(route('accounts.index'), {
        search: search.value,
        type: typeFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    typeFilter.value = '';
    applyFilters();
}

// Modal States
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingAccount = ref(null);

const createForm = useForm({
    account_code: '',
    name: '',
    type: 'asset',
    subtype: '',
    description: '',
});

const editForm = useForm({
    name: '',
    subtype: '',
    description: '',
    is_active: true,
});

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
}

function submitCreate() {
    createForm.post(route('accounts.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(account) {
    editingAccount.value = account;
    editForm.name = account.name;
    editForm.subtype = account.subtype || '';
    editForm.description = account.description || '';
    editForm.is_active = Boolean(account.is_active);
    editForm.clearErrors();
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!editingAccount.value) return;
    editForm.put(route('accounts.update', editingAccount.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingAccount.value = null;
        },
    });
}

function deleteAccount(account) {
    if (confirm(`Are you sure you want to delete account ${account.account_code} - ${account.name}?`)) {
        router.delete(route('accounts.destroy', account.id));
    }
}

function typeBadgeClass(type) {
    switch (type) {
        case 'asset': return 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20';
        case 'liability': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
        case 'equity': return 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20';
        case 'revenue': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
        case 'expense': return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20';
        default: return 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20';
    }
}
</script>

<template>
    <AppLayout title="Chart of Accounts">
        <template #header>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Chart of Accounts (COA)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Manage master general ledger structure and normal balance rules.</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Highlights -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Accounts</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ metrics.total_accounts }}</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-sky-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-sky-600">Assets (1000s)</p>
                    <p class="text-2xl font-bold text-sky-900 mt-1">{{ metrics.assets }}</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Liabilities (2000s)</p>
                    <p class="text-2xl font-bold text-amber-900 mt-1">{{ metrics.liabilities }}</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-600">Equity (3000s)</p>
                    <p class="text-2xl font-bold text-purple-900 mt-1">{{ metrics.equity }}</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Revenue (4000s)</p>
                    <p class="text-2xl font-bold text-emerald-900 mt-1">{{ metrics.revenue }}</p>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-rose-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Expenses (5000s)</p>
                    <p class="text-2xl font-bold text-rose-900 mt-1">{{ metrics.expense }}</p>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2 items-stretch md:items-center justify-between">
                <div class="flex-1 flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by account code, name, or subtype..."
                            class="w-full pl-10 pr-4 py-2 text-xs bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 transition-all"
                        />
                    </div>
                    <select
                        v-model="typeFilter"
                        @change="applyFilters"
                        class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl pl-3 pr-8 min-w-[150px] py-2 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                    >
                        <option value="">All Account Types</option>
                        <option value="asset">Assets</option>
                        <option value="liability">Liabilities</option>
                        <option value="equity">Equity</option>
                        <option value="revenue">Revenue</option>
                        <option value="expense">Expenses</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all"
                    >
                        Filter
                    </button>
                    <button
                        v-if="search || typeFilter"
                        @click="resetFilters"
                        class="px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-800 transition-all"
                    >
                        Clear
                    </button>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm shadow-indigo-600/30 transition-all shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Account</span>
                    </button>
                </div>
            </div>

            <!-- Accounts Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Code</th>
                                <th class="py-3 px-4">Account Name</th>
                                <th class="py-3 px-4">Classification</th>
                                <th class="py-3 px-4">Subtype</th>
                                <th class="py-3 px-4 text-center">Normal Balance</th>
                                <th class="py-3 px-4 text-center">Activity</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="account in accounts.data"
                                :key="account.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    {{ account.account_code }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800">{{ account.name }}</div>
                                    <div v-if="account.description" class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{{ account.description }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider', typeBadgeClass(account.type)]">
                                        {{ account.type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                    {{ account.subtype || '—' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-semibold uppercase text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                        {{ account.normal_balance }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="text-slate-600 font-mono text-xs">
                                        {{ account.journal_lines_count }} entries
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        v-if="account.is_active"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700"
                                    >
                                        Active
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500"
                                    >
                                        Inactive
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <ActionDropdown align="right">
                                            <button
                                                @click="openEditModal(account)"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Account Info</span>
                                            </button>
                                            <Link
                                                :href="route('accounting.ledger', { account_id: account.id })"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>View Account Ledger</span>
                                            </Link>
                                            <button
                                                v-if="!account.is_system && account.journal_lines_count === 0"
                                                @click="deleteAccount(account)"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50 font-semibold transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Account</span>
                                            </button>
                                        </ActionDropdown>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!accounts.data || accounts.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No accounts match the specified criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div v-if="accounts.links && accounts.links.length > 3" class="p-4 border-t border-slate-100">
                    <Pagination :links="accounts.links" />
                </div>
            </div>
        </div>

        <!-- Create Account Modal -->
        <Modal :show="isCreateModalOpen" @close="isCreateModalOpen = false" max-width="lg">
            <div class="p-6">
                <h3 class="text-base font-bold text-slate-900">Add General Ledger Account</h3>
                <p class="text-xs text-slate-500 mt-1">Add a new account to your chart of accounts ledger.</p>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Account Code *</label>
                        <input
                            v-model="createForm.account_code"
                            type="text"
                            placeholder="e.g. 1050"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        />
                        <p v-if="createForm.errors.account_code" class="text-rose-500 text-[11px] mt-1">{{ createForm.errors.account_code }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Account Name *</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="e.g. Petty Cash"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        />
                        <p v-if="createForm.errors.name" class="text-rose-500 text-[11px] mt-1">{{ createForm.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Type *</label>
                            <select
                                v-model="createForm.type"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                required
                            >
                                <option value="asset">Asset (Normal: Debit)</option>
                                <option value="liability">Liability (Normal: Credit)</option>
                                <option value="equity">Equity (Normal: Credit)</option>
                                <option value="revenue">Revenue (Normal: Credit)</option>
                                <option value="expense">Expense (Normal: Debit)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Subtype</label>
                            <input
                                v-model="createForm.subtype"
                                type="text"
                                placeholder="e.g. current_asset"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Description / Notes</label>
                        <textarea
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Optional notes or purpose..."
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 font-medium text-slate-600 hover:text-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-sm shadow-indigo-600/30"
                        >
                            Save Account
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Account Modal -->
        <Modal :show="isEditModalOpen" @close="isEditModalOpen = false" max-width="lg">
            <div class="p-6">
                <h3 class="text-base font-bold text-slate-900">Edit Account: {{ editingAccount?.account_code }}</h3>
                <p class="text-xs text-slate-500 mt-1">Classification and code are protected to maintain ledger consistency.</p>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Account Name *</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            placeholder="e.g. Petty Cash"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        />
                        <p v-if="editForm.errors.name" class="text-rose-500 text-[11px] mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Subtype</label>
                        <input
                            v-model="editForm.subtype"
                            type="text"
                            placeholder="e.g. current_asset"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Description</label>
                        <textarea
                            v-model="editForm.description"
                            rows="2"
                            placeholder="Optional notes or purpose..."
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="edit_is_active"
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="edit_is_active" class="font-semibold text-slate-700">Account is Active</label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2 font-medium text-slate-600 hover:text-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-sm shadow-indigo-600/30"
                        >
                            Update Account
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
