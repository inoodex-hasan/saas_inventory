<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import DateDisplay from '@/Components/DateDisplay.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    profile: Object,
    generatedInvoices: Object,
});

function generateNow() {
    if (confirm(`Generate an invoice right now for Subscription #${props.profile.subscription_number}? A confirmed Sales Order will be created.`)) {
        router.post(route('recurring-invoices.generate-now', props.profile.id));
    }
}

function pauseProfile() {
    if (confirm('Pause this recurring subscription? Automated cycles will be suspended.')) {
        router.post(route('recurring-invoices.pause', props.profile.id));
    }
}

function resumeProfile() {
    if (confirm('Resume this recurring subscription? Automated cycles will restart.')) {
        router.post(route('recurring-invoices.resume', props.profile.id));
    }
}

function cancelProfile() {
    if (confirm('Cancel this recurring subscription? This action cannot be undone.')) {
        router.post(route('recurring-invoices.cancel', props.profile.id));
    }
}

function deleteProfile() {
    if (confirm('Delete this recurring profile completely?')) {
        router.delete(route('recurring-invoices.destroy', props.profile.id));
    }
}

function frequencyLabel(freq) {
    const map = {
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        semi_annual: 'Semi-Annual',
        yearly: 'Yearly',
    };
    return map[freq] || freq;
}
</script>

<template>
    <AppLayout>
        <Head :title="`Subscription ${profile.subscription_number}`" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Link :href="route('recurring-invoices.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            &larr; Back to Subscriptions
                        </Link>
                    </div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight font-mono">
                            {{ profile.subscription_number }}
                        </h1>
                        <StatusBadge :status="profile.status" />
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ profile.profile_name }} &bull; Client: {{ profile.customer?.name }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="profile.status === 'active'"
                        @click="generateNow"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 text-white font-semibold text-xs hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Generate Invoice Now
                    </button>

                    <Link
                        :href="route('recurring-invoices.edit', profile.id)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 transition-all shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </Link>

                    <button
                        v-if="profile.status === 'active'"
                        @click="pauseProfile"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-amber-300 bg-amber-50 text-amber-700 font-semibold text-xs hover:bg-amber-100 transition-all"
                    >
                        Pause
                    </button>

                    <button
                        v-else-if="profile.status === 'paused'"
                        @click="resumeProfile"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-700 font-semibold text-xs hover:bg-emerald-100 transition-all"
                    >
                        Resume
                    </button>

                    <button
                        v-if="profile.status !== 'cancelled' && profile.status !== 'completed'"
                        @click="cancelProfile"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 font-semibold text-xs hover:bg-rose-100 transition-all"
                    >
                        Cancel Plan
                    </button>

                    <button
                        v-if="generatedInvoices.data.length === 0"
                        @click="deleteProfile"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-rose-600 hover:bg-rose-50 font-semibold text-xs transition-all"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric KPI Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Run Rate (MRR)</span>
                    <div class="mt-2 text-2xl font-bold text-indigo-600">
                        <CurrencyDisplay :amount="profile.monthly_recurring_revenue" />
                    </div>
                    <span class="text-[11px] text-slate-400">normalized monthly revenue</span>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cycle Amount</span>
                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        <CurrencyDisplay :amount="profile.total_amount" />
                    </div>
                    <span class="text-[11px] text-slate-400">billed per {{ profile.frequency }} cycle</span>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Next Scheduled Issue</span>
                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        <DateDisplay :date="profile.next_issue_date" />
                    </div>
                    <span class="text-[11px] text-slate-400">
                        Last issued: {{ profile.last_issued_at ? profile.last_issued_at : 'Never' }}
                    </span>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cycle Progress</span>
                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ profile.cycles_completed }}
                        <span class="text-slate-400 text-lg">/ {{ profile.total_cycles_limit || '∞' }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400">
                        {{ profile.total_cycles_limit ? `${profile.total_cycles_limit - profile.cycles_completed} cycles remaining` : 'Ongoing recurring contract' }}
                    </span>
                </div>
            </div>

            <!-- Profile Specifications Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Customer Information -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 border-b border-slate-100 pb-2">
                        Client / Customer Profile
                    </h2>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Name:</span>
                            <span class="font-bold text-slate-800">{{ profile.customer?.name }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Phone:</span>
                            <span class="font-medium text-slate-800">{{ profile.customer?.phone || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Email:</span>
                            <span class="font-medium text-slate-800">{{ profile.customer?.email || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Billing Address:</span>
                            <span class="font-medium text-slate-800 max-w-xs text-right">{{ profile.customer?.address || 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Fulfillment Info -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 border-b border-slate-100 pb-2">
                        Recurrence & Billing Terms
                    </h2>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Cadence:</span>
                            <span class="font-bold text-slate-800">{{ frequencyLabel(profile.frequency) }} (Every {{ profile.interval_count }} cycle)</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Contract Period:</span>
                            <span class="font-medium text-slate-800">
                                {{ profile.start_date }} &rarr; {{ profile.end_date || 'Perpetual / Ongoing' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Payment Due Terms:</span>
                            <span class="font-medium text-slate-800">Due within {{ profile.payment_terms_days }} days</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Warehouse:</span>
                            <span class="font-medium text-slate-800">{{ profile.warehouse?.name || 'Default Warehouse' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Auto-Email PDF:</span>
                            <span class="font-semibold" :class="profile.auto_send_email ? 'text-emerald-600' : 'text-slate-400'">
                                {{ profile.auto_send_email ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">
                        Subscription Line Items
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] uppercase font-bold text-slate-500 tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Item / Service</th>
                                <th class="py-3 px-4 text-center">Quantity</th>
                                <th class="py-3 px-4 text-right">Unit Price</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="item in profile.items" :key="item.id" class="hover:bg-slate-50/70">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ item.item_name }}</div>
                                    <div v-if="item.description" class="text-[11px] text-slate-400 mt-0.5">{{ item.description }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">{{ item.quantity }}</td>
                                <td class="py-3.5 px-4 text-right font-mono">
                                    <CurrencyDisplay :amount="item.unit_price" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900 font-mono">
                                    <CurrencyDisplay :amount="item.subtotal" />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50/60 font-semibold text-xs border-t border-slate-200/80">
                            <tr>
                                <td colspan="3" class="py-2.5 px-4 text-right text-slate-500">Subtotal:</td>
                                <td class="py-2.5 px-4 text-right font-mono text-slate-800">
                                    <CurrencyDisplay :amount="profile.subtotal" />
                                </td>
                            </tr>
                            <tr v-if="profile.discount_amount > 0">
                                <td colspan="3" class="py-2 px-4 text-right text-slate-500">Discount:</td>
                                <td class="py-2 px-4 text-right font-mono text-rose-600">
                                    -<CurrencyDisplay :amount="profile.discount_amount" />
                                </td>
                            </tr>
                            <tr v-if="profile.tax_amount > 0">
                                <td colspan="3" class="py-2 px-4 text-right text-slate-500">Tax / VAT:</td>
                                <td class="py-2 px-4 text-right font-mono text-slate-800">
                                    +<CurrencyDisplay :amount="profile.tax_amount" />
                                </td>
                            </tr>
                            <tr class="border-t border-slate-200 font-bold text-sm text-slate-900">
                                <td colspan="3" class="py-3 px-4 text-right">Cycle Total:</td>
                                <td class="py-3 px-4 text-right font-mono text-indigo-600">
                                    <CurrencyDisplay :amount="profile.total_amount" />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes if any -->
            <div v-if="profile.notes" class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-2">Terms & Notes</span>
                <p class="text-xs text-slate-600 whitespace-pre-wrap">{{ profile.notes }}</p>
            </div>

            <!-- Invoices Generated History -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">
                            Generated Commercial Invoices
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            History of all Sales Orders automatically or manually generated from this subscription profile.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] uppercase font-bold text-slate-500 tracking-wider">
                            <tr>
                                <th class="py-3 px-4">Order #</th>
                                <th class="py-3 px-4">Invoice Date</th>
                                <th class="py-3 px-4">Fulfillment Status</th>
                                <th class="py-3 px-4">Payment Status</th>
                                <th class="py-3 px-4 text-right">Total Amount</th>
                                <th class="py-3 px-4 text-right">Paid</th>
                                <th class="py-3 px-4 text-right">Due Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr
                                v-for="order in generatedInvoices.data"
                                :key="order.id"
                                class="hover:bg-slate-50/70"
                            >
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-indigo-600">
                                        {{ order.order_number }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <DateDisplay :date="order.order_date" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="order.status" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="order.payment_status" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900 font-mono">
                                    <CurrencyDisplay :amount="order.total_amount" />
                                </td>
                                <td class="py-3.5 px-4 text-right text-emerald-600 font-mono">
                                    <CurrencyDisplay :amount="order.paid_amount" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono" :class="order.due_amount > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                    <CurrencyDisplay :amount="order.due_amount" />
                                </td>
                            </tr>

                            <tr v-if="!generatedInvoices.data || generatedInvoices.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    No invoices have been generated from this subscription profile yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="generatedInvoices.data && generatedInvoices.data.length > 0" class="p-4 border-t border-slate-100">
                    <Pagination :links="generatedInvoices.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
