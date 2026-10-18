<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    suppliers: Array,
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
    vendor_mode: 'existing', // 'existing' | 'new'
    supplier_id: props.suppliers[0]?.id || '',
    new_vendor_name: '',
    new_vendor_phone: '',
    new_vendor_address: '',
    new_vendor_contact: '',
    new_vendor_email: '',
    new_vendor_tax_number: '',
    warehouse_id: props.defaultWarehouseId || '',
    order_date: props.todayDate || new Date().toISOString().split('T')[0],
    expected_delivery_date: '',
    notes: '',
    paid_amount: 0,
    financial_account_id: defaultAccount?.id || '',
    payment_reference: '',
    items: [
        {
            product_id: '',
            quantity_ordered: 1,
            unit_cost: 0,
            batch_number: '',
            manufacture_date: '',
            expiry_date: '',
        },
    ],
});

function addItemRow() {
    form.items.push({
        product_id: '',
        quantity_ordered: 1,
        unit_cost: 0,
        batch_number: '',
        manufacture_date: '',
        expiry_date: '',
    });
}

function removeItemRow(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function onProductSelected(item) {
    const prod = props.products.find(p => p.id === Number(item.product_id));
    if (prod) {
        item.unit_cost = Number(prod.cost_price) || 0;
    }
}

const grandTotal = computed(() => {
    return form.items.reduce((acc, it) => {
        const qty = Number(it.quantity_ordered) || 0;
        const cost = Number(it.unit_cost) || 0;
        return acc + (qty * cost);
    }, 0);
});

const duePayable = computed(() => {
    const paid = Number(form.paid_amount) || 0;
    return Math.max(0, grandTotal.value - paid);
});

function setFullPayment() {
    form.paid_amount = grandTotal.value;
}

function submit() {
    form.post(route('purchases.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Create Purchase Order" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('purchases.index')" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Create Inbound Purchase Order</h1>
                        <p class="text-xs text-slate-500 font-medium">Issue procurement sheets, track vendor batches, and schedule warehouse intake</p>
                    </div>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. Vendor Selection Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                        Procurement Partner / Vendor
                    </h2>

                    <!-- Mode Toggle -->
                    <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200 text-xs">
                        <button 
                            type="button" 
                            @click="form.vendor_mode = 'existing'"
                            class="px-3 py-1 rounded-lg font-semibold transition-all"
                            :class="form.vendor_mode === 'existing' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Existing Vendor
                        </button>
                        <button 
                            type="button" 
                            @click="form.vendor_mode = 'new'"
                            class="px-3 py-1 rounded-lg font-semibold transition-all"
                            :class="form.vendor_mode === 'new' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            + New Vendor
                        </button>
                    </div>
                </div>

                <!-- Existing Vendor Picker -->
                <div v-if="form.vendor_mode === 'existing'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Vendor *</label>
                    <SearchableSelect 
                        v-model="form.supplier_id" 
                        :options="suppliers"
                        labelKey="name"
                        sublabelKey="phone"
                        placeholder="Search & select vendor by name or phone..."
                        searchPlaceholder="Type vendor name or phone..."
                        :error="form.errors.supplier_id"
                    />
                </div>

                <!-- Inline New Vendor Fields -->
                <div v-else class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Vendor / Company Name *</label>
                            <input 
                                type="text" 
                                v-model="form.new_vendor_name" 
                                required
                                placeholder="e.g. Apex Global Supply"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.new_vendor_name" class="text-[11px] text-rose-600 mt-1">{{ form.errors.new_vendor_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile / Phone Number *</label>
                            <input 
                                type="text" 
                                v-model="form.new_vendor_phone" 
                                required
                                placeholder="e.g. 01700000000"
                                class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.new_vendor_phone" class="text-[11px] text-rose-600 mt-1">{{ form.errors.new_vendor_phone }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Billing / Physical Address *</label>
                            <input 
                                type="text" 
                                v-model="form.new_vendor_address" 
                                required
                                placeholder="e.g. Plot 10, Tejgaon, Dhaka"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.new_vendor_address" class="text-[11px] text-rose-600 mt-1">{{ form.errors.new_vendor_address }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                            <input 
                                type="text" 
                                v-model="form.new_vendor_contact" 
                                placeholder="e.g. John Doe"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Logistics & Dates -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                    Logistics & Delivery Schedule
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Fulfillment Hub *</label>
                        <select 
                            v-model="form.warehouse_id" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">Select Hub...</option>
                            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                {{ wh.name }} {{ wh.is_default ? '(Default)' : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.warehouse_id" class="text-[11px] text-rose-600 mt-1">{{ form.errors.warehouse_id }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">PO Order Date *</label>
                        <input 
                            type="date" 
                            v-model="form.order_date" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Expected Inbound Delivery</label>
                        <input 
                            type="date" 
                            v-model="form.expected_delivery_date" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 font-mono"
                        />
                    </div>
                </div>
            </div>

            <!-- 3. Line Items -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                        Itemized Procurement Lines
                    </h2>

                    <button 
                        type="button" 
                        @click="addItemRow"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Row</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-bold">
                                <th class="py-2.5 w-1/3">Product *</th>
                                <th class="py-2.5 text-center w-24">Quantity *</th>
                                <th class="py-2.5 text-right w-28">Unit Cost *</th>
                                <th class="py-2.5 w-32">Batch / Lot #</th>
                                <th class="py-2.5 w-28">Expiry Date</th>
                                <th class="py-2.5 text-right w-28">Subtotal</th>
                                <th class="py-2.5 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(it, idx) in form.items" :key="idx">
                                <td class="py-2.5 pr-2">
                                    <SearchableSelect 
                                        v-model="it.product_id" 
                                        :options="products"
                                        labelKey="name"
                                        sublabelKey="sku"
                                        placeholder="Search product..."
                                        searchPlaceholder="Type name or SKU..."
                                        size="compact"
                                        @change="() => onProductSelected(it)"
                                    />
                                </td>

                                <td class="py-2.5 px-1 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0.01"
                                        v-model="it.quantity_ordered" 
                                        required
                                        class="w-full px-2 py-1.5 text-center rounded-lg border border-slate-200 text-xs font-mono font-bold"
                                    />
                                </td>

                                <td class="py-2.5 px-1 text-right">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        v-model="it.unit_cost" 
                                        required
                                        class="w-full px-2 py-1.5 text-right rounded-lg border border-slate-200 text-xs font-mono font-bold text-slate-800"
                                    />
                                </td>

                                <td class="py-2.5 px-1">
                                    <input 
                                        type="text" 
                                        v-model="it.batch_number" 
                                        placeholder="LOT-XXXX"
                                        class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-mono"
                                    />
                                </td>

                                <td class="py-2.5 px-1">
                                    <input 
                                        type="date" 
                                        v-model="it.expiry_date" 
                                        class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-mono"
                                    />
                                </td>

                                <td class="py-2.5 pl-2 text-right font-mono font-bold text-slate-900">
                                    <CurrencyDisplay :amount="(it.quantity_ordered || 0) * (it.unit_cost || 0)" />
                                </td>

                                <td class="py-2.5 text-center">
                                    <button 
                                        type="button" 
                                        v-if="form.items.length > 1"
                                        @click="removeItemRow(idx)"
                                        class="text-slate-400 hover:text-rose-600 p-1"
                                    >
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Financial Summary & Advance Payment -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end">
                    <!-- Grand Total Card -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Procurement Value</span>
                        <div class="text-xl font-black text-slate-900 mt-1">
                            <CurrencyDisplay :amount="grandTotal" />
                        </div>
                    </div>

                    <!-- Advance Paid -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-slate-700">Advance Paid Now</label>
                            <button 
                                type="button" 
                                @click="setFullPayment"
                                class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                            >
                                Pay Full (100%)
                            </button>
                        </div>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0"
                            :max="grandTotal"
                            v-model="form.paid_amount" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold text-emerald-600 border border-slate-200 focus:border-indigo-500"
                        />
                    </div>

                    <!-- Due Payable -->
                    <div class="p-4 rounded-xl border" :class="duePayable > 0 ? 'bg-rose-50 border-rose-200' : 'bg-emerald-50 border-emerald-200'">
                        <span class="text-[10px] font-bold uppercase tracking-wider" :class="duePayable > 0 ? 'text-rose-500' : 'text-emerald-500'">
                            Remaining Payable Due
                        </span>
                        <div class="text-xl font-black mt-1" :class="duePayable > 0 ? 'text-rose-700' : 'text-emerald-700'">
                            <CurrencyDisplay :amount="duePayable" />
                        </div>
                    </div>
                </div>

                <!-- Account Selection when paying advance -->
                <div v-if="form.paid_amount > 0" class="mt-6 pt-5 border-t border-slate-100 bg-amber-50/40 -mx-6 -mb-6 p-6 rounded-b-2xl border-dashed">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">#</span>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900">Payment Account / Source Details</h4>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Pay From Account <span class="text-rose-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.financial_account_id"
                                :options="accountOptions"
                                placeholder="Search & select payment account..."
                                searchPlaceholder="Type account name or bank..."
                                :error="form.errors.financial_account_id"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Cheque No / TrxID / Reference
                            </label>
                            <input
                                v-model="form.payment_reference"
                                type="text"
                                placeholder="e.g. Cheque #, bKash TrxID, Bank voucher"
                                class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-1 focus:ring-indigo-500 font-mono placeholder:font-sans placeholder:text-slate-400"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link 
                    :href="route('purchases.index')" 
                    class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors"
                >
                    Cancel
                </Link>
                <button 
                    type="submit" 
                    :disabled="form.processing || grandTotal <= 0"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                >
                    Place Purchase Order
                </button>
            </div>
        </form>
    </AppLayout>
</template>
