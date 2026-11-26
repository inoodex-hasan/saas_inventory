<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import { formatDate, formatRelative, daysRemaining } from '@/Utils/date';

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

const expandedProductIds = ref(new Set());

const groupedProducts = computed(() => {
    const groups = new Map();
    const list = props.serials?.data || [];

    for (const s of list) {
        const pid = s.product_id || 0;
        if (!groups.has(pid)) {
            groups.set(pid, {
                product_id: pid,
                product: s.product || { id: pid, name: 'Unknown Product', sku: '' },
                serials: [],
                inStockCount: 0,
                soldCount: 0,
                defectiveCount: 0,
                activeWarrantyCount: 0,
            });
        }
        const g = groups.get(pid);
        g.serials.push(s);
        if (s.status === 'in_stock') g.inStockCount++;
        else if (s.status === 'sold') {
            g.soldCount++;
            if (s.warranty_expires_at && daysRemaining(s.warranty_expires_at) > 0) {
                g.activeWarrantyCount++;
            }
        } else if (s.status === 'defective') g.defectiveCount++;
    }

    return Array.from(groups.values());
});

function toggleProductGroup(productId) {
    const next = new Set(expandedProductIds.value);
    if (next.has(productId)) {
        next.delete(productId);
    } else {
        next.add(productId);
    }
    expandedProductIds.value = next;
}

function isGroupExpanded(productId) {
    return expandedProductIds.value.has(productId);
}

watch(
    () => props.filters?.search,
    (val) => {
        if (val && val.trim() !== '') {
            expandedProductIds.value = new Set(groupedProducts.value.map((g) => g.product_id));
        }
    },
    { immediate: true }
);

const isDefectiveModalOpen = ref(false);
const selectedSerial = ref(null);

const isClaimModalOpen = ref(false);
const availableReplacements = ref([]);
const isLoadingReplacements = ref(false);
const claimForm = useForm({
    claim_type: 'repair',
    reason: '',
    replacement_serial_id: '',
    technician_name: '',
});

const defectiveForm = useForm({
    reason: '',
});

function applyFilter() {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value && status.value !== 'all') params.status = status.value;
    if (warehouseId.value) params.warehouse_id = warehouseId.value;
    router.get(route('warranties.index'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = 'all';
    warehouseId.value = '';
    router.get(route('warranties.index'), {}, { preserveState: false, replace: true });
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

async function openClaimModal(serial) {
    selectedSerial.value = serial;
    claimForm.reset();
    claimForm.clearErrors();
    claimForm.claim_type = 'repair';
    availableReplacements.value = [];
    isClaimModalOpen.value = true;

    if (serial.product_id) {
        isLoadingReplacements.value = true;
        try {
            const res = await axios.get(route('warranties.available-replacements', serial.product_id));
            availableReplacements.value = res.data?.serials || [];
        } catch (e) {
            availableReplacements.value = [];
        } finally {
            isLoadingReplacements.value = false;
        }
    }
}

function submitClaim() {
    if (!selectedSerial.value) return;
    claimForm.post(route('warranties.claim', selectedSerial.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isClaimModalOpen.value = false;
            claimForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Serial Numbers & Warranty Center" />

        <template #header>
            <div class="flex items-center justify-between gap-2 w-full">
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg md:text-xl font-extrabold text-slate-900 tracking-tight truncate">Serial Numbers & Warranty Center</h1>
                </div>
            </div>
        </template>

        <div class="space-y-4 sm:space-y-6">
            <!-- Metrics (2x2 Grid on Mobile, 4-col on Desktop) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <div class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Active Warranty</span>
                    <div class="text-base sm:text-2xl font-black text-emerald-600 mt-1 sm:mt-2">
                        {{ metrics?.active_warranties || 0 }}
                    </div>
                </div>

                <div class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Expiring (30d)</span>
                    <div class="text-base sm:text-2xl font-black text-amber-600 mt-1 sm:mt-2">
                        {{ metrics?.expiring_soon || 0 }}
                    </div>
                </div>

                <div class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Serials In Stock</span>
                    <div class="text-base sm:text-2xl font-black text-blue-600 mt-1 sm:mt-2">
                        {{ metrics?.in_stock_count || 0 }}
                    </div>
                </div>

                <div class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Defective Units</span>
                    <div class="text-base sm:text-2xl font-black text-rose-600 mt-1 sm:mt-2">
                        {{ metrics?.defective_count || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        placeholder="Search serial #, product, customer..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-400 placeholder:text-xs transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-2.5 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 min-w-[120px]"
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
                        class="py-2 pl-2.5 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 min-w-[120px] max-w-xs"
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

            <!-- Table Container -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm min-w-[700px]">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 whitespace-nowrap">Serial Number</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Product Catalog Item</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Status</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Warehouse</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Customer Holder</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Warranty Expiration</th>
                                <th class="py-3.5 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template v-for="group in groupedProducts" :key="`grp-${group.product_id}`">
                                <!-- Cascade Parent Row: Product Group -->
                                <tr
                                    @click="toggleProductGroup(group.product_id)"
                                    class="bg-slate-50/70 hover:bg-indigo-50/40 cursor-pointer transition select-none"
                                >
                                    <td colspan="7" class="py-3 px-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span
                                                    class="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 transition-transform duration-150 shrink-0"
                                                    :class="isGroupExpanded(group.product_id) ? 'rotate-90 text-indigo-600 border-indigo-200' : ''"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-900 text-xs sm:text-sm truncate">{{ group.product.name }}</div>
                                                    <div v-if="group.product.sku" class="text-[11px] font-mono text-slate-400">SKU: {{ group.product.sku }}</div>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                                                <span class="px-2.5 py-0.5 rounded-full bg-slate-200/80 text-slate-700">
                                                    {{ group.serials.length }} {{ group.serials.length === 1 ? 'Serial' : 'Serials' }}
                                                </span>
                                                <span v-if="group.inStockCount > 0" class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200/70">
                                                    {{ group.inStockCount }} In Stock
                                                </span>
                                                <span v-if="group.activeWarrantyCount > 0" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                                    {{ group.activeWarrantyCount }} Active Warranty
                                                </span>
                                                <span v-if="group.defectiveCount > 0" class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/70">
                                                    {{ group.defectiveCount }} Defective
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Cascade Child Rows: Individual Serials -->
                                <tr
                                    v-if="isGroupExpanded(group.product_id)"
                                    v-for="s in group.serials"
                                    :key="s.id"
                                    class="bg-white hover:bg-slate-50/70 transition"
                                >
                                    <td class="py-3 pl-10 pr-4 font-mono font-bold text-slate-900 whitespace-nowrap border-l-2 border-indigo-300">
                                        {{ s.serial_number }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="text-xs text-slate-500">SN #{{ s.id }}</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <StatusBadge :status="s.status" />
                                    </td>
                                    <td class="py-3 px-4 text-xs font-medium text-slate-600 whitespace-nowrap">
                                        {{ s.warehouse?.name || 'Customer Custody' }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div v-if="s.customer" class="font-medium text-slate-900 max-w-[160px] lg:max-w-[220px] truncate" :title="s.customer.name">{{ s.customer.name }}</div>
                                        <div v-if="s.sales_order" class="text-xs text-slate-400">SO: {{ s.sales_order.order_number }}</div>
                                        <span v-if="!s.customer" class="text-xs text-slate-400 italic">Unsold in Depot</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div v-if="s.warranty_expires_at">
                                            <div class="font-semibold text-xs text-slate-800">{{ formatDate(s.warranty_expires_at) }}</div>
                                            <div 
                                                class="text-[11px] font-medium"
                                                :class="daysRemaining(s.warranty_expires_at) > 0 ? 'text-emerald-600' : 'text-rose-600'"
                                            >
                                                {{ daysRemaining(s.warranty_expires_at) > 0 
                                                    ? `Active (${daysRemaining(s.warranty_expires_at)}d left)` 
                                                    : `Expired (${formatRelative(s.warranty_expires_at)})` 
                                                }}
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">N/A</span>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end">
                                            <ActionDropdown align="right">
                                                <button
                                                    v-if="s.status === 'sold'"
                                                    @click="openClaimModal(s)"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-indigo-600 hover:bg-indigo-50 font-semibold transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                    </svg>
                                                    <span>Claim Warranty</span>
                                                </button>
                                                <button
                                                    v-if="s.status !== 'defective'"
                                                    @click="openDefectiveModal(s)"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50 font-semibold transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>Mark Defective</span>
                                                </button>
                                                <Link
                                                    v-if="s.product"
                                                    :href="route('products.index', { search: s.product.sku || s.product.name })"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                    <span>View Product Item</span>
                                                </Link>
                                                <Link
                                                    v-if="s.sales_order"
                                                    :href="route('sales.index', { search: s.sales_order.order_number })"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <span>View Sales Order</span>
                                                </Link>
                                            </ActionDropdown>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!groupedProducts.length">
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

        <!-- Claim Warranty Modal (Repair or Instant Replacement) -->
        <Modal :show="isClaimModalOpen" max-width="lg" @close="isClaimModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Process Warranty Claim</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Serial: <span class="font-mono font-bold text-slate-800">{{ selectedSerial?.serial_number }}</span>
                    &middot; {{ selectedSerial?.product?.name }}
                    <span v-if="selectedSerial?.customer"> &middot; Customer: <strong>{{ selectedSerial.customer.name }}</strong></span>
                </p>

                <form @submit.prevent="submitClaim" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-2">Resolution Type *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                class="p-3 rounded-xl border-2 cursor-pointer transition flex items-start gap-2.5"
                                :class="claimForm.claim_type === 'repair' ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <input type="radio" v-model="claimForm.claim_type" value="repair" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Send to Repair / Service</div>
                                    <div class="text-[11px] text-slate-500">Creates a $0 Warranty Service Ticket</div>
                                </div>
                            </label>

                            <label
                                class="p-3 rounded-xl border-2 cursor-pointer transition flex items-start gap-2.5"
                                :class="claimForm.claim_type === 'replace' ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <input type="radio" v-model="claimForm.claim_type" value="replace" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Instant Replacement</div>
                                    <div class="text-[11px] text-slate-500">Swap with an in-stock serial unit</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-if="claimForm.claim_type === 'repair'">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Assigned Technician (Optional)</label>
                        <input
                            v-model="claimForm.technician_name"
                            type="text"
                            placeholder="e.g. Rahim Technician"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>

                    <div v-if="claimForm.claim_type === 'replace'">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Select Replacement Serial (In Stock) *</label>
                        <div v-if="isLoadingReplacements" class="text-xs text-slate-400 py-2">Loading available stock serials...</div>
                        <select
                            v-else
                            v-model="claimForm.replacement_serial_id"
                            required
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="" disabled>-- Choose replacement serial --</option>
                            <option v-for="rep in availableReplacements" :key="rep.id" :value="rep.id">
                                {{ rep.serial_number }} ({{ rep.warehouse?.name || 'Warehouse' }})
                            </option>
                        </select>
                        <p v-if="!isLoadingReplacements && availableReplacements.length === 0" class="text-[11px] text-rose-600 mt-1">
                            No in-stock serials available for this product.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Issue / Defect Description *</label>
                        <textarea
                            v-model="claimForm.reason"
                            required
                            rows="3"
                            placeholder="Describe the customer's reported issue..."
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isClaimModalOpen = false"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="claimForm.processing || (claimForm.claim_type === 'replace' && !claimForm.replacement_serial_id)"
                            class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            {{ claimForm.processing ? 'Processing...' : 'Submit Warranty Claim' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
