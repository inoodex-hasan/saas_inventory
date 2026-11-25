<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    fields: {
        type: Array,
        default: () => [],
    },
});

// Search Filter
const search = ref('');

const filteredFields = computed(() => {
    if (!search.value.trim()) return props.fields || [];
    const q = search.value.toLowerCase();
    return (props.fields || []).filter(f =>
        f.name?.toLowerCase().includes(q) ||
        f.applies_to?.toLowerCase().includes(q) ||
        f.type?.toLowerCase().includes(q)
    );
});

// Metric Summary Counts
const totalCount = computed(() => props.fields?.length || 0);
const salesCount = computed(() => (props.fields || []).filter(f => f.applies_to === 'sales' || f.applies_to === 'both').length);
const purchaseCount = computed(() => (props.fields || []).filter(f => f.applies_to === 'purchases' || f.applies_to === 'both').length);
const activeCount = computed(() => (props.fields || []).filter(f => f.is_active).length);

// Modal State
const isModalOpen = ref(false);
const editingField = ref(null);
const deletingField = ref(null);

const form = useForm({
    name: '',
    applies_to: 'sales',
    type: 'fixed',
    default_amount: 0,
    show_in_pos: true,
    is_active: true,
    sort_order: 0,
});

function openAddModal() {
    editingField.value = null;
    form.reset();
    form.clearErrors();
    form.applies_to = 'sales';
    form.type = 'fixed';
    form.default_amount = 0;
    form.show_in_pos = true;
    form.is_active = true;
    form.sort_order = props.fields.length;
    isModalOpen.value = true;
}

function openEditModal(field) {
    editingField.value = field;
    form.clearErrors();
    form.name = field.name;
    form.applies_to = field.applies_to;
    form.type = field.type;
    form.default_amount = Number(field.default_amount) || 0;
    form.show_in_pos = Boolean(field.show_in_pos);
    form.is_active = Boolean(field.is_active);
    form.sort_order = Number(field.sort_order) || 0;
    isModalOpen.value = true;
}

function submitForm() {
    if (editingField.value) {
        form.put(route('settings.order-charges.update', editingField.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('settings.order-charges.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function toggleStatus(field) {
    router.post(route('settings.order-charges.toggle-active', field.id), {}, {
        preserveScroll: true,
    });
}

function confirmDelete(field) {
    deletingField.value = field;
}

function handleDelete() {
    if (!deletingField.value) return;
    router.delete(route('settings.order-charges.destroy', deletingField.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deletingField.value = null;
        },
    });
}
</script>

<template>
    <AppLayout title="Additional Charges & Costs">
        <Head title="Additional Charges & Costs - Settings" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Additional Order Charges & Landed Costs</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Configure custom dynamic surcharges (Delivery, Customs Duty, Labor, Packaging) for Sales and Purchases.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Charges</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">{{ totalCount }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Configured charge types</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-blue-100 shadow-xs">
                    <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Sales Charges</div>
                    <div class="text-xl font-extrabold text-blue-700 mt-1">{{ salesCount }}</div>
                    <div class="text-[10px] text-blue-600/70 mt-0.5">Applies to Sales & POS</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-xs">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Landed Costs</div>
                    <div class="text-xl font-extrabold text-emerald-700 mt-1">{{ purchaseCount }}</div>
                    <div class="text-[10px] text-emerald-600/70 mt-0.5">Applies to Purchase Orders</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-xs">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Active Status</div>
                    <div class="text-xl font-extrabold text-indigo-700 mt-1">{{ activeCount }} of {{ totalCount }}</div>
                    <div class="text-[10px] text-indigo-600/70 mt-0.5">Ready on order screens</div>
                </div>
            </div>

            <!-- Filter Toolbar & Action Button -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2 items-center justify-between">
                <div class="relative w-full md:w-80">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search charges by name or scope..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400 placeholder:text-xs bg-slate-50/50 focus:bg-white"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button
                        type="button"
                        @click="openAddModal"
                        class="w-full md:w-auto justify-center px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Charge Field</span>
                    </button>
                </div>
            </div>

            <!-- Charge Fields Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div v-if="filteredFields.length === 0" class="text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3 font-bold text-lg">
                        %
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ search ? 'No Matching Charges Found' : 'No Custom Charges Configured' }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        {{ search ? 'Try modifying your search term.' : 'Add delivery charges, customs tariffs, labor costs or any surcharge to automatically apply to your sales or purchases.' }}
                    </p>
                    <button
                        v-if="!search"
                        type="button"
                        @click="openAddModal"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        Create Your First Charge Field
                    </button>
                </div>

                <div v-else class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs text-slate-600 min-w-[700px]">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Field Name</th>
                                <th class="py-3 px-4">Scope</th>
                                <th class="py-3 px-4">Calculation Type</th>
                                <th class="py-3 px-4">Default Amount / Rate</th>
                                <th class="py-3 px-4">POS Terminal</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="field in filteredFields" :key="field.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ field.name }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="field.applies_to === 'sales'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60"
                                    >
                                        Sales Only
                                    </span>
                                    <span
                                        v-else-if="field.applies_to === 'purchases'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                                    >
                                        Purchases Only
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/60"
                                    >
                                        Sales & Purchases
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="capitalize text-slate-700 font-semibold">
                                        {{ field.type === 'percentage' ? 'Percentage (%)' : 'Fixed Amount' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ field.type === 'percentage' ? `${field.default_amount}%` : `৳${Number(field.default_amount).toFixed(2)}` }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="field.applies_to !== 'purchases'">
                                        <span v-if="field.show_in_pos" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                            Enabled
                                        </span>
                                        <span v-else class="text-slate-400 text-xs">Hidden</span>
                                    </span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <button
                                        type="button"
                                        @click="toggleStatus(field)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold cursor-pointer transition"
                                        :class="field.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-slate-200/60'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="field.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ field.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="openEditModal(field)"
                                            class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <span class="text-slate-200">|</span>
                                        <button
                                            type="button"
                                            @click="confirmDelete(field)"
                                            class="text-rose-600 hover:text-rose-800 font-semibold text-xs cursor-pointer"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        {{ editingField ? 'Edit Charge Field' : 'Add Charge Field' }}
                    </h3>
                    <button
                        type="button"
                        @click="isModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Charge Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Field Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.name"
                            placeholder="e.g. Delivery Charge, Customs Duty, Installation"
                            class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            required
                        />
                        <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Scope / Applies To -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Applies To <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.applies_to"
                            class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="sales">Sales Only (Sales Orders & POS)</option>
                            <option value="purchases">Purchases Only (Vendor PO & Landed Cost)</option>
                            <option value="both">Both Sales & Purchases</option>
                        </select>
                        <div v-if="form.errors.applies_to" class="text-rose-500 text-[11px] mt-1">{{ form.errors.applies_to }}</div>
                    </div>

                    <!-- Calculation Type & Default Value -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Calculation Type <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Default {{ form.type === 'percentage' ? 'Rate (%)' : 'Amount' }}
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                v-model.number="form.default_amount"
                                class="w-full text-xs rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono"
                            />
                            <div v-if="form.errors.default_amount" class="text-rose-500 text-[11px] mt-1">{{ form.errors.default_amount }}</div>
                        </div>
                    </div>

                    <!-- Options / Checkboxes -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label v-if="form.applies_to !== 'purchases'" class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.show_in_pos"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-xs font-medium text-slate-700">Display in Quick POS Checkout</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-xs font-medium text-slate-700">Active</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ form.processing ? 'Saving...' : (editingField ? 'Update Field' : 'Create Field') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingField"
            title="Delete Charge Field"
            :item-name="deletingField?.name || ''"
            message="Are you sure you want to delete this charge field? Historical orders using this charge will retain their saved charge amounts."
            confirm-text="Delete Field"
            @close="deletingField = null"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
