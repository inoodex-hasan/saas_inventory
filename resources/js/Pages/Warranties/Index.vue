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
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Warranty</p>
                        <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ metrics.active_warranties }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Expiring Soon (30d)</p>
                        <p class="text-xl font-bold text-amber-600 mt-0.5">{{ metrics.expiring_soon }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Serials In Stock</p>
                        <p class="text-xl font-bold text-blue-600 mt-0.5">{{ metrics.in_stock_count }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Defective RMA Units</p>
                        <p class="text-xl font-bold text-rose-600 mt-0.5">{{ metrics.defective_count }}</p>
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
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
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
                            placeholder="e.g. Device fails POST, burned capacitors, physical screen crack..."
                            class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500"
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
