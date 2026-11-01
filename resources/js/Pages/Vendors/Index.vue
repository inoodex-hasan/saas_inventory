<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    vendors: Object,
    metrics: Object,
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
    opening_balance: 0,
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
    form.opening_balance = 0;
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
    form.opening_balance = Number(v.opening_balance) || 0;
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

function setVendorStatus(v, newStatus) {
    const isCurrentlyActive = Boolean(v.is_active);
    const wantActive = newStatus === '1';
    if (isCurrentlyActive !== wantActive) {
        toggleActive(v);
    }
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
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Vendors</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_vendors || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Trade Partners</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        {{ metrics?.active_vendors || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Open Purchase Orders</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.pending_orders || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Payables</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_payables || 0" />
                    </div>
                </div>
            </div>

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
                                <th class="py-3 px-4 whitespace-nowrap">Vendor & Company</th>
                                <th class="py-3 px-4 whitespace-nowrap">Contact Person</th>
                                <th class="py-3 px-4 whitespace-nowrap">Phone & Mobile</th>
                                <th class="py-3 px-4 whitespace-nowrap">Email</th>
                                <th class="py-3 px-4 whitespace-nowrap">Address</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="v in vendors.data" :key="v.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 text-sm max-w-[180px] lg:max-w-[220px] truncate" :title="v.name">{{ v.name }}</div>
                                    <div v-if="v.tax_number" class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        Tax/Trade: {{ v.tax_number }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 font-medium text-slate-700 whitespace-nowrap">
                                    {{ v.contact_name || 'N/A' }}
                                </td>

                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                    {{ v.phone }}
                                </td>

                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    {{ v.email || '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 max-w-[200px] lg:max-w-[280px] truncate" :title="v.address">
                                    {{ v.address ? (v.address.length > 50 ? v.address.slice(0, 50) + '...' : v.address) : '—' }}
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <select 
                                        :value="v.is_active ? '1' : '0'"
                                        @change="(e) => setVendorStatus(v, e.target.value)"
                                        class="inline-flex items-center text-xs font-semibold pl-3 pr-7 py-1 rounded-full border transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1"
                                        :class="v.is_active 
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 focus:ring-emerald-400 hover:bg-emerald-100/70' 
                                            : 'bg-slate-100 text-slate-600 border-slate-200 focus:ring-slate-400 hover:bg-slate-200/70'"
                                    >
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <button 
                                                @click="openEditModal(v)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Vendor</span>
                                            </button>

                                            <button 
                                                @click="deleteVendor(v)" 
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Vendor</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
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
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                            <input 
                                type="text" 
                                v-model="form.contact_name" 
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
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
class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <p v-if="form.errors.phone" class="text-[11px] text-rose-600 mt-1">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input 
                                type="email" 
                                v-model="form.email" 
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Physical / Billing Address *</label>
                        <textarea 
                            v-model="form.address" 
                            rows="2"
                            required
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-[11px] text-rose-600 mt-1">{{ form.errors.address }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Trade License / Tax Number</label>
                        <input 
                            type="text" 
                            v-model="form.tax_number" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="bg-amber-50/70 p-3.5 rounded-xl border border-amber-200/80">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-amber-900">Opening Payable Due Balance (৳)</label>
                            <span class="text-[10px] text-amber-700 font-medium">Optional initial debt</span>
                        </div>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0"
                            v-model.number="form.opening_balance" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold text-slate-800 bg-white border border-amber-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        />
                        <p class="text-[11px] text-amber-800/80 mt-1.5 leading-relaxed">
                            If you owe this vendor an outstanding balance from prior purchases before using this software, enter it here. An opening payable invoice will be recorded or adjusted in the vendor due ledger.
                        </p>
                        <p v-if="form.errors.opening_balance" class="text-[11px] text-rose-600 mt-1">{{ form.errors.opening_balance }}</p>
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
