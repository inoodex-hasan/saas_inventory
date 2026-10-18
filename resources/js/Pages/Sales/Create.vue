<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    customers: Array,
    warehouses: Array,
    products: Array,
    financialAccounts: Array,
    defaultWarehouseId: Number,
    todayDate: String,
});

const defaultAccount = (props.financialAccounts || []).find(a => a.is_default) || props.financialAccounts?.[0];

const accountOptions = computed(() => {
    return (props.financialAccounts || []).map(acc => ({
        value: acc.id,
        label: acc.name,
        sublabel: acc.type === 'cash'
            ? `Balance: ৳${Number(acc.current_balance).toFixed(2)}`
            : `${acc.institution_name} (${acc.account_number}) - Bal: ৳${Number(acc.current_balance).toFixed(2)}`,
        group: acc.type === 'cash' ? 'Cash Registers' : (acc.type === 'bank' ? 'Bank Accounts' : 'MFS Wallets'),
    }));
});

const form = useForm({
    customer_mode: 'existing',
    customer_id: '',
    new_customer_name: '',
    new_customer_phone: '',
    new_customer_address: '',
    new_customer_email: '',
    warehouse_id: props.defaultWarehouseId || '',
    order_date: props.todayDate || new Date().toISOString().slice(0, 10),
    notes: '',
    discount_amount: 0,
    tax_amount: 0,
    shipping_cost: 0,
    paid_amount: 0,
    financial_account_id: defaultAccount?.id || '',
    payment_reference: '',
    status: 'draft',
    items: [
        {
            product_id: '',
            quantity: 1,
            unit_price: 0,
            warranty_days: 0,
        },
    ],
});

function addItem() {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_price: 0,
        warranty_days: 0,
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
        item.warranty_days = parseInt(product.warranty_days) || 0;
    }
}

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0));
    }, 0);
});

const totalAmount = computed(() => {
    const sub = subtotal.value;
    const disc = parseFloat(form.discount_amount) || 0;
    const tax = parseFloat(form.tax_amount) || 0;
    const ship = parseFloat(form.shipping_cost) || 0;
    return Math.max(0, sub - disc + tax + ship);
});

const dueAmount = computed(() => {
    const total = totalAmount.value;
    const paid = parseFloat(form.paid_amount) || 0;
    return Math.max(0, total - paid);
});

function setFullPayment() {
    form.paid_amount = totalAmount.value;
}

function submit() {
    form.post(route('sales.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="New Outbound Sales Order & Invoice" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('sales.index')" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">New Outbound Sales Order & Invoice</h1>
                        <p class="text-xs text-slate-500 font-medium">Issue customer invoice, POS sales docket, and allocate warehouse inventory</p>
                    </div>
                </div>
                <Link
                    :href="route('sales.index')"
                    class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-xs"
                >
                    &larr; Back to Orders
                </Link>
            </div>
        </template>

        <div class="space-y-6">

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Customer & Line items -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Details Card -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900">Customer Details</h2>
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

                        <!-- Existing Customer dropdown -->
                        <div v-if="form.customer_mode === 'existing'">
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Select Customer *</label>
                            <SearchableSelect
                                v-model="form.customer_id"
                                :options="customers"
                                labelKey="name"
                                sublabelKey="phone"
                                placeholder="Search & select customer by name or phone..."
                                searchPlaceholder="Type customer name or phone..."
                                :error="form.errors.customer_id"
                            />
                        </div>

                        <!-- Inline New Customer fields -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Customer Full Name *</label>
                                <input
                                    v-model="form.new_customer_name"
                                    type="text"
                                    required
                                    placeholder="e.g. Jahangir Alam"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-400 placeholder:text-xs"
                                />
                                <div v-if="form.errors.new_customer_name" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Phone / Mobile *</label>
                                <input
                                    v-model="form.new_customer_phone"
                                    type="text"
                                    required
                                    placeholder="e.g. 01711223399"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 font-mono placeholder:text-slate-400 placeholder:text-xs"
                                />
                                <div v-if="form.errors.new_customer_phone" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_phone }}</div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Delivery / Street Address *</label>
                                <input
                                    v-model="form.new_customer_address"
                                    type="text"
                                    required
                                    placeholder="e.g. Mirpur-10, Dhaka"
                                    class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-400 placeholder:text-xs"
                                />
                                <div v-if="form.errors.new_customer_address" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_address }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Line Items Card -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900">Order Line Items</h2>
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
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
                                class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/70 flex flex-col sm:flex-row gap-3 items-start sm:items-center"
                            >
                                <div class="flex-1 w-full sm:w-auto">
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-0.5">Product / SKU</label>
                                    <SearchableSelect
                                        v-model="item.product_id"
                                        :options="products"
                                        labelKey="name"
                                        sublabelKey="sku"
                                        placeholder="Search product by name or SKU..."
                                        searchPlaceholder="Type name or SKU..."
                                        @change="() => onProductSelected(idx)"
                                    />
                                </div>

                                <div class="w-24">
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-0.5">Qty</label>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="0.01"
                                        step="any"
                                        required
                                        class="w-full text-sm px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>

                                <div class="w-32">
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-0.5">Unit Price</label>
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="w-full text-sm px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>

                                <div class="w-24">
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-0.5">Warranty (Days)</label>
                                    <input
                                        v-model.number="item.warranty_days"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="w-full text-sm px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>

                                <div class="w-28 text-right self-end sm:self-center">
                                    <label class="block text-[11px] font-semibold text-slate-500 uppercase mb-0.5">Line Total</label>
                                    <span class="text-sm font-bold text-slate-900 block py-2">
                                        <CurrencyDisplay :amount="(item.quantity || 0) * (item.unit_price || 0)" />
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    @click="removeItem(idx)"
                                    :disabled="form.items.length <= 1"
                                    class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition self-end sm:self-center disabled:opacity-30 disabled:hover:text-slate-400"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Col: Order Meta & Financial Summary -->
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Warehouse & Timing</h2>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Dispatching Warehouse *</label>
                            <select
                                v-model="form.warehouse_id"
                                required
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="" disabled>-- Select Warehouse Hub --</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }} ({{ w.code }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Order Date *</label>
                            <input
                                v-model="form.order_date"
                                type="date"
                                required
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Order Status</label>
                            <select
                                v-model="form.status"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 font-medium"
                            >
                                <option value="draft">Draft (No reservation yet)</option>
                                <option value="confirmed">Confirmed (Reserve stock immediately)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Notes / Terms</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="e.g. Delivery instructions or notes"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-400 placeholder:text-xs"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Financial Ledger Summary -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Billing & Payment</h2>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Items Subtotal:</span>
                                <span class="font-semibold text-slate-900"><CurrencyDisplay :amount="subtotal" /></span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-600 text-xs uppercase font-semibold">Discount:</span>
                                <input
                                    v-model.number="form.discount_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-28 text-right text-xs px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-600 text-xs uppercase font-semibold">Tax / VAT:</span>
                                <input
                                    v-model.number="form.tax_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-28 text-right text-xs px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-600 text-xs uppercase font-semibold">Shipping:</span>
                                <input
                                    v-model.number="form.shipping_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-28 text-right text-xs px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white"
                                />
                            </div>

                            <div class="pt-3 border-t border-slate-200 flex justify-between text-base font-bold text-slate-900">
                                <span>Grand Total:</span>
                                <span class="text-emerald-700"><CurrencyDisplay :amount="totalAmount" /></span>
                            </div>

                            <!-- Payment Section -->
                            <div class="pt-3 border-t border-slate-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-600 uppercase">Paid Now:</span>
                                    <button
                                        type="button"
                                        @click="setFullPayment"
                                        class="text-[11px] text-emerald-600 hover:text-emerald-800 font-semibold underline"
                                    >
                                        Paid in Full
                                    </button>
                                </div>
                                <input
                                    v-model.number="form.paid_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full text-base font-bold px-3 py-2 bg-emerald-50/50 border border-emerald-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                                />
                                <div class="flex justify-between text-xs font-semibold pt-1">
                                    <span class="text-slate-500">Remaining Due:</span>
                                    <span :class="dueAmount > 0 ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold'">
                                        <CurrencyDisplay :amount="dueAmount" />
                                    </span>
                                </div>

                                <!-- Dynamic Deposit Account & Reference -->
                                <div v-if="form.paid_amount > 0" class="pt-2 space-y-3 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                            Deposit Into Account *
                                        </label>
                                        <SearchableSelect
                                            v-model="form.financial_account_id"
                                            :options="accountOptions"
                                            placeholder="Select deposit account..."
                                            searchPlaceholder="Type account name or bank..."
                                            size="compact"
                                            :error="form.errors.financial_account_id"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                            Transaction Ref / TrxID #
                                        </label>
                                        <input
                                            v-model="form.payment_reference"
                                            type="text"
                                            placeholder="e.g. bKash TrxID, Cheque #, or deposit slip"
                                            class="w-full text-xs px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-400 placeholder:text-xs font-mono"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 rounded-xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-md shadow-emerald-500/20 transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50"
                        >
                            <span v-if="form.processing">Processing Order...</span>
                            <span v-else>Generate Sales Order & Invoice</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
