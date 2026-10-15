<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    metrics: Object,
    tenants: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

function applyFilter() {
    router.get(route('admin.dashboard'), {
        search: search.value,
        status: status.value,
    }, { preserveState: true, replace: true });
}

function resetFilter() {
    search.value = '';
    status.value = '';
    applyFilter();
}

function toggleStatus(tenant) {
    const action = tenant.status === 'suspended' ? 'reactivate' : 'suspend';
    if (confirm(`Are you sure you want to ${action} tenant workspace '${tenant.name}'?`)) {
        router.post(route('admin.tenants.toggle-status', tenant.id));
    }
}

function switchToTenant(tenant) {
    if (confirm(`Switch workspace context to '${tenant.name}'?`)) {
        router.post(route('admin.tenants.switch', tenant.id));
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Platform Super-Admin Control Plane" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Platform Control Panel</h1>
                    </div>
                    <p class="text-xs text-slate-500 font-medium">Tenant provisioning, lifecycle administration, cross-tenant isolation enforcement, and workspace switching</p>
                </div>
                <div v-if="$page.props.auth?.user?.current_team_id">
                    <a
                        :href="route('admin.exit-tenant')"
                        class="px-3.5 py-1.5 text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition shadow-xs"
                    >
                        Exit Impersonation &rarr;
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Platform System Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Tenants</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ metrics.total_tenants }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Active Tenants</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ metrics.active_tenants }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Suspended</p>
                    <p class="text-2xl font-bold text-rose-600 mt-1">{{ metrics.suspended_tenants }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Platform Users</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ metrics.total_users }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Products</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ metrics.total_products }}</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Orders</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ metrics.total_sales }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center gap-2">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        placeholder="Search tenant by company name, owner name, email..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilter"
                        class="py-2 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="suspended">Suspended Only</option>
                    </select>

                    <button
                        @click="resetFilter"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Tenants Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="tenant in tenants.data"
                    :key="tenant.id"
                    class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4 hover:shadow-md transition"
                >
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">{{ tenant.name }}</h2>
                                <p class="text-xs text-slate-400">{{ tenant.legal_name || 'Individual SaaS Account' }}</p>
                            </div>
                            <span
                                :class="tenant.status === 'suspended' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border uppercase"
                            >
                                {{ tenant.status }}
                            </span>
                        </div>

                        <div class="text-xs text-slate-500 space-y-0.5 pt-1">
                            <div><span class="font-semibold text-slate-600">Owner:</span> {{ tenant.owner?.name }} ({{ tenant.owner?.email }})</div>
                            <div v-if="tenant.email"><span class="font-semibold text-slate-600">Billing Email:</span> {{ tenant.email }}</div>
                        </div>

                        <!-- Stats Pills -->
                        <div class="grid grid-cols-3 gap-2 pt-2 text-center text-xs">
                            <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold text-slate-900 block">{{ tenant.users_count || 1 }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Users</span>
                            </div>
                            <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold text-slate-900 block">{{ tenant.warehouses_count || 0 }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Hubs</span>
                            </div>
                            <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                                <span class="font-bold text-slate-900 block">{{ tenant.products_count || 0 }}</span>
                                <span class="text-[10px] text-slate-400 uppercase">Products</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="switchToTenant(tenant)"
                            class="flex-1 py-2 px-3 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition text-center"
                        >
                            Switch Workspace
                        </button>
                        <button
                            type="button"
                            @click="toggleStatus(tenant)"
                            :class="tenant.status === 'suspended' ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200' : 'text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200'"
                            class="py-2 px-3 text-xs font-semibold rounded-xl border transition"
                        >
                            {{ tenant.status === 'suspended' ? 'Reactivate' : 'Suspend' }}
                        </button>
                    </div>
                </div>

                <div v-if="!tenants.data || tenants.data.length === 0" class="col-span-3 bg-white p-12 text-center rounded-2xl border border-slate-200 text-slate-400">
                    No client tenant workspaces found matching your query.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tenants.links && tenants.links.length > 3" class="bg-white p-4 rounded-2xl border border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <div>Showing {{ tenants.from || 0 }} to {{ tenants.to || 0 }} of {{ tenants.total }} tenants</div>
                <div class="flex items-center gap-1">
                    <button
                        v-for="(link, idx) in tenants.links"
                        :key="idx"
                        @click="router.visit(link.url)"
                        :disabled="!link.url"
                        :class="[
                            'px-3 py-1.5 rounded-lg transition',
                            link.active ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-slate-100 text-slate-700',
                            !link.url ? 'opacity-40 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
