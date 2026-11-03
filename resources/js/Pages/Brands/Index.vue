<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    brands: Object,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const isModalOpen = ref(false);
const editingBrand = ref(null);
const deletingBrand = ref(null);
const isDeleting = ref(false);

const form = useForm({
    name: '',
    description: '',
});

function handleSearch() {
    router.get(route('brands.index'), {
        search: search.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    search.value = '';
    router.get(route('brands.index'), {}, { preserveState: false, replace: true });
}

function openCreateModal() {
    editingBrand.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(brand) {
    editingBrand.value = brand;
    form.clearErrors();
    form.name = brand.name;
    form.description = brand.description || '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingBrand.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingBrand.value) {
        form.put(route('brands.update', editingBrand.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('brands.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

const optimisticallyHiddenBrandIds = ref(new Set());
const displayedBrands = computed(() => {
    return (props.brands?.data || []).filter(b => !optimisticallyHiddenBrandIds.value.has(b.id));
});

function deleteBrand(brand) {
    deletingBrand.value = brand;
}

function executeDeleteBrand() {
    if (!deletingBrand.value) return;
    const targetId = deletingBrand.value.id;
    optimisticallyHiddenBrandIds.value.add(targetId);
    deletingBrand.value = null;

    router.delete(route('brands.destroy', targetId), {
        preserveScroll: true,
        only: ['brands', 'metrics', 'flash'],
        onError: () => {
            optimisticallyHiddenBrandIds.value.delete(targetId);
        },
        onFinish: () => {
            optimisticallyHiddenBrandIds.value.delete(targetId);
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Product Brands" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Product Brands</h1>
                    <p class="text-xs text-slate-500 font-medium">Manufacturer labels, trademarks & brand associations across your catalog</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Brands</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_brands || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Branded Products</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        {{ Number(metrics?.total_products_branded || 0).toLocaleString() }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Top Brand by Catalog Size</span>
                    <div class="text-xl font-black text-slate-900 mt-2 truncate">
                        {{ metrics?.top_brand_name || 'N/A' }}
                    </div>
                    <span v-if="metrics?.top_brand_count > 0" class="text-[11px] text-slate-500 font-semibold block mt-0.5">
                        {{ metrics.top_brand_count }} products linked
                    </span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Unbranded Products</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ Number(metrics?.unbranded_products || 0).toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Filter & Action Toolbar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-1 items-center gap-2">
                    <div class="relative flex-1 max-w-sm">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="handleSearch"
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <button 
                        v-if="search"
                        @click="clearFilters" 
                        class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="openCreateModal" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Brand</span>
                    </button>
                </div>
            </div>

            <!-- Brands Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Brand / Manufacturer</th>
                                <th class="py-3 px-4 whitespace-nowrap">Slug Identifier</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Assigned Products</th>
                                <th class="py-3 px-4 whitespace-nowrap">Description</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="b in displayedBrands" :key="b.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-sm">
                                            {{ b.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 text-sm block max-w-[180px] lg:max-w-[240px] truncate" :title="b.name">{{ b.name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ b.slug }}
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span 
                                        v-if="b.products_count > 0"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100"
                                    >
                                        {{ b.products_count }} products
                                    </span>
                                    <span v-else class="text-slate-400 font-medium">0 products</span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 max-w-[220px] lg:max-w-[320px] truncate" :title="b.description">
                                    {{ b.description ? (b.description.length > 55 ? b.description.slice(0, 55) + '...' : b.description) : '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <button 
                                                @click="openEditModal(b)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Brand</span>
                                            </button>

                                            <button 
                                                @click="deleteBrand(b)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Brand</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="brands.data.length === 0" class="text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">No brands found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Add manufacturer and brand names to tag and categorize your inventory items.</p>
                    <button 
                        @click="openCreateModal" 
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add First Brand</span>
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="brands.links && brands.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-500">
                        Showing {{ brands.from || 0 }} to {{ brands.to || 0 }} of {{ brands.total || 0 }} brands
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in brands.links" 
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

        <!-- Create / Edit Brand Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            {{ editingBrand ? 'Edit Product Brand' : 'Create New Brand' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ editingBrand ? 'Update brand details and description' : 'Register a manufacturer or brand for inventory classification' }}
                        </p>
                    </div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Brand Name <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            v-model="form.name" 
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            required
                        />
                        <span v-if="form.errors.name" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Description
                        </label>
                        <textarea 
                            v-model="form.description" 
                            rows="3"
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 resize-none"
                        ></textarea>
                        <span v-if="form.errors.description" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ form.errors.description }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            <span>{{ editingBrand ? 'Update Brand' : 'Save Brand' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingBrand"
            title="Delete Brand"
            :item-name="deletingBrand?.name"
            message="Are you sure you want to delete this brand? Products linked to this brand will remain intact without this label."
            confirm-text="Delete Brand"
            :processing="isDeleting"
            @confirm="executeDeleteBrand"
            @close="deletingBrand = null"
        />
    </AppLayout>
</template>
