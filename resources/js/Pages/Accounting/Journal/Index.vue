<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Pagination from '@/Components/Pagination.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    entries: Object,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

function applyFilters() {
    const params = {};
    if (search.value) params.search = search.value;
    if (startDate.value) params.start_date = startDate.value;
    if (endDate.value) params.end_date = endDate.value;
    router.get(route('journal.index'), params, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    startDate.value = '';
    endDate.value = '';
    router.get(route('journal.index'), {}, { preserveState: false, replace: true });
}
</script>

<template>
    <AppLayout title="General Journal">
        <template #header>
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">General Journal</h2>
                <p class="text-xs text-slate-500 mt-0.5">Chronological record of double-entry financial transactions.</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Highlights -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Posted Entries</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1">{{ metrics.total_entries }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Cumulative Volume (Debit)</p>
                        <p class="text-3xl font-extrabold text-emerald-600 mt-1">
                            <CurrencyDisplay :amount="metrics.total_debit_volume" />
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filters -->
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
                            class="w-full pl-10 pr-4 py-2 text-xs bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="startDate"
                            type="date"
                            class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-indigo-500"
                        />
                        <span class="text-xs text-slate-400">to</span>
                        <input
                            v-model="endDate"
                            type="date"
                            class="text-xs bg-slate-50/70 border border-slate-200 rounded-xl px-3 py-2 focus:bg-white focus:border-indigo-500"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white rounded-xl transition-all"
                    >
                        Filter
                    </button>
                    <button
                        v-if="search || startDate || endDate"
                        @click="resetFilters"
                        class="px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-800 transition-all"
                    >
                        Clear
                    </button>
                    <Link
                        :href="route('journal.create')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm shadow-indigo-600/30 transition-all shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Journal Entry</span>
                    </Link>
                </div>
            </div>

            <!-- Entries Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4 whitespace-nowrap">Entry Number</th>
                                <th class="py-3 px-4 whitespace-nowrap">Date</th>
                                <th class="py-3 px-4 whitespace-nowrap">Narration / Description</th>
                                <th class="py-3 px-4 whitespace-nowrap">Account Lines Preview</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Debit / Credit Balance</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Posted By</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="entry in entries.data"
                                :key="entry.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-bold text-indigo-600 whitespace-nowrap">
                                    <Link :href="route('journal.show', entry.id)" class="hover:underline">
                                        {{ entry.entry_number }}
                                    </Link>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                    {{ formatDate(entry.entry_date) }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800 max-w-[220px] lg:max-w-[300px] truncate" :title="entry.narration">
                                    {{ entry.narration ? (entry.narration.length > 55 ? entry.narration.slice(0, 55) + '...' : entry.narration) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-[11px] text-slate-500 whitespace-nowrap">
                                    <div v-for="line in entry.lines.slice(0, 2)" :key="line.id" class="truncate font-mono">
                                        <span class="text-slate-700 font-semibold">{{ line.account?.account_code }}</span>:
                                        {{ line.debit > 0 ? `Dr ${line.debit}` : `Cr ${line.credit}` }}
                                    </div>
                                    <div v-if="entry.lines.length > 2" class="text-indigo-500 font-medium">
                                        +{{ entry.lines.length - 2 }} more lines...
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <CurrencyDisplay :amount="entry.total_debit" />
                                </td>
                                <td class="py-3 px-4 text-center text-slate-500 text-[11px] whitespace-nowrap">
                                    {{ entry.creator?.name || 'System' }}
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end">
                                        <ActionDropdown align="right">
                                            <Link
                                                :href="route('journal.show', entry.id)"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-indigo-600 hover:bg-indigo-50 font-semibold transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Voucher Details</span>
                                            </Link>
                                            <Link
                                                :href="route('accounting.ledger')"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span>Open General Ledger</span>
                                            </Link>
                                        </ActionDropdown>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!entries.data || entries.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No journal entries recorded.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div v-if="entries.links && entries.links.length > 3" class="p-4 border-t border-slate-100">
                    <Pagination :links="entries.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
