<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    challan: Object,
    orders: Array,
});

const initialDispatchDate = props.challan.dispatch_date
    ? (typeof props.challan.dispatch_date === 'string' && props.challan.dispatch_date.length > 10
        ? props.challan.dispatch_date.slice(0, 10)
        : props.challan.dispatch_date)
    : new Date().toISOString().slice(0, 10);

const form = useForm({
    sales_order_id: props.challan.sales_order_id,
    dispatch_date: initialDispatchDate,
    status: props.challan.status || 'dispatched',
    transport_carrier: props.challan.transport_carrier || 'In-house Fleet',
    vehicle_no: props.challan.vehicle_no || '',
    driver_name: props.challan.driver_name || '',
    driver_phone: props.challan.driver_phone || '',
    delivery_address: props.challan.delivery_address || '',
    notes: props.challan.notes || '',
    items: (props.challan.items || []).map(item => ({
        product_id: item.product_id,
        product_name: item.product?.name || `Product #${item.product_id}`,
        product_code: item.product?.sku || item.product?.code || '',
        quantity: Number(item.quantity) || 1,
        packaging_description: item.packaging_description || '',
        notes: item.notes || '',
    })),
});

function removeItem(index) {
    if (form.items.length <= 1) {
        alert('Challan must contain at least one item.');
        return;
    }
    form.items.splice(index, 1);
}

function submit() {
    form.put(route('challans.update', props.challan.id));
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Delivery Challan ${challan.challan_number}`" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('challans.index')"
                        class="p-2 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                                Edit Challan <span class="font-mono text-cyan-600">{{ challan.challan_number }}</span>
                            </h1>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 text-[11px] font-bold rounded-full capitalize border',
                                    challan.status === 'delivered' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                    challan.status === 'dispatched' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                    'bg-slate-100 text-slate-700 border-slate-200'
                                ]"
                            >
                                {{ challan.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Update logistics routing, driver credentials, and packing quantities</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('challans.pdf', challan.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print PDF Gate Pass</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">
                <!-- Sales Order Reference Info -->
                <div class="bg-slate-50/80 border border-slate-200/80 p-4 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Linked Sales Order</div>
                        <div class="text-sm font-black text-slate-800 mt-0.5">
                            {{ challan.sales_order?.order_number || 'N/A' }}
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            Customer: {{ challan.customer?.name || challan.sales_order?.customer?.name || 'N/A' }}
                            <span v-if="challan.warehouse"> &bull; Warehouse: {{ challan.warehouse?.name }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Status</label>
                            <select
                                v-model="form.status"
                                class="text-xs px-3 py-1.5 bg-white border border-slate-200 rounded-lg font-semibold text-slate-700 focus:ring-1 focus:ring-cyan-500"
                            >
                                <option value="draft">Draft</option>
                                <option value="dispatched">Dispatched</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Logistics & Date Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Dispatch Date *</label>
                        <input
                            v-model="form.dispatch_date"
                            type="date"
                            required
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-medium"
                        />
                        <div v-if="form.errors.dispatch_date" class="text-xs text-rose-500 mt-1">{{ form.errors.dispatch_date }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Transport Carrier</label>
                        <input
                            v-model="form.transport_carrier"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500"
                        />
                    </div>
                </div>

                <!-- Vehicle & Driver Details -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Vehicle Registration #</label>
                        <input
                            v-model="form.vehicle_no"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Driver Name</label>
                        <input
                            v-model="form.driver_name"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Driver Phone #</label>
                        <input
                            v-model="form.driver_phone"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 font-mono"
                        />
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Destination Delivery Address *</label>
                        <input
                            v-model="form.delivery_address"
                            type="text"
                            required
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500"
                        />
                        <div v-if="form.errors.delivery_address" class="text-xs text-rose-500 mt-1">{{ form.errors.delivery_address }}</div>
                    </div>
                </div>

                <!-- Packing List Items -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Transport Packing List</h2>
                        <span class="text-xs text-slate-400">{{ form.items.length }} item(s)</span>
                    </div>

                    <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden bg-slate-50/30">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="p-3.5 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 transition"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-slate-900 text-sm truncate">
                                    {{ item.product_name }}
                                </div>
                                <div v-if="item.product_code" class="text-xs text-slate-400 font-mono">
                                    SKU: {{ item.product_code }}
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-slate-500 font-medium whitespace-nowrap">Dispatch Qty:</label>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        step="any"
                                        min="0.01"
                                        required
                                        class="w-24 text-right text-sm font-bold px-2 py-1 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                    />
                                </div>

                                <button
                                    type="button"
                                    @click="removeItem(idx)"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                    title="Remove item"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.items" class="text-xs text-rose-500">{{ form.errors.items }}</div>
                </div>

                <!-- Notes / Remarks -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Gate Pass Instructions & Remarks</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-cyan-500 placeholder:text-slate-400 placeholder:text-xs"
                    ></textarea>
                </div>

                <!-- Submit / Cancel -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <Link
                        :href="route('challans.index')"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || form.items.length === 0"
                        class="px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 shadow-sm shadow-cyan-600/30 transition disabled:opacity-40"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Challan Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
