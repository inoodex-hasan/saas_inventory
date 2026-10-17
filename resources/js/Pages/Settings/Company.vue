<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('profile');

const popularCurrencies = [
    { code: 'BDT', symbol: '৳', name: 'Bangladeshi Taka', flag: '🇧🇩' },
    { code: 'USD', symbol: '$', name: 'US Dollar', flag: '🇺🇸' },
    { code: 'EUR', symbol: '€', name: 'Euro', flag: '🇪🇺' },
    { code: 'GBP', symbol: '£', name: 'British Pound', flag: '🇬🇧' },
    { code: 'SAR', symbol: '﷼', name: 'Saudi Riyal', flag: '🇸🇦' },
    { code: 'AED', symbol: 'د.إ', name: 'UAE Dirham', flag: '🇦🇪' },
    { code: 'INR', symbol: '₹', name: 'Indian Rupee', flag: '🇮🇳' },
    { code: 'CAD', symbol: '$', name: 'Canadian Dollar', flag: '🇨🇦' },
    { code: 'AUD', symbol: '$', name: 'Australian Dollar', flag: '🇦🇺' },
    { code: 'SGD', symbol: '$', name: 'Singapore Dollar', flag: '🇸🇬' },
    { code: 'MYR', symbol: 'RM', name: 'Malaysian Ringgit', flag: '🇲🇾' },
    { code: 'JPY', symbol: '¥', name: 'Japanese Yen', flag: '🇯🇵' },
];

function selectCurrency(curr) {
    form.currency_code = curr.code;
    form.currency_symbol = curr.symbol;
}

function onCurrencyDropdownChange(event) {
    const selected = popularCurrencies.find(c => c.code === event.target.value);
    if (selected) {
        selectCurrency(selected);
    }
}

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
                <div v-show="activeTab === 'localization'" class="space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2">Financial Localization & Prefixes</h2>
                        <p class="text-xs text-slate-500 mt-1">Select your organization's primary operating currency or customize symbols for quotes, invoices, and ledgers.</p>
                    </div>

                    <!-- Preset Popular Currencies Picker Card -->
                    <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/90 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Pre-Set Popular Currencies</span>
                                <span class="text-[11px] text-slate-500">Click any preset or pick from the dropdown to automatically apply its standard ISO code and symbol.</span>
                            </div>
                            <!-- Fast Dropdown Selector -->
                            <div class="sm:w-72">
                                <select 
                                    :value="form.currency_code" 
                                    @change="onCurrencyDropdownChange"
                                    class="w-full text-xs font-semibold px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-xs"
                                >
                                    <option value="" disabled>-- Select a popular currency --</option>
                                    <option 
                                        v-for="curr in popularCurrencies" 
                                        :key="curr.code" 
                                        :value="curr.code"
                                    >
                                        {{ curr.flag }} {{ curr.name }} — {{ curr.symbol }} ({{ curr.code }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Clickable Preset Chips -->
                        <div class="flex flex-wrap gap-2 pt-1">
                            <button
                                v-for="curr in popularCurrencies"
                                :key="curr.code"
                                type="button"
                                @click="selectCurrency(curr)"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border shadow-xs',
                                    form.currency_code === curr.code
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-600/25 ring-2 ring-indigo-600/30'
                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100 hover:border-slate-300'
                                ]"
                            >
                                <span class="text-sm leading-none">{{ curr.flag }}</span>
                                <span class="font-bold">{{ curr.symbol }}</span>
                                <span class="text-[11px] opacity-80">{{ curr.code }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Currency Symbol *</label>
                            <input
                                v-model="form.currency_symbol"
                                type="text"
                                required
                                placeholder="৳, $, €, £"
                                class="w-full text-base font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Displayed on screen totals, POS slips, and invoices.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Currency Code (ISO) *</label>
                            <input
                                v-model="form.currency_code"
                                type="text"
                                required
                                placeholder="BDT, USD, EUR"
                                class="w-full text-base font-bold px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 uppercase"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">3-letter standard ISO currency code (e.g., BDT, USD, SAR).</p>
                        </div>

                        <!-- Live Visual Format Preview -->
                        <div class="sm:col-span-2 p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-2 text-indigo-900 font-medium">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Live Document Format Preview:</span>
                            </div>
                            <div class="font-bold text-indigo-700 bg-white px-3 py-1.5 rounded-lg border border-indigo-200 shadow-xs flex items-center gap-1.5">
                                <span class="text-slate-500 font-normal">Invoice Grand Total:</span>
                                <span class="text-indigo-950 font-black text-sm">{{ form.currency_symbol || '$' }} 12,34.56</span>
                                <span class="text-[10px] text-indigo-500 font-mono">({{ form.currency_code || 'USD' }})</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Sales Invoice Prefix *</label>
                            <input
                                v-model="form.invoice_prefix"
                                type="text"
                                required
                                placeholder="INV-, SO-"
                                class="w-full text-sm font-mono px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Purchase Order Prefix *</label>
                            <input
                                v-model="form.po_prefix"
                                type="text"
                                required
                                placeholder="PO-, ORD-"
                                class="w-full text-sm font-mono px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500"
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
