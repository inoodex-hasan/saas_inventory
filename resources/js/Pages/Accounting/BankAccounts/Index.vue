<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    accounts: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');

function applyFilters() {
    router.get(route('bank-accounts.index'), {
        search: search.value,
        type: typeFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function setTypeFilter(t) {
    typeFilter.value = t;
    applyFilters();
}

function resetFilters() {
    search.value = '';
    typeFilter.value = '';
    applyFilters();
}

// Modal States
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isHistoryModalOpen = ref(false);
const selectedAccount = ref(null);

const createForm = useForm({
    name: '',
    type: 'bank', // 'bank', 'mfs', 'cash'
    institution_name: '',
    account_number: '',
    account_type: 'current',
    branch: '',
    routing_number: '',
    swift_code: '',
    opening_balance: 0,
    is_default: false,
    notes: '',
});

const editForm = useForm({
    name: '',
    type: 'bank',
    institution_name: '',
    account_number: '',
    account_type: '',
    branch: '',
    routing_number: '',
    swift_code: '',
    is_default: false,
    is_active: true,
    notes: '',
});

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    createForm.type = 'bank';
    createForm.account_type = 'current';
    isCreateModalOpen.value = true;
}

function onTypeChanged() {
    if (createForm.type === 'mfs') {
        createForm.institution_name = createForm.institution_name || 'bKash';
        createForm.account_type = 'merchant';
    } else if (createForm.type === 'bank') {
        createForm.account_type = 'current';
    } else if (createForm.type === 'cash') {
        createForm.institution_name = 'Cash Drawer';
        createForm.account_type = '';
    }
}

function submitCreate() {
    createForm.post(route('bank-accounts.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(acc) {
    selectedAccount.value = acc;
    editForm.name = acc.name;
    editForm.type = acc.type;
    editForm.institution_name = acc.institution_name || '';
    editForm.account_number = acc.account_number || '';
    editForm.account_type = acc.account_type || '';
    editForm.branch = acc.branch || '';
    editForm.routing_number = acc.routing_number || '';
    editForm.swift_code = acc.swift_code || '';
    editForm.is_default = Boolean(acc.is_default);
    editForm.is_active = Boolean(acc.is_active);
    editForm.notes = acc.notes || '';
    editForm.clearErrors();
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!selectedAccount.value) return;
    editForm.put(route('bank-accounts.update', selectedAccount.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            selectedAccount.value = null;
        },
    });
}

function openHistoryModal(acc) {
    selectedAccount.value = acc;
    isHistoryModalOpen.value = true;
}

function setDefault(acc) {
    router.post(route('bank-accounts.set-default', acc.id));
}

function toggleActive(acc) {
    router.post(route('bank-accounts.toggle-active', acc.id));
}

function deleteAccount(acc) {
    if (confirm(`Delete account '${acc.name}'?`)) {
        router.delete(route('bank-accounts.destroy', acc.id));
    }
}

const isInitializing = ref(false);

function initializeDefaultAccounts() {
    if (confirm('Load standard accounts template (Main Cash Drawer, Bank Operating Account, bKash Merchant)?')) {
        isInitializing.value = true;
        router.post(route('bank-accounts.initialize'), {}, {
            onFinish: () => {
                isInitializing.value = false;
            },
        });
    }
}

function getProviderBadge(acc) {
    if (acc.type === 'mfs') {
        const inst = (acc.institution_name || '').toLowerCase();
        if (inst.includes('bkash')) return { label: 'bKash', color: 'bg-pink-50 text-pink-700 border-pink-200' };
        if (inst.includes('nagad')) return { label: 'Nagad', color: 'bg-orange-50 text-orange-700 border-orange-200' };
        if (inst.includes('rocket')) return { label: 'Rocket', color: 'bg-purple-50 text-purple-700 border-purple-200' };
        return { label: acc.institution_name || 'MFS', color: 'bg-cyan-50 text-cyan-700 border-cyan-200' };
    }
    if (acc.type === 'bank') {
        return { label: acc.institution_name || 'Bank', color: 'bg-blue-50 text-blue-700 border-blue-200' };
    }
    return { label: 'Cash Desk', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
}
</script>

<template>
    <AppLayout>
        <Head title="Bank & MFS Accounts" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Bank & MFS Financial Accounts</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage corporate bank accounts, mobile financial services (bKash/Nagad), and cash registers</p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="metrics?.active_accounts_count === 0"
                        type="button"
                        @click="initializeDefaultAccounts"
                        :disabled="isInitializing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>{{ isInitializing ? 'Loading...' : 'Load Standard Accounts' }}</span>
                    </button>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add New Account</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Liquid Assets</span>
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_liquidity || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Across {{ metrics?.active_accounts_count || 0 }} active accounts</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Bank Accounts</span>
                        <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-blue-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.bank_balance || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Current & savings deposits</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">MFS Wallets</span>
                        <div class="p-2 rounded-xl bg-pink-50 text-pink-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-pink-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.mfs_balance || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">bKash, Nagad & Rocket</div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Cash in Hand</span>
                        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.cash_balance || 0" />
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Physical cash drawer</div>
                </div>
            </div>

            <!-- Filters Bar & Tabs -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
                    <button
                        type="button"
                        @click="setTypeFilter('')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            typeFilter === '' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        All Accounts
                    </button>
                    <button
                        type="button"
                        @click="setTypeFilter('bank')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            typeFilter === 'bank' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        Banks
                    </button>
                    <button
                        type="button"
                        @click="setTypeFilter('mfs')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            typeFilter === 'mfs' ? 'bg-pink-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        MFS Wallets
                    </button>
                    <button
                        type="button"
                        @click="setTypeFilter('cash')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            typeFilter === 'cash' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        Cash Drawers
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search accounts, A/C #, branch..."
                            class="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <button
                        v-if="search || typeFilter"
                        @click="resetFilters"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Accounts Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Account Name & Channel</th>
                                <th class="py-3.5 px-4">Account / Wallet #</th>
                                <th class="py-3.5 px-4">Institution / Branch</th>
                                <th class="py-3.5 px-4 text-right">Current Live Balance</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="acc in accounts" :key="acc.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="font-bold text-slate-900 text-sm">
                                            {{ acc.name }}
                                        </div>
                                        <span
                                            v-if="acc.is_default"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                            title="Primary payment account"
                                        >
                                            ★ Default
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-md text-[10px] font-bold border capitalize',
                                                getProviderBadge(acc).color
                                            ]"
                                        >
                                            {{ getProviderBadge(acc).label }}
                                        </span>
                                        <span v-if="acc.account_type" class="text-[11px] text-slate-400 capitalize">
                                            {{ acc.account_type }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-xs font-semibold text-slate-800">
                                    {{ acc.account_number || '—' }}
                                    <div v-if="acc.routing_number" class="text-[10px] text-slate-400 font-normal">
                                        Routing: {{ acc.routing_number }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-xs">
                                    <div class="font-medium text-slate-800">{{ acc.institution_name || '—' }}</div>
                                    <div v-if="acc.branch" class="text-slate-400">{{ acc.branch }}</div>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="text-base font-black text-slate-900 font-mono">
                                        <CurrencyDisplay :amount="acc.current_balance" />
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        Opening: <CurrencyDisplay :amount="acc.opening_balance" />
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 text-[11px] font-bold rounded-full capitalize border',
                                            acc.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ acc.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <ActionDropdown align="right">
                                        <div class="p-1 space-y-0.5">
                                            <!-- History -->
                                            <button
                                                type="button"
                                                @click="openHistoryModal(acc)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Recent Transactions</span>
                                            </button>

                                            <!-- Edit -->
                                            <button
                                                type="button"
                                                @click="openEditModal(acc)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Account</span>
                                            </button>

                                            <!-- Set Default -->
                                            <button
                                                v-if="!acc.is_default && acc.is_active"
                                                type="button"
                                                @click="setDefault(acc)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-amber-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                                <span>Set as Default</span>
                                            </button>

                                            <!-- Toggle Active -->
                                            <button
                                                type="button"
                                                @click="toggleActive(acc)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                <span>{{ acc.is_active ? 'Deactivate' : 'Activate' }}</span>
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                type="button"
                                                @click="deleteAccount(acc)"
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

                            <tr v-if="accounts.length === 0">
                                <td colspan="6" class="text-center py-12 text-slate-400 font-medium">
                                    <div v-if="metrics?.active_accounts_count === 0 && !search && !typeFilter" class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                        </div>
                                        <p class="font-medium text-slate-600">No bank or cash accounts created yet.</p>
                                        <p class="text-xs text-slate-400 max-w-sm">Create your cash drawer, bank operating accounts, or mobile financial service wallets.</p>
                                        <div class="flex items-center gap-2 pt-2">
                                            <button
                                                @click="openCreateModal"
                                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-xs transition-all"
                                            >
                                                Add New Account
                                            </button>
                                            <button
                                                @click="initializeDefaultAccounts"
                                                :disabled="isInitializing"
                                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-xl transition-all disabled:opacity-50"
                                            >
                                                {{ isInitializing ? 'Loading...' : 'Load Standard Accounts' }}
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else>
                                        No financial accounts match your criteria.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Account Modal -->
        <Modal :show="isCreateModalOpen" max-width="lg" @close="isCreateModalOpen = false">
            <div class="p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Add New Financial Account</h2>
                        <p class="text-xs text-slate-500 font-medium">Configure corporate bank accounts, mobile financial wallets, or cash registers</p>
                    </div>
                    <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <!-- Account Type Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Account Category *</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="createForm.type = 'bank'; onTypeChanged()"
                                :class="[
                                    'py-2 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5',
                                    createForm.type === 'bank' ? 'bg-blue-50 border-blue-500 text-blue-700 shadow-xs' : 'bg-slate-50 border-slate-200 text-slate-600'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                                <span>Bank Account</span>
                            </button>

                            <button
                                type="button"
                                @click="createForm.type = 'mfs'; onTypeChanged()"
                                :class="[
                                    'py-2 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5',
                                    createForm.type === 'mfs' ? 'bg-pink-50 border-pink-500 text-pink-700 shadow-xs' : 'bg-slate-50 border-slate-200 text-slate-600'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span>MFS Wallet</span>
                            </button>

                            <button
                                type="button"
                                @click="createForm.type = 'cash'; onTypeChanged()"
                                :class="[
                                    'py-2 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5',
                                    createForm.type === 'cash' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-xs' : 'bg-slate-50 border-slate-200 text-slate-600'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>Cash Drawer</span>
                            </button>
                        </div>
                    </div>

                    <!-- Display Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Account Label / Name *</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-medium"
                        />
                        <div v-if="createForm.errors.name" class="text-xs text-rose-500 mt-1">{{ createForm.errors.name }}</div>
                    </div>

                    <!-- Dynamic Fields: Bank -->
                    <div v-if="createForm.type === 'bank'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Bank Name *</label>
                                <input
                                    v-model="createForm.institution_name"
                                    type="text"
                                    required
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Account Number *</label>
                                <input
                                    v-model="createForm.account_number"
                                    type="text"
                                    required
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Branch Name</label>
                                <input
                                    v-model="createForm.branch"
                                    type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Routing Number</label>
                                <input
                                    v-model="createForm.routing_number"
                                    type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Account Type</label>
                                <select
                                    v-model="createForm.account_type"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-medium"
                                >
                                    <option value="current">Current / Checking</option>
                                    <option value="savings">Savings</option>
                                    <option value="salary">Corporate Payroll</option>
                                    <option value="escrow">Escrow</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields: MFS -->
                    <div v-if="createForm.type === 'mfs'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">MFS Provider *</label>
                                <select
                                    v-model="createForm.institution_name"
                                    required
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-medium"
                                >
                                    <option value="bKash">bKash</option>
                                    <option value="Nagad">Nagad</option>
                                    <option value="Rocket">Rocket (DBBL)</option>
                                    <option value="Upay">Upay</option>
                                    <option value="Cellfin">Cellfin (IBBL)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Wallet Mobile # *</label>
                                <input
                                    v-model="createForm.account_number"
                                    type="text"
                                    required
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Wallet Type</label>
                                <select
                                    v-model="createForm.account_type"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-medium"
                                >
                                    <option value="merchant">Merchant (Payment Gateway/QR)</option>
                                    <option value="personal">Personal Account</option>
                                    <option value="agent">Agent Wallet</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Opening Balance -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Opening Starting Balance (৳)</label>
                        <input
                            v-model.number="createForm.opening_balance"
                            type="number"
                            step="0.01"
                            min="0"
class="w-full text-xs font-mono font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Is Default Toggle -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="create_is_default"
                            v-model="createForm.is_default"
                            type="checkbox"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="create_is_default" class="text-xs font-medium text-slate-700">
                            Make this the primary default account for incoming collections
                        </label>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Notes / Instructions</label>
                        <textarea
                            v-model="createForm.notes"
                            rows="2"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Creating...' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Account Modal -->
        <Modal :show="isEditModalOpen" max-width="lg" @close="isEditModalOpen = false">
            <div class="p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Edit Account Details</h2>
                        <p class="text-xs text-slate-500 font-medium">{{ selectedAccount?.name }}</p>
                    </div>
                    <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Account Label / Name *</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-medium"
                        />
                    </div>

                    <div v-if="editForm.type === 'bank'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Bank Name</label>
                                <input
                                    v-model="editForm.institution_name"
                                    type="text"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Account Number</label>
                                <input
                                    v-model="editForm.account_number"
                                    type="text"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Branch</label>
                                <input
                                    v-model="editForm.branch"
                                    type="text"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Routing Number</label>
                                <input
                                    v-model="editForm.routing_number"
                                    type="text"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-if="editForm.type === 'mfs'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Provider</label>
                            <input
                                v-model="editForm.institution_name"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Wallet Mobile #</label>
                            <input
                                v-model="editForm.account_number"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-1">
                        <div class="flex items-center gap-2">
                            <input
                                id="edit_is_default"
                                v-model="editForm.is_default"
                                type="checkbox"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="edit_is_default" class="text-xs font-medium text-slate-700">Primary Default</label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="edit_is_active"
                                v-model="editForm.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="edit_is_active" class="text-xs font-medium text-slate-700">Active Account</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Notes / Instructions</label>
                        <textarea
                            v-model="editForm.notes"
                            rows="2"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Transaction History Modal -->
        <Modal :show="isHistoryModalOpen" max-width="2xl" @close="isHistoryModalOpen = false">
            <div class="p-6 sm:p-8 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Recent Account Transactions</h2>
                        <p class="text-xs text-slate-500 font-semibold">{{ selectedAccount?.name }} &bull; Current Balance: <span class="text-emerald-600 font-bold font-mono"><CurrencyDisplay :amount="selectedAccount?.current_balance" /></span></p>
                    </div>
                    <button @click="isHistoryModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-96 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Description / Reference</th>
                                <th class="py-2.5 px-3 text-right">Amount</th>
                                <th class="py-2.5 px-3 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="t in selectedAccount?.transactions || []" :key="t.id" class="hover:bg-slate-50/50">
                                <td class="py-2 px-3 text-slate-600 whitespace-nowrap">{{ formatDate(t.transaction_date) }}</td>
                                <td class="py-2 px-3">
                                    <div class="font-medium text-slate-800">{{ t.description }}</div>
                                    <div v-if="t.reference_number" class="text-[10px] text-slate-400 font-mono">Ref: {{ t.reference_number }}</div>
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold whitespace-nowrap">
                                    <span :class="t.transaction_type === 'deposit' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ t.transaction_type === 'deposit' ? '+' : '-' }}<CurrencyDisplay :amount="t.amount" />
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <CurrencyDisplay :amount="t.balance_after" />
                                </td>
                            </tr>
                            <tr v-if="!selectedAccount?.transactions || selectedAccount.transactions.length === 0">
                                <td colspan="4" class="text-center py-6 text-slate-400">No transactions recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="button"
                        @click="isHistoryModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
