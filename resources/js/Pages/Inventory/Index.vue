<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BarcodeCameraModal from '@/Components/BarcodeCameraModal.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { playScanSuccess, playScanError } from '@/Utils/sound';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    metrics: Object,
    stockItems: Object,
    movements: Object,
    warehouses: Array,
    categories: Array,
    productsList: Array,
    filters: Object,
});

const currentTab = ref(props.filters?.tab || 'stock');
const search = ref(props.filters?.search || '');
const warehouseFilter = ref(props.filters?.warehouse_id || '');
const categoryFilter = ref(props.filters?.category_id || '');

// Modals state
const isAdjustModalOpen = ref(false);
const isTransferModalOpen = ref(false);
const isAuditModalOpen = ref(false);
const isCameraModalOpen = ref(false);

// Audit Cycle Count State
const auditWarehouseId = ref(props.warehouseFilter || props.warehouses[0]?.id || '');
const auditBarcode = ref('');
const auditFeedback = ref(null);
const auditList = ref([]);
const isLookingUp = ref(false);

// Adjust Form
const adjustForm = useForm({
    product_id: '',
    warehouse_id: props.warehouses[0]?.id || '',
    new_quantity: '',
    reason: '',
});

// Transfer Form
const transferForm = useForm({
    product_id: '',
    from_warehouse_id: props.warehouses[0]?.id || '',
    to_warehouse_id: props.warehouses[1]?.id || '',
    quantity: 1,
    reason: '',
});

function handleFilter() {
    router.get(route('inventory.index'), {
        tab: currentTab.value,
        search: search.value || undefined,
        warehouse_id: warehouseFilter.value || undefined,
        category_id: categoryFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function setTab(tab) {
    currentTab.value = tab;
    handleFilter();
}

function clearFilters() {
    search.value = '';
    warehouseFilter.value = '';
    categoryFilter.value = '';
    router.get(route('inventory.index'), { tab: currentTab.value }, { preserveState: false, replace: true, preserveScroll: true });
}

function openAdjustModal(product = null) {
    adjustForm.reset();
    adjustForm.clearErrors();
    if (product) {
        adjustForm.product_id = product.id;
    }
    adjustForm.warehouse_id = warehouseFilter.value || props.warehouses[0]?.id || '';
    isAdjustModalOpen.value = true;
}

function openTransferModal(product = null) {
    transferForm.reset();
    transferForm.clearErrors();
    if (product) {
        transferForm.product_id = product.id;
    }
    transferForm.from_warehouse_id = warehouseFilter.value || props.warehouses[0]?.id || '';
    transferForm.to_warehouse_id = props.warehouses.find(w => w.id !== transferForm.from_warehouse_id)?.id || '';
    transferForm.quantity = 1;
    isTransferModalOpen.value = true;
}

function openAuditModal() {
    auditWarehouseId.value = warehouseFilter.value || props.warehouses[0]?.id || '';
    auditFeedback.value = null;
    isAuditModalOpen.value = true;
}

function updateAuditCount(item, change) {
    item.counted_qty = Math.max(0, Number((item.counted_qty + change).toFixed(2)));
    item.variance = Number((item.counted_qty - item.system_stock).toFixed(2));
    item.reconciled = item.variance === 0;
}

function onCountInput(item) {
    item.counted_qty = Math.max(0, Number(item.counted_qty || 0));
    item.variance = Number((item.counted_qty - item.system_stock).toFixed(2));
    item.reconciled = item.variance === 0;
}

function reconcileItem(item) {
    if (item.reconciling) return;
    item.reconciling = true;
    router.post(route('inventory.adjust'), {
        warehouse_id: auditWarehouseId.value,
        product_id: item.product_id,
        new_quantity: item.counted_qty,
        reason: 'Physical stock audit cycle count scan reconciliation',
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            item.reconciling = false;
            item.reconciled = true;
            item.system_stock = item.counted_qty;
            item.variance = 0;
            playScanSuccess();
            auditFeedback.value = {
                type: 'success',
                text: `Successfully reconciled stock for "${item.name}" to ${item.counted_qty} units.`
            };
        },
        onError: (errors) => {
            item.reconciling = false;
            playScanError();
            auditFeedback.value = {
                type: 'error',
                text: 'Adjustment failed: ' + (Object.values(errors)[0] || 'Unknown error')
            };
        }
    });
}

function removeAuditItem(index) {
    auditList.value.splice(index, 1);
}

function clearAuditSheet() {
    auditList.value = [];
    auditFeedback.value = null;
}

async function handleBarcode(code) {
    if (!code || !code.trim()) return;
    const clean = code.trim();

    if (isAuditModalOpen.value) {
        await processAuditScan(clean);
    } else {
        search.value = clean;
        handleFilter();
        playScanSuccess();
    }
}

async function processAuditScan(code) {
    auditFeedback.value = null;

    // Check if already in audit list
    const existing = auditList.value.find(i =>
        (i.barcode && i.barcode.toLowerCase() === code.toLowerCase()) ||
        (i.sku && i.sku.toLowerCase() === code.toLowerCase())
    );

    if (existing) {
        existing.counted_qty += 1;
        existing.variance = Number((existing.counted_qty - existing.system_stock).toFixed(2));
        existing.reconciled = existing.variance === 0;
        playScanSuccess();
        auditFeedback.value = {
            type: 'success',
            text: `Count incremented for "${existing.name}" (Counted: ${existing.counted_qty} | System: ${existing.system_stock})`
        };
        auditBarcode.value = '';
        return;
    }

    // Lookup via API for stock level at selected warehouse
    isLookingUp.value = true;
    try {
        const response = await axios.get(route('products.lookup-barcode'), {
            params: { barcode: code }
        });

        if (response.data && response.data.found && response.data.product) {
            const prod = response.data.product;
            const sl = prod.stock_levels?.find(s => String(s.warehouse_id) === String(auditWarehouseId.value));
            const systemStock = sl ? Number(sl.quantity) : 0;
            const counted = 1;
            const variance = Number((counted - systemStock).toFixed(2));

            auditList.value.unshift({
                product_id: prod.id,
                name: prod.name,
                sku: prod.sku,
                barcode: prod.barcode || code,
                category: prod.category?.name || 'General',
                unit: prod.unit?.short_name || 'pcs',
                system_stock: systemStock,
                counted_qty: counted,
                variance: variance,
                reconciling: false,
                reconciled: variance === 0
            });

            playScanSuccess();
            auditFeedback.value = {
                type: 'success',
                text: `Added "${prod.name}" to audit sheet (Counted: 1 | System: ${systemStock})`
            };
        } else {
            // Check local productsList fallback
            const local = (props.productsList || []).find(p =>
                (p.barcode && p.barcode.toLowerCase() === code.toLowerCase()) ||
                (p.sku && p.sku.toLowerCase() === code.toLowerCase())
            );
            if (local) {
                auditList.value.unshift({
                    product_id: local.id,
                    name: local.name,
                    sku: local.sku,
                    barcode: local.barcode || code,
                    category: 'General',
                    unit: 'pcs',
                    system_stock: 0,
                    counted_qty: 1,
                    variance: 1,
                    reconciling: false,
                    reconciled: false
                });
                playScanSuccess();
                auditFeedback.value = {
                    type: 'success',
                    text: `Added "${local.name}" to audit sheet (Counted: 1)`
                };
            } else {
                playScanError();
                auditFeedback.value = {
                    type: 'error',
                    text: `Barcode or SKU "${code}" not found in catalog.`
                };
            }
        }
    } catch (err) {
        console.error('Audit lookup error:', err);
        playScanError();
        auditFeedback.value = {
            type: 'error',
            text: `Lookup failed for barcode "${code}".`
        };
    } finally {
        isLookingUp.value = false;
        auditBarcode.value = '';
    }
}

// Global Hardware Scanner Composable
useBarcodeScanner({
    onScan: (code) => {
        handleBarcode(code);
    },
    enabled: true,
});

function submitAdjust() {
    adjustForm.post(route('inventory.adjust'), {
        preserveScroll: true,
        onSuccess: () => {
            isAdjustModalOpen.value = false;
            adjustForm.reset();
        },
    });
}

function submitTransfer() {
    transferForm.post(route('inventory.transfer'), {
        preserveScroll: true,
        onSuccess: () => {
            isTransferModalOpen.value = false;
            transferForm.reset();
        },
    });
}

const productOptions = computed(() => {
    return (props.productsList || []).map(p => ({
        id: p.id,
        name: p.name,
        sku: p.sku,
        barcode: p.barcode,
        cost_price: p.cost_price,
    }));
});
</script>

<template>
    <AppLayout>
        <Head title="Stock & Inventory Balances" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Stock & Inventory Balances</h1>
                    <p class="text-xs text-slate-500 font-medium">Real-time warehouse stock tracking, holding valuation & inventory audit reconciliation</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- 1. KPI Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Stock On Hand</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ Number(metrics?.total_units || 0).toLocaleString() }} <span class="text-xs font-normal text-slate-500">units</span>
                    </div>
                    <div v-if="metrics?.total_incoming_units > 0" class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-amber-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        <span>+{{ Number(metrics.total_incoming_units).toLocaleString() }} on order (incoming)</span>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inventory Holding Valuation</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_valuation || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Low Stock Warnings</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.low_stock_count || 0 }} <span class="text-xs font-normal text-slate-500">items</span>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Out of Stock Depleted</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        {{ metrics?.out_of_stock_count || 0 }} <span class="text-xs font-normal text-slate-500">items</span>
                    </div>
                </div>
            </div>

            <!-- 2. Tabs & Toolbar Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Navigation Tabs -->
                <div class="inline-flex p-1 bg-slate-200/70 rounded-xl text-xs font-bold">
                    <button
                        type="button"
                        @click="setTab('stock')"
                        class="px-4 py-2 rounded-lg transition-all"
                        :class="currentTab === 'stock' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Stock Balances & Valuation
                    </button>
                    <button
                        type="button"
                        @click="setTab('ledger')"
                        class="px-4 py-2 rounded-lg transition-all"
                        :class="currentTab === 'ledger' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Movement Audit Ledger
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <button
                        @click="openAuditModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold shadow-sm shadow-emerald-500/20 transition-all"
                        title="Open physical stock audit cycle count scanner"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        <span>Stock Audit Scan</span>
                    </button>

                    <button
                        @click="openTransferModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-all"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span>Transfer Stock</span>
                    </button>

                    <button
                        @click="openAdjustModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>
                </div>
            </div>

            <!-- 3. Search & Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="handleFilter"
                            placeholder="Search by name, SKU or scan barcode..."
                            class="w-full pl-9 pr-9 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button
                            type="button"
                            @click="isCameraModalOpen = true"
                            title="Scan barcode with camera"
                            class="absolute right-2.5 top-2 p-0.5 text-slate-400 hover:text-indigo-600 transition"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>

                    <select
                        v-model="warehouseFilter"
                        @change="handleFilter"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 transition"
                    >
                        <option value="">All Warehouses</option>
                        <option v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                            {{ w.name }} ({{ w.code }})
                        </option>
                    </select>

                    <select
                        v-if="currentTab === 'stock'"
                        v-model="categoryFilter"
                        @change="handleFilter"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 transition"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                            {{ cat.name }}
                        </option>
                    </select>

                    <button
                        v-if="search || warehouseFilter || categoryFilter"
                        @click="clearFilters"
                        class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- 4. TAB 1: Stock Balances & Valuation Table -->
            <div v-if="currentTab === 'stock'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Item & Catalog</th>
                                <th class="py-3 px-4 whitespace-nowrap">Warehouse Distribution</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">On Hand</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Incoming</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Reserved</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Available</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Cost Price</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Holding Value</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Health</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in stockItems.data" :key="item.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 text-sm leading-snug max-w-[200px] lg:max-w-[260px] truncate" :title="item.name">{{ item.name }}</div>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400 font-mono">
                                        <span>SKU: <span class="text-slate-600 font-semibold">{{ item.sku }}</span></span>
                                        <span v-if="item.barcode">&bull; Barcode: {{ item.barcode }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span v-if="item.category" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                            {{ item.category }}
                                        </span>
                                        <span v-if="item.brand" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                            {{ item.brand }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div v-if="item.warehouses && item.warehouses.length > 0" class="flex flex-wrap gap-1.5 max-w-xs">
                                        <span
                                            v-for="wh in item.warehouses"
                                            :key="wh.warehouse_id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-50 border border-slate-200 whitespace-nowrap"
                                        >
                                            <span class="text-slate-500">{{ wh.warehouse_name }}:</span>
                                            <span class="font-bold text-slate-900">{{ wh.quantity }}</span>
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-300 font-mono text-[11px]">— No stock assigned —</span>
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-900 text-sm whitespace-nowrap">
                                    {{ item.on_hand }}
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono whitespace-nowrap">
                                    <span
                                        v-if="item.incoming > 0"
                                        class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md text-[11px] border border-amber-200/80"
                                        title="Ordered from vendor (pending warehouse receipt)"
                                    >
                                        <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        <span>+{{ item.incoming }}</span>
                                    </span>
                                    <span v-else class="text-slate-300 text-xs">0</span>
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono text-slate-400 whitespace-nowrap">
                                    {{ item.reserved > 0 ? item.reserved : '0' }}
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono font-bold text-indigo-600 text-sm whitespace-nowrap">
                                    {{ item.available }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-700 whitespace-nowrap">
                                    <CurrencyDisplay :amount="item.cost_price" />
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <CurrencyDisplay :amount="item.valuation" />
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        v-if="item.status === 'in_stock'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100"
                                    >
                                        In Stock
                                    </span>
                                    <span
                                        v-else-if="item.status === 'low_stock'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-100 animate-pulse"
                                    >
                                        Low Stock
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100"
                                    >
                                        Depleted
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="stockItems.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No inventory records match the selected filters.
                </div>

                <!-- Stock Pagination -->
                <div v-if="stockItems.links && stockItems.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ stockItems.from }} to {{ stockItems.to }} of {{ stockItems.total }} products
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in stockItems.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="link.active ? 'bg-indigo-600 text-white' : (link.url ? 'text-slate-600 hover:bg-slate-200' : 'text-slate-300 cursor-not-allowed')"
                        />
                    </div>
                </div>
            </div>

            <!-- 5. TAB 2: Movement Audit Ledger Table -->
            <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Timestamp</th>
                                <th class="py-3 px-4 whitespace-nowrap">Product Item</th>
                                <th class="py-3 px-4 whitespace-nowrap">Warehouse</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Movement Type</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Quantity</th>
                                <th class="py-3 px-4 whitespace-nowrap">Reason / Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ formatDate(m.created_at) }}
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 max-w-[180px] lg:max-w-[240px] truncate" :title="m.product?.name || 'Deleted Product'">{{ m.product?.name || 'Deleted Product' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ m.product?.sku }}</div>
                                </td>

                                <td class="py-3 px-4 font-medium text-slate-700 whitespace-nowrap">
                                    <span class="max-w-[150px] truncate block" :title="m.warehouse?.name || 'Unknown Hub'">{{ m.warehouse?.name || 'Unknown Hub' }}</span>
                                </td>

                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-100': m.type === 'in',
                                            'bg-rose-50 text-rose-700 border border-rose-100': m.type === 'out',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-100': m.type === 'transfer' || m.type === 'transfer_in' || m.type === 'transfer_out',
                                            'bg-amber-50 text-amber-700 border border-amber-100': m.type === 'adjustment',
                                        }"
                                    >
                                        {{ m.type }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center font-mono font-bold text-sm whitespace-nowrap" :class="Number(m.quantity) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ Number(m.quantity) > 0 ? '+' : '' }}{{ Number(m.quantity) }}
                                </td>

                                <td class="py-3 px-4 text-slate-600 max-w-[200px] lg:max-w-[280px] truncate" :title="m.reason || m.reference_type">
                                    {{ (m.reason || m.reference_type) ? ((m.reason || m.reference_type).length > 50 ? (m.reason || m.reference_type).slice(0, 50) + '...' : (m.reason || m.reference_type)) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="movements.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No stock movements recorded yet.
                </div>

                <!-- Ledger Pagination -->
                <div v-if="movements.links && movements.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ movements.from }} to {{ movements.to }} of {{ movements.total }} records
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in movements.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="link.active ? 'bg-indigo-600 text-white' : (link.url ? 'text-slate-600 hover:bg-slate-200' : 'text-slate-300 cursor-not-allowed')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Stock Modal -->
        <Modal :show="isAdjustModalOpen" @close="isAdjustModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Reconcile / Adjust Physical Stock</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Update physical on-hand counts with documented audit reason</p>
                    </div>
                    <button @click="isAdjustModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAdjust" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Select Product *</label>
                        <SearchableSelect
                            v-model="adjustForm.product_id"
                            :options="productOptions"
                            labelKey="name"
                            sublabelKey="sku"
                            :error="adjustForm.errors.product_id"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fulfillment Hub / Warehouse *</label>
                        <select
                            v-model="adjustForm.warehouse_id"
                            required
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }} ({{ w.code }})
                            </option>
                        </select>
                        <span v-if="adjustForm.errors.warehouse_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ adjustForm.errors.warehouse_id }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Physical Count *</label>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            v-model.number="adjustForm.new_quantity"
                            required
                            class="w-full px-3.5 py-2 rounded-xl text-xs font-mono font-bold border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                        <span v-if="adjustForm.errors.new_quantity" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ adjustForm.errors.new_quantity }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Adjustment *</label>
                        <textarea
                            v-model="adjustForm.reason"
                            required
                            rows="2"
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 resize-none"
                        ></textarea>
                        <span v-if="adjustForm.errors.reason" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ adjustForm.errors.reason }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAdjustModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="adjustForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Transfer Stock Modal -->
        <Modal :show="isTransferModalOpen" @close="isTransferModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Inter-Warehouse Stock Transfer</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Move inventory units atomically between two fulfillment hubs</p>
                    </div>
                    <button @click="isTransferModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Select Product *</label>
                        <SearchableSelect
                            v-model="transferForm.product_id"
                            :options="productOptions"
                            labelKey="name"
                            sublabelKey="sku"
                            :error="transferForm.errors.product_id"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">From Hub *</label>
                            <select
                                v-model="transferForm.from_warehouse_id"
                                required
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                            <span v-if="transferForm.errors.from_warehouse_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                                {{ transferForm.errors.from_warehouse_id }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">To Hub *</label>
                            <select
                                v-model="transferForm.to_warehouse_id"
                                required
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                            <span v-if="transferForm.errors.to_warehouse_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                                {{ transferForm.errors.to_warehouse_id }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Quantity to Transfer *</label>
                        <input
                            type="number"
                            step="any"
                            min="0.01"
                            v-model.number="transferForm.quantity"
                            required
                            class="w-full px-3.5 py-2 rounded-xl text-xs font-mono font-bold border border-slate-200 focus:border-indigo-500"
                        />
                        <span v-if="transferForm.errors.quantity" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ transferForm.errors.quantity }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Transfer Notes (Optional)</label>
                        <textarea
                            v-model="transferForm.reason"
                            rows="2"
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isTransferModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="transferForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            Execute Transfer
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Warehouse Stock Audit / Cycle Count Modal -->
        <Modal :show="isAuditModalOpen" @close="isAuditModalOpen = false" max-width="4xl">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-4 border-b border-slate-100 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-extrabold text-slate-900">Stock Audit & Cycle Count Scanner</h2>
                                <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded-full animate-pulse">Live Hardware / Camera Ready</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Scan product barcodes continuously to audit physical on-hand stock and reconcile discrepancies.</p>
                        </div>
                    </div>
                    <button @click="isAuditModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Scanner Controls & Warehouse Switcher -->
                <div class="space-y-3 mb-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Target Audit Warehouse *</label>
                            <select
                                v-model="auditWarehouseId"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-emerald-500 font-medium"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }} ({{ w.code }})
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Barcode / SKU Scanner (Auto-records on Enter or Laser scan)</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input
                                        v-model="auditBarcode"
                                        @keyup.enter="handleBarcode(auditBarcode)"
                                        type="text"
                                        placeholder="Scan barcode label or type SKU..."
                                        class="w-full pl-9 pr-4 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                                    />
                                    <svg class="w-4 h-4 text-emerald-600 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                                <button
                                    type="button"
                                    @click="isCameraModalOpen = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                                >
                                    <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Camera</span>
                                </button>
                                <button
                                    v-if="auditList.length > 0"
                                    type="button"
                                    @click="clearAuditSheet"
                                    class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 text-xs font-semibold transition"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Scan Feedback Banner -->
                    <div
                        v-if="auditFeedback"
                        class="p-2.5 rounded-xl text-xs font-medium flex items-center justify-between"
                        :class="auditFeedback.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'"
                    >
                        <span>{{ auditFeedback.text }}</span>
                        <button @click="auditFeedback = null" class="text-xs font-bold hover:underline">Dismiss</button>
                    </div>
                </div>

                <!-- Live Audit Summary Tally -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Items Audited</span>
                        <div class="text-lg font-black text-slate-900 mt-0.5">{{ auditList.length }}</div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Units Counted</span>
                        <div class="text-lg font-black text-indigo-600 mt-0.5">
                            {{ auditList.reduce((acc, i) => acc + (Number(i.counted_qty) || 0), 0) }}
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Variances to Reconcile</span>
                        <div class="text-lg font-black mt-0.5" :class="auditList.some(i => i.variance !== 0 && !i.reconciled) ? 'text-rose-600' : 'text-emerald-600'">
                            {{ auditList.filter(i => i.variance !== 0 && !i.reconciled).length }}
                        </div>
                    </div>
                </div>

                <!-- Audit Items Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto mb-4">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/90 text-slate-500 uppercase text-[10px] font-bold sticky top-0 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Product Catalog Item</th>
                                <th class="py-2.5 px-3 text-center">System Stock</th>
                                <th class="py-2.5 px-3 text-center">Audited Count</th>
                                <th class="py-2.5 px-3 text-center">Variance</th>
                                <th class="py-2.5 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in auditList" :key="item.product_id" class="hover:bg-slate-50/60 transition">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900">{{ item.name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono flex items-center gap-2">
                                        <span>SKU: {{ item.sku }}</span>
                                        <span v-if="item.barcode" class="text-indigo-600 font-semibold">• Barcode: {{ item.barcode }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-center font-bold text-slate-600">
                                    {{ item.system_stock }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <div class="inline-flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white">
                                        <button
                                            type="button"
                                            @click="updateAuditCount(item, -1)"
                                            class="w-7 h-7 flex items-center justify-center text-slate-500 hover:bg-slate-100 text-sm font-bold"
                                        >-</button>
                                        <input
                                            type="number"
                                            v-model.number="item.counted_qty"
                                            @input="onCountInput(item)"
                                            min="0"
                                            class="w-14 text-center font-mono font-bold text-xs border-0 focus:ring-0 p-0"
                                        />
                                        <button
                                            type="button"
                                            @click="updateAuditCount(item, 1)"
                                            class="w-7 h-7 flex items-center justify-center text-slate-500 hover:bg-slate-100 text-sm font-bold"
                                        >+</button>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <span
                                        v-if="item.variance === 0 || item.reconciled"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800"
                                    >
                                        ✓ Match (0)
                                    </span>
                                    <span
                                        v-else-if="item.variance < 0"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800"
                                    >
                                        Missing {{ Math.abs(item.variance) }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800"
                                    >
                                        Over +{{ item.variance }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="item.variance !== 0 && !item.reconciled"
                                            @click="reconcileItem(item)"
                                            :disabled="item.reconciling"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-xs transition disabled:opacity-50"
                                        >
                                            <span v-if="item.reconciling">Updating...</span>
                                            <span v-else>Reconcile</span>
                                        </button>
                                        <span
                                            v-else-if="item.reconciled"
                                            class="text-[11px] text-emerald-600 font-bold"
                                        >
                                            Reconciled ✓
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeAuditItem(idx)"
                                            class="p-1 text-slate-400 hover:text-rose-500 rounded transition"
                                            title="Remove item"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="auditList.length === 0">
                                <td colspan="5" class="py-8 text-center text-xs text-slate-400">
                                    No items audited yet. Point your barcode scanner or camera at product packaging to start counting.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <p class="text-[11px] text-slate-400">
                        Adjustments post atomic balance corrections to the inventory movement audit log.
                    </p>
                    <button
                        type="button"
                        @click="isAuditModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                    >
                        Done / Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Camera Scanner Modal -->
        <BarcodeCameraModal
            v-model:open="isCameraModalOpen"
            @scan="handleBarcode"
        />
    </AppLayout>
</template>
