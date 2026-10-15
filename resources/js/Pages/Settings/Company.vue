<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('profile');

const form = useForm({
    name: props.settings.name || '',
    legal_name: props.settings.legal_name || '',
    email: props.settings.email || '',
    phone: props.settings.phone || '',
    tax_id: props.settings.tax_id || '',
    address_line1: props.settings.address_line1 || '',
    address_line2: props.settings.address_line2 || '',
    city: props.settings.city || '',
    state: props.settings.state || '',
    postal_code: props.settings.postal_code || '',
    country: props.settings.country || '',
    currency_symbol: props.settings.currency_symbol || '৳',
    currency_code: props.settings.currency_code || 'BDT',
    invoice_prefix: props.settings.invoice_prefix || 'INV-',
    po_prefix: props.settings.po_prefix || 'PO-',
});

function submit() {
    form.put(route('settings.company.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Company Settings & Branding" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Company Settings & Branding</h1>
                    <p class="text-xs text-slate-500 font-medium">Configure tenant workspace identity, tax compliance, localization, and document sequence prefixes</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-slate-200">
                <button
                    type="button"
                    @click="activeTab = 'profile'"
                    :class="[
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px',
                        activeTab === 'profile'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    Organization Identity
                </button>
                <button
                    type="button"
                    @click="activeTab = 'address'"
                    :class="[
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px',
                        activeTab === 'address'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    Physical Address
                </button>
                <button
                    type="button"
                    @click="activeTab = 'localization'"
                    :class="[
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px',
                        activeTab === 'localization'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    Currency & Prefixes
                </button>
            </div>

            <form @submit.prevent="submit" class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">
                <!-- Tab 1: Organization Identity -->
                <div v-show="activeTab === 'profile'" class="space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2">Business Profile</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Company / Workspace Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <div v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Legal Registered Entity Name</label>
                            <input
                                v-model="form.legal_name"
                                type="text"
                                placeholder="e.g. Apex Logistics Corporation Ltd."
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Official Contact Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="billing@yourcompany.com"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Customer Service Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="+880 1711 000000"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Tax ID / TIN / BIN / VAT Number</label>
                            <input
                                v-model="form.tax_id"
                                type="text"
                                placeholder="e.g. BIN-0098234-2026, TIN-982173"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Physical Address -->
                <div v-show="activeTab === 'address'" class="space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2">Headquarters Address</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Street Address Line 1</label>
                            <input
                                v-model="form.address_line1"
                                type="text"
                                placeholder="e.g. 770 Innovation Way, Level 4"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Street Address Line 2</label>
                            <input
                                v-model="form.address_line2"
                                type="text"
                                placeholder="Suite, unit, building..."
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">City</label>
                            <input
                                v-model="form.city"
                                type="text"
                                placeholder="Dhaka"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">State / Province / Division</label>
                            <input
                                v-model="form.state"
                                type="text"
                                placeholder="Dhaka Division"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Postal Code</label>
                            <input
                                v-model="form.postal_code"
                                type="text"
                                placeholder="1212"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Country</label>
                            <input
                                v-model="form.country"
                                type="text"
                                placeholder="Bangladesh"
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Currency & Prefixes -->
                <div v-show="activeTab === 'localization'" class="space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2">Financial Localization & Prefixes</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Currency Symbol *</label>
                            <input
                                v-model="form.currency_symbol"
                                type="text"
                                required
                                placeholder="৳, $, €, £"
                                class="w-full text-base font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Currency Code (ISO) *</label>
                            <input
                                v-model="form.currency_code"
                                type="text"
                                required
                                placeholder="BDT, USD, EUR"
                                class="w-full text-base font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 uppercase"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Sales Invoice Prefix *</label>
                            <input
                                v-model="form.invoice_prefix"
                                type="text"
                                required
                                placeholder="INV-, SO-"
                                class="w-full text-sm font-mono px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Purchase Order Prefix *</label>
                            <input
                                v-model="form.po_prefix"
                                type="text"
                                required
                                placeholder="PO-, ORD-"
                                class="w-full text-sm font-mono px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-md shadow-emerald-500/20 transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50"
                    >
                        Save Company Profile
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
