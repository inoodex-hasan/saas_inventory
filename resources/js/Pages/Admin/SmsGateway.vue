<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTranslation } from '@/Utils/translations';

const { t } = useTranslation();

const props = defineProps({
    balance: Object,
    gatewayConfig: Object,
    recentUsers: Array,
});

const isRefreshing = ref(false);

function refreshBalance() {
    isRefreshing.value = true;
    router.post(route('admin.sms-gateway.refresh'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

const testForm = useForm({
    phone: '',
    message: 'Test message from InoManage Cloud Inventory SMS Gateway.',
});

function submitTestSms() {
    testForm.post(route('admin.sms-gateway.test'), {
        preserveScroll: true,
        onSuccess: () => {
            testForm.phone = '';
        },
    });
}
</script>

<template>
    <AppLayout title="SMS Gateway & Balance">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span>{{ t('SMS Gateway & Balance') }}</span>
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="refreshBalance"
                        :disabled="isRefreshing"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/80 shadow-xs transition disabled:opacity-60 cursor-pointer"
                    >
                        <svg
                            class="w-4 h-4 text-slate-500"
                            :class="{ 'animate-spin': isRefreshing }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ isRefreshing ? 'Checking...' : 'Refresh Balance' }}</span>
                    </button>

                    <a
                        href="https://bulksmsbd.net/login"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-sm shadow-amber-600/30 transition cursor-pointer"
                    >
                        <span>{{ t('Recharge BulkSMS') }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Low Balance Warning Banner -->
            <div
                v-if="balance.is_low"
                class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-start gap-3 shadow-xs"
            >
                <div class="p-2 rounded-xl bg-amber-500/20 text-amber-600 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1 text-xs text-amber-900">
                    <h2 class="font-bold text-sm text-amber-950 mb-0.5">Critical Notice: SMS Gateway Balance is Low!</h2>
                    <p class="leading-relaxed">
                        Your current balance is <strong class="font-mono text-amber-950 font-bold">{{ balance.formatted_balance }}</strong>. 
                        If the balance exhausts, user phone verification OTPs and password reset OTPs will fail immediately. 
                        Please recharge your account via the BulkSMSBD portal.
                    </p>
                </div>
                <a
                    href="https://bulksmsbd.net/login"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition"
                >
                    Recharge Now
                </a>
            </div>

            <!-- Top Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Card 1: Balance -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">BulkSMSBD Balance</span>
                            <span
                                v-if="balance.status === 'healthy'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Healthy
                            </span>
                            <span
                                v-else-if="balance.status === 'warning'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase"
                            >
                                Low Balance
                            </span>
                            <span
                                v-else-if="balance.status === 'critical'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase"
                            >
                                Exhausted
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase"
                            >
                                Dev Driver
                            </span>
                        </div>

                        <div class="text-3xl font-black text-slate-900 font-mono tracking-tight mt-1">
                            {{ balance.formatted_balance }}
                        </div>
                        <p class="text-xs text-slate-500 mt-2">{{ balance.message }}</p>
                    </div>

                    <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-[11px] text-slate-400">
                        <span>Last synchronized:</span>
                        <span class="font-mono text-slate-600 font-semibold">{{ balance.last_checked_at }}</span>
                    </div>
                </div>

                <!-- Card 2: Gateway Configuration Details -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gateway Configuration</span>
                        <div class="space-y-2 mt-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Provider:</span>
                                <span class="font-bold text-slate-800">BulkSMSBD HTTP API</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Driver:</span>
                                <span class="font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">{{ gatewayConfig.driver }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Sender ID:</span>
                                <span class="font-mono font-bold text-indigo-600">{{ gatewayConfig.sender_id }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">API Key:</span>
                                <span class="font-mono text-slate-600 text-[11px]">{{ gatewayConfig.masked_api_key || 'Not Set' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 mt-3 text-[11px] text-slate-400">
                        <span>Managed via <code class="font-mono text-indigo-600 bg-indigo-50 px-1 py-0.5 rounded">.env</code></span>
                    </div>
                </div>

                <!-- Card 3: Send Test SMS -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ t('Send Test SMS') }}</span>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Send a live test message to verify delivery.</p>

                        <form @submit.prevent="submitTestSms" class="space-y-2.5">
                            <div>
                                <input
                                    v-model="testForm.phone"
                                    type="text"
                                    placeholder="e.g. 018XXXXXXXX"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-amber-500 font-mono"
                                    required
                                />
                            </div>
                            <div>
                                <input
                                    v-model="testForm.message"
                                    type="text"
                                    maxlength="160"
                                    placeholder="Test message..."
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-amber-500"
                                    required
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="testForm.processing"
                                class="w-full py-2 px-3 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-xs transition disabled:opacity-60 cursor-pointer text-center"
                            >
                                {{ testForm.processing ? 'Dispatching...' : 'Dispatch Test SMS' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Phone & OTP Activity Table -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Recent User Phone OTP Activity</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Latest user phone verification statuses across all tenant accounts</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3 px-5">User</th>
                                <th class="py-3 px-5">Phone Number</th>
                                <th class="py-3 px-5">Verification Status</th>
                                <th class="py-3 px-5">Verified At</th>
                                <th class="py-3 px-5">Last OTP Sent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in recentUsers" :key="user.id" class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-800">
                                    {{ user.name }}
                                </td>
                                <td class="py-3.5 px-5 font-mono text-slate-600">
                                    {{ user.masked_phone || user.phone }}
                                </td>
                                <td class="py-3.5 px-5">
                                    <span
                                        v-if="user.is_verified"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase"
                                    >
                                        {{ t('Verified') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase"
                                    >
                                        {{ t('Unverified') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-500 font-mono text-[11px]">
                                    {{ user.verified_at || 'Not yet' }}
                                </td>
                                <td class="py-3.5 px-5 text-slate-500 font-mono text-[11px]">
                                    {{ user.last_otp_at }}
                                </td>
                            </tr>
                            <tr v-if="!recentUsers || recentUsers.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400 italic">
                                    No phone verification records found yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
