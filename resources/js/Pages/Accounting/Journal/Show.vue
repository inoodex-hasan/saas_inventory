<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    page_title: String,
    entry: Object,
});

function printVoucher() {
    window.print();
}
</script>

<template>
    <AppLayout :title="page_title">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Journal Voucher #{{ entry.entry_number }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Recorded on {{ entry.entry_date }} &bull; Double-entry audit ledger</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('journal.index')"
                        class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition-all"
                    >
                        &larr; All Entries
                    </Link>
                    <button
                        @click="printVoucher"
                        class="px-3.5 py-1.5 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print Voucher</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 md:p-8 space-y-6">
                <!-- Voucher Header -->
                <div class="border-b border-slate-100 pb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 uppercase tracking-wider mb-2">
                            General Journal Voucher
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 font-mono">{{ entry.entry_number }}</h1>
                        <p class="text-xs text-slate-500 mt-1">Date: <span class="font-semibold text-slate-800">{{ entry.entry_date }}</span></p>
                    </div>

                    <div class="text-left sm:text-right text-xs space-y-1">
                        <div>
                            <span class="text-slate-400">Created by: </span>
                            <span class="font-semibold text-slate-700">{{ entry.creator?.name || 'System Auto' }}</span>
                        </div>
                        <div v-if="entry.fiscal_year">
                            <span class="text-slate-400">Fiscal Period: </span>
                            <span class="font-semibold text-slate-700">{{ entry.fiscal_year.title }}</span>
                        </div>
                        <div v-if="entry.source_type">
                            <span class="text-slate-400">Source: </span>
                            <span class="font-mono font-semibold text-slate-700">{{ entry.source_type }} #{{ entry.source_id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Narration Block -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Narration / Purpose</p>
                    <p class="text-xs text-slate-800 font-medium whitespace-pre-wrap">{{ entry.narration }}</p>
                </div>

                <!-- Transaction Lines Table -->
                <div class="overflow-hidden border border-slate-200 rounded-xl">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4 w-2/12">Account Code</th>
                                <th class="py-3 px-4 w-4/12">Account Title</th>
                                <th class="py-3 px-4 w-2/12">Memo</th>
                                <th class="py-3 px-4 w-2/12 text-right">Debit</th>
                                <th class="py-3 px-4 w-2/12 text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="line in entry.lines"
                                :key="line.id"
                                class="hover:bg-slate-50/50"
                            >
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ line.account?.account_code }}
                                </td>
                                <td class="py-3 px-4 font-sans font-medium text-slate-800">
                                    {{ line.account?.name }}
                                    <span class="ml-1 text-[10px] uppercase font-mono text-slate-400">({{ line.account?.type }})</span>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-500 text-[11px]">
                                    {{ line.memo || '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900">
                                    <span v-if="parseFloat(line.debit) > 0">
                                        <CurrencyDisplay :amount="line.debit" />
                                    </span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900">
                                    <span v-if="parseFloat(line.credit) > 0">
                                        <CurrencyDisplay :amount="line.credit" />
                                    </span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100/80 font-bold border-t-2 border-slate-300 text-xs">
                                <td colspan="3" class="py-3.5 px-4 text-right uppercase tracking-wider text-slate-700">
                                    Total Balanced Volume:
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-900">
                                    <CurrencyDisplay :amount="entry.total_debit" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-900">
                                    <CurrencyDisplay :amount="entry.total_credit" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer Signatures (For physical verification printout) -->
                <div class="pt-12 grid grid-cols-3 gap-6 text-center text-xs text-slate-400">
                    <div>
                        <div class="border-t border-slate-300 pt-2 font-medium">Prepared By</div>
                    </div>
                    <div>
                        <div class="border-t border-slate-300 pt-2 font-medium">Verified By</div>
                    </div>
                    <div>
                        <div class="border-t border-slate-300 pt-2 font-medium">Approved Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
