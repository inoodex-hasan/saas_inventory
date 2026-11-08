<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BarcodeCameraModal from '@/Components/BarcodeCameraModal.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { playScanSuccess, playScanError } from '@/Utils/sound';

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
                    id: null,
                    product_id: prod.id,
                    quantity_ordered: 1,
                    unit_cost: Number(prod.cost_price) || 0,
                    batch_number: '',
                    expiry_date: '',
                    received_quantity: 0,
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
                                    <div v-if="it.received_quantity > 0" class="text-[10px] text-emerald-700 font-semibold mt-1">
                                        Received: {{ it.received_quantity }} units
                                    </div>
                                </td>

                                <td class="py-2.5 px-1 text-center align-middle">
                                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50/80 p-0.5 shadow-2xs">
                                        <button
                                            type="button"
                                            @click="it.quantity_ordered = Math.max(Number(it.received_quantity) || 1, (Number(it.quantity_ordered) || 1) - 1)"
                                            :disabled="Number(it.quantity_ordered) <= (Number(it.received_quantity) || 1)"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed font-bold transition text-xs hover:shadow-xs active:scale-95"
                                            title="Decrease quantity by 1"
                                        >
                                            -
                                        </button>
                                        <input 
                                            type="number" 
                                            step="1" 
                                            :min="it.received_quantity || 0.01"
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
                                        v-model.number="it.unit_cost" 
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
                                    <CurrencyDisplay :amount="(Number(it.quantity_ordered) || 0) * (Number(it.unit_cost) || 0)" />
                                </td>

                                <td class="py-2.5 text-center align-middle">
                                    <button 
                                        type="button" 
                                        @click="removeItemRow(idx)"
                                        :disabled="it.received_quantity > 0"
                                        class="text-slate-400 hover:text-rose-600 disabled:opacity-30 disabled:cursor-not-allowed transition p-1 rounded-md hover:bg-slate-100 inline-flex items-center justify-center"
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

        <!-- Camera Barcode Scanner Modal -->
        <BarcodeCameraModal
            v-model:open="isCameraModalOpen"
            @scan="handleBarcodeScanned"
        />
    </AppLayout>
</template>
