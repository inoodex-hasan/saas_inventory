<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    vendors: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const isModalOpen = ref(false);
const editingVendor = ref(null);

const form = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    tax_number: '',
    is_active: true,
});

function handleSearch() {
    router.get(route('vendors.index'), {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function openCreateModal() {
    editingVendor.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isModalOpen.value = true;
}

function openEditModal(v) {
    editingVendor.value = v;
    form.clearErrors();
    form.name = v.name;
    form.contact_name = v.contact_name || '';
    form.phone = v.phone || '';
    form.email = v.email || '';
    form.address = v.address || '';
    form.tax_number = v.tax_number || '';
    form.is_active = Boolean(v.is_active);
    isModalOpen.value = true;
}

function submitForm() {
    if (editingVendor.value) {
        form.put(route('vendors.update', editingVendor.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('vendors.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
}

function toggleActive(v) {
    router.post(route('vendors.toggle-active', v.id), {}, {
        preserveScroll: true,
    });
}

function deleteVendor(v) {
    if (confirm(`Are you sure you want to delete vendor "${v.name}"?`)) {
        router.delete(route('vendors.destroy', v.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Vendors & Suppliers" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Vendors & Suppliers Directory</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage procurement sources, contact persons, and trade credentials</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter & Action Header -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-1 items-center gap-2">
                    <div class="relative flex-1 max-w-sm">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="handleSearch"
                            placeholder="Search by vendor name, phone, contact..."
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 placeholder:text-slate-400"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select 
                        v-model="status" 
                        @change="handleSearch"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="openCreateModal" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Vendor</span>
                    </button>
                </div>
            </div>

            <!-- Vendors Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4">Vendor & Company</th>
                                <th class="py-3 px-4">Contact Person</th>
                                <th class="py-3 px-4">Phone & Mobile</th>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">Address</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="v in vendors.data" :key="v.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ v.name }}</div>
                                    <div v-if="v.tax_number" class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        Tax/Trade: {{ v.tax_number }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-medium text-slate-700">
                                    {{ v.contact_name || 'N/A' }}
                                </td>

                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    {{ v.phone }}
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ v.email || '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate">
                                    {{ v.address }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <button 
                                        @click="toggleActive(v)"
                                        title="Click to toggle status"
                                    >
                                        <StatusBadge :status="v.is_active ? 'active' : 'inactive'" />
                                    </button>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            @click="openEditModal(v)" 
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="deleteVendor(v)" 
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vendors.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No vendors found matching criteria.
                </div>

                <!-- Pagination -->
                <div v-if="vendors.links && vendors.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ vendors.from }} to {{ vendors.to }} of {{ vendors.total }} vendors
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in vendors.links" 
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

        <!-- Create / Edit Vendor Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" maxWidth="xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            {{ editingVendor ? 'Edit Vendor Details' : 'Add New Vendor / Supplier' }}
                        </h2>
                        <p class="text-xs text-slate-500">Procurement partner profile and communication info</p>
                    </div>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Company / Vendor Name *</label>
                            <input 
                                type="text" 
                                v-model="form.name" 
                                required
                                placeholder="e.g. Acme Electronics Ltd"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                            <input 
                                type="text" 
                                v-model="form.contact_name" 
                                placeholder="e.g. John Doe"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile / Phone Number *</label>
                            <input 
                                type="text" 
                                v-model="form.phone" 
                                required
                                placeholder="+880 1..."
                                class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                            <p v-if="form.errors.phone" class="text-[11px] text-rose-600 mt-1">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input 
                                type="email" 
                                v-model="form.email" 
                                placeholder="orders@vendor.com"
                                class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Physical / Billing Address *</label>
                        <textarea 
                            v-model="form.address" 
                            rows="2"
                            required
                            placeholder="Plot / Road / City / Area..."
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-[11px] text-rose-600 mt-1">{{ form.errors.address }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Trade License / Tax Number</label>
                        <input 
                            type="text" 
                            v-model="form.tax_number" 
                            placeholder="TIN-XXXX / Trade ID"
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.is_active" 
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-xs font-semibold text-slate-700">Active Vendor (Available in Purchase Orders)</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="isModalOpen = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-sm transition-colors disabled:opacity-50"
                        >
                            {{ editingVendor ? 'Update Vendor' : 'Save Vendor' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
