<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
            product_name: item.product?.name || `Product #${item.product_id}`,
            quantity: item.quantity,
        }));
    }
}

watch(() => form.sales_order_id, () => {
    updateSelectedOrder();
}, { immediate: true });

function submit() {
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
                        <select
                            v-model="form.sales_order_id"
                            required
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-medium"
                        >
                            <option value="" disabled>-- Select Confirmed Sales Order --</option>
                            <option v-for="o in orders" :key="o.id" :value="o.id">
                                {{ o.order_number }} - {{ o.customer?.name }} (Warehouse: {{ o.warehouse?.name }})
                            </option>
                        </select>
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
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-xs">Transport Packing List</h2>

                    <div v-if="form.items.length > 0" class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="p-3.5 bg-slate-50/50 flex items-center justify-between gap-4"
                        >
                            <div class="font-medium text-slate-900 text-sm">
                                {{ item.product_name }}
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
    </AppLayout>
</template>
