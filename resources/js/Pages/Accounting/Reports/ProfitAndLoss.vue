<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    page_title: String,
    report: Object,
});

const startDate = ref(props.report?.start_date || '');
const endDate = ref(props.report?.end_date || '');

function updateDates() {
    router.get(route('reports.profit-loss'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function downloadPdf() {
    window.open(route('pdf.profit-loss', {
        start_date: startDate.value,
        end_date: endDate.value,
    }), '_blank');
}
</script>

<template>
    <AppLayout title="Profit & Loss Statement">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Profit & Loss Statement (P&L)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Period: {{ report.start_date }} to {{ report.end_date }} &bull; Income and Expenditure Statement</p>
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
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-700">Period:</span>
                    <input
                        v-model="startDate"
                        type="date"
                        class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-1.5 focus:bg-white focus:border-indigo-500"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        v-model="endDate"
                        type="date"
                        class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-1.5 focus:bg-white focus:border-indigo-500"
                    />
                    <button
                        @click="updateDates"
                        class="px-3 py-1.5 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all"
                    >
                        Apply
                    </button>
                </div>

                <div class="text-xs">
                    <span class="text-slate-500 font-medium">Net Margin Status: </span>
                    <span
                        v-if="report.net_income >= 0"
                        class="font-bold text-emerald-600"
                    >
                        Profitable (+{{ ((report.net_income / (report.total_revenue || 1)) * 100).toFixed(1) }}%)
                    </span>
                    <span
                        v-else
                        class="font-bold text-rose-600"
                    >
                        Operating Deficit
                    </span>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Total Operating Revenue</p>
                    <p class="text-2xl font-extrabold text-emerald-900 mt-1">
                        <CurrencyDisplay :amount="report.total_revenue" />
                    </p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-rose-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Total Operating Expenses</p>
                    <p class="text-2xl font-extrabold text-rose-900 mt-1">
                        <CurrencyDisplay :amount="report.total_expenses" />
                    </p>
                </div>
                <div
                    class="p-5 rounded-2xl border shadow-xs"
                    :class="report.net_income >= 0 ? 'bg-emerald-50/70 border-emerald-200' : 'bg-rose-50/70 border-rose-200'"
                >
                    <p
                        class="text-[11px] font-bold uppercase tracking-wider"
                        :class="report.net_income >= 0 ? 'text-emerald-700' : 'text-rose-700'"
                    >
                        Net {{ report.net_income >= 0 ? 'Income (Profit)' : 'Loss' }}
                    </p>
                    <p
                        class="text-2xl font-extrabold mt-1"
                        :class="report.net_income >= 0 ? 'text-emerald-900' : 'text-rose-900'"
                    >
                        <CurrencyDisplay :amount="report.net_income" />
                    </p>
                </div>
            </div>

            <!-- Detailed Statement Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden p-6 md:p-8 space-y-8">
                <!-- 1. Revenue Section -->
                <div>
                    <div class="flex items-center justify-between border-b-2 border-emerald-500 pb-2 mb-3">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-900">1. Revenue / Incomes</h3>
                        <span class="text-xs font-mono font-bold text-emerald-700">Credit Balance</span>
                    </div>

                    <table class="w-full text-left border-collapse text-xs">
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="item in (report.revenue_accounts || report.revenue_rows)"
                                :key="item.account_code"
                                class="hover:bg-slate-50/60"
                            >
                                <td class="py-2.5 px-3 w-2/12 font-bold text-slate-800">{{ item.account_code }}</td>
                                <td class="py-2.5 px-3 w-7/12 font-sans font-medium text-slate-700">{{ item.account_name || item.name }}</td>
                                <td class="py-2.5 px-3 w-3/12 text-right font-bold text-slate-900">
                                    <CurrencyDisplay :amount="item.balance ?? item.amount" />
                                </td>
                            </tr>
                            <tr v-if="!report.revenue_accounts || report.revenue_accounts.length === 0">
                                <td colspan="3" class="py-4 text-center text-slate-400 font-sans">
                                    No revenue transactions recorded during this period.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-emerald-50/60 font-bold border-t border-emerald-200 text-xs">
                                <td colspan="2" class="py-3 px-3 uppercase font-sans tracking-wider text-emerald-900">
                                    Total Revenue:
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-emerald-900 text-sm">
                                    <CurrencyDisplay :amount="report.total_revenue" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 2. Expenses Section -->
                <div>
                    <div class="flex items-center justify-between border-b-2 border-rose-500 pb-2 mb-3">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-rose-900">2. Operating & General Expenses</h3>
                        <span class="text-xs font-mono font-bold text-rose-700">Debit Balance</span>
                    </div>

                    <table class="w-full text-left border-collapse text-xs">
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="item in (report.expense_accounts || report.expense_rows)"
                                :key="item.account_code"
                                class="hover:bg-slate-50/60"
                            >
                                <td class="py-2.5 px-3 w-2/12 font-bold text-slate-800">{{ item.account_code }}</td>
                                <td class="py-2.5 px-3 w-7/12 font-sans font-medium text-slate-700">{{ item.account_name || item.name }}</td>
                                <td class="py-2.5 px-3 w-3/12 text-right font-bold text-slate-900">
                                    <CurrencyDisplay :amount="item.balance ?? item.amount" />
                                </td>
                            </tr>
                            <tr v-if="!report.expense_accounts || report.expense_accounts.length === 0">
                                <td colspan="3" class="py-4 text-center text-slate-400 font-sans">
                                    No expense transactions recorded during this period.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-rose-50/60 font-bold border-t border-rose-200 text-xs">
                                <td colspan="2" class="py-3 px-3 uppercase font-sans tracking-wider text-rose-900">
                                    Total Expenses:
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-rose-900 text-sm">
                                    <CurrencyDisplay :amount="report.total_expenses" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 3. Bottom Line Net Profit / Loss -->
                <div
                    class="p-5 rounded-2xl border-2 flex items-center justify-between"
                    :class="report.net_income >= 0 ? 'bg-emerald-50 border-emerald-400' : 'bg-rose-50 border-rose-400'"
                >
                    <div>
                        <h4
                            class="text-base font-extrabold uppercase tracking-wider"
                            :class="report.net_income >= 0 ? 'text-emerald-950' : 'text-rose-950'"
                        >
                            Net {{ report.net_income >= 0 ? 'Income / Profit' : 'Loss' }} for the Period
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">Calculated as Total Revenue &minus; Total Expenses</p>
                    </div>
                    <div
                        class="text-2xl font-black font-mono"
                        :class="report.net_income >= 0 ? 'text-emerald-900' : 'text-rose-900'"
                    >
                        <CurrencyDisplay :amount="report.net_income" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
