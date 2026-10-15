<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    page_title: String,
    report: Object,
});

const asOfDate = ref(props.report?.as_of_date || new Date().toISOString().slice(0, 10));

function updateDate() {
    router.get(route('reports.trial-balance'), {
        as_of_date: asOfDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function downloadPdf() {
    window.open(route('pdf.trial-balance', { as_of_date: asOfDate.value }), '_blank');
}
</script>

<template>
    <AppLayout title="Trial Balance">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Trial Balance Statement</h2>
                    <p class="text-xs text-slate-500 mt-0.5">As of {{ report.as_of_date }} &bull; General Ledger account balancing verification</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="downloadPdf"
                        class="px-3.5 py-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm shadow-indigo-600/30 transition-all inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Download PDF</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-semibold text-slate-700">As of Date:</label>
                    <input
                        v-model="asOfDate"
                        type="date"
                        @change="updateDate"
                        class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-1.5 focus:bg-white focus:border-indigo-500"
                    />
                    <button
                        @click="updateDate"
                        class="px-3 py-1.5 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all"
                    >
                        Apply
                    </button>
                </div>

                <!-- Status indicator -->
                <div>
                    <span
                        v-if="report.is_balanced"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Ledger In Balance: Debits Equal Credits
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Ledger Out of Balance! Diff: {{ Math.abs(report.total_debit - report.total_credit).toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- Report Statement Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4 w-2/12">Account Code</th>
                                <th class="py-3 px-4 w-4/12">Account Name</th>
                                <th class="py-3 px-4 w-2/12">Classification</th>
                                <th class="py-3 px-4 w-2/12 text-right">Debit Balance</th>
                                <th class="py-3 px-4 w-2/12 text-right">Credit Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="item in report.rows"
                                :key="item.account_code"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ item.account_code }}
                                </td>
                                <td class="py-3 px-4 font-sans font-medium text-slate-800">
                                    {{ item.account_name }}
                                </td>
                                <td class="py-3 px-4 font-sans capitalize text-slate-500">
                                    {{ item.type }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900">
                                    <span v-if="parseFloat(item.debit_balance) > 0">
                                        <CurrencyDisplay :amount="item.debit_balance" />
                                    </span>
                                    <span v-else class="text-slate-300 font-sans">—</span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900">
                                    <span v-if="parseFloat(item.credit_balance) > 0">
                                        <CurrencyDisplay :amount="item.credit_balance" />
                                    </span>
                                    <span v-else class="text-slate-300 font-sans">—</span>
                                </td>
                            </tr>
                            <tr v-if="!report.rows || report.rows.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400 font-sans">
                                    No ledger activity recorded as of {{ report.as_of_date }}.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 font-bold border-t-2 border-slate-300 text-xs font-mono">
                                <td colspan="3" class="py-3.5 px-4 text-right uppercase font-sans tracking-wider text-slate-700">
                                    Total Trial Balance:
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-900 text-sm">
                                    <CurrencyDisplay :amount="report.total_debit" />
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-900 text-sm">
                                    <CurrencyDisplay :amount="report.total_credit" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
