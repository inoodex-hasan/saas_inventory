<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    page_title: String,
    accounts: Array,
    fiscalYears: Array,
    todayDate: String,
});

const form = useForm({
    entry_date: props.todayDate || new Date().toISOString().slice(0, 10),
    fiscal_year_id: props.fiscalYears && props.fiscalYears.length > 0 ? props.fiscalYears[0].id : '',
    narration: '',
    lines: [
        { chart_of_account_id: '', debit: 0, credit: 0, memo: '' },
        { chart_of_account_id: '', debit: 0, credit: 0, memo: '' },
    ],
});

function addLine() {
    form.lines.push({
        chart_of_account_id: '',
        debit: 0,
        credit: 0,
        memo: '',
    });
}

function removeLine(index) {
    if (form.lines.length > 2) {
        form.lines.splice(index, 1);
    }
}

const totalDebits = computed(() => {
    return form.lines.reduce((acc, line) => acc + (parseFloat(line.debit) || 0), 0);
});

const totalCredits = computed(() => {
    return form.lines.reduce((acc, line) => acc + (parseFloat(line.credit) || 0), 0);
});

const difference = computed(() => {
    return Math.abs(totalDebits.value - totalCredits.value);
});

const isBalanced = computed(() => {
    return totalDebits.value > 0 && Math.abs(totalDebits.value - totalCredits.value) < 0.001;
});

function onDebitChange(line) {
    if (parseFloat(line.debit) > 0) {
        line.credit = 0;
    }
}

function onCreditChange(line) {
    if (parseFloat(line.credit) > 0) {
        line.debit = 0;
    }
}

function submit() {
    form.post(route('journal.store'));
}
</script>

<template>
    <AppLayout title="Post Journal Entry">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Post Manual Journal Entry</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Strict double-entry transaction voucher recorder.</p>
                </div>
                <Link
                    :href="route('journal.index')"
                    class="px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition-all"
                >
                    &larr; Back to Journal
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Voucher Header Card -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 text-xs">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Voucher Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Posting Date *</label>
                            <input
                                v-model="form.entry_date"
                                type="date"
                                class="w-full px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            <p v-if="form.errors.entry_date" class="text-rose-500 text-[11px] mt-1">{{ form.errors.entry_date }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Fiscal Year</label>
                            <select
                                v-model="form.fiscal_year_id"
                                class="w-full px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="">Auto / Current Year</option>
                                <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">
                                    {{ fy.title }} ({{ fy.start_date }} - {{ fy.end_date }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Narration / Business Purpose *</label>
                        <textarea
                            v-model="form.narration"
                            rows="2"
                            placeholder="e.g. Owner capital injection, adjustment entry, or depreciation expense"
                            class="w-full px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            required
                        ></textarea>
                        <p v-if="form.errors.narration" class="text-rose-500 text-[11px] mt-1">{{ form.errors.narration }}</p>
                    </div>
                </div>

                <!-- Lines Table Card -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Debit & Credit Entries</h3>
                            <p class="text-xs text-slate-500">Every journal entry must contain at least 2 balanced line items.</p>
                        </div>
                        <button
                            type="button"
                            @click="addLine"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Row</span>
                        </button>
                    </div>

                    <p v-if="form.errors.lines" class="text-rose-500 text-xs font-semibold">{{ form.errors.lines }}</p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-2.5 px-3 w-5/12">Account</th>
                                    <th class="py-2.5 px-3 w-2/12 text-right">Debit</th>
                                    <th class="py-2.5 px-3 w-2/12 text-right">Credit</th>
                                    <th class="py-2.5 px-3 w-2/12">Line Memo</th>
                                    <th class="py-2.5 px-3 w-1/12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(line, index) in form.lines" :key="index" class="hover:bg-slate-50/50">
                                    <td class="py-2 px-3">
                                        <select
                                            v-model="line.chart_of_account_id"
                                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50/70 border border-slate-200 rounded-lg focus:bg-white focus:border-indigo-500"
                                            required
                                        >
                                            <option value="" disabled>Select Ledger Account...</option>
                                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                {{ acc.account_code }} - {{ acc.name }} ({{ acc.type }})
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-3">
                                        <input
                                            v-model.number="line.debit"
                                            @input="onDebitChange(line)"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full px-2.5 py-1.5 text-xs text-right font-mono bg-slate-50/70 border border-slate-200 rounded-lg focus:bg-white focus:border-indigo-500"
                                        />
                                    </td>
                                    <td class="py-2 px-3">
                                        <input
                                            v-model.number="line.credit"
                                            @input="onCreditChange(line)"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full px-2.5 py-1.5 text-xs text-right font-mono bg-slate-50/70 border border-slate-200 rounded-lg focus:bg-white focus:border-indigo-500"
                                        />
                                    </td>
                                    <td class="py-2 px-3">
                                        <input
                                            v-model="line.memo"
                                            type="text"
                                            placeholder="Optional note"
                                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50/70 border border-slate-200 rounded-lg focus:bg-white focus:border-indigo-500"
                                        />
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="removeLine(index)"
                                            :disabled="form.lines.length <= 2"
                                            class="p-1 text-slate-400 hover:text-rose-500 disabled:opacity-30 disabled:hover:text-slate-400 transition-colors"
                                            title="Delete Line"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 font-bold border-t-2 border-slate-200 text-xs">
                                    <td class="py-3 px-3 text-right uppercase tracking-wider text-slate-600">Totals:</td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-900">
                                        <CurrencyDisplay :amount="totalDebits" />
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-slate-900">
                                        <CurrencyDisplay :amount="totalCredits" />
                                    </td>
                                    <td colspan="2" class="py-3 px-3">
                                        <span v-if="isBalanced" class="inline-flex items-center gap-1.5 text-emerald-600 font-semibold text-xs">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Balanced (Net Diff: 0.00)
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 text-rose-500 font-semibold text-xs">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            Out of balance by {{ difference.toFixed(2) }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between">
                    <Link
                        :href="route('journal.index')"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-800"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="!isBalanced || form.processing"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-xl shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        {{ form.processing ? 'Posting Entry...' : 'Post Journal Voucher' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
