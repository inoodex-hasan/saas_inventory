<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    metrics: Object,
    tenants: Object,
    plans: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const planFilter = ref(props.filters.plan_id ? String(props.filters.plan_id) : '');

watch(() => props.filters, (newVal) => {
    if (newVal) {
        search.value = newVal.search || '';
        status.value = newVal.status || '';
        planFilter.value = newVal.plan_id ? String(newVal.plan_id) : '';
    }
}, { deep: true });

function applyFilter() {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (planFilter.value) params.plan_id = planFilter.value;
    router.get(route('admin.dashboard'), params, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    planFilter.value = '';
    router.get(route('admin.dashboard'), {}, { preserveState: false, replace: true });
}

// -------------------------------------------------------------
// Action 1: Toggle Status (Suspend / Reactivate)
// -------------------------------------------------------------
function toggleStatus(tenant) {
    const action = tenant.status === 'suspended' ? 'reactivate' : 'suspend';
    if (confirm(`Are you sure you want to ${action} tenant workspace '${tenant.name}'?`)) {
        router.post(route('admin.tenants.toggle-status', tenant.id), {}, {
            preserveScroll: true,
            only: ['tenants', 'flash', 'metrics'],
        });
    }
}

// -------------------------------------------------------------
// Action 2: Switch Workspace (Impersonation)
// -------------------------------------------------------------
function switchToTenant(tenant) {
    if (confirm(`Switch platform context to tenant workspace '${tenant.name}'?`)) {
        router.post(route('admin.tenants.switch', tenant.id));
    }
}

// -------------------------------------------------------------
// Modal 1: Provision / Create New Tenant
// -------------------------------------------------------------
const isCreateModalOpen = ref(false);
const createForm = useForm({
    name: '',
    legal_name: '',
    owner_name: '',
    owner_email: '',
    owner_password: '',
    plan_id: props.plans && props.plans.length > 0 ? props.plans[0].id : '',
    billing_cycle: 'monthly',
    validity_days: 30,
    currency_code: 'BDT',
    currency_symbol: '৳',
    phone: '',
    city: 'Dhaka',
    country: 'Bangladesh',
});

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    createForm.plan_id = props.plans && props.plans.length > 0 ? props.plans[0].id : '';
    createForm.billing_cycle = 'monthly';
    createForm.validity_days = 30;
    createForm.currency_code = 'BDT';
    createForm.currency_symbol = '৳';
    isCreateModalOpen.value = true;
}

function submitCreateTenant() {
    createForm.post(route('admin.tenants.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
}

// -------------------------------------------------------------
// Modal 2: Manage & Control Tenant (Deep Inspection Drawer)
// -------------------------------------------------------------
const isManageModalOpen = ref(false);
const managingTenant = ref(null);
const activeTab = ref('profile'); // 'profile' | 'subscription' | 'danger'

const profileForm = useForm({
    name: '',
    legal_name: '',
    email: '',
    phone: '',
    tax_id: '',
    currency_code: 'BDT',
    currency_symbol: '৳',
    address_line1: '',
    city: '',
    state: '',
    country: '',
});

const subForm = useForm({
    plan_id: '',
    status: 'active',
    billing_cycle: 'monthly',
    extension_days: 0,
    custom_ends_at: '',
});

function openManageModal(tenant, defaultTab = 'profile') {
    managingTenant.value = tenant;
    activeTab.value = defaultTab;

    // Fill profile form
    profileForm.name = tenant.name || '';
    profileForm.legal_name = tenant.legal_name || '';
    profileForm.email = tenant.email || '';
    profileForm.phone = tenant.phone || '';
    profileForm.tax_id = tenant.tax_id || '';
    profileForm.currency_code = tenant.currency_code || 'BDT';
    profileForm.currency_symbol = tenant.currency_symbol || '৳';
    profileForm.address_line1 = tenant.address_line1 || '';
    profileForm.city = tenant.city || '';
    profileForm.state = tenant.state || '';
    profileForm.country = tenant.country || '';
    profileForm.clearErrors();

    // Fill subscription form
    subForm.plan_id = tenant.subscription_info?.plan_id || (props.plans && props.plans.length > 0 ? props.plans[0].id : '');
    subForm.status = tenant.subscription_info?.status || 'active';
    subForm.billing_cycle = tenant.subscription_info?.billing_cycle || 'monthly';
    subForm.extension_days = 0;
    subForm.custom_ends_at = tenant.subscription_info?.ends_at || '';
    subForm.clearErrors();

    isManageModalOpen.value = true;
}

function submitUpdateProfile() {
    if (!managingTenant.value) return;
    profileForm.put(route('admin.tenants.update', managingTenant.value.id), {
        preserveScroll: true,
        only: ['tenants', 'flash'],
        onSuccess: () => {
            isManageModalOpen.value = false;
        },
    });
}

function submitUpdateSubscription() {
    if (!managingTenant.value) return;
    subForm.put(route('admin.tenants.subscription.update', managingTenant.value.id), {
        preserveScroll: true,
        only: ['tenants', 'flash'],
        onSuccess: () => {
            isManageModalOpen.value = false;
        },
    });
}

function confirmDeleteTenant(tenant) {
    const confirmation = prompt(`DANGER ZONE: Type "${tenant.name}" to permanently delete this organization and its records.`);
    if (confirmation === tenant.name) {
        router.delete(route('admin.tenants.destroy', tenant.id), {
            preserveScroll: true,
            onSuccess: () => {
                isManageModalOpen.value = false;
            },
        });
    } else if (confirmation !== null) {
        alert('Verification mismatch. Deletion cancelled.');
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Platform Super-Admin Control Plane" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Platform Control Panel</h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            Super Admin
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium">Tenant provisioning, lifecycle administration, cross-tenant isolation enforcement, and workspace switching</p>
                </div>

                <div class="flex items-center gap-2">

                    <div v-if="$page.props.auth?.user?.current_team_id">
                        <a
                            :href="route('admin.exit-tenant')"
                            class="px-3.5 py-2 text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition shadow-xs inline-flex items-center gap-1.5"
                        >
                            <span>Exit Impersonation</span>
                            <span class="text-amber-600">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Platform System Metrics -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tenants</p>
                    <p class="text-2xl font-black text-slate-900 font-mono mt-1">{{ metrics.total_tenants }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Tenants</p>
                    <p class="text-2xl font-black text-emerald-600 font-mono mt-1">{{ metrics.active_tenants }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Suspended</p>
                    <p class="text-2xl font-black text-rose-600 font-mono mt-1">{{ metrics.suspended_tenants }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Platform Users</p>
                    <p class="text-2xl font-black text-blue-600 font-mono mt-1">{{ metrics.total_users }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Products</p>
                    <p class="text-2xl font-black text-indigo-600 font-mono mt-1">{{ metrics.total_products }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Orders</p>
                    <p class="text-2xl font-black text-purple-600 font-mono mt-1">{{ metrics.total_sales }}</p>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center gap-2.5">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition placeholder:text-slate-400 placeholder:text-xs"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="suspended">Suspended Only</option>
                    </select>

                    <select
                        v-model="planFilter"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Plans</option>
                        <option v-for="p in plans" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                    </select>

                    <button
                        @click="resetFilter"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition cursor-pointer"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>New Tenant</span>
                    </button>
                </div>
            </div>

            <!-- Tenants Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="tenant in tenants.data"
                    :key="tenant.id"
                    class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between space-y-4 hover:shadow-md transition"
                >
                    <div class="space-y-3">
                        <!-- Top Header with Status & Plan Badge -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-extrabold text-slate-900 tracking-tight">{{ tenant.name }}</h2>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5 truncate">{{ tenant.legal_name || 'Individual SaaS Account' }}</p>
                            </div>
                            <span
                                :class="tenant.status === 'suspended' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase shrink-0"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="tenant.status === 'suspended' ? 'bg-rose-500' : 'bg-emerald-500 animate-pulse'"></span>
                                {{ tenant.status }}
                            </span>
                        </div>

                        <!-- Subscription & Expiry Pill -->
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">
                                    {{ tenant.subscription_info?.plan_name }}
                                </span>
                                <span class="text-[11px] text-slate-400 uppercase font-semibold">
                                    {{ tenant.subscription_info?.billing_cycle }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] font-mono font-bold" :class="tenant.subscription_info?.days_remaining <= 5 ? 'text-rose-600 font-extrabold' : 'text-slate-700'">
                                    {{ tenant.subscription_info?.days_remaining }}d left
                                </span>
                            </div>
                        </div>

                        <!-- Owner Details -->
                        <div class="text-xs text-slate-500 space-y-1 pt-1 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-500">Owner:</span>
                                <span class="font-bold text-slate-800">{{ tenant.owner?.name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-500">Email:</span>
                                <span class="font-mono text-slate-700 text-[11px]">{{ tenant.owner?.email }}</span>
                            </div>
                            <div v-if="tenant.phone" class="flex items-center justify-between">
                                <span class="font-medium text-slate-500">Phone:</span>
                                <span class="font-mono text-slate-700 text-[11px]">{{ tenant.phone }}</span>
                            </div>
                        </div>

                        <!-- Live Resource Counts -->
                        <div class="grid grid-cols-4 gap-1.5 pt-2 text-center text-xs">
                            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold font-mono text-slate-900 block">{{ tenant.users_count || 1 }}</span>
                                <span class="text-[9px] text-slate-400 uppercase font-bold">Staff</span>
                            </div>
                            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold font-mono text-slate-900 block">{{ tenant.warehouses_count || 0 }}</span>
                                <span class="text-[9px] text-slate-400 uppercase font-bold">Hubs</span>
                            </div>
                            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold font-mono text-slate-900 block">{{ tenant.products_count || 0 }}</span>
                                <span class="text-[9px] text-slate-400 uppercase font-bold">SKUs</span>
                            </div>
                            <div class="bg-slate-50/80 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold font-mono text-slate-900 block">{{ tenant.sales_orders_count || 0 }}</span>
                                <span class="text-[9px] text-slate-400 uppercase font-bold">Orders</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Toolbar -->
                    <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
                        <button
                            type="button"
                            @click="switchToTenant(tenant)"
                            class="flex-1 py-2 px-3 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition text-center cursor-pointer"
                        >
                            Switch Workspace
                        </button>
                        <button
                            type="button"
                            @click="openManageModal(tenant, 'profile')"
                            class="py-2 px-3 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition cursor-pointer"
                        >
                            Manage
                        </button>
                        <button
                            type="button"
                            @click="toggleStatus(tenant)"
                            :class="tenant.status === 'suspended' ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200' : 'text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200'"
                            class="py-2 px-2.5 text-xs font-bold rounded-xl border transition cursor-pointer"
                        >
                            {{ tenant.status === 'suspended' ? 'Reactivate' : 'Suspend' }}
                        </button>
                    </div>
                </div>

                <div v-if="!tenants.data || tenants.data.length === 0" class="col-span-full bg-white p-12 text-center rounded-3xl border border-slate-200 text-slate-400">
                    <p class="font-semibold text-xs text-slate-600">No client tenant workspaces found matching your query.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tenants.links && tenants.links.length > 3" class="bg-white p-4 rounded-2xl border border-slate-200/80 flex items-center justify-between text-xs text-slate-500">
                <div>Showing {{ tenants.from || 0 }} to {{ tenants.to || 0 }} of {{ tenants.total }} tenants</div>
                <div class="flex items-center gap-1">
                    <button
                        v-for="(link, idx) in tenants.links"
                        :key="idx"
                        @click="router.visit(link.url)"
                        :disabled="!link.url"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition text-xs',
                            link.active ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-100 text-slate-700',
                            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- MODAL 1: Provision / Create New Tenant                         -->
        <!-- ------------------------------------------------------------- -->
        <Modal :show="isCreateModalOpen" @close="isCreateModalOpen = false" max-width="lg">
            <div class="p-6 md:p-8 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Tenant Onboarding
                            </span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mt-1">Provision New Tenant Workspace</h3>
                    </div>
                    <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg leading-none cursor-pointer">&times;</button>
                </div>

                <form @submit.prevent="submitCreateTenant" class="space-y-4 text-xs">
                    <!-- Company Info -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Company / Team Name *</label>
                            <input
                                v-model="createForm.name"
                                type="text"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                required
                            />
                            <p v-if="createForm.errors.name" class="text-rose-500 text-[11px] mt-1">{{ createForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Legal Entity Name</label>
                            <input
                                v-model="createForm.legal_name"
                                type="text"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>

                    <!-- Owner Credentials -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-3">
                        <div class="font-bold text-slate-900 text-xs">Tenant Owner Account Credentials</div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-600 mb-1">Owner Full Name *</label>
                                <input
                                    v-model="createForm.owner_name"
                                    type="text"
class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                    required
                                />
                                <p v-if="createForm.errors.owner_name" class="text-rose-500 text-[11px] mt-1">{{ createForm.errors.owner_name }}</p>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-600 mb-1">Owner Email *</label>
                                <input
                                    v-model="createForm.owner_email"
                                    type="email"
class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                    required
                                />
                                <p v-if="createForm.errors.owner_email" class="text-rose-500 text-[11px] mt-1">{{ createForm.errors.owner_email }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-600 mb-1">Initial Password * (min 8 chars)</label>
                            <input
                                v-model="createForm.owner_password"
                                type="password"
class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                required
                            />
                            <p v-if="createForm.errors.owner_password" class="text-rose-500 text-[11px] mt-1">{{ createForm.errors.owner_password }}</p>
                        </div>
                    </div>

                    <!-- Plan & Subscription Details -->
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Subscription Plan *</label>
                            <select
                                v-model="createForm.plan_id"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                                required
                            >
                                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Billing Cycle *</label>
                            <select
                                v-model="createForm.billing_cycle"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 uppercase"
                                required
                            >
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Validity (Days) *</label>
                            <input
                                v-model.number="createForm.validity_days"
                                type="number"
                                min="1"
                                max="3650"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 font-mono"
                                required
                            />
                        </div>
                    </div>

                    <!-- Currency & Location -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Currency Code & Symbol</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="createForm.currency_code"
                                    type="text"
class="w-1/2 text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl uppercase font-mono"
                                    required
                                />
                                <input
                                    v-model="createForm.currency_symbol"
                                    type="text"
class="w-1/2 text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono text-center"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="createForm.phone"
                                type="text"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ createForm.processing ? 'Provisioning...' : 'Confirm & Provision Workspace' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ------------------------------------------------------------- -->
        <!-- MODAL 2: Deep Tenant Management & Control Drawer               -->
        <!-- ------------------------------------------------------------- -->
        <Modal :show="isManageModalOpen" @close="isManageModalOpen = false" max-width="lg">
            <div class="p-6 md:p-8 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Tenant Control
                            </span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mt-1">Manage {{ managingTenant?.name }}</h3>
                    </div>
                    <button @click="isManageModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg leading-none cursor-pointer">&times;</button>
                </div>

                <!-- Navigation Tabs -->
                <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl border border-slate-200">
                    <button
                        type="button"
                        @click="activeTab = 'profile'"
                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer"
                        :class="activeTab === 'profile' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                    >
                        Company Profile
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'subscription'"
                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer"
                        :class="activeTab === 'subscription' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                    >
                        Plan & Expiry Override
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'danger'"
                        class="flex-1 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer"
                        :class="activeTab === 'danger' ? 'bg-rose-600 text-white shadow-xs' : 'text-rose-600 hover:text-rose-800'"
                    >
                        Danger Zone
                    </button>
                </div>

                <!-- TAB 1: Company Profile -->
                <form v-if="activeTab === 'profile'" @submit.prevent="submitUpdateProfile" class="space-y-3.5 text-xs pt-1">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Company Name *</label>
                            <input
                                v-model="profileForm.name"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Legal Name</label>
                            <input
                                v-model="profileForm.legal_name"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Billing Email</label>
                            <input
                                v-model="profileForm.email"
                                type="email"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Phone</label>
                            <input
                                v-model="profileForm.phone"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tax / VAT ID</label>
                            <input
                                v-model="profileForm.tax_id"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Currency (Code & Symbol) *</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="profileForm.currency_code"
                                    type="text"
                                    class="w-1/2 text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl uppercase font-mono"
                                    required
                                />
                                <input
                                    v-model="profileForm.currency_symbol"
                                    type="text"
                                    class="w-1/2 text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono text-center"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Street Address</label>
                        <input
                            v-model="profileForm.address_line1"
                            type="text"
                            class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">City</label>
                            <input
                                v-model="profileForm.city"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Country</label>
                            <input
                                v-model="profileForm.country"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isManageModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-5 py-2.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ profileForm.processing ? 'Saving...' : 'Save Profile Changes' }}
                        </button>
                    </div>
                </form>

                <!-- TAB 2: Subscription & Expiry Override -->
                <form v-if="activeTab === 'subscription'" @submit.prevent="submitUpdateSubscription" class="space-y-4 text-xs pt-1">
                    <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-1">
                        <span class="text-[10px] font-bold text-indigo-700 uppercase">Current Validity Status</span>
                        <div class="flex items-baseline justify-between font-mono">
                            <span class="text-sm font-bold text-slate-900">{{ managingTenant?.subscription_info?.plan_name }}</span>
                            <span class="text-xs font-bold text-indigo-600">Expires: {{ managingTenant?.subscription_info?.formatted_ends_at }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Assign Plan Tier *</label>
                            <select
                                v-model="subForm.plan_id"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 font-bold"
                                required
                            >
                                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Subscription Status *</label>
                            <select
                                v-model="subForm.status"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 uppercase font-bold"
                                required
                            >
                                <option value="active">Active</option>
                                <option value="trialing">Trialing</option>
                                <option value="past_due">Past Due</option>
                                <option value="expired">Expired</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Billing Cycle *</label>
                            <select
                                v-model="subForm.billing_cycle"
                                class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 uppercase"
                                required
                            >
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Add Extension Days (e.g. 30)</label>
                            <input
                                v-model.number="subForm.extension_days"
                                type="number"
                                min="0"
class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Or Set Explicit Expiry Date</label>
                        <input
                            v-model="subForm.custom_ends_at"
                            type="date"
                            class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-indigo-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isManageModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="subForm.processing"
                            class="px-5 py-2.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ subForm.processing ? 'Applying...' : 'Apply Subscription Overrides' }}
                        </button>
                    </div>
                </form>

                <!-- TAB 3: Danger Zone -->
                <div v-if="activeTab === 'danger'" class="space-y-4 pt-1 text-xs">
                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2">
                        <div class="font-bold text-amber-900">Workspace Suspension & Access Lockout</div>
                        <p class="text-amber-800 text-[11px] leading-relaxed">
                            Suspending will immediately block all users in this tenant organization from logging into or viewing inventory, sales, POS, and accounting.
                        </p>
                        <button
                            type="button"
                            @click="toggleStatus(managingTenant)"
                            :class="managingTenant?.status === 'suspended' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-600 hover:bg-amber-700 text-white'"
                            class="px-3.5 py-2 text-xs font-bold rounded-xl transition cursor-pointer shadow-xs"
                        >
                            {{ managingTenant?.status === 'suspended' ? 'Reactivate Tenant Access' : 'Suspend Tenant Access' }}
                        </button>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 space-y-2">
                        <div class="font-bold text-rose-900">Permanent Tenant Deletion</div>
                        <p class="text-rose-800 text-[11px] leading-relaxed">
                            Irreversibly delete this tenant organization. Requires typing the exact company name to confirm.
                        </p>
                        <button
                            type="button"
                            @click="confirmDeleteTenant(managingTenant)"
                            class="px-3.5 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl transition cursor-pointer shadow-xs"
                        >
                            Permanently Delete Tenant
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
