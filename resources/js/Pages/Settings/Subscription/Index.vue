<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Pagination from '@/Components/Pagination.vue';

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

const payForm = useForm({
    plan_id: '',
    billing_cycle: 'monthly',
    payment_method: 'bkash',
    sender_number: '',
    trx_id: '',
    notes: '',
});

function openPayModal(plan) {
    selectedPlan.value = plan;
    payForm.plan_id = plan.id;
    payForm.billing_cycle = billingCycle.value;
    payForm.sender_number = '';
    payForm.trx_id = '';
    payForm.notes = '';
    payForm.clearErrors();
    isPayModalOpen.value = true;
}

function submitPayment() {
    payForm.post(route('subscription.pay'), {
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

function statusBadgeClass(status) {
    switch (status) {
        case 'active': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
        case 'trialing': return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20';
        case 'past_due': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
        case 'expired': return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20';
        default: return 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20';
    }
}

function paymentStatusClass(status) {
    switch (status) {
        case 'approved': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'rejected': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
}
</script>

<template>
    <AppLayout title="Subscription & Billing">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Subscription & Quota Management</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Manage your workspace tier, Bangladeshi payment verifications, and resource quotas.</p>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Top Status Banner -->
            <div
                class="rounded-3xl p-6 md:p-8 text-white relative overflow-hidden shadow-lg"
                :class="usage.subscription.status === 'active' ? 'bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-900' : 'bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950'"
            >
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/10 border border-white/20 backdrop-blur-md">
                                Current Plan: {{ usage.plan.name }}
                            </span>
                            <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider', statusBadgeClass(usage.subscription.status)]">
                                {{ usage.subscription.status }}
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
                            {{ usage.plan.name }} Tier
                        </h1>
                        <p class="text-xs text-slate-300 max-w-xl">
                            {{ usage.plan.description }}
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-md">
                        <div class="text-left sm:text-right">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Subscription Validity</p>
                            <p class="text-lg font-extrabold text-white">
                                {{ usage.days_remaining }} Days Remaining
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Expires: {{ usage.subscription.ends_at ? new Date(usage.subscription.ends_at).toLocaleDateString() : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Quota Meters -->
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Resource Quotas & Utilization</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Products Quota -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">Products Catalog</span>
                            <span class="font-mono font-bold text-slate-900 text-sm">
                                {{ usage.products.current }} / {{ usage.products.is_unlimited ? '∞' : usage.products.limit }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="usage.products.percent > 90 ? 'bg-rose-500' : (usage.products.percent > 75 ? 'bg-amber-500' : 'bg-indigo-600')"
                                :style="{ width: usage.products.is_unlimited ? '20%' : `${usage.products.percent}%` }"
                            ></div>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ usage.products.is_unlimited ? 'Unlimited catalog size' : `${usage.products.percent}% utilized of plan limit` }}
                        </p>
                    </div>

                    <!-- Warehouses Quota -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">Warehouses / Branches</span>
                            <span class="font-mono font-bold text-slate-900 text-sm">
                                {{ usage.warehouses.current }} / {{ usage.warehouses.is_unlimited ? '∞' : usage.warehouses.limit }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="usage.warehouses.percent > 90 ? 'bg-rose-500' : 'bg-indigo-600'"
                                :style="{ width: usage.warehouses.is_unlimited ? '20%' : `${usage.warehouses.percent}%` }"
                            ></div>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ usage.warehouses.is_unlimited ? 'Unlimited hubs enabled' : `${usage.warehouses.percent}% of active hubs limit` }}
                        </p>
                    </div>

                    <!-- Users Quota -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-slate-500">Staff & Team Seats</span>
                            <span class="font-mono font-bold text-slate-900 text-sm">
                                {{ usage.users.current }} / {{ usage.users.is_unlimited ? '∞' : usage.users.limit }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="usage.users.percent > 90 ? 'bg-rose-500' : 'bg-indigo-600'"
                                :style="{ width: usage.users.is_unlimited ? '20%' : `${usage.users.percent}%` }"
                            ></div>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ usage.users.is_unlimited ? 'Unlimited employee logins' : `${usage.users.percent}% seat allocation` }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Available Plans & Upgrade Checkout -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Available Subscription Plans</h3>
                        <p class="text-xs text-slate-500">Choose a plan tailored to your business scale. Pay securely in Bangladeshi Taka (৳).</p>
                    </div>

                    <!-- Monthly / Yearly Billing Cycle Toggle -->
                    <div class="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200">
                        <button
                            @click="billingCycle = 'monthly'"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all"
                            :class="billingCycle === 'monthly' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Monthly Billing
                        </button>
                        <button
                            @click="billingCycle = 'yearly'"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5"
                            :class="billingCycle === 'yearly' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            <span>Yearly (Save 17%)</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-emerald-400 text-emerald-950 font-bold">2 Mos Free</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="bg-white rounded-3xl border transition-all duration-300 p-6 flex flex-col justify-between relative shadow-xs hover:shadow-md"
                        :class="usage.subscription.plan_id === plan.id ? 'border-2 border-indigo-600 ring-4 ring-indigo-50' : 'border-slate-200/80'"
                    >
                        <div v-if="usage.subscription.plan_id === plan.id" class="absolute -top-3 left-6 px-3 py-0.5 rounded-full bg-indigo-600 text-white text-[10px] font-bold uppercase tracking-wider">
                            Active Workspace Plan
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900">{{ plan.name }}</h4>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ plan.description }}</p>
                            </div>

                            <div class="pt-2">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-black text-slate-900 font-mono">
                                        ৳{{ billingCycle === 'yearly' ? Math.round(plan.annual_price_bdt / 12).toLocaleString() : Math.round(plan.price_bdt).toLocaleString() }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500">/ month</span>
                                </div>
                                <p v-if="billingCycle === 'yearly'" class="text-[11px] text-emerald-600 font-semibold mt-0.5">
                                    Billed ৳{{ Math.round(plan.annual_price_bdt).toLocaleString() }} annually
                                </p>
                            </div>

                            <!-- Limits Breakdown -->
                            <div class="space-y-2 py-3 border-t border-slate-100 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span><strong>{{ plan.max_products === -1 ? 'Unlimited' : plan.max_products }}</strong> Products Catalog</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span><strong>{{ plan.max_warehouses === -1 ? 'Unlimited' : plan.max_warehouses }}</strong> Warehouse Hubs</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span><strong>{{ plan.max_users === -1 ? 'Unlimited' : plan.max_users }}</strong> Staff User Accounts</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100">
                            <button
                                @click="openPayModal(plan)"
                                class="w-full py-2.5 px-4 text-xs font-bold rounded-xl transition-all shadow-sm"
                                :class="usage.subscription.plan_id === plan.id
                                    ? 'bg-slate-900 hover:bg-slate-800 text-white'
                                    : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/30'"
                            >
                                {{ usage.subscription.plan_id === plan.id ? 'Renew / Extend Plan' : 'Select Plan & Pay' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Submission History -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Payment & Verification History</h3>
                    <p class="text-xs text-slate-500">Record of submitted bKash, Nagad, Rocket, and Bank transfer receipts.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Receipt #</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Plan & Cycle</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Sender Number</th>
                                <th class="py-3 px-4">TrxID</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Reviewer Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr
                                v-for="pmt in payments.data"
                                :key="pmt.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4 font-bold text-slate-900">{{ pmt.payment_number }}</td>
                                <td class="py-3 px-4 font-sans text-slate-600">{{ new Date(pmt.created_at).toLocaleDateString() }}</td>
                                <td class="py-3 px-4 font-sans font-semibold text-slate-800">
                                    {{ pmt.plan?.name }} ({{ pmt.billing_cycle }})
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <CurrencyDisplay :amount="pmt.amount_bdt" />
                                </td>
                                <td class="py-3 px-4 uppercase text-slate-600 font-bold font-sans">{{ pmt.payment_method }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-700">{{ pmt.sender_number || '—' }}</td>
                                <td class="py-3 px-4 font-bold text-indigo-600">{{ pmt.trx_id || '—' }}</td>
                                <td class="py-3 px-4 text-center font-sans">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase', paymentStatusClass(pmt.status)]">
                                        {{ pmt.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-500 text-[11px] max-w-xs truncate">
                                    {{ pmt.rejection_reason || pmt.notes || '—' }}
                                </td>
                            </tr>
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="9" class="py-10 text-center text-slate-400 font-sans">
                                    No payment submissions recorded yet.
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

        <!-- Payment Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="lg">
            <div class="p-6 md:p-8 space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700">
                        Bangladeshi Merchant Payment
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 mt-2">
                        Subscribe to {{ selectedPlan?.name }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Amount Payable: <strong class="text-slate-900 font-mono text-sm">৳{{ Math.round(getAmount()).toLocaleString() }}</strong> ({{ payForm.billing_cycle }})
                    </p>
                </div>

                <!-- Payment Instructions Box -->
                <div class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-100 space-y-2 text-xs">
                    <p class="font-bold text-indigo-950 uppercase tracking-wider text-[10px]">Payment Instructions</p>
                    <div class="space-y-1 text-indigo-900">
                        <p><strong>bKash:</strong> {{ payment_instructions.bkash }}</p>
                        <p><strong>Nagad:</strong> {{ payment_instructions.nagad }}</p>
                        <p><strong>Rocket:</strong> {{ payment_instructions.rocket }}</p>
                        <p><strong>Bank:</strong> {{ payment_instructions.bank }}</p>
                    </div>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Billing Cycle</label>
                            <select
                                v-model="payForm.billing_cycle"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold"
                            >
                                <option value="monthly">Monthly (৳{{ Math.round(selectedPlan?.price_bdt || 0).toLocaleString() }})</option>
                                <option value="yearly">Yearly (৳{{ Math.round(selectedPlan?.annual_price_bdt || 0).toLocaleString() }})</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payment Method *</label>
                            <select
                                v-model="payForm.payment_method"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold uppercase"
                                required
                            >
                                <option value="bkash">bKash Merchant</option>
                                <option value="nagad">Nagad Merchant</option>
                                <option value="rocket">Rocket</option>
                                <option value="bank_transfer">Bank Transfer (City Bank)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Your Sender Mobile / Account Number *</label>
                        <input
                            v-model="payForm.sender_number"
                            type="text"
                            placeholder="e.g. 017XXXXXXXX"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-indigo-500"
                            required
                        />
                        <p v-if="payForm.errors.sender_number" class="text-rose-500 text-[11px] mt-1">{{ payForm.errors.sender_number }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Transaction ID (TrxID) *</label>
                        <input
                            v-model="payForm.trx_id"
                            type="text"
                            placeholder="e.g. BKA87X910Z"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold focus:bg-white focus:border-indigo-500 uppercase"
                            required
                        />
                        <p v-if="payForm.errors.trx_id" class="text-rose-500 text-[11px] mt-1">{{ payForm.errors.trx_id }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Additional Notes (Optional)</label>
                        <textarea
                            v-model="payForm.notes"
                            rows="2"
                            placeholder="Optional reference details"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 font-medium text-slate-600 hover:text-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-sm shadow-indigo-600/30"
                        >
                            {{ payForm.processing ? 'Submitting...' : 'Submit Payment for Verification' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
