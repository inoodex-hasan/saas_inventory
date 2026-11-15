<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    accounts: Array,
    selectedAccountId: Number,
    ledger: Object,
    filters: Object,
});

const accountId = ref(props.selectedAccountId || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

function applyFilters() {
    router.get(route('reports.general-ledger'), {
        account_id: accountId.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <AppLayout title="General Ledger">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">General Ledger Statement</h2>
                </div>
            </div>
        </template>

        <div class="space-y-4 sm:space-y-6">
            <!-- Account & Date Range Filter Bar -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <div class="flex-1 flex flex-col sm:flex-row gap-3">
                    <select
                        v-model="accountId"
                        @change="applyFilters"
                        class="w-full sm:w-auto flex-1 text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-indigo-500 font-semibold"
                    >
                        <option value="" disabled>Select Ledger Account...</option>
                        <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                            {{ acc.account_code }} - {{ acc.name }} ({{ acc.type }})
                        </option>
                    </select>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input
                            v-model="startDate"
                            type="date"
                            class="w-full flex-1 min-w-0 text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-2.5 py-2 focus:bg-white focus:border-indigo-500"
                        />
                        <span class="text-xs text-slate-400 shrink-0">to</span>
                        <input
                            v-model="endDate"
                            type="date"
                            class="w-full flex-1 min-w-0 text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-2.5 py-2 focus:bg-white focus:border-indigo-500"
                        />
                    </div>
                </div>

                <button
                    @click="applyFilters"
                    class="w-full sm:w-auto px-4 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all text-center justify-center cursor-pointer shrink-0"
                >
                    View Ledger
                </button>
            </div>

            <!-- Ledger Details & Metrics -->
            <div v-if="ledger" class="space-y-4 sm:space-y-6">
                <!-- KPI Overview (2-col on mobile, 4-col on desktop) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4">
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 truncate">Opening Balance</p>
                        <p class="text-sm sm:text-xl font-bold text-slate-900 mt-1 truncate">
                            <CurrencyDisplay :amount="ledger.opening_balance" />
                        </p>
                    </div>
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 truncate">Period Debits (+)</p>
                        <p class="text-sm sm:text-xl font-bold text-slate-900 mt-1 truncate">
                            <CurrencyDisplay :amount="ledger.total_debits" />
                        </p>
                    </div>
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 truncate">Period Credits (&minus;)</p>
                        <p class="text-sm sm:text-xl font-bold text-slate-900 mt-1 truncate">
                            <CurrencyDisplay :amount="ledger.total_credits" />
                        </p>
                    </div>
                    <div class="bg-indigo-50/70 p-3.5 sm:p-4 rounded-2xl border border-indigo-200 shadow-xs flex flex-col justify-between">
                        <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-indigo-700 truncate">Closing Balance</p>
                        <p class="text-sm sm:text-xl font-extrabold text-indigo-950 mt-1 truncate">
                            <CurrencyDisplay :amount="ledger.closing_balance" />
                        </p>
                    </div>
                </div>

                <!-- Transaction Ledger Table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-3.5 sm:p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                {{ ledger.account.account_code }} - {{ ledger.account.name }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Normal Balance: <span class="uppercase font-semibold text-slate-700">{{ ledger.account.normal_balance }}</span> &bull; Classification: <span class="capitalize font-semibold text-slate-700">{{ ledger.account.type }}</span></p>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse text-xs min-w-[720px]">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3 px-4 whitespace-nowrap">Date</th>
                                    <th class="py-3 px-4 whitespace-nowrap">Voucher #</th>
                                    <th class="py-3 px-4 min-w-[200px]">Narration / Memo</th>
                                    <th class="py-3 px-4 text-right whitespace-nowrap">Debit</th>
                                    <th class="py-3 px-4 text-right whitespace-nowrap">Credit</th>
                                    <th class="py-3 px-4 text-right whitespace-nowrap">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono">
                                <!-- Opening Balance Line -->
                                <tr class="bg-slate-50/40 text-slate-500">
                                    <td class="py-2.5 px-4 font-sans text-xs italic whitespace-nowrap">{{ startDate || 'Start' }}</td>
                                    <td class="py-2.5 px-4 italic font-sans whitespace-nowrap">—</td>
                                    <td class="py-2.5 px-4 font-sans font-medium italic">Opening Balance B/F</td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap">—</td>
                                    <td class="py-2.5 px-4 text-right whitespace-nowrap">—</td>
                                    <td class="py-2.5 px-4 text-right font-bold text-slate-700 whitespace-nowrap">
                                        <CurrencyDisplay :amount="ledger.opening_balance" />
                                    </td>
                                </tr>

                                <tr
                                    v-for="tx in ledger.transactions"
                                    :key="tx.line_id"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="py-3 px-4 text-slate-600 font-sans whitespace-nowrap">
                                        {{ formatDate(tx.date) }}
                                    </td>
                                    <td class="py-3 px-4 font-bold text-indigo-600 whitespace-nowrap">
                                        <Link :href="route('journal.show', tx.journal_entry_id)" class="hover:underline">
                                            {{ tx.entry_number }}
                                        </Link>
                                    </td>
                                    <td class="py-3 px-4 font-sans text-slate-800">
                                        <div class="font-medium text-xs">{{ tx.narration }}</div>
                                        <div v-if="tx.memo" class="text-[11px] text-slate-400 mt-0.5">{{ tx.memo }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                                        <span v-if="parseFloat(tx.debit) > 0">
                                            <CurrencyDisplay :amount="tx.debit" />
                                        </span>
                                        <span v-else class="text-slate-300 font-sans">—</span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                                        <span v-if="parseFloat(tx.credit) > 0">
                                            <CurrencyDisplay :amount="tx.credit" />
                                        </span>
                                        <span v-else class="text-slate-300 font-sans">—</span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-indigo-700 whitespace-nowrap">
                                        <CurrencyDisplay :amount="tx.running_balance" />
                                    </td>
                                </tr>

                                <tr v-if="!ledger.transactions || ledger.transactions.length === 0">
                                    <td colspan="6" class="py-8 text-center text-slate-400 font-sans">
                                        No transactions for this account in the selected date range.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-300 text-xs font-mono">
                                    <td colspan="3" class="py-3.5 px-4 text-right uppercase font-sans tracking-wider text-slate-700 whitespace-nowrap">
                                        Period Totals & Closing Balance:
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-slate-900 whitespace-nowrap">
                                        <CurrencyDisplay :amount="ledger.total_debits" />
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-slate-900 whitespace-nowrap">
                                        <CurrencyDisplay :amount="ledger.total_credits" />
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-indigo-900 text-sm whitespace-nowrap">
                                        <CurrencyDisplay :amount="ledger.closing_balance" />
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-400">
                Please select an account above to generate its General Ledger statement.
            </div>
        </div>
    </AppLayout>
</template>
