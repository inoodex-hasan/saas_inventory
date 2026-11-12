<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BarcodeCameraModal from '@/Components/BarcodeCameraModal.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { playScanSuccess, playScanError } from '@/Utils/sound';

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

// Barcode Scanning System (Hardware Laser Guns + Camera)
const isCameraModalOpen = ref(false);
const barcodeScanInput = ref('');
const scanNotification = ref('');

function handleBarcodeScanned(rawCode) {
    if (!rawCode) return;
    const code = rawCode.trim().toLowerCase();
    if (!code) return;

    const prod = props.products.find(p => 
        (p.barcode && p.barcode.toLowerCase() === code) ||
        (p.sku && p.sku.toLowerCase() === code)
    );

    if (prod) {
        const existingItem = form.items.find(it => Number(it.product_id) === prod.id);

        if (existingItem) {
            existingItem.quantity_ordered = (Number(existingItem.quantity_ordered) || 0) + 1;
            scanNotification.value = `Increased quantity for "${prod.name}" to ${existingItem.quantity_ordered}.`;
        } else {
            if (form.items.length === 1 && !form.items[0].product_id) {
                form.items[0].product_id = prod.id;
                form.items[0].unit_cost = Number(prod.cost_price) || 0;
                form.items[0].quantity_ordered = 1;
            } else {
                form.items.push({
                    product_id: prod.id,
                    quantity_ordered: 1,
                    unit_cost: Number(prod.cost_price) || 0,
                    batch_number: '',
                    manufacture_date: '',
                    expiry_date: '',
                });
            }
            scanNotification.value = `Added "${prod.name}" to procurement list.`;
        }

        playScanSuccess();
        barcodeScanInput.value = '';

        setTimeout(() => {
            scanNotification.value = '';
        }, 4000);
        return;
    }

    playScanError();
    scanNotification.value = `Product with barcode "${rawCode}" not found in catalog!`;
    setTimeout(() => {
        scanNotification.value = '';
    }, 4000);
}

function handleBarcodeSubmit() {
    if (barcodeScanInput.value.trim()) {
        handleBarcodeScanned(barcodeScanInput.value.trim());
    }
}

// Hardware Barcode Scanner listener (USB / Bluetooth / Wireless Gun)
useBarcodeScanner({
    onScan: (code) => {
        handleBarcodeScanned(code);
    },
});

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
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.new_vendor_address" class="text-[11px] text-rose-600 mt-1">{{ form.errors.new_vendor_address }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                            <input 
                                type="text" 
                                v-model="form.new_vendor_contact" 
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

                <!-- Barcode Quick Entry Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-slate-50 border border-slate-200/90 rounded-xl">
                    <div class="flex items-center gap-2 flex-1 max-w-md">
                        <div class="relative flex-1">
                            <input
                                v-model="barcodeScanInput"
                                @keydown.enter.prevent="handleBarcodeSubmit"
                                type="text"
                                placeholder="Scan barcode to add product (USB Gun or Camera)..."
                                class="w-full pl-9 pr-4 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <button
                            type="button"
                            @click="isCameraModalOpen = true"
                            class="px-3 py-2 rounded-xl bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 border border-slate-200 text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs shrink-0"
                            title="Scan Barcode with Camera"
                        >
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Camera</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <span v-if="scanNotification" class="text-xs font-bold text-indigo-600 animate-pulse flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            {{ scanNotification }}
                        </span>
                        <span v-else class="text-[10px] uppercase font-bold tracking-wider text-slate-400">
                            USB Scanner Ready
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto min-h-[280px]">
                    <table class="w-full min-w-[820px] text-left text-xs table-fixed">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-bold">
                                <th class="py-2.5 pr-2 w-auto min-w-[240px]">Product *</th>
                                <th class="py-2.5 px-1 text-center w-32">Quantity *</th>
                                <th class="py-2.5 px-1 text-right w-28">Unit Cost *</th>
                                <th class="py-2.5 px-1 w-28">Batch / Lot #</th>
                                <th class="py-2.5 px-1 w-32">Expiry Date</th>
                                <th class="py-2.5 pl-2 text-right w-28">Subtotal</th>
                                <th class="py-2.5 text-center w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(it, idx) in form.items" :key="idx" class="align-middle">
                                <td class="py-2.5 pr-2 align-middle min-w-0">
                                    <SearchableSelect 
                                        v-model="it.product_id" 
                                        :options="products"
                                        labelKey="name"
                                        sublabelKey="sku"
                                        size="compact"
                                        @change="() => onProductSelected(it)"
                                    />
                                </td>

                                <td class="py-2.5 px-1 text-center align-middle">
                                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50/80 p-0.5 shadow-2xs">
                                        <button
                                            type="button"
                                            @click="it.quantity_ordered = Math.max(1, (Number(it.quantity_ordered) || 1) - 1)"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 font-bold transition text-xs hover:shadow-xs active:scale-95"
                                            title="Decrease quantity by 1"
                                        >
                                            -
                                        </button>
                                        <input 
                                            type="number" 
                                            step="any" 
                                            min="0.01"
                                            v-model.number="it.quantity_ordered" 
                                            required
                                            class="w-14 h-7 text-center bg-transparent border-0 p-0 text-xs font-mono font-bold text-slate-800 focus:ring-0"
                                        />
                                        <button
                                            type="button"
                                            @click="it.quantity_ordered = (Number(it.quantity_ordered) || 0) + 1"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 font-bold transition text-xs hover:shadow-xs active:scale-95"
                                            title="Increase quantity by 1"
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>

                                <td class="py-2.5 px-1 text-right align-middle">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        v-model="it.unit_cost" 
                                        required
                                        class="w-full h-[34px] px-2 text-right rounded-lg border border-slate-200 text-xs font-mono font-bold text-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    />
                                </td>

                                <td class="py-2.5 px-1 align-middle">
                                    <input 
                                        type="text" 
                                        v-model="it.batch_number" 
                                        class="w-full h-[34px] px-2 rounded-lg border border-slate-200 text-xs font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    />
                                </td>

                                <td class="py-2.5 px-1 align-middle">
                                    <input 
                                        type="date" 
                                        v-model="it.expiry_date" 
                                        class="w-full h-[34px] px-2 rounded-lg border border-slate-200 text-xs font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    />
                                </td>

                                <td class="py-2.5 pl-2 text-right font-mono font-bold text-slate-900 align-middle whitespace-nowrap text-xs">
                                    <CurrencyDisplay :amount="(it.quantity_ordered || 0) * (it.unit_cost || 0)" />
                                </td>

                                <td class="py-2.5 text-center align-middle">
                                    <button 
                                        type="button" 
                                        v-if="form.items.length > 1"
                                        @click="removeItemRow(idx)"
                                        class="text-slate-400 hover:text-rose-600 p-1 rounded-md hover:bg-slate-100 transition inline-flex items-center justify-center"
                                        title="Remove Line"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Financial Summary & Advance Payment -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
                    <!-- Grand Total Card -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/90 flex flex-col justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Procurement Value</span>
                        <div class="text-xl font-black text-slate-900 mt-2">
                            <CurrencyDisplay :amount="grandTotal" />
                        </div>
                    </div>

                    <!-- Advance Paid Card -->
                    <div class="p-4 rounded-xl bg-white border border-slate-200/90 shadow-xs flex flex-col justify-between focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100 transition-all">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Advance Paid Now</span>
                            <button 
                                type="button" 
                                @click="setFullPayment"
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-2 py-0.5 rounded-full transition-all"
                            >
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Pay Full (100%)</span>
                            </button>
                        </div>
                        <div class="relative mt-2 flex items-baseline">
                            <span class="text-slate-400 font-bold text-lg mr-1.5 select-none font-mono">৳</span>
                            <input 
                                type="number" 
                                step="any" 
                                min="0"
                                :max="grandTotal"
                                v-model.number="form.paid_amount" 
                                class="w-full text-xl font-black text-emerald-600 bg-transparent border-0 p-0 focus:ring-0 font-mono"
                            />
                        </div>
                    </div>

                    <!-- Due Payable Card -->
                    <div 
                        class="p-4 rounded-xl border transition-colors flex flex-col justify-between" 
                        :class="duePayable > 0 ? 'bg-rose-50/70 border-rose-200' : 'bg-emerald-50/70 border-emerald-200'"
                    >
                        <span 
                            class="text-[10px] font-bold uppercase tracking-wider" 
                            :class="duePayable > 0 ? 'text-rose-600' : 'text-emerald-600'"
                        >
                            Remaining Payable Due
                        </span>
                        <div 
                            class="text-xl font-black mt-2" 
                            :class="duePayable > 0 ? 'text-rose-700' : 'text-emerald-700'"
                        >
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

        <!-- Camera Barcode Scanner Modal -->
        <BarcodeCameraModal
            v-model:open="isCameraModalOpen"
            @scan="handleBarcodeScanned"
        />
    </AppLayout>
</template>
