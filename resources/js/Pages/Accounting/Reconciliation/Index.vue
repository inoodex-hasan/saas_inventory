<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    bankAccounts: Array,
    selectedAccountId: Number,
    reconciliationDate: String,
    history: Object,
});

const isModalOpen = ref(false);
const search = ref('');
const statusFilter = ref('');

const form = useForm({
    chart_of_account_id: props.selectedAccountId || (props.bankAccounts && props.bankAccounts.length > 0 ? props.bankAccounts[0].id : ''),
    statement_balance: '',
    reconciliation_date: props.reconciliationDate || new Date().toISOString().slice(0, 10),
    notes: '',
});

function openModal() {
    form.clearErrors();
    form.statement_balance = '';
    form.notes = '';
    if (!form.chart_of_account_id && props.bankAccounts && props.bankAccounts.length > 0) {
        form.chart_of_account_id = props.bankAccounts[0].id;
    }
    isModalOpen.value = true;
}

function submitReconciliation() {
    form.post(route('reconciliation.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            form.statement_balance = '';
            form.notes = '';
        },
    });
}

const totalAudits = computed(() => props.history?.total || (props.history?.data ? props.history.data.length : 0));
const reconciledCount = computed(() => (props.history?.data || []).filter(h => h.status === 'reconciled' || Math.abs(h.difference) < 0.001).length);
const discrepancyCount = computed(() => (props.history?.data || []).filter(h => h.status !== 'reconciled' && Math.abs(h.difference) >= 0.001).length);

const filteredHistory = computed(() => {
    if (!props.history?.data) return [];
    return props.history.data.filter((item) => {
        const matchesSearch = !search.value ||
            (item.account?.name && item.account.name.toLowerCase().includes(search.value.toLowerCase())) ||
            (item.account?.account_code && item.account.account_code.toLowerCase().includes(search.value.toLowerCase())) ||
            (item.reconciliation_date && item.reconciliation_date.includes(search.value)) ||
            (item.notes && item.notes.toLowerCase().includes(search.value.toLowerCase())) ||
            (item.reconciled_by?.name && item.reconciled_by.name.toLowerCase().includes(search.value.toLowerCase()));

        const matchesStatus = !statusFilter.value || item.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <AppLayout title="Bank & Cash Reconciliation">
        <Head :title="page_title || 'Bank & Cash Reconciliation'" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                        {{ page_title || 'Bank & Cash Reconciliation' }}
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Audit external bank and cash statements against internal general ledger balances</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- KPI Summary Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Audits</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">{{ totalAudits }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Historical verification records</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-xs">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Clean / Reconciled</div>
                    <div class="text-xl font-extrabold text-emerald-700 mt-1">{{ reconciledCount }}</div>
                    <div class="text-[10px] text-emerald-600/70 mt-0.5">Zero variance balance matches</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-rose-100 shadow-xs">
                    <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Discrepancies</div>
                    <div class="text-xl font-extrabold text-rose-700 mt-1">{{ discrepancyCount }}</div>
                    <div class="text-[10px] text-rose-600/70 mt-0.5">Detected ledger variance flags</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-xs">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Bank & Cash Accounts</div>
                    <div class="text-xl font-extrabold text-indigo-700 mt-1">{{ bankAccounts?.length || 0 }} Hubs</div>
                    <div class="text-[10px] text-indigo-600/70 mt-0.5">Monitored ledger accounts</div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2 items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                    <div class="relative w-full md:w-72">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search account, date, notes, operator..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 placeholder:text-slate-400"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select
                        v-model="statusFilter"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 min-w-[140px] focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-semibold text-slate-700"
                    >
                        <option value="">All Audit Statuses</option>
                        <option value="reconciled">Reconciled (Match)</option>
                        <option value="discrepancy">Discrepancy (Variance)</option>
                    </select>

                    <button
                        v-if="search || statusFilter"
                        @click="search = ''; statusFilter = '';"
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
                        <span>New Reconciliation Audit</span>
                    </button>
                </div>
            </div>

            <!-- Reconciliation History Log Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Reconciliation Audit Trail</h3>
                        <p class="text-xs text-slate-500">Historical records of verified statement balances and detected variances</p>
                    </div>
                    <span class="text-xs font-mono font-semibold text-slate-400">
                        Showing {{ filteredHistory.length }} of {{ props.history?.total || filteredHistory.length }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Statement Date</th>
                                <th class="py-3 px-4">Account</th>
                                <th class="py-3 px-4 text-right">Statement Balance</th>
                                <th class="py-3 px-4 text-right">Ledger Balance</th>
                                <th class="py-3 px-4 text-right">Difference</th>
                                <th class="py-3 px-4 text-center">Audit Status</th>
                                <th class="py-3 px-4">Reconciled By</th>
                                <th class="py-3 px-4">Notes</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="recon in filteredHistory"
                                :key="recon.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-sans text-slate-600 whitespace-nowrap">
                                    {{ formatDate(recon.reconciliation_date) }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-800 font-sans">
                                    <span class="font-mono text-indigo-600 font-semibold">{{ recon.account?.account_code }}</span> - {{ recon.account?.name }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900">
                                    <CurrencyDisplay :amount="recon.statement_balance" />
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-700">
                                    <CurrencyDisplay :amount="recon.ledger_balance" />
                                </td>
                                <td
                                    class="py-3 px-4 text-right font-bold"
                                    :class="Math.abs(recon.difference) < 0.001 ? 'text-emerald-600' : 'text-rose-600'"
                                >
                                    <CurrencyDisplay :amount="recon.difference" />
                                </td>
                                <td class="py-3 px-4 text-center font-sans">
                                    <span
                                        v-if="recon.status === 'reconciled' || Math.abs(recon.difference) < 0.001"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        Reconciled
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200"
                                    >
                                        Discrepancy
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-600 whitespace-nowrap">
                                    {{ recon.reconciled_by?.name || 'System' }}
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-500 max-w-xs truncate">
                                    {{ recon.notes || '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-sans">
                                    <div class="flex items-center justify-end">
                                        <ActionDropdown align="right">
                                            <Link
                                                :href="route('accounting.ledger')"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-indigo-600 hover:bg-indigo-50 font-semibold transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>View General Ledger</span>
                                            </Link>
                                            <button
                                                @click="openModal"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Re-audit Account</span>
                                            </button>
                                        </ActionDropdown>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredHistory.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400 font-sans">
                                    No reconciliation records found matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div v-if="history.links && history.links.length > 3" class="p-4 border-t border-slate-100">
                    <Pagination :links="history.links" />
                </div>
            </div>
        </div>

        <!-- New Reconciliation Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="2xl">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">
                            New Reconciliation Audit
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Enter closing balance from official bank statement to compare with general ledger</p>
                    </div>
                    <button
                        @click="isModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg text-lg leading-none"
                    >
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitReconciliation" class="space-y-4 text-xs">
                    <div class="space-y-1">
                        <label class="block font-semibold text-slate-700">Bank / Cash Account *</label>
                        <select
                            v-model="form.chart_of_account_id"
                            class="w-full text-xs pl-3 pr-8 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 font-semibold"
                            required
                        >
                            <option value="" disabled>Select Bank / Cash Account...</option>
                            <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                {{ acc.account_code }} - {{ acc.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.chart_of_account_id" class="text-rose-500 text-[11px] mt-1">{{ form.errors.chart_of_account_id }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block font-semibold text-slate-700">Statement Ending Date *</label>
                            <input
                                v-model="form.reconciliation_date"
                                type="date"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 font-medium"
                                required
                            />
                            <p v-if="form.errors.reconciliation_date" class="text-rose-500 text-[11px] mt-1">{{ form.errors.reconciliation_date }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-semibold text-slate-700">Bank Statement Ending Balance *</label>
                            <input
                                v-model.number="form.statement_balance"
                                type="number"
                                step="0.01"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-indigo-500 font-bold placeholder:text-slate-400 placeholder:text-xs"
                                required
                            />
                            <p v-if="form.errors.statement_balance" class="text-rose-500 text-[11px] mt-1">{{ form.errors.statement_balance }}</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-semibold text-slate-700">Audit Notes / Explanation (Optional)</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white font-semibold rounded-xl shadow-sm shadow-indigo-600/30 transition-all"
                        >
                            {{ form.processing ? 'Calculating & Auditing...' : 'Perform Reconciliation' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
