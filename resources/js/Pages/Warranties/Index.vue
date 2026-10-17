<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    page_title: String,
    warranty_labels: Object,
    serials: Object,
    warehouses: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const warehouseId = ref(props.filters.warehouse_id || '');

const isDefectiveModalOpen = ref(false);
const selectedSerial = ref(null);

const defectiveForm = useForm({
    reason: '',
});

function applyFilter() {
    router.get(route('warranties.index'), {
        search: search.value,
        status: status.value,
        warehouse_id: warehouseId.value,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = 'all';
    warehouseId.value = '';
    applyFilter();
}

function openDefectiveModal(serial) {
    selectedSerial.value = serial;
    defectiveForm.reason = '';
    isDefectiveModalOpen.value = true;
}

function submitDefective() {
    if (!selectedSerial.value) return;
    defectiveForm.post(route('warranties.defective', selectedSerial.value.id), {
        onSuccess: () => {
            isDefectiveModalOpen.value = false;
            defectiveForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Serial Numbers & Warranty Center" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Serial Numbers & Warranty Center</h1>
                    <p class="text-xs text-slate-500 font-medium">Audit individual item serials, customer warranty periods, and RMA dispositions</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Warranty</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        {{ metrics?.active_warranties || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Expiring Soon (30d)</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.expiring_soon || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Serials In Stock</span>
                    <div class="text-2xl font-black text-blue-600 mt-2">
                        {{ metrics?.in_stock_count || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Defective RMA Units</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        {{ metrics?.defective_count || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center gap-2">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        placeholder="Search serial number, product name, customer..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 placeholder:text-slate-400 placeholder:text-xs transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 min-w-[140px]"
                    >
                        <option value="all">All Serials</option>
                        <option value="active">Active Warranty</option>
                        <option value="expiring_soon">Expiring Soon (30d)</option>
                        <option value="expired">Expired Warranty</option>
                        <option value="in_stock">In Warehouse Stock</option>
                        <option value="defective">Marked Defective</option>
                    </select>

                    <select
                        v-model="warehouseId"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 min-w-[135px] max-w-xs"
                    >
                        <option value="">All Warehouses</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </select>

                    <button
                        @click="resetFilter"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Serial Number</th>
                                <th class="py-3.5 px-4">Product Catalog Item</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Warehouse</th>
                                <th class="py-3.5 px-4">Customer Holder</th>
                                <th class="py-3.5 px-4">Warranty Expiration</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="s in serials.data" :key="s.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ s.serial_number }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900">{{ s.product?.name }}</div>
                                    <div class="text-xs text-slate-400">SKU: {{ s.product?.sku }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="s.status" />
                                </td>
                                <td class="py-3.5 px-4 text-xs font-medium text-slate-600">
                                    {{ s.warehouse?.name || 'Customer Custody' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div v-if="s.customer" class="font-medium text-slate-900">{{ s.customer.name }}</div>
                                    <div v-if="s.sales_order" class="text-xs text-slate-400">SO: {{ s.sales_order.order_number }}</div>
                                    <span v-if="!s.customer" class="text-xs text-slate-400 italic">Unsold in Depot</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div v-if="s.warranty_expires_at">
                                        <div class="font-semibold text-xs text-slate-800">{{ s.warranty_expires_at }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ new Date(s.warranty_expires_at) > new Date() ? 'Warranty Active' : 'Warranty Expired' }}
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic">N/A</span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button
                                        v-if="s.status !== 'defective'"
                                        @click="openDefectiveModal(s)"
                                        class="px-2.5 py-1 text-xs font-semibold text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition"
                                    >
                                        Mark Defective
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!serials.data || serials.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400 font-medium">
                                    No serial numbers found matching your query.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="serials.links && serials.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>Showing {{ serials.from || 0 }} to {{ serials.to || 0 }} of {{ serials.total }} serials</div>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, idx) in serials.links"
                            :key="idx"
                            @click="router.visit(link.url)"
                            :disabled="!link.url"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition',
                                link.active ? 'bg-emerald-600 text-white font-bold' : 'hover:bg-slate-100 text-slate-700',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Defective Modal -->
        <Modal :show="isDefectiveModalOpen" max-width="md" @close="isDefectiveModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Mark Serial As Defective</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Serial: <span class="font-mono font-bold text-slate-800">{{ selectedSerial?.serial_number }}</span>
                    ({{ selectedSerial?.product?.name }})
                </p>

                <form @submit.prevent="submitDefective" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Defect Diagnostic / Failure Reason *</label>
                        <textarea
                            v-model="defectiveForm.reason"
                            required
                            rows="3"
                            placeholder="e.g. Device fails POST or physical damage"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-rose-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isDefectiveModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="defectiveForm.processing"
                            class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition"
                        >
                            Confirm Defective Status
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
