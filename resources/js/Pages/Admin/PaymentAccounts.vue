<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { useTranslation } from '@/Utils/translations';

const { t } = useTranslation();

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    payment_bank_active: props.settings.payment_bank_active ?? true,
    payment_bank_name: props.settings.payment_bank_name || '',
    payment_bank_account_number: props.settings.payment_bank_account_number || '',
    payment_bank_account_name: props.settings.payment_bank_account_name || '',
    payment_bank_branch: props.settings.payment_bank_branch || '',
    payment_bank_routing: props.settings.payment_bank_routing || '',
    payment_bank_swift: props.settings.payment_bank_swift || '',
    payment_bank_type: props.settings.payment_bank_type || '',
    payment_bank_instructions: props.settings.payment_bank_instructions || '',

    payment_bkash_active: props.settings.payment_bkash_active ?? true,
    payment_bkash_name: props.settings.payment_bkash_name || '',
    payment_bkash_number: props.settings.payment_bkash_number || '',
    payment_bkash_counter: props.settings.payment_bkash_counter || '',
    payment_bkash_type: props.settings.payment_bkash_type || '',
    payment_bkash_instructions: props.settings.payment_bkash_instructions || '',

    payment_nagad_active: props.settings.payment_nagad_active ?? true,
    payment_nagad_name: props.settings.payment_nagad_name || '',
    payment_nagad_number: props.settings.payment_nagad_number || '',
    payment_nagad_counter: props.settings.payment_nagad_counter || '',
    payment_nagad_type: props.settings.payment_nagad_type || '',
    payment_nagad_instructions: props.settings.payment_nagad_instructions || '',

    payment_rocket_active: props.settings.payment_rocket_active ?? true,
    payment_rocket_name: props.settings.payment_rocket_name || '',
    payment_rocket_number: props.settings.payment_rocket_number || '',
    payment_rocket_counter: props.settings.payment_rocket_counter || '',
    payment_rocket_type: props.settings.payment_rocket_type || '',
    payment_rocket_instructions: props.settings.payment_rocket_instructions || '',
});

function submit() {
    form.post(route('admin.payment-accounts.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout title="Payment Accounts & Banking">
        <Head title="Subscription Payment Accounts & Banking" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="p-1.5 bg-amber-500/10 text-amber-600 rounded-xl inline-flex">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </span>
                        <span>{{ t('Payment Accounts & Banking') }}</span>
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 active:bg-amber-800 shadow-sm shadow-amber-600/30 transition disabled:opacity-50 cursor-pointer"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? t('Saving...') : t('Save Payment Accounts') }}</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Header Explanatory Card -->
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-indigo-500/5 to-transparent border border-amber-500/20 flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-xs">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/20 text-amber-700 shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tenant Subscription Payment Methods</h2>
                        <p class="text-xs text-slate-600 mt-0.5 max-w-2xl">
                            Configure the bank account and Mobile Financial Service (MFS) wallet numbers that tenants see during plan checkout and manual TrxID payment submission. Changes update live immediately.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active Gateway Channels: 4
                    </span>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Section 1: Bank Transfer Account -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                Corporate Bank Transfer Details
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Displayed to tenants selecting Direct Bank Transfer on plan checkout.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input 
                                    type="checkbox" 
                                    v-model="form.payment_bank_active" 
                                    class="sr-only peer"
                                />
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600 relative"></div>
                                <span class="text-xs font-bold" :class="form.payment_bank_active ? 'text-emerald-700' : 'text-slate-500'">
                                    {{ form.payment_bank_active ? 'Active' : 'Inactive' }}
                                </span>
                            </label>
                            <span 
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold"
                                :class="form.payment_bank_active ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="form.payment_bank_active ? 'bg-blue-500' : 'bg-amber-500'"></span>
                                {{ form.payment_bank_active ? 'Active' : 'Service temporary not available' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Bank Name -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Bank Name <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.payment_bank_name" 
                                type="text" 
                                placeholder="e.g. City Bank Ltd"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_name" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_name }}
                            </div>
                        </div>

                        <!-- Account Number -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Bank Account Number <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.payment_bank_account_number" 
                                type="text" 
                                placeholder="e.g. 1102938475001"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 font-mono focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_account_number" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_account_number }}
                            </div>
                        </div>

                        <!-- Account Name / Title -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Account Title / Beneficiary Name
                            </label>
                            <input 
                                v-model="form.payment_bank_account_name" 
                                type="text" 
                                placeholder="e.g. SaaS Inventory BD Ltd"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_account_name" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_account_name }}
                            </div>
                        </div>

                        <!-- Branch Name -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Branch Name
                            </label>
                            <input 
                                v-model="form.payment_bank_branch" 
                                type="text" 
                                placeholder="e.g. Gulshan Branch"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_branch" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_branch }}
                            </div>
                        </div>

                        <!-- Routing Number -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Routing Number
                            </label>
                            <input 
                                v-model="form.payment_bank_routing" 
                                type="text" 
                                placeholder="e.g. 225261145"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 font-mono focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_routing" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_routing }}
                            </div>
                        </div>

                        <!-- SWIFT Code -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                SWIFT / BIC Code
                            </label>
                            <input 
                                v-model="form.payment_bank_swift" 
                                type="text" 
                                placeholder="e.g. CIBLBDDH"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 font-mono uppercase focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_swift" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_swift }}
                            </div>
                        </div>

                        <!-- Account Type / Tag -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Account Type
                            </label>
                            <input 
                                v-model="form.payment_bank_type" 
                                type="text" 
                                placeholder="e.g. Corporate Account"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_type" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_type }}
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="md:col-span-2 lg:col-span-3 space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Bank Transfer Instructions
                            </label>
                            <input 
                                v-model="form.payment_bank_instructions" 
                                type="text" 
                                placeholder="e.g. Direct deposit, BEFTN, NPSB or RTGS to our corporate bank account"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.payment_bank_instructions" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.payment_bank_instructions }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Mobile Financial Services (bKash, Nagad, Rocket) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- bKash Configuration Card -->
                    <div class="bg-white rounded-2xl border border-pink-200/80 shadow-sm overflow-hidden flex flex-col">
                        <div class="p-5 border-b border-pink-100 bg-pink-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-pink-600"></span>
                                <h3 class="font-bold text-sm text-pink-900">bKash Account</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.payment_bkash_active" 
                                        class="sr-only peer"
                                    />
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-pink-600 relative"></div>
                                </label>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded" :class="form.payment_bkash_active ? 'bg-pink-100 text-pink-800' : 'bg-slate-200 text-slate-600'">
                                    {{ form.payment_bkash_active ? 'Active' : 'Offline' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 space-y-4 flex-1">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Display Name
                                </label>
                                <input 
                                    v-model="form.payment_bkash_name" 
                                    type="text" 
                                    placeholder="e.g. bKash Merchant"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-pink-500 focus:ring-pink-500 transition shadow-sm"
                                />
                                <div v-if="form.errors.payment_bkash_name" class="text-xs text-rose-600 font-medium">
                                    {{ form.errors.payment_bkash_name }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Wallet / Phone Number <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.payment_bkash_number" 
                                    type="text" 
                                    placeholder="e.g. 01712-345678"
                                    class="w-full text-xs sm:text-sm font-mono rounded-xl border-slate-300 focus:border-pink-500 focus:ring-pink-500 transition shadow-sm"
                                />
                                <div v-if="form.errors.payment_bkash_number" class="text-xs text-rose-600 font-medium">
                                    {{ form.errors.payment_bkash_number }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Counter / Ref
                                    </label>
                                    <input 
                                        v-model="form.payment_bkash_counter" 
                                        type="text" 
                                        placeholder="e.g. Counter 1"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-pink-500 focus:ring-pink-500 transition shadow-sm"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Payment Type
                                    </label>
                                    <input 
                                        v-model="form.payment_bkash_type" 
                                        type="text" 
                                        placeholder="e.g. Merchant Payment"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-pink-500 focus:ring-pink-500 transition shadow-sm"
                                    />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    User Instruction Note
                                </label>
                                <input 
                                    v-model="form.payment_bkash_instructions" 
                                    type="text" 
                                    placeholder="e.g. Select 'Payment' option in bKash App"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-pink-500 focus:ring-pink-500 transition shadow-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Nagad Configuration Card -->
                    <div class="bg-white rounded-2xl border border-orange-200/80 shadow-sm overflow-hidden flex flex-col">
                        <div class="p-5 border-b border-orange-100 bg-orange-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                                <h3 class="font-bold text-sm text-orange-900">Nagad Account</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.payment_nagad_active" 
                                        class="sr-only peer"
                                    />
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-orange-500 relative"></div>
                                </label>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded" :class="form.payment_nagad_active ? 'bg-orange-100 text-orange-800' : 'bg-slate-200 text-slate-600'">
                                    {{ form.payment_nagad_active ? 'Active' : 'Offline' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 space-y-4 flex-1">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Display Name
                                </label>
                                <input 
                                    v-model="form.payment_nagad_name" 
                                    type="text" 
                                    placeholder="e.g. Nagad Merchant"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-orange-500 focus:ring-orange-500 transition shadow-sm"
                                />
                                <div v-if="form.errors.payment_nagad_name" class="text-xs text-rose-600 font-medium">
                                    {{ form.errors.payment_nagad_name }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Wallet / Phone Number <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.payment_nagad_number" 
                                    type="text" 
                                    placeholder="e.g. 01812-345678"
                                    class="w-full text-xs sm:text-sm font-mono rounded-xl border-slate-300 focus:border-orange-500 focus:ring-orange-500 transition shadow-sm"
                                />
                                <div v-if="form.errors.payment_nagad_number" class="text-xs text-rose-600 font-medium">
                                    {{ form.errors.payment_nagad_number }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Counter / Ref
                                    </label>
                                    <input 
                                        v-model="form.payment_nagad_counter" 
                                        type="text" 
                                        placeholder="e.g. Counter 1"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-orange-500 focus:ring-orange-500 transition shadow-sm"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Payment Type
                                    </label>
                                    <input 
                                        v-model="form.payment_nagad_type" 
                                        type="text" 
                                        placeholder="e.g. Merchant Payment"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-orange-500 focus:ring-orange-500 transition shadow-sm"
                                    />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    User Instruction Note
                                </label>
                                <input 
                                    v-model="form.payment_nagad_instructions" 
                                    type="text" 
                                    placeholder="e.g. Select 'Merchant Pay' in Nagad App"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-orange-500 focus:ring-orange-500 transition shadow-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Rocket Configuration Card -->
                    <div class="bg-white rounded-2xl border border-purple-200/80 shadow-sm overflow-hidden flex flex-col">
                        <div class="p-5 border-b border-purple-100 bg-purple-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-purple-600"></span>
                                <h3 class="font-bold text-sm text-purple-900">Rocket Account</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.payment_rocket_active" 
                                        class="sr-only peer"
                                    />
                                    <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-purple-600 relative"></div>
                                </label>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded" :class="form.payment_rocket_active ? 'bg-purple-100 text-purple-800' : 'bg-slate-200 text-slate-600'">
                                    {{ form.payment_rocket_active ? 'Active' : 'Offline' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 space-y-4 flex-1">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Display Name
                                </label>
                                <input 
                                    v-model="form.payment_rocket_name" 
                                    type="text" 
                                    placeholder="e.g. Rocket Biller"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-purple-500 focus:ring-purple-500 transition shadow-sm"
                                />
                                <div v-if="form.errors.payment_rocket_name" class="text-xs text-rose-600 font-medium">
                                    {{ form.errors.payment_rocket_name }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Wallet / Phone Number <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.payment_rocket_number" 
                                    type="text" 
                                    placeholder="e.g. 01912-345678-9"
                                    class="w-full text-xs sm:text-sm font-mono rounded-xl border-slate-300 focus:border-purple-500 focus:ring-purple-500 transition shadow-sm"
                                />
                                <div v-if="form.errors.payment_rocket_number" class="text-xs text-rose-600 font-medium">
                                    {{ form.errors.payment_rocket_number }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Biller ID / Ref
                                    </label>
                                    <input 
                                        v-model="form.payment_rocket_counter" 
                                        type="text" 
                                        placeholder="e.g. Biller ID 4567"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-purple-500 focus:ring-purple-500 transition shadow-sm"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Payment Type
                                    </label>
                                    <input 
                                        v-model="form.payment_rocket_type" 
                                        type="text" 
                                        placeholder="e.g. Merchant / Biller"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-purple-500 focus:ring-purple-500 transition shadow-sm"
                                    />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    User Instruction Note
                                </label>
                                <input 
                                    v-model="form.payment_rocket_instructions" 
                                    type="text" 
                                    placeholder="e.g. Pay via Biller ID or Merchant Pay"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-purple-500 focus:ring-purple-500 transition shadow-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Live Preview of Tenant View -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Live Preview (How Tenants See This on Checkout)
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Real-time preview matching the exact tenant subscription checkout screen.</p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Live Preview
                        </span>
                    </div>

                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50/30">
                        <!-- Preview bKash -->
                        <div class="p-4 rounded-2xl border transition space-y-2" :class="form.payment_bkash_active ? 'border-pink-100 bg-pink-50/50' : 'border-slate-200 bg-slate-50/80 opacity-90'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" :class="form.payment_bkash_active ? 'text-pink-700' : 'text-slate-500'">{{ form.payment_bkash_name || 'bKash Merchant' }}</span>
                                <span v-if="form.payment_bkash_active" class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-pink-100 text-pink-800">{{ form.payment_bkash_counter || 'Counter 1' }}</span>
                                <span v-else class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">Paused</span>
                            </div>
                            <template v-if="form.payment_bkash_active">
                                <div class="font-mono font-bold text-sm text-slate-900">{{ form.payment_bkash_number || '01712-345678' }}</div>
                                <p class="text-[10px] text-slate-500">{{ form.payment_bkash_instructions || 'Select "Payment" option in bKash App' }}</p>
                                <div class="w-full mt-1 py-1 px-3 rounded-lg text-[10px] font-bold bg-white text-pink-700 border border-pink-200 text-center">
                                    Copy Number
                                </div>
                            </template>
                            <template v-else>
                                <div class="py-2.5 px-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center space-y-0.5">
                                    <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-800">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Service temporary not available</span>
                                    </div>
                                    <p class="text-[10px] text-amber-600">Please choose another payment channel</p>
                                </div>
                            </template>
                        </div>

                        <!-- Preview Nagad -->
                        <div class="p-4 rounded-2xl border transition space-y-2" :class="form.payment_nagad_active ? 'border-orange-100 bg-orange-50/50' : 'border-slate-200 bg-slate-50/80 opacity-90'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" :class="form.payment_nagad_active ? 'text-orange-700' : 'text-slate-500'">{{ form.payment_nagad_name || 'Nagad Merchant' }}</span>
                                <span v-if="form.payment_nagad_active" class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-orange-100 text-orange-800">{{ form.payment_nagad_counter || 'Counter 1' }}</span>
                                <span v-else class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">Paused</span>
                            </div>
                            <template v-if="form.payment_nagad_active">
                                <div class="font-mono font-bold text-sm text-slate-900">{{ form.payment_nagad_number || '01812-345678' }}</div>
                                <p class="text-[10px] text-slate-500">{{ form.payment_nagad_instructions || 'Select "Merchant Pay" in Nagad App' }}</p>
                                <div class="w-full mt-1 py-1 px-3 rounded-lg text-[10px] font-bold bg-white text-orange-700 border border-orange-200 text-center">
                                    Copy Number
                                </div>
                            </template>
                            <template v-else>
                                <div class="py-2.5 px-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center space-y-0.5">
                                    <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-800">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Service temporary not available</span>
                                    </div>
                                    <p class="text-[10px] text-amber-600">Please choose another payment channel</p>
                                </div>
                            </template>
                        </div>

                        <!-- Preview Rocket -->
                        <div class="p-4 rounded-2xl border transition space-y-2" :class="form.payment_rocket_active ? 'border-purple-100 bg-purple-50/50' : 'border-slate-200 bg-slate-50/80 opacity-90'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" :class="form.payment_rocket_active ? 'text-purple-700' : 'text-slate-500'">{{ form.payment_rocket_name || 'Rocket Biller' }}</span>
                                <span v-if="form.payment_rocket_active" class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-800">{{ form.payment_rocket_counter || 'Biller ID 4567' }}</span>
                                <span v-else class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">Paused</span>
                            </div>
                            <template v-if="form.payment_rocket_active">
                                <div class="font-mono font-bold text-sm text-slate-900">{{ form.payment_rocket_number || '01912-345678-9' }}</div>
                                <p class="text-[10px] text-slate-500">{{ form.payment_rocket_instructions || 'Pay via Biller ID or Merchant Pay' }}</p>
                                <div class="w-full mt-1 py-1 px-3 rounded-lg text-[10px] font-bold bg-white text-purple-700 border border-purple-200 text-center">
                                    Copy Number
                                </div>
                            </template>
                            <template v-else>
                                <div class="py-2.5 px-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center space-y-0.5">
                                    <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-800">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Service temporary not available</span>
                                    </div>
                                    <p class="text-[10px] text-amber-600">Please choose another payment channel</p>
                                </div>
                            </template>
                        </div>

                        <!-- Preview Bank -->
                        <div class="p-4 rounded-2xl border transition space-y-2" :class="form.payment_bank_active ? 'border-blue-100 bg-blue-50/50' : 'border-slate-200 bg-slate-50/80 opacity-90'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold truncate mr-1" :class="form.payment_bank_active ? 'text-blue-700' : 'text-slate-500'">{{ form.payment_bank_name || 'City Bank Ltd' }}</span>
                                <span v-if="form.payment_bank_active" class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800 shrink-0">{{ form.payment_bank_branch || 'Gulshan' }}</span>
                                <span v-else class="text-[9px] uppercase font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 shrink-0">Paused</span>
                            </div>
                            <template v-if="form.payment_bank_active">
                                <div class="font-mono font-bold text-xs text-slate-900 truncate">{{ form.payment_bank_account_number || '1102938475001' }}</div>
                                <p class="text-[10px] text-slate-500 truncate">{{ form.payment_bank_account_name || 'SaaS Inventory BD Ltd' }}</p>
                                <p v-if="form.payment_bank_routing || form.payment_bank_swift" class="text-[9px] text-slate-400 font-mono truncate">
                                    <span v-if="form.payment_bank_routing">Routing: {{ form.payment_bank_routing }}</span>
                                    <span v-if="form.payment_bank_routing && form.payment_bank_swift"> • </span>
                                    <span v-if="form.payment_bank_swift">SWIFT: {{ form.payment_bank_swift }}</span>
                                </p>
                                <div class="w-full mt-1 py-1 px-3 rounded-lg text-[10px] font-bold bg-white text-blue-700 border border-blue-200 text-center">
                                    Copy A/C No
                                </div>
                            </template>
                            <template v-else>
                                <div class="py-2.5 px-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center space-y-0.5">
                                    <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-800">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Service temporary not available</span>
                                    </div>
                                    <p class="text-[10px] text-amber-600">Please choose another payment channel</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Bottom Sticky / Save Bar -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium text-center sm:text-left">
                        Updated payment accounts immediately apply to tenant checkout screens and manual verification submissions.
                    </span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 active:bg-amber-800 disabled:opacity-50 transition shadow-xs text-center cursor-pointer shrink-0"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? t('Saving...') : t('Save Payment Accounts') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
