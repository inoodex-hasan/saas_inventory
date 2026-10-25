<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    usage: Object,
    plans: Array,
    payments: Object,
    payment_instructions: Object,
});

const isPayModalOpen = ref(false);
const selectedPlan = ref(null);
const billingCycle = ref('monthly'); // 'monthly' | 'yearly'
const copiedKey = ref(null);

const payForm = useForm({
    plan_id: '',
    billing_cycle: 'monthly',
    payment_method: 'bkash',
    sender_number: '',
    trx_id: '',
    notes: '',
});

function copyToClipboard(text, key) {
    if (navigator?.clipboard?.writeText) {
        navigator.clipboard.writeText(text);
        copiedKey.value = key;
        setTimeout(() => {
            if (copiedKey.value === key) copiedKey.value = null;
        }, 2000);
    }
}

function openPayModal(plan) {
    selectedPlan.value = plan;
    payForm.plan_id = plan.id;
    payForm.billing_cycle = billingCycle.value;
    payForm.payment_method = 'bkash';
    payForm.sender_number = '';
    payForm.trx_id = '';
    payForm.notes = '';
    payForm.clearErrors();
    isPayModalOpen.value = true;
}

function submitPayment() {
    payForm.post(route('subscription.pay'), {
        preserveScroll: true,
        onSuccess: () => {
            isPayModalOpen.value = false;
            payForm.reset();
        },
    });
}

function getAmount() {
    if (!selectedPlan.value) return 0;
    return payForm.billing_cycle === 'yearly'
        ? selectedPlan.value.annual_price_bdt
        : selectedPlan.value.price_bdt;
}

const paymentMethodDetails = {
    bkash: {
        name: 'bKash Merchant',
        number: '01712-345678',
        counter: 'Counter 1',
        type: 'Merchant Payment',
        color: 'from-pink-600 to-rose-600',
        textColor: 'text-pink-600',
        bgColor: 'bg-pink-50',
        borderColor: 'border-pink-200',
    },
    nagad: {
        name: 'Nagad Merchant',
        number: '01812-345678',
        counter: 'Counter 1',
        type: 'Merchant Payment',
        color: 'from-orange-500 to-amber-600',
        textColor: 'text-orange-600',
        bgColor: 'bg-orange-50',
        borderColor: 'border-orange-200',
    },
    rocket: {
        name: 'Rocket Biller',
        number: '01912-345678-9',
        counter: 'Biller ID 4567',
        type: 'Merchant / Biller',
        color: 'from-purple-600 to-indigo-600',
        textColor: 'text-purple-600',
        bgColor: 'bg-purple-50',
        borderColor: 'border-purple-200',
    },
    bank_transfer: {
        name: 'City Bank Ltd',
        number: '1102938475001',
        counter: 'Gulshan Branch',
        type: 'Corporate Account',
        color: 'from-blue-600 to-cyan-600',
        textColor: 'text-blue-600',
        bgColor: 'bg-blue-50',
        borderColor: 'border-blue-200',
    },
};

function statusBadgeClass(status) {
    switch (status) {
        case 'active': return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
        case 'trialing': return 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40';
        case 'past_due': return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
        case 'expired': return 'bg-rose-500/20 text-rose-300 border-rose-500/40';
        default: return 'bg-slate-500/20 text-slate-300 border-slate-500/40';
    }
}

function paymentStatusBadge(status) {
    switch (status) {
        case 'approved':
            return {
                class: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dot: 'bg-emerald-500',
                label: 'Verified & Active',
            };
        case 'pending':
            return {
                class: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500 animate-pulse',
                label: 'Awaiting Verification',
            };
        case 'rejected':
            return {
                class: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-500',
                label: 'Rejected',
            };
        default:
            return {
                class: 'bg-slate-50 text-slate-700 border-slate-200',
                dot: 'bg-slate-400',
                label: status,
            };
    }
}
</script>

<template>
    <AppLayout title="Subscription & Billing">
        <Head title="Subscription & Billing" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Subscription & Billing</h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Workspace Tier
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Manage resource quotas, subscription tiers, and local Bangladeshi merchant payments.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="plans && plans.length > 0"
                        @click="openPayModal(plans[0])"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Upgrade / Renew</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Hero Subscription Executive Card -->
            <div class="relative overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl border border-slate-800">
                <!-- Glowing Ambient Gradient Orbs -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-violet-600/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 p-6 md:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <!-- Left Info -->
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/10 border border-white/15 backdrop-blur-md">
                                    {{ usage.plan?.name }} Workspace
                                </span>
                                <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border backdrop-blur-md', statusBadgeClass(usage.subscription?.status)]">
                                    <span class="w-2 h-2 rounded-full" :class="usage.subscription?.status === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                                    {{ usage.subscription?.status }}
                                </span>
                            </div>

                            <div>
                                <h2 class="text-2xl md:text-3xl font-black tracking-tight text-white">
                                    {{ usage.plan?.name }} Plan
                                </h2>
                                <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
                                    {{ usage.plan?.description }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-1">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Renews / Expires: <strong class="text-slate-200">{{ formatDate(usage.subscription?.ends_at, 'N/A') }}</strong></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Billing Cycle: <strong class="text-slate-200 uppercase">{{ usage.subscription?.billing_cycle || 'Monthly' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Metric Gauge -->
                        <div class="flex items-center gap-5 bg-white/5 border border-white/10 p-5 rounded-2xl backdrop-blur-md">
                            <div class="text-center sm:text-right">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Validity Remaining</p>
                                <div class="flex items-baseline justify-center sm:justify-end gap-1 mt-0.5">
                                    <span class="text-3xl font-black font-mono text-white">{{ usage.days_remaining }}</span>
                                    <span class="text-xs text-slate-400 font-semibold">days</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Full operational access active</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-400 shrink-0">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Quotas & Utilization Cards -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <span>Resource Quotas & Capacity</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    </h3>
                    <span class="text-xs text-slate-400">Real-time database count</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Products Quota -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Products Catalog</div>
                                    <div class="text-[11px] text-slate-400">SKUs & active items</div>
                                </div>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="usage.products?.is_unlimited ? 'bg-emerald-50 text-emerald-700' : (usage.products?.percent > 85 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700')"
                            >
                                {{ usage.products?.is_unlimited ? 'Unlimited' : `${usage.products?.percent}%` }}
                            </span>
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between text-xs mb-1.5">
                                <span class="text-slate-500 font-medium">Occupancy</span>
                                <span class="font-mono font-bold text-slate-900">
                                    {{ usage.products?.current?.toLocaleString() }} / {{ usage.products?.is_unlimited ? '∞' : usage.products?.limit?.toLocaleString() }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="usage.products?.percent > 90 ? 'bg-rose-500' : (usage.products?.percent > 75 ? 'bg-amber-500' : 'bg-indigo-600')"
                                    :style="{ width: usage.products?.is_unlimited ? '15%' : `${usage.products?.percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Status:</span>
                            <span :class="usage.products?.can_add ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-bold'">
                                {{ usage.products?.can_add ? 'Capacity Available' : 'Quota Exceeded' }}
                            </span>
                        </div>
                    </div>

                    <!-- Warehouses Quota -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-violet-50 border border-violet-100 flex items-center justify-center text-violet-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Warehouse Hubs</div>
                                    <div class="text-[11px] text-slate-400">Stores & locations</div>
                                </div>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="usage.warehouses?.is_unlimited ? 'bg-emerald-50 text-emerald-700' : (usage.warehouses?.percent > 85 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700')"
                            >
                                {{ usage.warehouses?.is_unlimited ? 'Unlimited' : `${usage.warehouses?.percent}%` }}
                            </span>
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between text-xs mb-1.5">
                                <span class="text-slate-500 font-medium">Occupancy</span>
                                <span class="font-mono font-bold text-slate-900">
                                    {{ usage.warehouses?.current }} / {{ usage.warehouses?.is_unlimited ? '∞' : usage.warehouses?.limit }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="usage.warehouses?.percent > 90 ? 'bg-rose-500' : (usage.warehouses?.percent > 75 ? 'bg-amber-500' : 'bg-violet-600')"
                                    :style="{ width: usage.warehouses?.is_unlimited ? '20%' : `${usage.warehouses?.percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Status:</span>
                            <span :class="usage.warehouses?.can_add ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-bold'">
                                {{ usage.warehouses?.can_add ? 'Capacity Available' : 'Quota Exceeded' }}
                            </span>
                        </div>
                    </div>

                    <!-- Staff & Users Quota -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Staff & Team Seats</div>
                                    <div class="text-[11px] text-slate-400">Logins & managers</div>
                                </div>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="usage.users?.is_unlimited ? 'bg-emerald-50 text-emerald-700' : (usage.users?.percent > 85 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700')"
                            >
                                {{ usage.users?.is_unlimited ? 'Unlimited' : `${usage.users?.percent}%` }}
                            </span>
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between text-xs mb-1.5">
                                <span class="text-slate-500 font-medium">Occupancy</span>
                                <span class="font-mono font-bold text-slate-900">
                                    {{ usage.users?.current }} / {{ usage.users?.is_unlimited ? '∞' : usage.users?.limit }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="usage.users?.percent > 90 ? 'bg-rose-500' : (usage.users?.percent > 75 ? 'bg-amber-500' : 'bg-blue-600')"
                                    :style="{ width: usage.users?.is_unlimited ? '25%' : `${usage.users?.percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Status:</span>
                            <span :class="usage.users?.can_add ? 'text-emerald-600 font-semibold' : 'text-rose-600 font-bold'">
                                {{ usage.users?.can_add ? 'Capacity Available' : 'Quota Exceeded' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Plans & Upgrade Checkout -->
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Available Subscription Plans</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Transparent pricing in Bangladeshi Taka (৳ BDT). Instant manual or merchant activation.</p>
                    </div>

                    <!-- Monthly / Yearly Billing Cycle Segment Toggle -->
                    <div class="inline-flex items-center p-1 bg-slate-100 rounded-2xl border border-slate-200 self-start sm:self-auto">
                        <button
                            @click="billingCycle = 'monthly'"
                            class="px-4 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer"
                            :class="billingCycle === 'monthly' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Monthly Billing
                        </button>
                        <button
                            @click="billingCycle = 'yearly'"
                            class="px-4 py-1.5 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer"
                            :class="billingCycle === 'yearly' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            <span>Annual Billing</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="billingCycle === 'yearly' ? 'bg-emerald-400 text-emerald-950' : 'bg-emerald-100 text-emerald-700'">
                                Save 17% (2 Mo Free)
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Plans Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="bg-white rounded-3xl border transition-all duration-300 p-6 flex flex-col justify-between relative"
                        :class="[
                            usage.subscription?.plan_id === plan.id
                                ? 'border-2 border-indigo-600 ring-4 ring-indigo-50 shadow-md'
                                : (plan.slug === 'professional' ? 'border-indigo-300 shadow-md hover:shadow-xl' : 'border-slate-200/80 shadow-xs hover:shadow-md')
                        ]"
                    >
                        <!-- Most Popular Floating Badge -->
                        <div
                            v-if="plan.slug === 'professional' && usage.subscription?.plan_id !== plan.id"
                            class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-[10px] font-extrabold uppercase tracking-wider shadow-sm flex items-center gap-1"
                        >
                            <svg class="w-3 h-3 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <span>Most Popular</span>
                        </div>

                        <!-- Active Plan Badge -->
                        <div
                            v-if="usage.subscription?.plan_id === plan.id"
                            class="absolute -top-3.5 left-6 px-3 py-1 rounded-full bg-slate-900 text-white text-[10px] font-extrabold uppercase tracking-wider shadow-sm flex items-center gap-1"
                        >
                            <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Current Active Plan</span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h4 class="text-lg font-extrabold text-slate-900">{{ plan.name }}</h4>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">{{ plan.description }}</p>
                            </div>

                            <div class="pt-2">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-black text-slate-900 font-mono">
                                        ৳{{ billingCycle === 'yearly' ? Math.round(plan.annual_price_bdt / 12).toLocaleString() : Math.round(plan.price_bdt).toLocaleString() }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500">/ month</span>
                                </div>
                                <p v-if="billingCycle === 'yearly'" class="text-[11px] text-emerald-600 font-semibold mt-1">
                                    Billed ৳{{ Math.round(plan.annual_price_bdt).toLocaleString() }} annually
                                </p>
                                <p v-else class="text-[11px] text-slate-400 mt-1">
                                    Flexible monthly billing
                                </p>
                            </div>

                            <!-- Limits Breakdown -->
                            <div class="space-y-2.5 py-4 border-t border-slate-100 text-xs text-slate-700">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span><strong>{{ plan.max_products === -1 ? 'Unlimited' : plan.max_products.toLocaleString() }}</strong> Products Catalog</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span><strong>{{ plan.max_warehouses === -1 ? 'Unlimited' : plan.max_warehouses }}</strong> Warehouse Hubs</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span><strong>{{ plan.max_users === -1 ? 'Unlimited' : plan.max_users }}</strong> Staff User Accounts</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span>POS Billing & Barcode Labels</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-4 h-4 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span>Vector PDF Invoicing & Receipts</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100">
                            <button
                                @click="openPayModal(plan)"
                                class="w-full py-2.5 px-4 text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer flex items-center justify-center gap-2"
                                :class="[
                                    usage.subscription?.plan_id === plan.id
                                        ? 'bg-slate-900 hover:bg-slate-800 text-white'
                                        : (plan.slug === 'professional' ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/30' : 'bg-slate-800 hover:bg-slate-900 text-white')
                                ]"
                            >
                                <span>{{ usage.subscription?.plan_id === plan.id ? 'Renew / Extend This Plan' : 'Select Plan & Pay' }}</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bangladeshi Payment Instructions Cards -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 md:p-8 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Official Bangladeshi Payment Accounts</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Send payment via any channel below and submit the Transaction ID (TrxID) for instant verification.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Admin Verification Active
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                    <!-- bKash -->
                    <div class="p-4 rounded-2xl border border-pink-100 bg-pink-50/50 hover:bg-pink-50 transition-colors space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-pink-700">bKash Merchant</span>
                            <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-pink-100 text-pink-800">Counter 1</span>
                        </div>
                        <div class="font-mono font-bold text-sm text-slate-900">01712-345678</div>
                        <p class="text-[10px] text-slate-500">Select "Payment" option in bKash App</p>
                        <button
                            type="button"
                            @click="copyToClipboard('01712345678', 'bkash')"
                            class="w-full mt-1 py-1.5 px-3 rounded-lg text-[11px] font-bold bg-white text-pink-700 border border-pink-200 hover:bg-pink-100 transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <span>{{ copiedKey === 'bkash' ? 'Copied!' : 'Copy Number' }}</span>
                        </button>
                    </div>

                    <!-- Nagad -->
                    <div class="p-4 rounded-2xl border border-orange-100 bg-orange-50/50 hover:bg-orange-50 transition-colors space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-orange-700">Nagad Merchant</span>
                            <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-orange-100 text-orange-800">Counter 1</span>
                        </div>
                        <div class="font-mono font-bold text-sm text-slate-900">01812-345678</div>
                        <p class="text-[10px] text-slate-500">Select "Merchant Pay" in Nagad App</p>
                        <button
                            type="button"
                            @click="copyToClipboard('01812345678', 'nagad')"
                            class="w-full mt-1 py-1.5 px-3 rounded-lg text-[11px] font-bold bg-white text-orange-700 border border-orange-200 hover:bg-orange-100 transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <span>{{ copiedKey === 'nagad' ? 'Copied!' : 'Copy Number' }}</span>
                        </button>
                    </div>

                    <!-- Rocket -->
                    <div class="p-4 rounded-2xl border border-purple-100 bg-purple-50/50 hover:bg-purple-50 transition-colors space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-purple-700">Rocket Biller</span>
                            <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-800">ID: 4567</span>
                        </div>
                        <div class="font-mono font-bold text-sm text-slate-900">01912-345678-9</div>
                        <p class="text-[10px] text-slate-500">Pay via Biller ID or Merchant Pay</p>
                        <button
                            type="button"
                            @click="copyToClipboard('019123456789', 'rocket')"
                            class="w-full mt-1 py-1.5 px-3 rounded-lg text-[11px] font-bold bg-white text-purple-700 border border-purple-200 hover:bg-purple-100 transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <span>{{ copiedKey === 'rocket' ? 'Copied!' : 'Copy Number' }}</span>
                        </button>
                    </div>

                    <!-- City Bank -->
                    <div class="p-4 rounded-2xl border border-blue-100 bg-blue-50/50 hover:bg-blue-50 transition-colors space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-700">City Bank Ltd</span>
                            <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">Gulshan</span>
                        </div>
                        <div class="font-mono font-bold text-xs text-slate-900 truncate">1102938475001</div>
                        <p class="text-[10px] text-slate-500 truncate">SaaS Inventory BD Ltd</p>
                        <button
                            type="button"
                            @click="copyToClipboard('1102938475001', 'bank')"
                            class="w-full mt-1 py-1.5 px-3 rounded-lg text-[11px] font-bold bg-white text-blue-700 border border-blue-200 hover:bg-blue-100 transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <span>{{ copiedKey === 'bank' ? 'Copied!' : 'Copy A/C No' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Submission History Table -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Payment & Verification History</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Audit log of submitted bKash, Nagad, Rocket, and Bank transfer receipts.</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">
                        Total Submissions: <strong class="text-slate-900">{{ payments.total || payments.data?.length || 0 }}</strong>
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Receipt #</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Plan & Interval</th>
                                <th class="py-3 px-4">Amount (BDT)</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Sender Number</th>
                                <th class="py-3 px-4">TrxID</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Admin Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="pmt in payments.data"
                                :key="pmt.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ pmt.payment_number }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                    {{ formatDate(pmt.created_at) }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800">{{ pmt.plan?.name }}</span>
                                    <span class="text-[10px] text-slate-400 block uppercase font-semibold">{{ pmt.billing_cycle }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <CurrencyDisplay :amount="pmt.amount_bdt" />
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border" :class="pmt.payment_method === 'bkash' ? 'bg-pink-50 text-pink-700 border-pink-200' : (pmt.payment_method === 'nagad' ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-slate-50 text-slate-700 border-slate-200')">
                                        {{ pmt.payment_method }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-700">{{ pmt.sender_number || '—' }}</td>
                                <td class="py-3 px-4 font-mono font-bold text-indigo-600">{{ pmt.trx_id || '—' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="paymentStatusBadge(pmt.status).class"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="paymentStatusBadge(pmt.status).dot"></span>
                                        <span>{{ paymentStatusBadge(pmt.status).label }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px] max-w-xs truncate">
                                    {{ pmt.rejection_reason || pmt.notes || 'No remarks recorded' }}
                                </td>
                            </tr>
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <p class="font-medium text-xs text-slate-600">No payment submissions recorded yet.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">When you send a bKash, Nagad, or Bank payment, submit your TrxID here.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payments.links && payments.links.length > 3" class="p-4 border-t border-slate-100">
                    <Pagination :links="payments.links" />
                </div>
            </div>
        </div>

        <!-- Bangladeshi Merchant Payment Checkout Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="lg">
            <div class="p-6 md:p-8 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Merchant Checkout
                            </span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mt-1">
                            Subscribe to {{ selectedPlan?.name }}
                        </h3>
                    </div>
                    <button
                        @click="isPayModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg text-lg leading-none cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                <!-- Order Summary Breakdown Pill -->
                <div class="p-4 rounded-2xl bg-slate-900 text-white flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Payable Amount</span>
                        <div class="text-2xl font-black font-mono text-white mt-0.5">
                            ৳{{ Math.round(getAmount()).toLocaleString() }}
                        </div>
                        <span class="text-[10px] text-slate-400">
                            Billing Interval: <strong class="text-indigo-400 uppercase">{{ payForm.billing_cycle }}</strong>
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-white/10 text-slate-200 border border-white/15">
                            {{ selectedPlan?.name }}
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
                    <!-- Billing Cycle Toggle in Modal -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Billing Interval</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="payForm.billing_cycle = 'monthly'"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                                :class="payForm.billing_cycle === 'monthly' ? 'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-600 font-bold text-slate-900' : 'border-slate-200 bg-white text-slate-600'"
                            >
                                <div class="text-xs">Monthly Billing</div>
                                <div class="text-[11px] font-mono text-slate-500 mt-0.5">৳{{ Math.round(selectedPlan?.price_bdt || 0).toLocaleString() }} / mo</div>
                            </button>
                            <button
                                type="button"
                                @click="payForm.billing_cycle = 'yearly'"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                                :class="payForm.billing_cycle === 'yearly' ? 'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-600 font-bold text-slate-900' : 'border-slate-200 bg-white text-slate-600'"
                            >
                                <div class="text-xs flex items-center justify-between">
                                    <span>Annual (17% Off)</span>
                                </div>
                                <div class="text-[11px] font-mono text-emerald-600 mt-0.5 font-bold">৳{{ Math.round(selectedPlan?.annual_price_bdt || 0).toLocaleString() }} / yr</div>
                            </button>
                        </div>
                    </div>

                    <!-- Payment Method Visual Selector -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Select Payment Channel *</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                type="button"
                                @click="payForm.payment_method = 'bkash'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                                :class="payForm.payment_method === 'bkash' ? 'border-pink-500 bg-pink-50/60 ring-1 ring-pink-500 text-pink-700 font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            >
                                <div class="text-xs font-extrabold">bKash</div>
                                <div class="text-[10px] text-slate-400">Merchant</div>
                            </button>

                            <button
                                type="button"
                                @click="payForm.payment_method = 'nagad'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                                :class="payForm.payment_method === 'nagad' ? 'border-orange-500 bg-orange-50/60 ring-1 ring-orange-500 text-orange-700 font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            >
                                <div class="text-xs font-extrabold">Nagad</div>
                                <div class="text-[10px] text-slate-400">Merchant</div>
                            </button>

                            <button
                                type="button"
                                @click="payForm.payment_method = 'rocket'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                                :class="payForm.payment_method === 'rocket' ? 'border-purple-500 bg-purple-50/60 ring-1 ring-purple-500 text-purple-700 font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            >
                                <div class="text-xs font-extrabold">Rocket</div>
                                <div class="text-[10px] text-slate-400">Biller</div>
                            </button>

                            <button
                                type="button"
                                @click="payForm.payment_method = 'bank_transfer'"
                                class="p-2.5 rounded-xl border text-center transition cursor-pointer"
                                :class="payForm.payment_method === 'bank_transfer' ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500 text-blue-700 font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            >
                                <div class="text-xs font-extrabold">City Bank</div>
                                <div class="text-[10px] text-slate-400">Direct A/C</div>
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Instructions for Selected Method -->
                    <div class="p-3.5 rounded-xl border text-xs space-y-1.5" :class="paymentMethodDetails[payForm.payment_method]?.bgColor + ' ' + paymentMethodDetails[payForm.payment_method]?.borderColor">
                        <div class="flex items-center justify-between">
                            <span class="font-extrabold text-[11px]" :class="paymentMethodDetails[payForm.payment_method]?.textColor">
                                Send Payment To:
                            </span>
                            <button
                                type="button"
                                @click="copyToClipboard(paymentMethodDetails[payForm.payment_method]?.number, 'modal_' + payForm.payment_method)"
                                class="text-[10px] font-bold underline cursor-pointer"
                                :class="paymentMethodDetails[payForm.payment_method]?.textColor"
                            >
                                {{ copiedKey === 'modal_' + payForm.payment_method ? 'Copied!' : 'Copy Number' }}
                            </button>
                        </div>
                        <div class="font-mono font-bold text-sm text-slate-900">
                            {{ paymentMethodDetails[payForm.payment_method]?.number }}
                        </div>
                        <p class="text-[11px] text-slate-600">
                            {{ paymentMethodDetails[payForm.payment_method]?.counter }} • {{ paymentMethodDetails[payForm.payment_method]?.type }}
                        </p>
                    </div>

                    <!-- Sender Number -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Your Sender Mobile / Account Number *</label>
                        <input
                            v-model="payForm.sender_number"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        />
                        <p v-if="payForm.errors.sender_number" class="text-rose-500 text-[11px] mt-1">{{ payForm.errors.sender_number }}</p>
                    </div>

                    <!-- TrxID -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Transaction ID (TrxID) *</label>
                        <input
                            v-model="payForm.trx_id"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono font-bold uppercase focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        />
                        <p v-if="payForm.errors.trx_id" class="text-rose-500 text-[11px] mt-1">{{ payForm.errors.trx_id }}</p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reference Notes (Optional)</label>
                        <textarea
                            v-model="payForm.notes"
                            rows="2"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm shadow-indigo-600/30 transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ payForm.processing ? 'Submitting...' : 'Submit Payment for Verification' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
