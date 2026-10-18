<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    order: Object,
    suppliers: Array,
    warehouses: Array,
    products: Array,
});

const form = useForm({
    supplier_id: props.order.supplier_id || '',
    warehouse_id: props.order.warehouse_id || '',
    order_date: props.order.order_date ? String(props.order.order_date).slice(0, 10) : '',
    expected_delivery_date: props.order.expected_date ? String(props.order.expected_date).slice(0, 10) : '',
    notes: props.order.notes || '',
    items: props.order.items && props.order.items.length > 0
        ? props.order.items.map(it => ({
            id: it.id,
            product_id: it.product_id,
            quantity_ordered: Number(it.quantity),
            unit_cost: Number(it.unit_cost),
            batch_number: it.batch_number || '',
            expiry_date: it.expiry_date ? String(it.expiry_date).slice(0, 10) : '',
            received_quantity: Number(it.received_quantity || 0),
        }))
        : [
            {
                id: null,
                product_id: '',
                quantity_ordered: 1,
                unit_cost: 0,
                batch_number: '',
                expiry_date: '',
                received_quantity: 0,
            },
        ],
});

function addItemRow() {
    form.items.push({
        id: null,
        product_id: '',
        quantity_ordered: 1,
        unit_cost: 0,
        batch_number: '',
        expiry_date: '',
        received_quantity: 0,
    });
}

function removeItemRow(index) {
    const item = form.items[index];
    if (item.received_quantity > 0) {
        alert(`Cannot remove product that has already received ${item.received_quantity} units.`);
        return;
    }
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

const alreadyPaid = computed(() => {
    return Number(props.order.paid_amount) || 0;
});

const duePayable = computed(() => {
    return Math.max(0, grandTotal.value - alreadyPaid.value);
});

function submit() {
    form.put(route('purchases.update', props.order.id));
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Purchase Order ${order.po_number || order.order_number}`" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('purchases.index')" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Edit Purchase Order</h1>
                            <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200">
                                {{ order.po_number || order.order_number }}
                            </span>
                            <StatusBadge :status="order.status" />
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Update vendor information, warehouse allocation, or item quantities</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('purchases.pdf', order.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print PO</span>
                    </a>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. Vendor Selection Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-slate-400">
                    Procurement Partner / Vendor
                </h2>

                <div>
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
            </div>

            <!-- 2. Logistics & Dates -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-slate-400">
                    Logistics & Delivery Schedule
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Fulfillment Hub *</label>
                        <select 
                            v-model="form.warehouse_id" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white"
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
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 font-mono bg-white"
                        />
                        <p v-if="form.errors.order_date" class="text-[11px] text-rose-600 mt-1">{{ form.errors.order_date }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Expected Inbound Delivery</label>
                        <input 
                            type="date" 
                            v-model="form.expected_delivery_date" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 font-mono bg-white"
                        />
                        <p v-if="form.errors.expected_delivery_date" class="text-[11px] text-rose-600 mt-1">{{ form.errors.expected_delivery_date }}</p>
                    </div>
                </div>
            </div>

            <!-- 3. Line Items -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-slate-400">
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
                                <th class="py-2.5 text-center w-28">Quantity *</th>
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
                                    <div v-if="it.received_quantity > 0" class="text-[10px] text-emerald-700 font-semibold mt-1">
                                        Received: {{ it.received_quantity }} units
                                    </div>
                                </td>

                                <td class="py-2.5 px-1 text-center">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        :min="it.received_quantity || 0.01"
                                        v-model.number="it.quantity_ordered" 
                                        required
                                        class="w-full px-2 py-1.5 text-center rounded-lg border border-slate-200 text-xs font-mono font-bold"
                                    />
                                </td>

                                <td class="py-2.5 px-1 text-right">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        v-model.number="it.unit_cost" 
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
                                    <CurrencyDisplay :amount="(Number(it.quantity_ordered) || 0) * (Number(it.unit_cost) || 0)" />
                                </td>

                                <td class="py-2.5 pl-2 text-center">
                                    <button 
                                        type="button" 
                                        @click="removeItemRow(idx)"
                                        :disabled="it.received_quantity > 0"
                                        class="text-slate-400 hover:text-rose-600 disabled:opacity-30 disabled:cursor-not-allowed transition"
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

            <!-- 4. Notes & Order Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Notes -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-3">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-slate-400">
                        Procurement Notes & Terms
                    </h2>
                    <textarea 
                        v-model="form.notes"
                        rows="4"
                        placeholder="Internal instructions for dock receiving, transport specs, warranty remarks..."
                        class="w-full p-3 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400"
                    ></textarea>
                </div>

                <!-- Financial Calculation Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-slate-400">
                        Financial Summary
                    </h2>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Revised Order Total:</span>
                            <span class="font-mono font-bold text-slate-900 text-base">
                                <CurrencyDisplay :amount="grandTotal" />
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-slate-500 border-t border-slate-100 pt-2">
                            <span>Already Disbursed / Paid:</span>
                            <span class="font-mono font-semibold text-emerald-600">
                                <CurrencyDisplay :amount="alreadyPaid" />
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                            <span class="font-bold text-slate-800">Remaining Due:</span>
                            <span 
                                class="font-mono font-black text-base"
                                :class="duePayable > 0 ? 'text-rose-600' : 'text-emerald-600'"
                            >
                                <CurrencyDisplay :amount="duePayable" />
                            </span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <Link 
                            :href="route('purchases.index')"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition"
                        >
                            Cancel
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing || form.items.length === 0"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Update Purchase Order' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
