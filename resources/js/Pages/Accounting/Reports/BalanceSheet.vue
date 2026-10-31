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
    router.get(route('reports.balance-sheet'), {
        as_of_date: asOfDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function downloadPdf() {
    window.open(route('pdf.balance-sheet', { as_of_date: asOfDate.value }), '_blank');
}
</script>

<template>
    <AppLayout title="Balance Sheet">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Balance Sheet Statement</h2>
                    <p class="text-xs text-slate-500 mt-0.5">As of {{ report.as_of_date }} &bull; Financial Position Statement</p>
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
            <!-- Filter & Verification Bar -->
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

                <div>
                    <span
                        v-if="report.is_balanced"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Accounting Equation Holds: Assets = Liabilities + Equity
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Equation Imbalance: Diff: {{ Math.abs(report.total_assets - (report.total_liabilities + report.total_equity)).toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- Accounting Equation Tri-Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-sky-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-sky-600">Total Assets</p>
                    <p class="text-2xl font-extrabold text-sky-950 mt-1">
                        <CurrencyDisplay :amount="report.total_assets" />
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">Normal Debit Balance</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Total Liabilities</p>
                    <p class="text-2xl font-extrabold text-amber-950 mt-1">
                        <CurrencyDisplay :amount="report.total_liabilities" />
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">External obligations</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-600">Total Owner's Equity</p>
                    <p class="text-2xl font-extrabold text-purple-950 mt-1">
                        <CurrencyDisplay :amount="report.total_equity" />
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">Includes Net Period Profit / Loss</p>
                </div>
            </div>

            <!-- Two Column Financial Position Statement -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Column 1: Assets -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div class="border-b-2 border-sky-500 pb-2 flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-sky-950">Assets</h3>
                        <span class="text-xs font-mono font-bold text-sky-700">Debit</span>
                    </div>

                    <table class="w-full text-left border-collapse text-xs">
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="item in (report.asset_accounts || report.asset_rows)"
                                :key="item.account_code"
                                class="hover:bg-slate-50/60"
                            >
                                <td class="py-2.5 px-3 w-3/12 font-bold text-slate-800">{{ item.account_code }}</td>
                                <td class="py-2.5 px-3 w-5/12 font-sans font-medium text-slate-700">{{ item.account_name || item.name }}</td>
                                <td class="py-2.5 px-3 w-4/12 text-right font-bold text-slate-900">
                                    <CurrencyDisplay :amount="item.balance ?? item.amount" />
                                </td>
                            </tr>
                            <tr v-if="!report.asset_accounts || report.asset_accounts.length === 0">
                                <td colspan="3" class="py-6 text-center text-slate-400 font-sans">
                                    No asset ledger balances.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-sky-50/70 font-bold border-t border-sky-200 text-xs">
                                <td colspan="2" class="py-3 px-3 uppercase font-sans tracking-wider text-sky-950">
                                    Total Assets:
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-sky-950 text-sm">
                                    <CurrencyDisplay :amount="report.total_assets" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Column 2: Liabilities & Equity -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                    <!-- Liabilities Sub-section -->
                    <div class="space-y-3">
                        <div class="border-b-2 border-amber-500 pb-2 flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-amber-950">Liabilities</h3>
                            <span class="text-xs font-mono font-bold text-amber-700">Credit</span>
                        </div>

                        <table class="w-full text-left border-collapse text-xs">
                            <tbody class="divide-y divide-slate-100 font-mono">
                                <tr
                                    v-for="item in (report.liability_accounts || report.liability_rows)"
                                    :key="item.account_code"
                                    class="hover:bg-slate-50/60"
                                >
                                    <td class="py-2 px-3 w-3/12 font-bold text-slate-800">{{ item.account_code }}</td>
                                    <td class="py-2 px-3 w-5/12 font-sans font-medium text-slate-700">{{ item.account_name || item.name }}</td>
                                    <td class="py-2 px-3 w-4/12 text-right font-bold text-slate-900">
                                        <CurrencyDisplay :amount="item.balance ?? item.amount" />
                                    </td>
                                </tr>
                                <tr v-if="(!report.liability_accounts || report.liability_accounts.length === 0) && (!report.liability_rows || report.liability_rows.length === 0)">
                                    <td colspan="3" class="py-3 text-center text-slate-400 font-sans">
                                        No liability balances.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-amber-50/70 font-bold border-t border-amber-200 text-xs">
                                    <td colspan="2" class="py-2.5 px-3 uppercase font-sans tracking-wider text-amber-950">
                                        Total Liabilities:
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-amber-950">
                                        <CurrencyDisplay :amount="report.total_liabilities" />
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Equity Sub-section -->
                    <div class="space-y-3">
                        <div class="border-b-2 border-purple-500 pb-2 flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-purple-950">Equity</h3>
                            <span class="text-xs font-mono font-bold text-purple-700">Credit</span>
                        </div>

                        <table class="w-full text-left border-collapse text-xs">
                            <tbody class="divide-y divide-slate-100 font-mono">
                                <tr
                                    v-for="item in (report.equity_accounts || report.equity_rows)"
                                    :key="item.account_code"
                                    class="hover:bg-slate-50/60"
                                >
                                    <td class="py-2 px-3 w-3/12 font-bold text-slate-800">{{ item.account_code }}</td>
                                    <td class="py-2 px-3 w-5/12 font-sans font-medium text-slate-700">{{ item.account_name || item.name }}</td>
                                    <td class="py-2 px-3 w-4/12 text-right font-bold text-slate-900">
                                        <CurrencyDisplay :amount="item.balance ?? item.amount" />
                                    </td>
                                </tr>
                                <!-- Dynamic Current Period Net Income -->
                                <tr class="bg-purple-50/30">
                                    <td class="py-2 px-3 w-3/12 font-bold text-purple-700 font-mono">RETAINED</td>
                                    <td class="py-2 px-3 w-5/12 font-sans font-semibold text-purple-900">Current Period Net Income (P&L)</td>
                                    <td class="py-2 px-3 w-4/12 text-right font-bold text-purple-900 font-mono">
                                        <CurrencyDisplay :amount="report.current_period_net_income" />
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-purple-50/70 font-bold border-t border-purple-200 text-xs">
                                    <td colspan="2" class="py-2.5 px-3 uppercase font-sans tracking-wider text-purple-950">
                                        Total Equity:
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-purple-950">
                                        <CurrencyDisplay :amount="report.total_equity" />
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Liabilities + Equity Sum -->
                    <div class="p-4 rounded-xl bg-slate-900 text-white flex items-center justify-between font-mono text-sm">
                        <span class="font-sans font-bold uppercase tracking-wider text-xs text-slate-300">
                            Total Liabilities & Equity:
                        </span>
                        <span class="font-bold text-emerald-400 text-base">
                            <CurrencyDisplay :amount="report.total_liabilities + report.total_equity" />
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
