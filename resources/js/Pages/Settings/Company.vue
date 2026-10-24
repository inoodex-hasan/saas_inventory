<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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

const invoiceBgInput = ref(null);
const reportBgInput = ref(null);
const invoiceBgPreview = ref(null);
const reportBgPreview = ref(null);

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
    invoice_bg: null,
    report_bg: null,
    invoice_margin_top: props.settings.invoice_margin_top ?? 48,
    invoice_margin_bottom: props.settings.invoice_margin_bottom ?? 25,
    report_margin_top: props.settings.report_margin_top ?? 42,
    report_margin_bottom: props.settings.report_margin_bottom ?? 25,
    _method: 'PUT',
});

function handleInvoiceBgChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.invoice_bg = file;
        invoiceBgPreview.value = URL.createObjectURL(file);
    }
}

function handleReportBgChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.report_bg = file;
        reportBgPreview.value = URL.createObjectURL(file);
    }
}

function submit() {
    form.post(route('settings.company.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            invoiceBgPreview.value = null;
            reportBgPreview.value = null;
            if (invoiceBgInput.value) invoiceBgInput.value.value = '';
            if (reportBgInput.value) reportBgInput.value.value = '';
        },
    });
}

function removeInvoiceBg() {
    if (confirm('Are you sure you want to remove the custom invoice letterhead? Invoices will revert to standard digital header.')) {
        router.post(route('settings.company.remove-invoice-bg'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                invoiceBgPreview.value = null;
                if (invoiceBgInput.value) invoiceBgInput.value.value = '';
            }
        });
    }
}

function removeReportBg() {
    if (confirm('Are you sure you want to remove the custom report letterhead? Reports will revert to standard digital header.')) {
        router.post(route('settings.company.remove-report-bg'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                reportBgPreview.value = null;
                if (reportBgInput.value) reportBgInput.value.value = '';
            }
        });
    }
}

function previewInvoiceBg() {
    window.open(route('settings.company.preview-invoice-bg'), '_blank');
}

function previewReportBg() {
    window.open(route('settings.company.preview-report-bg'), '_blank');
}
</script>

<template>
    <AppLayout>
        <Head title="Company Settings & Branding" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Company Settings & Branding</h1>
                    <p class="text-xs text-slate-500 font-medium">Configure tenant workspace identity, tax compliance, pre-printed letterheads, and print margins</p>
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
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px flex items-center gap-2',
                        activeTab === 'profile'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Organization Identity
                </button>
                <button
                    type="button"
                    @click="activeTab = 'address'"
                    :class="[
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px flex items-center gap-2',
                        activeTab === 'address'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Physical Address
                </button>
                <button
                    type="button"
                    @click="activeTab = 'localization'"
                    :class="[
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px flex items-center gap-2',
                        activeTab === 'localization'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Currency & Prefixes
                </button>
                <button
                    type="button"
                    @click="activeTab = 'letterhead'"
                    :class="[
                        'px-4 py-2.5 text-sm font-semibold transition border-b-2 -mb-px flex items-center gap-2 relative',
                        activeTab === 'letterhead'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Print Letterheads & Backgrounds
                    <span 
                        v-if="settings.invoice_bg_path || settings.report_bg_path" 
                        class="w-2 h-2 rounded-full bg-emerald-500"
                        title="Custom Letterheads Active"
                    ></span>
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

                <!-- Tab 4: Pre-Printed Letterheads & Margins -->
                <div v-show="activeTab === 'letterhead'" class="space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center justify-between">
                            <span>Pre-Printed Letterhead Backgrounds</span>
                            <span class="text-xs font-normal text-slate-500">A4 Dimensions (210mm × 297mm)</span>
                        </h2>
                        <div class="mt-2 p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="space-y-1">
                                <p class="font-bold">How Pre-Printed Letterheads Work:</p>
                                <p class="text-blue-800 leading-relaxed">
                                    Upload an A4 graphic background that already includes your company logo, header artwork, legal address, and footer. When active, our PDF engine automatically suppresses digital text headers to avoid duplicate branding. Use the <strong>Top Margin</strong> control to align tables cleanly below your artwork.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Two-Column Cards: Invoice Letterhead & Report Letterhead -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- 1. Invoice & Sales Letterhead -->
                        <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Invoice & Sales Letterhead
                                    </h3>
                                    <p class="text-[11px] text-slate-500">Applied to Sales Invoices, Challans, and Quotes</p>
                                </div>
                                <span 
                                    v-if="settings.invoice_bg_path" 
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800"
                                >
                                    ● Active
                                </span>
                                <span 
                                    v-else 
                                    class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200 text-slate-600"
                                >
                                    Digital Mode
                                </span>
                            </div>

                            <!-- Current / Pending Image Preview -->
                            <div class="relative rounded-xl border border-dashed border-slate-300 bg-white p-3 text-center overflow-hidden">
                                <div v-if="invoiceBgPreview || settings.invoice_bg_url" class="relative group">
                                    <img 
                                        :src="invoiceBgPreview || settings.invoice_bg_url" 
                                        alt="Invoice Background Preview" 
                                        class="h-44 w-auto mx-auto object-contain rounded shadow-sm border border-slate-200 bg-white"
                                    />
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 rounded">
                                        <button 
                                            type="button" 
                                            @click="previewInvoiceBg"
                                            class="px-3 py-1.5 rounded-lg bg-white/95 text-slate-800 text-xs font-bold hover:bg-white flex items-center gap-1 shadow"
                                        >
                                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Preview PDF
                                        </button>
                                        <button 
                                            v-if="settings.invoice_bg_path"
                                            type="button" 
                                            @click="removeInvoiceBg"
                                            class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 flex items-center gap-1 shadow"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="py-6 flex flex-col items-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs text-slate-500 font-medium">No invoice background uploaded</span>
                                    <span class="text-[10px] text-slate-400">Default digital header will be used</span>
                                </div>
                            </div>

                            <!-- Upload Button & File Input -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Upload New Invoice Letterhead</label>
                                <input 
                                    ref="invoiceBgInput"
                                    type="file" 
                                    accept="image/png, image/jpeg, image/jpg"
                                    @change="handleInvoiceBgChange"
                                    class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                />
                                <span class="text-[10px] text-slate-400 block mt-1">Recommended: A4 portrait (2480 × 3508 px at 300 DPI), Max 5MB (.jpg, .png)</span>
                            </div>

                            <!-- Margin Offsets -->
                            <div class="pt-3 border-t border-slate-200/80 space-y-3">
                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Printable Area Margins (mm)</span>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                            <span>Top Margin (Header Offset)</span>
                                            <span class="text-indigo-600 font-mono font-bold">{{ form.invoice_margin_top }}mm</span>
                                        </label>
                                        <input 
                                            v-model.number="form.invoice_margin_top"
                                            type="range" 
                                            min="0" 
                                            max="120" 
                                            step="1"
                                            class="w-full accent-indigo-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
                                        />
                                        <p class="text-[10px] text-slate-400 mt-0.5">Leaves room for your top logo/header</p>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                            <span>Bottom Margin (Footer Offset)</span>
                                            <span class="text-indigo-600 font-mono font-bold">{{ form.invoice_margin_bottom }}mm</span>
                                        </label>
                                        <input 
                                            v-model.number="form.invoice_margin_bottom"
                                            type="range" 
                                            min="0" 
                                            max="80" 
                                            step="1"
                                            class="w-full accent-indigo-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
                                        />
                                        <p class="text-[10px] text-slate-400 mt-0.5">Leaves room for your bottom footer</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Action Button -->
                            <div class="pt-2">
                                <button
                                    type="button"
                                    @click="previewInvoiceBg"
                                    class="w-full py-2 px-3 rounded-xl border border-indigo-200 bg-white hover:bg-indigo-50/70 text-indigo-700 text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Preview Sample Invoice PDF
                                </button>
                            </div>
                        </div>

                        <!-- 2. Financial Report Letterhead -->
                        <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Financial Report Letterhead
                                    </h3>
                                    <p class="text-[11px] text-slate-500">Applied to Trial Balance, P&L, Balance Sheet, and Ledgers</p>
                                </div>
                                <span 
                                    v-if="settings.report_bg_path" 
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800"
                                >
                                    ● Active
                                </span>
                                <span 
                                    v-else 
                                    class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200 text-slate-600"
                                >
                                    Digital Mode
                                </span>
                            </div>

                            <!-- Current / Pending Image Preview -->
                            <div class="relative rounded-xl border border-dashed border-slate-300 bg-white p-3 text-center overflow-hidden">
                                <div v-if="reportBgPreview || settings.report_bg_url" class="relative group">
                                    <img 
                                        :src="reportBgPreview || settings.report_bg_url" 
                                        alt="Report Background Preview" 
                                        class="h-44 w-auto mx-auto object-contain rounded shadow-sm border border-slate-200 bg-white"
                                    />
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 rounded">
                                        <button 
                                            type="button" 
                                            @click="previewReportBg"
                                            class="px-3 py-1.5 rounded-lg bg-white/95 text-slate-800 text-xs font-bold hover:bg-white flex items-center gap-1 shadow"
                                        >
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Preview PDF
                                        </button>
                                        <button 
                                            v-if="settings.report_bg_path"
                                            type="button" 
                                            @click="removeReportBg"
                                            class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 flex items-center gap-1 shadow"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="py-6 flex flex-col items-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs text-slate-500 font-medium">No report background uploaded</span>
                                    <span class="text-[10px] text-slate-400">Default digital header will be used</span>
                                </div>
                            </div>

                            <!-- Upload Button & File Input -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Upload New Report Letterhead</label>
                                <input 
                                    ref="reportBgInput"
                                    type="file" 
                                    accept="image/png, image/jpeg, image/jpg"
                                    @change="handleReportBgChange"
                                    class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                                />
                                <span class="text-[10px] text-slate-400 block mt-1">Recommended: A4 portrait (2480 × 3508 px at 300 DPI), Max 5MB (.jpg, .png)</span>
                            </div>

                            <!-- Margin Offsets -->
                            <div class="pt-3 border-t border-slate-200/80 space-y-3">
                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Printable Area Margins (mm)</span>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                            <span>Top Margin (Header Offset)</span>
                                            <span class="text-emerald-600 font-mono font-bold">{{ form.report_margin_top }}mm</span>
                                        </label>
                                        <input 
                                            v-model.number="form.report_margin_top"
                                            type="range" 
                                            min="0" 
                                            max="120" 
                                            step="1"
                                            class="w-full accent-emerald-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
                                        />
                                        <p class="text-[10px] text-slate-400 mt-0.5">Leaves room for your top logo/header</p>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                            <span>Bottom Margin (Footer Offset)</span>
                                            <span class="text-emerald-600 font-mono font-bold">{{ form.report_margin_bottom }}mm</span>
                                        </label>
                                        <input 
                                            v-model.number="form.report_margin_bottom"
                                            type="range" 
                                            min="0" 
                                            max="80" 
                                            step="1"
                                            class="w-full accent-emerald-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
                                        />
                                        <p class="text-[10px] text-slate-400 mt-0.5">Leaves room for your bottom footer</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Action Button -->
                            <div class="pt-2">
                                <button
                                    type="button"
                                    @click="previewReportBg"
                                    class="w-full py-2 px-3 rounded-xl border border-emerald-200 bg-white hover:bg-emerald-50/70 text-emerald-700 text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Preview Sample Report PDF
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Submit Action Footer -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <p class="text-xs text-slate-400">
                        Changes are saved to your current tenant workspace instantly.
                    </p>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-md shadow-emerald-500/20 transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50"
                    >
                        Save Company Profile & Print Settings
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
