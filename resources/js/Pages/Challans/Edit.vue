<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

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
        alert('Delivery challan must contain at least one item.');
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
                                Edit Delivery Challan
                            </h1>
                            <span class="font-mono text-sm font-bold text-cyan-700 bg-cyan-50 px-2.5 py-0.5 rounded-lg border border-cyan-200">
                                {{ challan.challan_number }}
                            </span>
                            <StatusBadge :status="form.status" />
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Update transport logistics, driver credentials, and packing quantities</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('challans.pdf', challan.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print PDF Gate Pass</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Content (Left 2 Columns) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- 1. Linked Sales Order & Customer Recipient Card -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-cyan-50 border border-cyan-100 flex items-center justify-center text-cyan-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Order & Customer Information</h2>
                            </div>
                            <span v-if="challan.sales_order?.order_number" class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                SO: {{ challan.sales_order.order_number }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Customer Recipient</span>
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ challan.customer?.name || challan.sales_order?.customer?.name || 'Walk-in Customer' }}
                                </div>
                                <div v-if="challan.customer?.phone || challan.sales_order?.customer?.phone" class="text-xs text-slate-500">
                                    Phone: {{ challan.customer?.phone || challan.sales_order?.customer?.phone }}
                                </div>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Origin Warehouse / Branch</span>
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ challan.warehouse?.name || challan.sales_order?.warehouse?.name || 'Main Warehouse' }}
                                </div>
                                <div v-if="challan.warehouse?.code" class="text-xs font-mono text-slate-500">
                                    Code: {{ challan.warehouse.code }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                Destination Delivery Address <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.delivery_address"
                                type="text"
                                required
                                placeholder="Enter street, building, city, and delivery destination details"
                                class="w-full text-xs px-3.5 py-2.5 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                            />
                            <p v-if="form.errors.delivery_address" class="text-xs text-rose-500 mt-1">{{ form.errors.delivery_address }}</p>
                        </div>
                    </div>

                    <!-- 2. Transport Packing List Items -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Transport Packing List</h2>
                                    <p class="text-xs text-slate-500">Authorized items and physical package counts for gate dispatch</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                                {{ form.items.length }} Item(s)
                            </span>
                        </div>

                        <div class="overflow-x-auto border border-slate-200/80 rounded-xl">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                        <th class="py-3 px-4">#</th>
                                        <th class="py-3 px-4">Product &amp; SKU</th>
                                        <th class="py-3 px-4 w-36 text-center">Dispatch Qty</th>
                                        <th class="py-3 px-4">Packaging / Container</th>
                                        <th class="py-3 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50/60 transition">
                                        <td class="py-3 px-4 text-slate-400 font-mono text-[11px]">{{ idx + 1 }}</td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-slate-900 text-xs">{{ item.product_name }}</div>
                                            <div v-if="item.product_code" class="text-[11px] font-mono text-slate-400 mt-0.5">
                                                SKU: {{ item.product_code }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                step="any"
                                                min="0.01"
                                                required
                                                class="w-24 text-center font-bold text-xs px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                                            />
                                        </td>
                                        <td class="py-3 px-4">
                                            <input
                                                v-model="item.packaging_description"
                                                type="text"
                                                placeholder="e.g. 2 Cartons, 1 Box"
                                                class="w-full text-xs px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 placeholder:text-slate-400"
                                            />
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button
                                                type="button"
                                                @click="removeItem(idx)"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                title="Remove line item"
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
                        <p v-if="form.errors.items" class="text-xs text-rose-500">{{ form.errors.items }}</p>
                    </div>

                </div>

                <!-- Logistics, Transport & Actions Sidebar (Right Column) -->
                <div class="space-y-6">

                    <!-- 3. Logistics & Transport Details Card -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <div class="w-7 h-7 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Logistics &amp; Carrier</h2>
                        </div>

                        <!-- Status Selector -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Delivery Status</label>
                            <select
                                v-model="form.status"
                                class="w-full text-xs px-3.5 py-2.5 bg-slate-50/70 border border-slate-200 rounded-xl font-bold text-slate-700 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition capitalize"
                            >
                                <option value="draft">Draft</option>
                                <option value="dispatched">Dispatched</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>

                        <!-- Dispatch Date -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                Dispatch Date <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.dispatch_date"
                                type="date"
                                required
                                class="w-full text-xs px-3.5 py-2.5 bg-slate-50/70 border border-slate-200 rounded-xl font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                            />
                            <p v-if="form.errors.dispatch_date" class="text-xs text-rose-500 mt-1">{{ form.errors.dispatch_date }}</p>
                        </div>

                        <!-- Transport Carrier -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Transport Carrier</label>
                            <input
                                v-model="form.transport_carrier"
                                type="text"
                                placeholder="e.g. In-house Fleet, SA Paribahan, Sundarban"
                                class="w-full text-xs px-3.5 py-2.5 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                            />
                        </div>

                        <!-- Vehicle Registration No -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Vehicle Registration #</label>
                            <input
                                v-model="form.vehicle_no"
                                type="text"
                                placeholder="e.g. DHAKA METRO-TA-11-2345"
                                class="w-full text-xs px-3.5 py-2.5 bg-slate-50/70 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                            />
                        </div>

                        <!-- Driver Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Driver Name</label>
                                <input
                                    v-model="form.driver_name"
                                    type="text"
                                    placeholder="Full name"
                                    class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Driver Phone #</label>
                                <input
                                    v-model="form.driver_phone"
                                    type="text"
                                    placeholder="017XXXXXXXX"
                                    class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 4. Gate Pass Instructions Card -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <label class="block text-xs font-bold text-slate-900 uppercase tracking-wider">Gate Pass Remarks &amp; Notes</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Add special handling instructions, security checkpoints, or gate receiver notes..."
                            class="w-full text-xs p-3 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition resize-none placeholder:text-slate-400"
                        ></textarea>
                    </div>

                    <!-- 5. Submit Actions Card -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <button
                            type="submit"
                            :disabled="form.processing || form.items.length === 0"
                            class="w-full py-3 px-4 rounded-xl font-bold text-xs text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 shadow-md shadow-cyan-600/20 transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-40 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Saving Changes...' : 'Save Challan Changes' }}</span>
                        </button>

                        <Link
                            :href="route('challans.index')"
                            class="w-full py-2.5 px-4 text-center block text-xs font-bold text-slate-600 hover:text-slate-900 rounded-xl bg-slate-100 hover:bg-slate-200 transition"
                        >
                            Back to Challan List
                        </Link>
                    </div>

                </div>

            </form>
        </div>
    </AppLayout>
</template>
