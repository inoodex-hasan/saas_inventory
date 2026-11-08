<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

const props = defineProps({
    categories: Object,
    metrics: Object,
    parentOptions: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const parentFilter = ref(props.filters?.parent_id || '');
const isModalOpen = ref(false);
const editingCategory = ref(null);
const deletingCategory = ref(null);
const isDeleting = ref(false);

const form = useForm({
    name: '',
    parent_id: '',
    description: '',
});

function handleSearch() {
    router.get(route('categories.index'), {
        search: search.value || undefined,
        parent_id: parentFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    search.value = '';
    parentFilter.value = '';
    router.get(route('categories.index'), {}, { preserveState: false, replace: true });
}

function openCreateModal() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    form.parent_id = '';
    isModalOpen.value = true;
}

function openEditModal(cat) {
    editingCategory.value = cat;
    form.clearErrors();
    form.name = cat.name;
    form.parent_id = cat.parent_id ? String(cat.parent_id) : '';
    form.description = cat.description || '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingCategory.value) {
        form.put(route('categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route('categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

function deleteCategory(cat) {
    deletingCategory.value = cat;
}

function executeDeleteCategory() {
    if (!deletingCategory.value) return;
    isDeleting.value = true;

    router.delete(route('categories.destroy', deletingCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deletingCategory.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

// Filter out the category being edited from parent selection options to prevent self-parenting
const availableParentOptions = computed(() => {
    if (!props.parentOptions) return [];
    if (!editingCategory.value) return props.parentOptions;
    return props.parentOptions.filter(c => c.id !== editingCategory.value.id);
});
</script>

<template>
    <AppLayout>
        <Head title="Product Categories" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Product Categories</h1>
                    <p class="text-xs text-slate-500 font-medium">Organize products into hierarchical groups, taxonomy & departments</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Categories</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_categories || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Root / Main Groups</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        {{ metrics?.root_categories || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sub-Categories</span>
                    <div class="text-2xl font-black text-violet-600 mt-2">
                        {{ metrics?.sub_categories || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Categorized Products</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        {{ Number(metrics?.total_products_categorized || 0).toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Filter & Action Toolbar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-1 flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
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

                    <select 
                        v-model="parentFilter" 
                        @change="handleSearch"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 transition"
                    >
                        <option value="">All Categories</option>
                        <option value="root">Root Categories Only</option>
                        <option value="sub">Sub-Categories Only</option>
                    </select>

                    <button 
                        v-if="search || parentFilter"
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
                        <span>Add Category</span>
                    </button>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">Category Name</th>
                                <th class="py-3 px-4 whitespace-nowrap">Hierarchy / Parent</th>                                <th class="py-3 px-4 text-center whitespace-nowrap">Sub-Categories</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Assigned Products</th>
                                <th class="py-3 px-4 whitespace-nowrap">Description</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="cat in (categories?.data || [])" :key="cat.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ cat.name.substring(0, 1).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 text-sm block max-w-[180px] lg:max-w-[240px] truncate" :title="cat.name">{{ cat.name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span 
                                        v-if="cat.parent" 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-violet-50 text-violet-700 border border-violet-100"
                                    >
                                        <span class="text-slate-400">Child of</span>
                                        <span class="font-bold">{{ cat.parent.name }}</span>
                                    </span>
                                    <span 
                                        v-else 
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600"
                                    >
                                        Top-Level Root
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span 
                                        v-if="cat.children_count > 0"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700"
                                    >
                                        {{ cat.children_count }} sub
                                    </span>
                                    <span v-else class="text-slate-300 font-mono">—</span>
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span 
                                        v-if="cat.products_count > 0"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700"
                                    >
                                        {{ cat.products_count }} items
                                    </span>
                                    <span v-else class="text-slate-400 font-medium">0 items</span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 max-w-[200px] lg:max-w-[280px] truncate" :title="cat.description">
                                    {{ cat.description ? (cat.description.length > 50 ? cat.description.slice(0, 50) + '...' : cat.description) : '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <button 
                                                @click="openEditModal(cat)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Category</span>
                                            </button>

                                            <button 
                                                @click="deleteCategory(cat)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Category</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="categories.data.length === 0" class="text-center py-16 px-4">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">No categories found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Get started by creating your first product category to organize your catalog.</p>
                    <button 
                        @click="openCreateModal" 
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add First Category</span>
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-500">
                        Showing {{ categories.from || 0 }} to {{ categories.to || 0 }} of {{ categories.total || 0 }} categories
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in categories.links" 
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

        <!-- Create / Edit Category Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            {{ editingCategory ? 'Edit Product Category' : 'Create New Category' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ editingCategory ? 'Update category name, parent hierarchy, and description' : 'Classify and organize catalog items by department or type' }}
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
                            Category Name <span class="text-rose-500">*</span>
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
                            Parent Category (Optional)
                        </label>
                        <select 
                            v-model="form.parent_id" 
                            class="w-full px-3.5 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">(None - Top Level Root Category)</option>
                            <option 
                                v-for="opt in availableParentOptions" 
                                :key="opt.id" 
                                :value="String(opt.id)"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Leave empty to create a top-level parent category.</p>
                        <span v-if="form.errors.parent_id" class="text-[11px] text-rose-500 font-medium mt-1 block">
                            {{ form.errors.parent_id }}
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
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition disabled:opacity-50 cursor-pointer"
                        >
                            <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                            <span>{{ form.processing ? 'Saving...' : (editingCategory ? 'Update Category' : 'Save Category') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :show="!!deletingCategory"
            title="Delete Category"
            :item-name="deletingCategory?.name"
            message="Are you sure you want to delete this category? Products assigned to this category will be preserved as Uncategorized."
            confirm-text="Delete Category"
            :processing="isDeleting"
            @confirm="executeDeleteCategory"
            @close="deletingCategory = null"
        />
    </AppLayout>
</template>
