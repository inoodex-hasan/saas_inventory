<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import DateDisplay from '@/Components/DateDisplay.vue';
import Pagination from '@/Components/Pagination.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    profiles: Object,
    customers: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const frequency = ref(props.filters.frequency || '');
const customerId = ref(props.filters.customer_id || '');

function applyFilter() {
    router.get(route('recurring-invoices.index'), {
        search: search.value,
        status: status.value,
        frequency: frequency.value,
        customer_id: customerId.value,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    frequency.value = '';
    customerId.value = '';
    applyFilter();
}

function generateNow(profile) {
    if (confirm(`Generate invoice now for Subscription #${profile.subscription_number}? A new Sales Order will be created immediately.`)) {
        router.post(route('recurring-invoices.generate-now', profile.id));
    }
}

function pauseProfile(profile) {
    if (confirm(`Pause subscription #${profile.subscription_number}? Automated invoicing will be suspended.`)) {
        router.post(route('recurring-invoices.pause', profile.id));
    }
}

function resumeProfile(profile) {
    if (confirm(`Resume subscription #${profile.subscription_number}? Automated billing will reactivate.`)) {
        router.post(route('recurring-invoices.resume', profile.id));
    }
}

function cancelProfile(profile) {
    if (confirm(`Cancel subscription #${profile.subscription_number}? This cannot be undone.`)) {
        router.post(route('recurring-invoices.cancel', profile.id));
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

function isDue(dateStr) {
    if (!dateStr) return false;
    const today = new Date().toISOString().slice(0, 10);
    return dateStr <= today;
}
</script>

<template>
    <AppLayout>
        <Head title="Recurring Invoices & Subscriptions" />

        <template #header>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                    Recurring Invoices & Subscriptions
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Automate periodic billing cycles, retainer contracts, and subscription receivables for your customers.
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Active Subscriptions -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Subscriptions</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.active_subscriptions }}</span>
                        <span class="text-xs text-slate-400">of {{ metrics.total_profiles }} total profiles</span>
                    </div>
                </div>

                <!-- Monthly Recurring Revenue (MRR) -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Run Rate (MRR)</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-indigo-600">
                            <CurrencyDisplay :amount="metrics.total_mrr" />
                        </span>
                        <span class="text-xs text-slate-400">normalized / mo</span>
                    </div>
                </div>

                <!-- Due for Issuance Today -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Due for Issuance</span>
                        <div 
                            class="w-8 h-8 rounded-lg flex items-center justify-center"
                            :class="metrics.due_today > 0 ? 'bg-amber-100 text-amber-700 font-bold' : 'bg-slate-100 text-slate-500'"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold" :class="metrics.due_today > 0 ? 'text-amber-600' : 'text-slate-900'">
                            {{ metrics.due_today }}
                        </span>
                        <span class="text-xs text-slate-400">ready to bill today</span>
                    </div>
                </div>

                <!-- Total Invoices Generated -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Automated Engine</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-sm font-semibold text-emerald-600 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Scheduled Daily
                        </span>
                        <span class="text-xs text-slate-400">at 06:00 AM</span>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
                        <!-- Search input -->
                        <div class="sm:col-span-2 relative">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search subscription #, title, customer..."
                                @keydown.enter="applyFilter"
                                class="w-full text-xs rounded-xl border-slate-200 pl-9 pr-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Status filter -->
                        <div>
                            <select
                                v-model="status"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-slate-200 py-2.5 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Frequency filter -->
                        <div>
                            <select
                                v-model="frequency"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-slate-200 py-2.5 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Frequencies</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semi_annual">Semi-Annual</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            @click="applyFilter"
                            class="px-4 py-2.5 rounded-xl bg-slate-900 text-white font-medium text-xs hover:bg-slate-800 transition-colors"
                        >
                            Filter
                        </button>
                        <button
                            v-if="search || status || frequency || customerId"
                            @click="resetFilter"
                            class="px-3 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs transition-colors"
                            title="Reset filters"
                        >
                            Reset
                        </button>
                        <Link
                            :href="route('recurring-invoices.create')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 shadow-sm shadow-indigo-600/30 transition-all whitespace-nowrap"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Create Profile</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Profiles Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] uppercase font-bold text-slate-500 tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Subscription #</th>
                                <th class="py-3.5 px-4">Customer & Profile</th>
                                <th class="py-3.5 px-4">Cadence</th>
                                <th class="py-3.5 px-4 text-right">Cycle Amount</th>
                                <th class="py-3.5 px-4 text-right">MRR Value</th>
                                <th class="py-3.5 px-4">Next Issue</th>
                                <th class="py-3.5 px-4 text-center">Progress</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr
                                v-for="profile in profiles.data"
                                :key="profile.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Subscription Number -->
                                <td class="py-3.5 px-4">
                                    <Link
                                        :href="route('recurring-invoices.show', profile.id)"
                                        class="font-mono font-semibold text-indigo-600 hover:text-indigo-800 hover:underline block"
                                    >
                                        {{ profile.subscription_number }}
                                    </Link>
                                    <span class="text-[10px] text-slate-400">
                                        {{ profile.sales_orders_count }} invoice(s) issued
                                    </span>
                                </td>

                                <!-- Customer & Profile Name -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ profile.profile_name }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ profile.customer?.name }}
                                        <span v-if="profile.customer?.phone" class="text-slate-400">&bull; {{ profile.customer.phone }}</span>
                                    </div>
                                </td>

                                <!-- Cadence -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        {{ frequencyLabel(profile.frequency) }}
                                    </span>
                                </td>

                                <!-- Cycle Amount -->
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900">
                                    <CurrencyDisplay :amount="profile.total_amount" />
                                </td>

                                <!-- MRR -->
                                <td class="py-3.5 px-4 text-right font-semibold text-indigo-600">
                                    <CurrencyDisplay :amount="profile.monthly_recurring_revenue" />
                                </td>

                                <!-- Next Issue Date -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="profile.status === 'active' && isDue(profile.next_issue_date)"
                                            class="w-2 h-2 rounded-full bg-amber-500 animate-ping"
                                        ></span>
                                        <span :class="profile.status === 'active' && isDue(profile.next_issue_date) ? 'text-amber-600 font-bold' : 'text-slate-700'">
                                            <DateDisplay :date="profile.next_issue_date" />
                                        </span>
                                    </div>
                                    <span v-if="profile.status === 'active' && isDue(profile.next_issue_date)" class="text-[10px] text-amber-600 font-medium block">
                                        Due now
                                    </span>
                                </td>

                                <!-- Progress -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-mono text-slate-700">
                                        {{ profile.cycles_completed }}
                                        <span class="text-slate-400">/ {{ profile.total_cycles_limit || '&infin;' }}</span>
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="profile.status" />
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="profile.status === 'active'"
                                            @click="generateNow(profile)"
                                            title="Generate Invoice Now"
                                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </button>

                                        <Link
                                            :href="route('recurring-invoices.show', profile.id)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>

                                        <Link
                                            :href="route('recurring-invoices.edit', profile.id)"
                                            class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                                            title="Edit Profile"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>

                                        <button
                                            v-if="profile.status === 'active'"
                                            @click="pauseProfile(profile)"
                                            title="Pause Subscription"
                                            class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>

                                        <button
                                            v-else-if="profile.status === 'paused'"
                                            @click="resumeProfile(profile)"
                                            title="Resume Subscription"
                                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!profiles.data || profiles.data.length === 0">
                                <td colspan="9" class="py-12 text-center">
                                    <div class="max-w-xs mx-auto text-center space-y-3">
                                        <p class="text-sm font-semibold text-slate-800">No recurring subscriptions found</p>
                                        <p class="text-xs text-slate-500">
                                            Create recurring billing profiles for your retainers, subscriptions, and periodic contracts.
                                        </p>
                                        <Link
                                            :href="route('recurring-invoices.create')"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition-colors"
                                        >
                                            + Create Profile
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div v-if="profiles.data && profiles.data.length > 0" class="p-4 border-t border-slate-100">
                    <Pagination :links="profiles.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
