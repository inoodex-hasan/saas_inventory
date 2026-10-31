<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    page_title: String,
    quotationNumber: String,
    customers: Array,
    products: Array,
    warehouses: Array,
    todayDate: String,
    defaultValidUntil: String,
});

const defaultTerms = "1. Payment terms: 50% advance upon order confirmation, 50% upon delivery.\n2. Prices are valid for 15 days from the date of quotation.\n3. Standard manufacturer warranty applies where applicable.";

const form = useForm({
    customer_mode: 'existing',
    customer_id: '',
    new_customer_name: '',
    new_customer_phone: '',
    new_customer_address: '',
    quotation_date: props.todayDate || new Date().toISOString().slice(0, 10),
    valid_until: props.defaultValidUntil || new Date(Date.now() + 15 * 86400000).toISOString().slice(0, 10),
    status: 'draft',
    discount_amount: 0,
    tax_rate: 0,
    notes: '',
    terms: defaultTerms,
    items: [
        {
            product_id: '',
            quantity: 1,
            unit_price: 0,
        },
    ],
});

function addItem() {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_price: 0,
    });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function onProductSelected(index) {
    const item = form.items[index];
    const product = props.products.find(p => p.id === parseInt(item.product_id));
    if (product) {
        item.unit_price = parseFloat(product.unit_price) || 0;
    }
}

function getProductStock(productId) {
    const p = props.products.find(prod => prod.id === parseInt(productId));
    if (!p) return { available: 0, total: 0 };
    return {
        available: p.total_stock ?? 0,
        total: p.total_stock ?? 0,
    };
}

const productSelectOptions = computed(() => {
    return props.products.map(p => {
        const stockQty = p.total_stock ?? 0;
        return {
            ...p,
            sku_stock: `${p.sku || 'No SKU'} • Stock: ${stockQty}`,
        };
    });
});

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0));
    }, 0);
});

const taxAmount = computed(() => {
    const base = Math.max(0, subtotal.value - (parseFloat(form.discount_amount) || 0));
    const rate = parseFloat(form.tax_rate) || 0;
    return base * (rate / 100);
});

const totalAmount = computed(() => {
    const sub = subtotal.value;
    const disc = parseFloat(form.discount_amount) || 0;
    return Math.max(0, sub - disc + taxAmount.value);
});

function submit(statusValue = 'draft') {
    form.status = statusValue;
    form.post(route('quotations.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Create Formal Quotation" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('quotations.index')" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Create Formal Quotation</h1>
                        <p class="text-xs text-slate-500 font-medium">Issue branded estimate or commercial proposal with pricing terms</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <form @submit.prevent="submit('draft')" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Details -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900">Customer Recipient</h2>
                            <div class="flex items-center bg-slate-100 p-0.5 rounded-lg text-xs font-semibold">
                                <button
                                    type="button"
                                    @click="form.customer_mode = 'existing'"
                                    :class="form.customer_mode === 'existing' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                                    class="px-3 py-1 rounded-md transition"
                                >
                                    Existing Customer
                                </button>
                                <button
                                    type="button"
                                    @click="form.customer_mode = 'new'"
                                    :class="form.customer_mode === 'new' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                                    class="px-3 py-1 rounded-md transition"
                                >
                                    + New Customer
                                </button>
                            </div>
                        </div>

                        <div v-if="form.customer_mode === 'existing'">
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Select Customer *</label>
                            <SearchableSelect
                                v-model="form.customer_id"
                                :options="customers"
                                labelKey="name"
                                sublabelKey="phone"
                                :error="form.errors.customer_id"
                            />
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Customer / Organization Name *</label>
                                <input
                                    v-model="form.new_customer_name"
                                    type="text"
                                    required
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Phone / Mobile *</label>
                                <input
                                    v-model="form.new_customer_phone"
                                    type="text"
                                    required
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-blue-500 font-mono"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Address</label>
                                <input
                                    v-model="form.new_customer_address"
                                    type="text"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900">Quotation Line Items</h2>
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Line Item
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/70 space-y-3"
                            >
                                <!-- Line 1: Product Selection, Live Stock Badge, & Remove Button -->
                                <div class="flex items-start gap-3 justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-[11px] font-semibold text-slate-500 uppercase">Product / SKU *</label>
                                            <div v-if="item.product_id" class="flex items-center gap-1.5 text-[11px]">
                                                <span class="text-slate-400">Stock:</span>
                                                <span
                                                    class="font-mono font-bold px-1.5 py-0.5 rounded text-[10px]"
                                                    :class="getProductStock(item.product_id).available > 0
                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
                                                        : 'bg-rose-50 text-rose-700 border border-rose-200/60'"
                                                >
                                                    {{ getProductStock(item.product_id).available }} in stock
                                                </span>
                                            </div>
                                        </div>
                                        <SearchableSelect
                                            v-model="item.product_id"
                                            :options="productSelectOptions"
                                            labelKey="name"
                                            sublabelKey="sku_stock"
                                            size="compact"
                                            @change="() => onProductSelected(idx)"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        @click="removeItem(idx)"
                                        :disabled="form.items.length <= 1"
                                        class="mt-5 p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition shrink-0 disabled:opacity-30 disabled:hover:text-slate-400"
                                        title="Remove item"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Line 2: Qty, Unit Price, and Line Total -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-center pt-2 border-t border-slate-200/60">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Quantity *</label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="0.01"
                                            step="any"
                                            required
                                            class="w-full h-[34px] text-xs px-2.5 bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-blue-500 font-mono font-bold"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Offered Unit Price *</label>
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full h-[34px] text-xs px-2.5 bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-blue-500 font-mono"
                                        />
                                    </div>

                                    <div class="text-right">
                                        <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Total</label>
                                        <span class="text-xs font-bold text-slate-900 block py-1.5 font-mono">
                                            <CurrencyDisplay :amount="(item.quantity || 0) * (item.unit_price || 0)" />
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Col -->
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Validity & Quotation #</h2>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Quotation Number</label>
                            <input
                                :value="quotationNumber"
                                readonly
                                class="w-full text-xs px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-slate-600 font-semibold cursor-not-allowed font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Quotation Date *</label>
                            <input
                                v-model="form.quotation_date"
                                type="date"
                                required
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-blue-500 font-medium"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Valid Until *</label>
                            <input
                                v-model="form.valid_until"
                                type="date"
                                required
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-blue-500 font-medium"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Terms & Conditions</label>
                            <textarea
                                v-model="form.terms"
                                rows="6"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 font-mono"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Totals & Submit -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Price Quotation</h2>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal:</span>
                                <span class="font-semibold text-slate-900"><CurrencyDisplay :amount="subtotal" /></span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-600 text-xs uppercase font-semibold">Discount:</span>
                                <input
                                    v-model.number="form.discount_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-28 text-right text-xs px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-600 text-xs uppercase font-semibold">Tax Rate (%):</span>
                                <input
                                    v-model.number="form.tax_rate"
                                    type="number"
                                    min="0"
                                    step="0.1"
                                    class="w-28 text-right text-xs px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg"
                                />
                            </div>

                            <div class="pt-3 border-t border-slate-200 flex justify-between text-base font-bold text-slate-900">
                                <span>Grand Total:</span>
                                <span class="text-blue-700"><CurrencyDisplay :amount="totalAmount" /></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button
                                type="button"
                                @click="submit('draft')"
                                :disabled="form.processing"
                                class="py-2.5 px-3 rounded-xl font-semibold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 transition text-center"
                            >
                                Save as Draft
                            </button>
                            <button
                                type="button"
                                @click="submit('sent')"
                                :disabled="form.processing"
                                class="py-2.5 px-3 rounded-xl font-bold text-xs text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition text-center"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save Quotation</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
