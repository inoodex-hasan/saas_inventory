<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    customers: Array,
    warehouses: Array,
    products: Array,
    frequencies: Array,
    defaultWarehouseId: Number,
});

const todayDate = new Date().toISOString().slice(0, 10);

const customerOptions = computed(() => {
    return (props.customers || []).map(c => ({
        value: c.id,
        label: c.name,
        sublabel: c.phone ? `${c.phone} - ${c.email || ''}` : (c.email || ''),
    }));
});

const productOptions = computed(() => {
    return (props.products || []).map(p => ({
        value: p.id,
        label: p.name,
        sublabel: `SKU: ${p.sku || 'N/A'} - ৳${Number(p.sale_price).toFixed(2)}`,
        price: Number(p.sale_price) || 0,
    }));
});

const form = useForm({
    profile_name: '',
    customer_id: '',
    warehouse_id: props.defaultWarehouseId || '',
    frequency: 'monthly',
    interval_count: 1,
    start_date: todayDate,
    next_issue_date: todayDate,
    end_date: '',
    total_cycles_limit: '',
    discount_amount: 0,
    tax_amount: 0,
    payment_terms_days: 7,
    auto_send_email: true,
    notes: '',
    items: [
        {
            product_id: '',
            item_name: '',
            description: '',
            quantity: 1,
            unit_price: 0,
        },
    ],
});

function addItem() {
    form.items.push({
        product_id: '',
        item_name: '',
        description: '',
        quantity: 1,
        unit_price: 0,
    });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function onProductSelected(index, productId) {
    const item = form.items[index];
    item.product_id = productId;
    const product = (props.products || []).find(p => p.id === parseInt(productId));
    if (product) {
        if (!item.item_name || item.item_name === '') {
            item.item_name = product.name;
        }
        item.unit_price = Number(product.sale_price) || 0;
    }
}

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.unit_price) || 0;
        return sum + (qty * price);
    }, 0);
});

const totalAmount = computed(() => {
    const sub = subtotal.value;
    const disc = parseFloat(form.discount_amount) || 0;
    const tax = parseFloat(form.tax_amount) || 0;
    return Math.max(0, sub - disc + tax);
});

const estimatedMrr = computed(() => {
    const total = totalAmount.value;
    const interval = Math.max(1, parseInt(form.interval_count) || 1);
    switch (form.frequency) {
        case 'weekly':
            return (total / interval) * 4.333;
        case 'monthly':
            return total / interval;
        case 'quarterly':
            return (total / interval) / 3;
        case 'semi_annual':
            return (total / interval) / 6;
        case 'yearly':
            return (total / interval) / 12;
        default:
            return total;
    }
});

function submit() {
    form.post(route('recurring-invoices.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Create Subscription Profile" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('recurring-invoices.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            &larr; Back to Subscriptions
                        </Link>
                    </div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight mt-1">
                        New Recurring Subscription Profile
                    </h1>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- General & Customer Information -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 border-b border-slate-100 pb-3">
                        Subscription & Customer Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Profile Name -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Profile Name / Title <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.profile_name"
                                type="text"
class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                                required
                            />
                            <p v-if="form.errors.profile_name" class="text-xs text-rose-500 mt-1">{{ form.errors.profile_name }}</p>
                        </div>

                        <!-- Customer Select -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Customer / Client <span class="text-rose-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.customer_id"
                                :options="customerOptions"
                                placeholder="Select a customer..."
                            />
                            <p v-if="form.errors.customer_id" class="text-xs text-rose-500 mt-1">{{ form.errors.customer_id }}</p>
                        </div>

                        <!-- Warehouse Select -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Fulfillment Warehouse
                            </label>
                            <select
                                v-model="form.warehouse_id"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                            >
                                <option :value="''">Primary / Default Warehouse</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                    {{ wh.name }} {{ wh.is_default ? '(Default)' : '' }}
                                </option>
                            </select>
                            <p v-if="form.errors.warehouse_id" class="text-xs text-rose-500 mt-1">{{ form.errors.warehouse_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Recurrence Rules -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 border-b border-slate-100 pb-3">
                        Recurrence Cadence & Billing Schedule
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Frequency -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Frequency Cadence <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.frequency"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 font-medium"
                                required
                            >
                                <option v-for="freq in frequencies" :key="freq.value" :value="freq.value">
                                    {{ freq.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.frequency" class="text-xs text-rose-500 mt-1">{{ form.errors.frequency }}</p>
                        </div>

                        <!-- Interval Count -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Interval Multiplier <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.interval_count"
                                type="number"
                                min="1"
                                max="12"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                                required
                            />
                            <span class="text-[10px] text-slate-400">e.g. 1 = every cycle, 2 = every 2nd cycle</span>
                            <p v-if="form.errors.interval_count" class="text-xs text-rose-500 mt-1">{{ form.errors.interval_count }}</p>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Start Date <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                                required
                            />
                            <p v-if="form.errors.start_date" class="text-xs text-rose-500 mt-1">{{ form.errors.start_date }}</p>
                        </div>

                        <!-- Next Issue Date -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Next Issue Date <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.next_issue_date"
                                type="date"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5 font-bold text-indigo-700"
                                required
                            />
                            <p v-if="form.errors.next_issue_date" class="text-xs text-rose-500 mt-1">{{ form.errors.next_issue_date }}</p>
                        </div>

                        <!-- End Date (Optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                End Date <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                            />
                            <p v-if="form.errors.end_date" class="text-xs text-rose-500 mt-1">{{ form.errors.end_date }}</p>
                        </div>

                        <!-- Total Cycles Limit (Optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Max Cycles Limit <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input
                                v-model="form.total_cycles_limit"
                                type="number"
                                min="1"
class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                            />
                            <p v-if="form.errors.total_cycles_limit" class="text-xs text-rose-500 mt-1">{{ form.errors.total_cycles_limit }}</p>
                        </div>

                        <!-- Payment Terms Days -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Payment Due In (Days)
                            </label>
                            <input
                                v-model="form.payment_terms_days"
                                type="number"
                                min="0"
                                max="365"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2.5"
                            />
                            <p v-if="form.errors.payment_terms_days" class="text-xs text-rose-500 mt-1">{{ form.errors.payment_terms_days }}</p>
                        </div>

                        <!-- Auto-send email toggle -->
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="form.auto_send_email"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                                />
                                <span class="text-xs font-semibold text-slate-700">Auto-email PDF Invoice</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Line Items Section -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600">
                            Recurring Line Items
                        </h2>
                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-semibold text-xs transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Line Item
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 relative"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                <!-- Catalog Product Select -->
                                <div class="md:col-span-4">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                        Catalog Product (Optional)
                                    </label>
                                    <select
                                        :value="item.product_id"
                                        @change="onProductSelected(index, $event.target.value)"
                                        class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 bg-white"
                                    >
                                        <option value="">Custom Service / Retainer</option>
                                        <option v-for="prod in products" :key="prod.id" :value="prod.id">
                                            {{ prod.name }} (৳{{ Number(prod.sale_price).toFixed(2) }})
                                        </option>
                                    </select>
                                </div>

                                <!-- Item Name -->
                                <div class="md:col-span-4">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                        Item / Service Name <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="item.item_name"
                                        type="text"
class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 bg-white"
                                        required
                                    />
                                </div>

                                <!-- Quantity -->
                                <div class="md:col-span-1">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Qty</label>
                                    <input
                                        v-model="item.quantity"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 bg-white text-center"
                                        required
                                    />
                                </div>

                                <!-- Unit Price -->
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Unit Price (৳)</label>
                                    <input
                                        v-model="item.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-2 bg-white text-right"
                                        required
                                    />
                                </div>

                                <!-- Subtotal & Delete -->
                                <div class="md:col-span-1 flex items-center justify-between md:flex-col md:items-end pt-2 md:pt-4">
                                    <span class="text-xs font-bold text-slate-800">
                                        ৳{{ ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)).toFixed(2) }}
                                    </span>
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="text-rose-500 hover:text-rose-700 text-xs mt-1"
                                        title="Remove Line"
                                    >
                                        &times; Remove
                                    </button>
                                </div>

                                <!-- Description (Full width underneath) -->
                                <div class="md:col-span-12">
                                    <input
                                        v-model="item.description"
                                        type="text"
class="w-full text-[11px] rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 bg-white text-slate-600"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary & Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Notes -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-3">
                        <label class="block text-xs font-bold text-slate-700">
                            Invoice Terms & Notes
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3"
                        ></textarea>
                    </div>

                    <!-- Summary Card -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex justify-between items-center text-xs text-slate-600 pb-2 border-b border-slate-100">
                            <span>Subtotal:</span>
                            <span class="font-bold text-slate-800">
                                <CurrencyDisplay :amount="subtotal" />
                            </span>
                        </div>

                        <div class="flex justify-between items-center text-xs text-slate-600">
                            <span>Discount:</span>
                            <div class="w-32">
                                <input
                                    v-model="form.discount_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full text-xs text-right rounded-lg border-slate-200 py-1.5"
                                />
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-xs text-slate-600 pb-2 border-b border-slate-100">
                            <span>Tax / VAT:</span>
                            <div class="w-32">
                                <input
                                    v-model="form.tax_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full text-xs text-right rounded-lg border-slate-200 py-1.5"
                                />
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm font-extrabold text-slate-900 pt-1">
                            <span>Total per Cycle:</span>
                            <span class="text-indigo-600 text-lg">
                                <CurrencyDisplay :amount="totalAmount" />
                            </span>
                        </div>

                        <div class="flex justify-between items-center text-xs font-semibold text-emerald-700 bg-emerald-50 p-2.5 rounded-xl border border-emerald-100 mt-2">
                            <span>Normalized Monthly Run Rate (MRR):</span>
                            <span>
                                <CurrencyDisplay :amount="estimatedMrr" />
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <Link
                        :href="route('recurring-invoices.index')"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition-colors"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving Profile...' : 'Save Subscription Profile' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
