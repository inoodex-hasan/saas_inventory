<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    warehouses: Object,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const isFormModalOpen = ref(false);
const isInspectModalOpen = ref(false);
const editingWarehouse = ref(null);
const inspectedWarehouse = ref(null);
const inspectedStocks = ref([]);
const isInspectingLoading = ref(false);
const deletingWarehouse = ref(null);
const isDeleting = ref(false);

const form = useForm({
    name: '',
    type: 'outlet',
    code: '',
    address: '',
    phone: '',
    email: '',
    is_default: false,
    is_active: true,
});

function handleSearch() {
    router.get(route('warehouses.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}

function openCreateModal() {
    editingWarehouse.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'outlet';
    form.is_active = true;
    form.is_default = false;
    isFormModalOpen.value = true;
}

function openEditModal(wh) {
    editingWarehouse.value = wh;
    form.clearErrors();
    form.name = wh.name;
    form.type = wh.type || 'outlet';
    form.code = wh.code || '';
    form.address = wh.address || '';
    form.phone = wh.phone || '';
    form.email = wh.email || '';
    form.is_default = Boolean(wh.is_default);
    form.is_active = Boolean(wh.is_active);
    isFormModalOpen.value = true;
}

function submitForm() {
    if (editingWarehouse.value) {
        form.put(route('warehouses.update', editingWarehouse.value.id), {
            onSuccess: () => {
                isFormModalOpen.value = false;
            },
        });
    } else {
        form.post(route('warehouses.store'), {
            onSuccess: () => {
                isFormModalOpen.value = false;
            },
        });
    }
}

function setDefault(wh) {
    router.post(route('warehouses.set-default', wh.id), {}, {
        preserveScroll: true,
    });
}

const optimisticallyHiddenWarehouseIds = ref(new Set());
const displayedWarehouses = computed(() => {
    return (props.warehouses?.data || []).filter(w => !optimisticallyHiddenWarehouseIds.value.has(w.id));
});

function deleteWarehouse(wh) {
    deletingWarehouse.value = wh;
}

function executeDeleteWarehouse() {
    if (!deletingWarehouse.value) return;
    const targetId = deletingWarehouse.value.id;
    // Optimistic UI: card vanishes immediately (0ms)
    optimisticallyHiddenWarehouseIds.value.add(targetId);
    deletingWarehouse.value = null;

    router.delete(route('warehouses.destroy', targetId), {
        preserveScroll: true,
        only: ['warehouses', 'metrics', 'flash'],
        onError: () => {
            optimisticallyHiddenWarehouseIds.value.delete(targetId);
        },
        onFinish: () => {
            optimisticallyHiddenWarehouseIds.value.delete(targetId);
        },
    });
}

async function inspectStock(wh) {
    inspectedWarehouse.value = wh;
    isInspectModalOpen.value = true;
    isInspectingLoading.value = true;
    inspectedStocks.value = [];

    try {
        const response = await axios.get(route('warehouses.inspect', wh.id));
        inspectedStocks.value = response.data.stocks || [];
    } catch (e) {
        console.error('Failed to inspect warehouse stocks', e);
    } finally {
        isInspectingLoading.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Branches & Outlets" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Branches & Fulfillment Locations</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage retail shops (POS counters) and central storage godowns</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Locations</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_warehouses || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">Retail Outlets (POS)</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        {{ metrics?.total_outlets || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Central Godowns</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.total_godowns || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inventory Holding Value</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_valuation || 0" />
                    </div>
                </div>
            </div>

            <!-- Header Action & Search Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="relative w-full sm:w-80">
                    <input 
                        type="text" 
                        v-model="search" 
                        @keyup.enter="handleSearch"
                        placeholder="Search branches or godowns..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all placeholder:text-slate-400"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="handleSearch" 
                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all"
                    >
                        Search
                    </button>
                    <button 
                        @click="openCreateModal" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Branch / Godown</span>
                    </button>
                </div>
            </div>

            <!-- Warehouses Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div 
                    v-for="wh in displayedWarehouses" 
                    :key="wh.id" 
                    class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base leading-snug">{{ wh.name }}</h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-mono text-indigo-600 font-bold">{{ wh.code }}</span>
                                    <span 
                                        v-if="wh.type === 'warehouse'"
                                        class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200"
                                    >
                                        📦 Central Godown
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200"
                                    >
                                        🏪 Retail Outlet
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                <span 
                                    v-if="wh.is_default" 
                                    class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200"
                                >
                                    ★ Default Hub
                                </span>
                                <StatusBadge :status="wh.is_active ? 'active' : 'inactive'" />
                            </div>
                        </div>

                        <div class="text-xs text-slate-500 space-y-1 py-3 border-y border-slate-100">
                            <div v-if="wh.address" class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="truncate">{{ wh.address }}</span>
                            </div>
                            <div v-if="wh.phone" class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>{{ wh.phone }}</span>
                            </div>
                            <div v-if="wh.email" class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="truncate">{{ wh.email }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button 
                            @click="inspectStock(wh)" 
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>Inspect Stock</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <ActionDropdown align="right">
                                <button 
                                    @click="openEditModal(wh)"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit Warehouse</span>
                                </button>
                                <button 
                                    v-if="!wh.is_default" 
                                    @click="setDefault(wh)"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-indigo-600 hover:bg-indigo-50 font-semibold transition-colors"
                                >
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Set as Default Hub</span>
                                </button>
                                <button 
                                    @click="deleteWarehouse(wh)"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50 font-semibold transition-colors"
                                >
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Delete Warehouse</span>
                                </button>
                            </ActionDropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="warehouses.data.length === 0" class="text-center py-16 bg-white rounded-2xl border border-slate-200">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm">No warehouses found</h3>
                <p class="text-xs text-slate-500 mt-1">Get started by creating your first fulfillment center.</p>
                <button 
                    @click="openCreateModal" 
                    class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm"
                >
                    Add Warehouse Hub
                </button>
            </div>
        </div>

        <!-- Create / Edit Warehouse Modal -->
        <Modal :show="isFormModalOpen" @close="isFormModalOpen = false" maxWidth="xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            {{ editingWarehouse ? 'Edit Branch / Godown' : 'Add New Branch / Godown' }}
                        </h2>
                        <p class="text-xs text-slate-500">Configure outlet shop for POS or central godown for bulk holding</p>
                    </div>
                    <button @click="isFormModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Location Type Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Location Purpose & Type *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label 
                                class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="form.type === 'outlet' ? 'bg-indigo-50/70 border-indigo-500 ring-1 ring-indigo-500' : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100/70'"
                            >
                                <input 
                                    type="radio" 
                                    value="outlet" 
                                    v-model="form.type" 
                                    class="mt-0.5 text-indigo-600 focus:ring-indigo-500" 
                                />
                                <div>
                                    <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>🏪 Retail Shop / Outlet</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        Customer-facing store with POS counter & retail stock
                                    </p>
                                </div>
                            </label>

                            <label 
                                class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                                :class="form.type === 'warehouse' ? 'bg-amber-50/70 border-amber-500 ring-1 ring-amber-500' : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100/70'"
                            >
                                <input 
                                    type="radio" 
                                    value="warehouse" 
                                    v-model="form.type" 
                                    class="mt-0.5 text-amber-600 focus:ring-amber-500" 
                                />
                                <div>
                                    <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>📦 Central Godown</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        Bulk storage & supplier receiving hub (Storage only)
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                {{ form.type === 'warehouse' ? 'Godown / Warehouse Name *' : 'Branch / Outlet Name *' }}
                            </label>
                            <input 
                                type="text" 
                                v-model="form.name" 
                                :placeholder="form.type === 'warehouse' ? 'e.g. Central Godown' : 'e.g. Gulshan Outlet'"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-600 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Branch Code *</label>
                            <input 
                                type="text" 
                                v-model="form.code" 
                                :placeholder="form.type === 'warehouse' ? 'e.g. GDW-01' : 'e.g. OUT-01'"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 uppercase font-mono placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.code" class="text-[11px] text-rose-600 mt-1">{{ form.errors.code }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Address / Location</label>
                        <input 
                            type="text" 
                            v-model="form.address" 
                            placeholder="Physical address of the branch or godown"
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <p v-if="form.errors.address" class="text-[11px] text-rose-600 mt-1">{{ form.errors.address }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone</label>
                            <input 
                                type="text" 
                                v-model="form.phone" 
                                placeholder="Branch phone number"
                                class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Email</label>
                            <input 
                                type="email" 
                                v-model="form.email" 
                                placeholder="Branch email address"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>

                    <div class="pt-2 flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.is_default" 
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-xs font-semibold text-slate-700">Set as Primary Default Location</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.is_active" 
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-xs font-semibold text-slate-700">Active</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="isFormModalOpen = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-sm transition-colors disabled:opacity-50"
                        >
                            {{ editingWarehouse ? 'Update Location' : 'Save Location' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Inspect Stock Modal -->
        <Modal :show="isInspectModalOpen" @close="isInspectModalOpen = false" maxWidth="3xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Bin-Level Stock Inspector</span>
                            <span class="text-xs font-mono px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 font-bold">
                                {{ inspectedWarehouse?.name }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500">Live physical on-hand, reserved, and available balance audit</p>
                    </div>
                    <button @click="isInspectModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="isInspectingLoading" class="py-12 text-center text-slate-400 text-xs">
                    Loading warehouse stock levels...
                </div>

                <div v-else-if="inspectedStocks.length > 0" class="overflow-x-auto max-h-96">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-bold sticky top-0 bg-white">
                                <th class="py-2.5">Product & SKU</th>
                                <th class="py-2.5 text-center">On Hand</th>
                                <th class="py-2.5 text-center">Reserved</th>
                                <th class="py-2.5 text-center">Available</th>
                                <th class="py-2.5 text-right">Holding Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in inspectedStocks" :key="item.id" class="hover:bg-slate-50">
                                <td class="py-2.5">
                                    <div class="font-bold text-slate-800">{{ item.product_name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ item.sku }}</div>
                                </td>
                                <td class="py-2.5 text-center font-bold font-mono text-slate-800">
                                    {{ item.quantity_on_hand }}
                                </td>
                                <td class="py-2.5 text-center font-mono text-amber-600 font-semibold">
                                    {{ item.quantity_reserved }}
                                </td>
                                <td class="py-2.5 text-center font-bold font-mono text-emerald-600">
                                    {{ item.quantity_available }}
                                </td>
                                <td class="py-2.5 text-right font-bold font-mono text-slate-900">
                                    <CurrencyDisplay :amount="item.total_value" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-12 text-slate-400 text-xs">
                    No physical stock currently deposited in this warehouse.
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button 
                        @click="isInspectModalOpen = false" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors"
                    >
                        Close Inspector
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingWarehouse"
            title="Delete Warehouse"
            :item-name="deletingWarehouse ? `${deletingWarehouse.name} (${deletingWarehouse.code})` : ''"
            message="Are you sure you want to delete this warehouse? All stock levels and bin records allocated here will be permanently archived."
            confirm-text="Delete Warehouse"
            :processing="isDeleting"
            @confirm="executeDeleteWarehouse"
            @close="deletingWarehouse = null"
        />
    </AppLayout>
</template>
