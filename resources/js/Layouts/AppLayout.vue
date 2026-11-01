<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
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
const isSidebarPinned = ref(typeof window !== 'undefined' && localStorage.getItem('sidebar_pinned') === 'true');
const isSidebarHovered = ref(false);
const isSidebarExpanded = computed(() => {
    if (sidebarOpen.value) return true;
    return isSidebarPinned.value || isSidebarHovered.value;
});

function togglePinSidebar() {
    isSidebarPinned.value = !isSidebarPinned.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('sidebar_pinned', isSidebarPinned.value ? 'true' : 'false');
    }
}

const sidebarNav = ref(null);
const userMenuOpen = ref(false);
const topUserMenuOpen = ref(false);
const teamMenuOpen = ref(false);
const notificationMenuOpen = ref(false);
const bannerDismissed = ref(false);
const showLoginModal = ref(false);

function scrollToActiveNav(smooth = true) {
    nextTick(() => {
        if (!sidebarNav.value) return;

        const activeLink = sidebarNav.value.querySelector(
            'nav a.bg-indigo-600, nav a.bg-amber-600, nav [aria-current="page"]'
        );
        if (!activeLink) return;

        const container = sidebarNav.value;
        const containerRect = container.getBoundingClientRect();
        const linkRect = activeLink.getBoundingClientRect();

        const relativeTop = linkRect.top - containerRect.top;
        const relativeBottom = linkRect.bottom - containerRect.top;

        // Check if the item is already comfortably visible within container bounds
        const isComfortablyVisible = (
            relativeTop >= 20 &&
            relativeBottom <= (container.clientHeight - 20)
        );

        if (!isComfortablyVisible) {
            const currentScroll = container.scrollTop;
            const targetScroll = Math.max(
                0,
                currentScroll + relativeTop - (container.clientHeight / 2) + (activeLink.clientHeight / 2)
            );

            container.scrollTo({
                top: targetScroll,
                behavior: smooth ? 'smooth' : 'auto',
            });
        }
    });
}

watch(() => page.url, () => {
    scrollToActiveNav(true);
});

watch(sidebarOpen, (open) => {
    if (open) {
        setTimeout(() => scrollToActiveNav(false), 60);
    }
});

const user = computed(() => page.props.auth?.user);
const currentTeam = computed(() => page.props.auth?.team);
const flash = computed(() => page.props.flash);
const subscriptionNotice = computed(() => page.props.subscription_notice);

// Flash Alert 3-second Auto-Dismissal
const showSuccess = ref(false);
const showError = ref(false);
const showWarning = ref(false);
let successTimer = null;
let errorTimer = null;
let warningTimer = null;

watch(() => page.props.flash?.success, (val) => {
    if (val) {
        showSuccess.value = true;
        if (successTimer) clearTimeout(successTimer);
        successTimer = setTimeout(() => {
            showSuccess.value = false;
        }, 3000);
    } else {
        showSuccess.value = false;
    }
}, { immediate: true });

watch(() => page.props.flash?.error, (val) => {
    if (val) {
        showError.value = true;
        if (errorTimer) clearTimeout(errorTimer);
        errorTimer = setTimeout(() => {
            showError.value = false;
        }, 3000);
    } else {
        showError.value = false;
    }
}, { immediate: true });

watch(() => page.props.flash?.warning, (val) => {
    if (val) {
        showWarning.value = true;
        if (warningTimer) clearTimeout(warningTimer);
        warningTimer = setTimeout(() => {
            showWarning.value = false;
        }, 3000);
    } else {
        showWarning.value = false;
    }
}, { immediate: true });

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
    router.post(route('logout'), {}, {
        onSuccess: () => {
            window.location.href = route('login');
        },
        onError: () => {
            window.location.href = route('login');
        },
    });
}

function closeAllMenus() {
    userMenuOpen.value = false;
    topUserMenuOpen.value = false;
    teamMenuOpen.value = false;
    notificationMenuOpen.value = false;
}

onMounted(() => {
    scrollToActiveNav(false);
    setTimeout(() => scrollToActiveNav(true), 150);

    router.on('finish', () => {
        scrollToActiveNav(true);
    });

    if (subscriptionNotice.value) {
        const teamId = currentTeam.value?.id || 'team';
        const alreadySeenKey = `sub_alert_seen_${teamId}`;
        const hasSeenInSession = sessionStorage.getItem(alreadySeenKey);

        // If backend explicitly flashed login alert OR tenant hasn't seen it in this browser session yet
        if (page.props.subscription_login_alert || !hasSeenInSession) {
            showLoginModal.value = true;
            sessionStorage.setItem(alreadySeenKey, '1');
        }
    }
});
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
            @mouseenter="isSidebarHovered = true"
            @mouseleave="isSidebarHovered = false"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
                isSidebarExpanded ? 'w-64 shadow-2xl z-50' : 'w-64 md:w-[72px] md:shadow-none'
            ]" 
            class="fixed inset-y-0 left-0 z-50 bg-slate-900 text-slate-300 transition-all duration-300 ease-in-out flex flex-col justify-between border-r border-slate-800 shrink-0 select-none overflow-hidden"
        >
            <div ref="sidebarNav" class="overflow-y-auto overflow-x-hidden flex-1 custom-scrollbar scroll-smooth">
                <!-- Brand Header -->
                <div class="h-16 px-4 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/40 shrink-0">
                    <Link :href="isSuperAdmin && !isImpersonating ? route('admin.dashboard') : route('dashboard')" class="flex items-center gap-3 min-w-0 overflow-hidden">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-black text-lg shadow-md shadow-indigo-500/20 shrink-0">
                            I
                        </div>
                        <div v-show="isSidebarExpanded" class="min-w-0 transition-opacity duration-200">
                            <span class="font-bold text-white text-base tracking-tight leading-tight block truncate">Inoodex</span>
                            <span class="text-[10px] text-indigo-400 font-semibold tracking-wider uppercase block truncate">
                                {{ isSuperAdmin && !isImpersonating ? 'Platform Admin' : 'Cloud Inventory' }}
                            </span>
                        </div>
                    </Link>
                    <!-- Pin / Unpin Button on Desktop -->
                    <button
                        v-show="isSidebarExpanded"
                        type="button"
                        @click.stop="togglePinSidebar"
                        :title="isSidebarPinned ? 'Unpin sidebar (Auto-collapse on hover)' : 'Pin sidebar open'"
                        class="hidden md:flex p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        <svg v-if="isSidebarPinned" class="w-4 h-4 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                        <svg v-else class="w-4 h-4 text-slate-400 hover:text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>
                    <!-- Mobile Close Button -->
                    <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tenant / Organization Switcher -->
                <div v-if="isImpersonating && currentTeam" class="p-3 border-b border-amber-500/30 bg-amber-950/40 shrink-0">
                    <div v-show="isSidebarExpanded" class="flex items-center justify-between mb-1">
                        <span class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Inspecting Tenant</span>
                        <Link :href="route('admin.exit-tenant')" method="post" as="button" class="text-[10px] font-bold text-amber-300 hover:text-white underline cursor-pointer">Exit &rarr;</Link>
                    </div>
                    <div class="flex items-center gap-2.5" :class="{ 'justify-center': !isSidebarExpanded }">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-300 font-bold text-xs shrink-0" :title="!isSidebarExpanded ? currentTeam.name : ''">
                            {{ currentTeam.name.substring(0, 1) }}
                        </div>
                        <span v-show="isSidebarExpanded" class="font-semibold text-xs text-amber-200 truncate">{{ currentTeam.name }}</span>
                    </div>
                </div>

                <div v-else-if="!isSuperAdmin && currentTeam" class="p-3 border-b border-slate-800/60 bg-slate-900/40 relative shrink-0">
                    <div v-show="isSidebarExpanded" class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1 flex items-center justify-between">
                        <span>Current Organization</span>
                        <button 
                            v-if="user?.all_teams?.length > 1" 
                            @click="teamMenuOpen = !teamMenuOpen"
                            class="text-indigo-400 hover:text-indigo-300 text-[10px] lowercase"
                        >
                            switch &dtrif;
                        </button>
                    </div>
                    <div class="flex items-center gap-2.5" :class="{ 'justify-center': !isSidebarExpanded }">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold text-xs shrink-0" :title="!isSidebarExpanded ? currentTeam.name : ''">
                            {{ currentTeam.name.substring(0, 1) }}
                        </div>
                        <span v-show="isSidebarExpanded" class="font-semibold text-xs text-slate-200 truncate">{{ currentTeam.name }}</span>
                    </div>

                    <!-- Teams Dropdown (only when expanded) -->
                    <div v-if="teamMenuOpen && isSidebarExpanded" class="absolute left-4 right-4 top-full mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-xl py-1 z-30">
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
                <nav class="p-2 space-y-1">
                    <!-- Super Admin Platform Navigation (when not inspecting a tenant) -->
                    <template v-if="!showTenantSidebar">
                        <div v-show="isSidebarExpanded" class="px-3 pt-1 pb-2">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold tracking-wider uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                Super Admin Mode
                            </div>
                        </div>

                        <div v-if="isSidebarExpanded" class="pt-2 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Platform Management</div>
                        <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>
                        
                        <Link 
                            :href="route('admin.dashboard')" 
                            :title="!isSidebarExpanded ? 'Platform Overview & Tenants' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('admin.dashboard') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Platform Overview & Tenants</span>
                        </Link>

                        <Link 
                            :href="route('admin.plans.index')" 
                            :title="!isSidebarExpanded ? 'Subscription Plans' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('admin.plans.*') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Subscription Plans</span>
                        </Link>

                        <Link 
                            :href="route('admin.payments.index')" 
                            :title="!isSidebarExpanded ? 'Payment Verifications' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('admin.payments.*') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Payment Verifications</span>
                        </Link>

                        <div v-if="isSidebarExpanded" class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Security & Account</div>
                        <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                        <Link 
                            :href="route('profile.show')" 
                            :title="!isSidebarExpanded ? 'Profile Settings' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('profile.show') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Profile Settings</span>
                        </Link>
                    </template>

                    <!-- Tenant / Business Navigation -->
                    <template v-else>
                        <!-- Standard Navigation -->
                        <Link 
                            :href="route('dashboard')" 
                            :title="!isSidebarExpanded ? 'Dashboard' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('dashboard') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Dashboard</span>
                        </Link>

                        <!-- Sales & CRM -->
                        <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                            <span>Sales & CRM</span>
                        </div>
                        <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                        <Link 
                            :href="route('pos.index')" 
                            :title="!isSidebarExpanded ? 'POS Terminal' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('pos.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div v-show="isSidebarExpanded" class="flex items-center justify-between flex-1 truncate">
                                <span class="truncate">POS Terminal</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 ml-2 shrink-0">Fast</span>
                            </div>
                        </Link>

                        <Link 
                            :href="route('quotations.index')" 
                            :title="!isSidebarExpanded ? 'Quotations & Estimates' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('quotations.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Quotations & Estimates</span>
                        </Link>

                        <Link 
                            :href="route('sales.index')" 
                            :title="!isSidebarExpanded ? 'Sales & Dispatches' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('sales.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Sales & Dispatches</span>
                        </Link>

                        <Link 
                            :href="route('challans.index')" 
                            :title="!isSidebarExpanded ? 'Delivery Challans' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('challans.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Delivery Challans</span>
                        </Link>

                        <Link 
                            :href="route('dues.index')" 
                            :title="!isSidebarExpanded ? 'Customer Due Ledger' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('dues.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Customer Due Ledger</span>
                        </Link>

                        <Link 
                            :href="route('returns.index')" 
                            :title="!isSidebarExpanded ? 'Return Management' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('returns.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Return Management</span>
                        </Link>

                        <!-- Procurement & Vendors -->
                        <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                            <span>Procurement & Vendors</span>
                        </div>
                        <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                        <Link 
                            :href="route('purchases.index')" 
                            :title="!isSidebarExpanded ? 'Purchases & Inbound' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('purchases.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Purchases & Inbound</span>
                        </Link>

                        <Link 
                            :href="route('vendors.index')" 
                            :title="!isSidebarExpanded ? 'Vendors & Suppliers' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('vendors.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Vendors & Suppliers</span>
                        </Link>

                        <Link 
                            :href="route('vendor-dues.index')" 
                            :title="!isSidebarExpanded ? 'Vendor Due Ledger' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('vendor-dues.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Vendor Due Ledger</span>
                        </Link>

                        <!-- Inventory & Warehouses -->
                        <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                            <span>Inventory & Warehouses</span>
                        </div>
                        <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                        <Link 
                            :href="route('inventory.index')" 
                            :title="!isSidebarExpanded ? 'Stock & Inventory' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('inventory.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Stock & Inventory</span>
                        </Link>

                        <Link 
                            :href="route('products.index')" 
                            :title="!isSidebarExpanded ? 'Products & Catalog' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('products.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Products & Catalog</span>
                        </Link>

                        <Link 
                            :href="route('categories.index')" 
                            :title="!isSidebarExpanded ? 'Categories' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('categories.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Categories</span>
                        </Link>

                        <Link 
                            :href="route('brands.index')" 
                            :title="!isSidebarExpanded ? 'Brands' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('brands.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Brands</span>
                        </Link>

                        <Link 
                            :href="route('warehouses.index')" 
                            :title="!isSidebarExpanded ? 'Warehouses & Hubs' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('warehouses.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Warehouses & Hubs</span>
                        </Link>

                        <Link 
                            :href="route('warranties.index')" 
                            :title="!isSidebarExpanded ? 'Serials & Warranties' : ''"
                            class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                            :class="[
                                isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                                route().current('warranties.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Serials & Warranties</span>
                        </Link>

                    <!-- Services & Projects -->
                    <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                        <span>Services & Projects</span>
                    </div>
                    <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                    <Link 
                        :href="route('services.index')" 
                        :title="!isSidebarExpanded ? 'Services & Workshop' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('services.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Services & Workshop</span>
                    </Link>

                    <Link 
                        :href="route('projects.index')" 
                        :title="!isSidebarExpanded ? 'Projects & Costing' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('projects.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Projects & Costing</span>
                    </Link>

                    <!-- HR & Payroll -->
                    <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                        <span>HR & Payroll</span>
                    </div>
                    <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                    <Link 
                        :href="route('employees.index')" 
                        :title="!isSidebarExpanded ? 'Staff & Employees' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('employees.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Staff & Employees</span>
                    </Link>

                    <Link 
                        :href="route('expenses.index')" 
                        :title="!isSidebarExpanded ? 'Daily Expenses' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('expenses.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Daily Expenses</span>
                    </Link>

                    <Link 
                        :href="route('payroll.index')" 
                        :title="!isSidebarExpanded ? 'Staff Payroll' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('payroll.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Staff Payroll</span>
                    </Link>

                    <!-- Accounting & Finance -->
                    <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                        <span>Accounting & Finance</span>
                    </div>
                    <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                    <Link 
                        :href="route('bank-accounts.index')" 
                        :title="!isSidebarExpanded ? 'Bank & MFS Accounts' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('bank-accounts.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Bank & MFS Accounts</span>
                    </Link>

                    <Link 
                        :href="route('accounts.index')" 
                        :title="!isSidebarExpanded ? 'Chart of Accounts' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('accounts.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Chart of Accounts</span>
                    </Link>

                    <Link 
                        :href="route('journal.index')" 
                        :title="!isSidebarExpanded ? 'Journal Entries' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('journal.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Journal Entries</span>
                    </Link>

                    <!-- Bank Reconciliation (Hidden for now)
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
                    -->

                    <Link 
                        :href="route('reports.trial-balance')" 
                        :title="!isSidebarExpanded ? 'Trial Balance' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('reports.trial-balance') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Trial Balance</span>
                    </Link>

                    <Link 
                        :href="route('reports.profit-loss')" 
                        :title="!isSidebarExpanded ? 'Profit & Loss' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('reports.profit-loss') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Profit & Loss</span>
                    </Link>

                    <Link 
                        :href="route('reports.balance-sheet')" 
                        :title="!isSidebarExpanded ? 'Balance Sheet' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('reports.balance-sheet') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Balance Sheet</span>
                    </Link>

                    <Link 
                        :href="route('reports.general-ledger')" 
                        :title="!isSidebarExpanded ? 'General Ledger' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('reports.general-ledger') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 3h16M8 4v3m8-3v3" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">General Ledger</span>
                    </Link>

                    <!-- Analytics -->
                    <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                        <span>Analytics</span>
                    </div>
                    <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                    <Link 
                        :href="route('reports.index')" 
                        :title="!isSidebarExpanded ? 'Reports & Valuation' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('reports.index') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Reports & Valuation</span>
                    </Link>

                    <!-- Settings & Team -->
                    <div v-if="isSidebarExpanded" class="pt-4 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                        <span>Settings & Team</span>
                    </div>
                    <div v-else class="my-2 border-t border-slate-800/70 mx-1.5"></div>

                    <Link 
                        :href="route('settings.company')" 
                        :title="!isSidebarExpanded ? 'Company & Branding' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('settings.company') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Company & Branding</span>
                    </Link>

                    <Link 
                        :href="route('subscription.index')" 
                        :title="!isSidebarExpanded ? 'Subscription & Billing' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('subscription.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Subscription & Billing</span>
                    </Link>

                    <Link 
                        :href="route('settings.team')" 
                        :title="!isSidebarExpanded ? 'Team & Roles' : ''"
                        class="flex items-center rounded-xl text-xs font-semibold transition-all group overflow-hidden whitespace-nowrap"
                        :class="[
                            isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5',
                            route().current('settings.team') || route().current('teams.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                        ]"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span v-show="isSidebarExpanded" class="truncate">Team & Roles</span>
                    </Link>

                    <div v-if="isImpersonating" class="pt-3 pb-1">
                        <Link 
                            :href="route('admin.exit-tenant')" 
                            method="post"
                            as="button"
                            :title="!isSidebarExpanded ? 'Exit Tenant Workspace' : ''"
                            class="w-full flex items-center rounded-xl text-xs font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400 hover:bg-amber-500/20 hover:text-amber-300 transition-all overflow-hidden whitespace-nowrap cursor-pointer"
                            :class="isSidebarExpanded ? 'gap-3 px-3.5 py-2.5' : 'justify-center p-2.5'"
                        >
                            <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span v-show="isSidebarExpanded" class="truncate">Exit Tenant Workspace</span>
                        </Link>
                    </div>
                    </template>
                </nav>
            </div>
         
        </aside>

        <!-- Main Content Area -->
        <div 
            class="flex-1 flex flex-col min-h-screen min-w-0 w-full transition-[padding] duration-300"
            :class="isSidebarPinned ? 'md:pl-64' : 'md:pl-[72px]'"
        >
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
                <Link 
                    :href="route('admin.exit-tenant')" 
                    method="post"
                    as="button"
                    class="ml-4 shrink-0 px-3 py-1 rounded-md bg-white text-amber-900 font-bold hover:bg-amber-50 text-[11px] shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                >
                    <span>Exit Workspace</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </Link>
            </div>

            <!-- Top App Bar -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-8 flex items-center justify-between sticky top-0 z-40 shadow-xs">
                <div class="flex items-center gap-3 min-w-0 flex-1 mr-4">
                    <!-- Mobile Hamburger -->
                    <button 
                        @click="sidebarOpen = true" 
                        class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 shrink-0"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Desktop Sidebar Toggle -->
                    <button 
                        @click="togglePinSidebar" 
                        type="button"
                        :title="isSidebarPinned ? 'Collapse to icon sidebar (expand on hover)' : 'Pin sidebar open'"
                        class="hidden md:inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 shrink-0 transition-colors"
                    >
                        <svg class="w-5 h-5 transition-transform duration-200" :class="isSidebarPinned ? '' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
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

                <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                    <!-- POS Quick Launch Button -->
                    <Link
                        v-if="showTenantSidebar"
                        :href="route('pos.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-bold text-xs shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.03] active:scale-[0.98]"
                        title="Open Point of Sale Terminal"
                    >
                        <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="tracking-wide">POS</span>
                    </Link>

                    <!-- Quick Currency Pill -->
                    <div v-if="currentTeam" class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium border border-slate-200">
                        <span class="text-indigo-600 font-bold">{{ currentTeam.currency_symbol || '৳' }}</span>
                        <span>{{ currentTeam.currency_code || 'BDT' }}</span>
                    </div>

                    <!-- Notification Bell Trigger & Dropdown -->
                    <div class="relative">
                        <button 
                            type="button"
                            @click="notificationMenuOpen = !notificationMenuOpen; topUserMenuOpen = false"
                            class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none"
                            title="Workspace Notifications"
                            aria-label="Workspace Notifications"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>

                            <!-- Glowing ping indicator when subscription requires renewal/action -->
                            <span v-if="subscriptionNotice" class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                            </span>
                        </button>

                        <!-- Invisible backdrop to close on outside click -->
                        <div 
                            v-if="notificationMenuOpen" 
                            class="fixed inset-0 z-40" 
                            @click="notificationMenuOpen = false"
                        ></div>

                        <!-- Notification Dropdown -->
                        <div 
                            v-if="notificationMenuOpen"
                            class="absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-slate-200 rounded-2xl shadow-2xl py-2 z-50 divide-y divide-slate-100 overflow-hidden"
                        >
                            <div class="px-4 py-2.5 flex items-center justify-between bg-slate-50/80">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800 tracking-wide uppercase">Notifications</span>
                                    <span v-if="subscriptionNotice" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 animate-pulse">
                                        1 Urgent
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400">Workspace Alerts</span>
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                <!-- Urgent Subscription Notice Card -->
                                <div 
                                    v-if="subscriptionNotice" 
                                    class="p-4 transition border-l-4"
                                    :class="subscriptionNotice.is_expired ? 'bg-rose-50/60 border-rose-500' : 'bg-amber-50/60 border-amber-500'"
                                >
                                    <div class="flex items-start gap-3">
                                        <div 
                                            class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                                            :class="subscriptionNotice.is_expired ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-700'"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <h4 class="text-xs font-bold text-slate-900">
                                                    {{ subscriptionNotice.is_expired ? 'Subscription Expired' : 'Keep Subscription Active' }}
                                                </h4>
                                                <span 
                                                    class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded"
                                                    :class="subscriptionNotice.is_expired ? 'bg-rose-200 text-rose-800' : 'bg-amber-200 text-amber-900'"
                                                >
                                                    {{ subscriptionNotice.days_remaining <= 0 ? 'Expired' : `${subscriptionNotice.days_remaining}d left` }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                                Your <strong class="text-slate-900 font-semibold">{{ subscriptionNotice.plan_name }}</strong> subscription 
                                                <template v-if="subscriptionNotice.is_expired">
                                                    expired on <span class="font-semibold text-rose-700">{{ subscriptionNotice.ends_at }}</span>.
                                                </template>
                                                <template v-else>
                                                    will expire in <strong class="text-amber-800">{{ subscriptionNotice.days_remaining }} day{{ subscriptionNotice.days_remaining === 1 ? '' : 's' }}</strong> (on {{ subscriptionNotice.ends_at }}).
                                                </template>
                                                Keep your subscription active to prevent workspace interruption.
                                            </p>
                                            <div class="mt-3">
                                                <Link 
                                                    :href="route('subscription.index')" 
                                                    @click="notificationMenuOpen = false"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs hover:shadow transition"
                                                >
                                                    <span>Keep Subscription (Renew Now)</span>
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                    </svg>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State when no urgent notifications -->
                                <div v-else class="p-6 text-center">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">All caught up!</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Your subscription is in good standing. No urgent alerts.</p>
                                </div>
                            </div>

                            <div class="p-2.5 bg-slate-50/70 text-center">
                                <Link 
                                    :href="route('subscription.index')" 
                                    @click="notificationMenuOpen = false"
                                    class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1"
                                >
                                    <span>Manage Subscription & Billing</span>
                                    <span>&rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Top User Menu Trigger -->
                    <div class="relative">
                        <button 
                            type="button"
                            @click.stop="topUserMenuOpen = !topUserMenuOpen; notificationMenuOpen = false"
                            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer"
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
                            class="absolute right-0 mt-2 w-60 bg-white border border-slate-200 rounded-2xl shadow-xl py-1.5 z-50 divide-y divide-slate-100 overflow-hidden"
                        >
                            <div class="px-4 py-2.5">
                                <div class="flex items-center justify-between mb-0.5">
                                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Signed in as</p>
                                    <span v-if="isSuperAdmin" class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">
                                        Super Admin
                                    </span>
                                </div>
                                <p class="text-xs font-bold text-slate-900 truncate">{{ user?.name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ user?.email }}</p>
                            </div>

                            <!-- Super Admin Platform Control Links -->
                            <div v-if="isSuperAdmin" class="py-1 bg-amber-50/30">
                                <Link 
                                    :href="route('admin.dashboard')" 
                                    @click="topUserMenuOpen = false"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-amber-900 hover:bg-amber-50 font-bold transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span>Platform Control Panel</span>
                                </Link>
                                <Link 
                                    :href="route('admin.plans.index')" 
                                    @click="topUserMenuOpen = false"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-amber-50 hover:text-amber-800 font-medium transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>Subscription Plans</span>
                                </Link>
                                <Link 
                                    :href="route('admin.payments.index')" 
                                    @click="topUserMenuOpen = false"
                                    class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-amber-50 hover:text-amber-800 font-medium transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Payment Verifications</span>
                                </Link>
                                <Link 
                                    v-if="isImpersonating"
                                    :href="route('admin.exit-tenant')" 
                                    method="post"
                                    as="button"
                                    class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs text-amber-700 hover:bg-amber-100 font-bold transition cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Exit Tenant Workspace &rarr;</span>
                                </Link>
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

                                <!-- Tenant-scoped settings (only displayed when active tenant context exists) -->
                                <template v-if="currentTeam">
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
                                    <Link 
                                        :href="route('settings.team')" 
                                        @click="topUserMenuOpen = false"
                                        class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer"
                                    >
                                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span>Team & Staff Roles</span>
                                    </Link>
                                    <Link 
                                        :href="route('subscription.index')" 
                                        @click="topUserMenuOpen = false"
                                        class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer"
                                    >
                                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        <span>Subscription & Billing</span>
                                    </Link>
                                </template>
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

            <!-- Subscription Notice Banner (Rendered right under header if <= 7 days or expired) -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div 
                    v-if="subscriptionNotice && !bannerDismissed" 
                    class="bg-gradient-to-r text-white px-4 md:px-8 py-2.5 shadow-md flex items-center justify-between gap-3 text-xs md:text-sm font-medium z-20"
                    :class="subscriptionNotice.is_expired ? 'from-rose-600 via-rose-700 to-amber-700' : 'from-amber-600 via-orange-600 to-indigo-700'"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="flex h-2.5 w-2.5 relative shrink-0">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
                        </span>
                        <div class="truncate">
                            <span class="font-extrabold uppercase tracking-wide text-[11px] bg-white/20 px-2 py-0.5 rounded mr-2">Subscription Alert</span>
                            <span>
                                Your <strong class="underline font-bold">{{ subscriptionNotice.plan_name }}</strong> subscription 
                                <template v-if="subscriptionNotice.is_expired">
                                    expired on <strong>{{ subscriptionNotice.ends_at }}</strong>!
                                </template>
                                <template v-else>
                                    expires in <strong class="text-amber-200 font-black">{{ subscriptionNotice.days_remaining }} day{{ subscriptionNotice.days_remaining === 1 ? '' : 's' }}</strong> (on {{ subscriptionNotice.ends_at }}).
                                </template>
                                Keep your subscription active to avoid workspace interruption.
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <Link 
                            :href="route('subscription.index')" 
                            class="px-3 py-1.5 rounded-lg bg-white text-slate-900 font-bold hover:bg-slate-100 text-xs shadow-xs transition inline-flex items-center gap-1.5"
                        >
                            <span>Renew Now</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <button 
                            type="button" 
                            @click="bannerDismissed = true"
                            class="p-1 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition"
                            title="Dismiss notification banner"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Flash Alert Messages (3-Second Smooth Fade Away) -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-500 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div 
                    v-if="showSuccess && flash?.success" 
                    class="mx-4 md:mx-8 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="truncate">{{ flash.success }}</span>
                    </div>
                    <button 
                        type="button" 
                        @click="showSuccess = false" 
                        class="p-1 rounded-lg hover:bg-emerald-100 text-emerald-600 hover:text-emerald-900 transition-colors ml-3 shrink-0"
                        aria-label="Dismiss alert"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </Transition>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-500 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div 
                    v-if="showError && flash?.error" 
                    class="mx-4 md:mx-8 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="truncate">{{ flash.error }}</span>
                    </div>
                    <button 
                        type="button" 
                        @click="showError = false" 
                        class="p-1 rounded-lg hover:bg-rose-100 text-rose-600 hover:text-rose-900 transition-colors ml-3 shrink-0"
                        aria-label="Dismiss alert"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </Transition>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-500 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div 
                    v-if="showWarning && flash?.warning" 
                    class="mx-4 md:mx-8 mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center justify-between shadow-sm"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="truncate">{{ flash.warning }}</span>
                    </div>
                    <button 
                        type="button" 
                        @click="showWarning = false" 
                        class="p-1 rounded-lg hover:bg-amber-100 text-amber-600 hover:text-amber-900 transition-colors ml-3 shrink-0"
                        aria-label="Dismiss alert"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </Transition>

            <!-- Page Body -->
            <main class="flex-1 p-4 md:p-8 min-w-0 w-full">
                <slot />
            </main>
        </div>

        <!-- Auto Login Subscription Alert Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-if="showLoginModal && subscriptionNotice" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs"
                role="dialog"
                aria-modal="true"
            >
                <!-- Outside click backdrop -->
                <div class="fixed inset-0" @click="showLoginModal = false"></div>

                <div 
                    class="relative bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 z-10 transform transition-all"
                >
                    <!-- Header -->
                    <div 
                        class="px-6 py-6 text-white relative"
                        :class="subscriptionNotice.is_expired ? 'bg-gradient-to-br from-rose-600 via-rose-700 to-rose-900' : 'bg-gradient-to-br from-amber-500 via-orange-600 to-indigo-700'"
                    >
                        <button 
                            type="button" 
                            @click="showLoginModal = false"
                            class="absolute top-4 right-4 p-1.5 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition"
                            title="Dismiss notice"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-white shadow-inner shrink-0">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-[11px] font-black uppercase tracking-wider bg-white/20 px-2.5 py-0.5 rounded-full">
                                    {{ subscriptionNotice.is_expired ? 'Subscription Expired' : 'Action Required' }}
                                </span>
                                <h3 class="text-lg md:text-xl font-bold tracking-tight text-white mt-1">
                                    {{ subscriptionNotice.is_expired ? 'Your Subscription Has Expired' : 'Keep Your Subscription Active' }}
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <div class="rounded-2xl bg-slate-50 border border-slate-200/80 p-4 mb-4">
                            <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                <span>Workspace Plan</span>
                                <span class="font-bold text-slate-800 uppercase">{{ subscriptionNotice.plan_name }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                <span>Expiration Date</span>
                                <span class="font-bold text-slate-800">{{ subscriptionNotice.ends_at }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>Time Remaining</span>
                                <span 
                                    class="font-black px-2 py-0.5 rounded text-[11px]"
                                    :class="subscriptionNotice.is_expired ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ subscriptionNotice.days_remaining <= 0 ? 'Expired' : `${subscriptionNotice.days_remaining} day${subscriptionNotice.days_remaining === 1 ? '' : 's'} remaining` }}
                                </span>
                            </div>
                        </div>

                        <p class="text-xs md:text-sm text-slate-600 leading-relaxed">
                            Welcome back, <strong class="text-slate-900">{{ user?.name }}</strong>! 
                            <template v-if="subscriptionNotice.is_expired">
                                Your workspace subscription has expired. Please renew now to maintain uninterrupted access to sales, invoicing, POS, and inventory management for your team.
                            </template>
                            <template v-else>
                                Your workspace subscription has only <strong class="text-amber-700 font-bold">{{ subscriptionNotice.days_remaining }} day{{ subscriptionNotice.days_remaining === 1 ? '' : 's' }}</strong> remaining (expires {{ subscriptionNotice.ends_at }}). Please renew to avoid any disruptions to your business operations.
                            </template>
                        </p>

                        <!-- Actions -->
                        <div class="mt-6 flex flex-col sm:flex-row items-center gap-3">
                            <Link 
                                :href="route('subscription.index')" 
                                @click="showLoginModal = false"
                                class="w-full sm:flex-1 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs md:text-sm text-center shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/40 transition inline-flex items-center justify-center gap-2"
                            >
                                <span>Keep Subscription Active (Renew)</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                            <button 
                                type="button" 
                                @click="showLoginModal = false"
                                class="w-full sm:w-auto py-3 px-4 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold text-xs text-center transition"
                            >
                                Remind Me Later
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
