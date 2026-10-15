<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const page = usePage();
const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const topUserMenuOpen = ref(false);
const teamMenuOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const currentTeam = computed(() => page.props.auth?.team);
const flash = computed(() => page.props.flash);

const isSuperAdmin = computed(() => Boolean(user.value?.is_super_admin));
const isImpersonating = computed(() => Boolean(user.value?.is_impersonating));
const showTenantSidebar = computed(() => !isSuperAdmin.value || isImpersonating.value);

function switchTeam(teamId) {
    teamMenuOpen.value = false;
    router.put(route('current-team.update'), {
        team_id: teamId,
    }, {
        preserveState: false,
    });
}

function logout() {
    router.post(route('logout'));
}

function closeAllMenus() {
    userMenuOpen.value = false;
    topUserMenuOpen.value = false;
    teamMenuOpen.value = false;
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans antialiased text-slate-800 flex flex-col md:flex-row">
        <!-- Mobile Sidebar Overlay -->
        <div 
            v-if="sidebarOpen" 
            class="fixed inset-0 bg-slate-900/80 z-40 md:hidden transition-opacity"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar Navigation -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'" 
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out flex flex-col justify-between border-r border-slate-800 shrink-0 shadow-xl md:shadow-none"
        >
            <div class="overflow-y-auto flex-1 custom-scrollbar">
                <!-- Brand Header -->
                <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/40">
                    <Link :href="isSuperAdmin && !isImpersonating ? route('admin.dashboard') : route('dashboard')" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-black text-lg shadow-md shadow-indigo-500/20">
                            I
                        </div>
                        <div>
                            <span class="font-bold text-white text-base tracking-tight leading-tight block">Inoodex</span>
                            <span class="text-[10px] text-indigo-400 font-semibold tracking-wider uppercase block">
                                {{ isSuperAdmin && !isImpersonating ? 'Platform Admin' : 'Cloud Inventory' }}
                            </span>
                        </div>
                    </Link>
                    <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tenant / Organization Switcher -->
                <div v-if="isImpersonating && currentTeam" class="px-4 py-3 border-b border-amber-500/30 bg-amber-950/40">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Inspecting Tenant</span>
                        <a :href="route('admin.exit-tenant')" class="text-[10px] font-bold text-amber-300 hover:text-white underline">Exit &rarr;</a>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-md bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-300 font-bold text-xs">
                            {{ currentTeam.name.substring(0, 1) }}
                        </div>
                        <span class="font-semibold text-xs text-amber-200 truncate">{{ currentTeam.name }}</span>
                    </div>
                </div>

                <div v-else-if="!isSuperAdmin && currentTeam" class="px-4 py-3 border-b border-slate-800/60 bg-slate-900/40 relative">
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1 flex items-center justify-between">
                        <span>Current Organization</span>
                        <button 
                            v-if="user?.all_teams?.length > 1" 
                            @click="teamMenuOpen = !teamMenuOpen"
                            class="text-indigo-400 hover:text-indigo-300 text-[10px] lowercase"
                        >
                            switch &dtrif;
                        </button>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-md bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold text-xs">
                            {{ currentTeam.name.substring(0, 1) }}
                        </div>
                        <span class="font-semibold text-xs text-slate-200 truncate">{{ currentTeam.name }}</span>
                    </div>

                    <!-- Teams Dropdown -->
                    <div v-if="teamMenuOpen" class="absolute left-4 right-4 top-full mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-xl py-1 z-30">
                        <div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-400">Switch Workspace</div>
                        <button 
                            v-for="team in user.all_teams" 
                            :key="team.id"
                            @click="switchTeam(team.id)"
                            class="w-full text-left px-3 py-2 text-xs hover:bg-slate-700 flex items-center justify-between"
                            :class="team.id === currentTeam.id ? 'text-indigo-400 font-bold' : 'text-slate-300'"
                        >
                            <span class="truncate">{{ team.name }}</span>
                            <span v-if="team.id === currentTeam.id" class="text-indigo-400">&check;</span>
                        </button>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="p-3 space-y-1">
                    <!-- Super Admin Platform Navigation (when not inspecting a tenant) -->
                    <template v-if="!showTenantSidebar">
                        <div class="px-3 pt-1 pb-2">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold tracking-wider uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                Super Admin Mode
                            </div>
                        </div>

                        <div class="pt-2 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Platform Management</div>
                        
                        <Link 
                            :href="route('admin.dashboard')" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                            :class="route().current('admin.dashboard') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span>Platform Overview & Tenants</span>
                        </Link>

                        <Link 
                            :href="route('admin.plans.index')" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                            :class="route().current('admin.plans.*') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Subscription Plans</span>
                        </Link>

                        <Link 
                            :href="route('admin.payments.index')" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                            :class="route().current('admin.payments.*') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Payment Verifications</span>
                        </Link>

                        <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">System Configuration</div>

                        <Link 
                            :href="route('settings.company')" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                            :class="route().current('settings.company') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Platform Branding & Profile</span>
                        </Link>
                    </template>

                    <!-- Tenant / Business Navigation -->
                    <template v-else>
                        <!-- Standard Navigation -->
                        <Link 
                            :href="route('dashboard')" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                            :class="route().current('dashboard') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span>Command Dashboard</span>
                        </Link>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Inventory Operations</div>

                    <Link 
                        :href="route('warehouses.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('warehouses.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Warehouses & Hubs</span>
                    </Link>

                    <Link 
                        :href="route('products.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('products.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Products & Catalog</span>
                    </Link>

                    <Link 
                        :href="route('vendors.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('vendors.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Vendors & Suppliers</span>
                    </Link>

                    <Link 
                        :href="route('purchases.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('purchases.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                        </svg>
                        <span>Purchases & Inbound</span>
                    </Link>

                    <Link 
                        :href="route('vendor-dues.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('vendor-dues.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                        </svg>
                        <span>Vendor Due Ledger</span>
                    </Link>

                    <Link 
                        :href="route('sales.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('sales.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>Sales & Dispatches</span>
                    </Link>

                    <Link 
                        :href="route('quotations.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('quotations.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Quotations & Estimates</span>
                    </Link>

                    <Link 
                        :href="route('challans.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('challans.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                        </svg>
                        <span>Delivery Challans</span>
                    </Link>

                    <Link 
                        :href="route('dues.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('dues.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Customer Due Ledger</span>
                    </Link>

                    <Link 
                        :href="route('returns.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('returns.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                        </svg>
                        <span>Returns & RMA</span>
                    </Link>

                    <Link 
                        :href="route('warranties.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('warranties.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Serials & Warranties</span>
                    </Link>

                    <Link 
                        :href="route('services.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('services.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Services & Workshop</span>
                    </Link>

                    <Link 
                        :href="route('projects.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('projects.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Projects & Costing</span>
                    </Link>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">HR & Accounts</div>

                    <Link 
                        :href="route('employees.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('employees.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Staff & Employees</span>
                    </Link>

                    <Link 
                        :href="route('expenses.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('expenses.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Daily Expenses</span>
                    </Link>

                    <Link 
                        :href="route('tada.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('tada.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Field TA/DA Claims</span>
                    </Link>

                    <Link 
                        :href="route('payroll.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('payroll.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Staff Payroll</span>
                    </Link>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Accounting & Finance</div>

                    <Link 
                        :href="route('accounts.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('accounts.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Chart of Accounts</span>
                    </Link>

                    <Link 
                        :href="route('journal.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('journal.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Journal Entries</span>
                    </Link>

                    <Link 
                        :href="route('reports.trial-balance')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('reports.trial-balance') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                        <span>Trial Balance</span>
                    </Link>

                    <Link 
                        :href="route('reports.profit-loss')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('reports.profit-loss') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span>Profit & Loss</span>
                    </Link>

                    <Link 
                        :href="route('reports.balance-sheet')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('reports.balance-sheet') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Balance Sheet</span>
                    </Link>

                    <Link 
                        :href="route('reports.general-ledger')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('reports.general-ledger') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 3h16M8 4v3m8-3v3" />
                        </svg>
                        <span>General Ledger</span>
                    </Link>

                    <Link 
                        :href="route('reconciliation.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('reconciliation.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Bank Reconciliation</span>
                    </Link>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Analytics</div>

                    <Link 
                        :href="route('reports.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('reports.index') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Reports & Valuation</span>
                    </Link>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Settings & Team</div>

                    <Link 
                        :href="route('settings.company')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('settings.company') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Company & Branding</span>
                    </Link>

                    <Link 
                        :href="route('subscription.index')" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all"
                        :class="route().current('subscription.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Subscription & Billing</span>
                    </Link>

                    <a 
                        v-if="currentTeam" 
                        :href="route('teams.show', currentTeam.id)" 
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-800/60 hover:text-slate-100 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Team & Roles</span>
                    </a>

                    <div v-if="isImpersonating" class="pt-3 pb-1">
                        <a 
                            :href="route('admin.exit-tenant')" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400 hover:bg-amber-500/20 hover:text-amber-300 transition-all"
                        >
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Exit Tenant Workspace</span>
                        </a>
                    </div>
                    </template>
                </nav>
            </div>
         
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 md:pl-64 flex flex-col min-h-screen min-w-0 w-full">
            <!-- Sticky Impersonation Alert Banner for Super Admin -->
            <div 
                v-if="isImpersonating" 
                class="bg-amber-600 text-white px-4 md:px-8 py-2.5 text-xs font-semibold flex items-center justify-between sticky top-0 z-50 shadow-sm border-b border-amber-500"
            >
                <div class="flex items-center gap-2 truncate">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 text-white shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <span class="truncate">
                        Super Admin Mode: Viewing tenant workspace <strong class="underline decoration-white/40">{{ currentTeam?.name }}</strong>. All actions operate on this tenant.
                    </span>
                </div>
                <a 
                    :href="route('admin.exit-tenant')" 
                    class="ml-4 shrink-0 px-3 py-1 rounded-md bg-white text-amber-900 font-bold hover:bg-amber-50 text-[11px] shadow-xs transition-colors flex items-center gap-1.5"
                >
                    <span>Exit Workspace</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>

            <!-- Top App Bar -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 shadow-xs">
                <div class="flex items-center gap-3 min-w-0 flex-1 mr-4">
                    <button 
                        @click="sidebarOpen = true" 
                        class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 shrink-0"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <slot name="header">
                        <div class="min-w-0">
                            <h1 class="text-base md:text-lg font-bold text-slate-900 tracking-tight leading-tight truncate">
                                {{ title || $page.props.page_title || 'Workspace Overview' }}
                            </h1>
                            <p v-if="subtitle" class="text-xs text-slate-500 truncate hidden sm:block">
                                {{ subtitle }}
                            </p>
                        </div>
                    </slot>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <!-- Quick Currency Pill -->
                    <div v-if="currentTeam" class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium border border-slate-200">
                        <span class="text-indigo-600 font-bold">{{ currentTeam.currency_symbol || '৳' }}</span>
                        <span>{{ currentTeam.currency_code || 'BDT' }}</span>
                    </div>

                    <!-- Top User Menu Trigger -->
                    <div class="relative">
                        <button 
                            @click="topUserMenuOpen = !topUserMenuOpen"
                            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none"
                        >
                            <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-xs shadow-xs">
                                {{ user?.name ? user.name.substring(0, 2).toUpperCase() : 'U' }}
                            </div>
                            <span class="text-xs font-semibold text-slate-700 hidden md:block">{{ user?.name }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Invisible backdrop to close on outside click -->
                        <div 
                            v-if="topUserMenuOpen" 
                            class="fixed inset-0 z-40" 
                            @click="topUserMenuOpen = false"
                        ></div>

                        <!-- Top User Dropdown -->
                        <div 
                            v-if="topUserMenuOpen"
                            class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-2xl shadow-xl py-1.5 z-50 divide-y divide-slate-100"
                        >
                            <div class="px-4 py-2.5">
                                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Signed in as</p>
                                <p class="text-xs font-bold text-slate-900 truncate">{{ user?.name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ user?.email }}</p>
                            </div>
                            <div class="py-1">
                                <Link 
                                    :href="route('profile.show')" 
                                    @click="topUserMenuOpen = false"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Profile Settings</span>
                                </Link>
                                <Link 
                                    :href="route('settings.company')" 
                                    @click="topUserMenuOpen = false"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span>Organization Settings</span>
                                </Link>
                            </div>
                            <div class="pt-1">
                                <button 
                                    type="button"
                                    @click="topUserMenuOpen = false; logout()" 
                                    class="w-full flex items-center gap-2 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 font-semibold text-left transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Log Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Alert Messages -->
            <div v-if="flash?.success" class="mx-4 md:mx-8 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ flash.success }}</span>
                </div>
            </div>

            <div v-if="flash?.error" class="mx-4 md:mx-8 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ flash.error }}</span>
                </div>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-4 md:p-8 min-w-0 w-full">
                <slot />
            </main>
        </div>
    </div>
</template>
