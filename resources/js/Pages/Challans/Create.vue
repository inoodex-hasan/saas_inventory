<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BarcodeCameraModal from '@/Components/BarcodeCameraModal.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { playScanSuccess, playScanError } from '@/Utils/sound';

const props = defineProps({
    orders: Array,
    selectedOrderId: Number,
    challanNumber: String,
    todayDate: String,
});

const form = useForm({
    sales_order_id: props.selectedOrderId || '',
    dispatch_date: props.todayDate || new Date().toISOString().slice(0, 10),
    transport_carrier: 'In-house Fleet',
    vehicle_no: '',
    driver_name: '',
    driver_phone: '',
    delivery_address: '',
    notes: '',
    items: [],
});

const selectedOrder = ref(null);

const orderOptions = computed(() => {
    return (props.orders || []).map(o => ({
        id: o.id,
        label: `${o.order_number} — ${o.customer?.name || 'Walk-in'}`,
        sublabel: `Warehouse: ${o.warehouse?.name || 'N/A'}${o.status ? ' • Status: ' + o.status.toUpperCase() : ''}`,
    }));
});

// Barcode Verification System
const isCameraModalOpen = ref(false);
const scanNotification = ref('');

function handleBarcodeScanned(rawCode) {
    if (!rawCode || form.items.length === 0) return;
    const code = rawCode.trim().toLowerCase();

    const item = form.items.find(it => 
        (it.barcode && it.barcode.toLowerCase() === code) ||
        (it.sku && it.sku.toLowerCase() === code)
    );

    if (item) {
        item.verified_count = (item.verified_count || 0) + 1;
        playScanSuccess();
        scanNotification.value = `Verified: 1x "${item.product_name}" (${item.verified_count}/${item.quantity})`;
        setTimeout(() => { scanNotification.value = ''; }, 3500);
        return;
    }

    playScanError();
    scanNotification.value = `Scanned barcode "${rawCode}" is NOT in this delivery order!`;
    setTimeout(() => { scanNotification.value = ''; }, 3500);
}

useBarcodeScanner({
    onScan: (code) => {
        handleBarcodeScanned(code);
    },
});

function updateSelectedOrder() {
    if (!form.sales_order_id) {
        selectedOrder.value = null;
        form.items = [];
        return;
    }
    const order = props.orders.find(o => o.id === parseInt(form.sales_order_id));
    if (order) {
        selectedOrder.value = order;
        form.delivery_address = order.shipping_address || order.customer?.address || '';
        form.items = (order.items || []).map(item => ({
            sales_order_item_id: item.id,
            product_id: item.product_id,
            barcode: item.product?.barcode || '',
            sku: item.product?.sku || '',
            product_name: item.product?.name || `Product #${item.product_id}`,
            quantity: item.quantity,
            verified_count: 0,
        }));
    }
}

watch(() => form.sales_order_id, () => {
    updateSelectedOrder();
}, { immediate: true });

function submit() {
    if (!form.sales_order_id) {
        form.errors.sales_order_id = 'Please select a sales order';
        return;
    }
    form.post(route('challans.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Issue Delivery Challan & Gate Pass" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('challans.index')" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Issue Delivery Challan & Gate Pass</h1>
                        <p class="text-xs text-slate-500 font-medium">Generate shipping manifest and authorization for warehouse goods release</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <form @submit.prevent="submit" class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">
                <!-- Order selection -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Select Sales Order / Invoice *</label>
                        <SearchableSelect
                            v-model="form.sales_order_id"
                            :options="orderOptions"
                            value-key="id"
                            label-key="label"
                            sublabel-key="sublabel"
                            :error="form.errors.sales_order_id"
                        />
                        <div v-if="form.errors.sales_order_id" class="text-xs text-rose-500 mt-1">{{ form.errors.sales_order_id }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Dispatch Date *</label>
                        <input
                            v-model="form.dispatch_date"
                            type="date"
                            required
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-medium"
                        />
                    </div>
                </div>

                <!-- Carrier details -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Transport Carrier</label>
                        <input
                            v-model="form.transport_carrier"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Vehicle Registration #</label>
                        <input
                            v-model="form.vehicle_no"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-mono placeholder:text-slate-400 placeholder:text-xs"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Driver Name & Mobile</label>
                        <div class="flex gap-2">
                            <input
                                v-model="form.driver_name"
                                type="text"
class="w-1/2 text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <input
                                v-model="form.driver_phone"
                                type="text"
class="w-1/2 text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-mono placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Destination Delivery Address *</label>
                        <input
                            v-model="form.delivery_address"
                            type="text"
                            required
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <div v-if="form.errors.delivery_address" class="text-xs text-rose-500 mt-1">{{ form.errors.delivery_address }}</div>
                    </div>
                </div>

                <!-- Transport Packing List -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-xs">Transport Packing List</h2>
                        <button
                            v-if="form.items.length > 0"
                            type="button"
                            @click="isCameraModalOpen = true"
                            class="px-2.5 py-1 rounded-lg bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 text-xs font-semibold flex items-center gap-1.5 transition"
                        >
                            <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Verify by Camera</span>
                        </button>
                    </div>

                    <div v-if="scanNotification" class="p-2.5 rounded-xl bg-cyan-50 border border-cyan-200 text-xs font-bold text-cyan-800 flex items-center gap-2 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                        {{ scanNotification }}
                    </div>

                    <div v-if="form.items.length > 0" class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="p-3.5 bg-slate-50/50 flex items-center justify-between gap-4"
                        >
                            <div class="space-y-0.5">
                                <div class="font-medium text-slate-900 text-sm">
                                    {{ item.product_name }}
                                </div>
                                <span v-if="item.verified_count > 0" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold" :class="item.verified_count >= item.quantity ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                    ✓ {{ item.verified_count }} / {{ item.quantity }} Scanned & Verified
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-slate-500">Dispatch Qty:</label>
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="any"
                                    min="0.01"
                                    required
                                    class="w-24 text-right text-sm font-bold px-2 py-1 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-sm text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Please select a sales order above to load packing list items.
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Special Delivery Remarks</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 placeholder:text-slate-400 placeholder:text-xs"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <Link
                        :href="route('challans.index')"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || form.items.length === 0"
                        class="px-6 py-2.5 rounded-xl font-semibold text-sm text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 shadow-md shadow-cyan-500/20 transition disabled:opacity-40"
                    >
                        Issue & Authorize Challan
                    </button>
                </div>
            </form>
        </div>

        <!-- Camera Scanner Modal -->
        <BarcodeCameraModal
            v-model:open="isCameraModalOpen"
            @scan="handleBarcodeScanned"
        />
    </AppLayout>
</template>
