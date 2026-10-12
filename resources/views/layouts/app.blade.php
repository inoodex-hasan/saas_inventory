<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Inoodex Inventory') }}</title>

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
        </style>
    </head>
    <body class="h-full font-sans antialiased text-slate-800 bg-slate-50 flex flex-col md:flex-row overflow-x-hidden" x-data="{ sidebarOpen: false }">
        <x-banner />

        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/80 z-40 md:hidden" 
             @click="sidebarOpen = false"></div>

        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'" 
               class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out flex flex-col justify-between border-r border-slate-800 shrink-0">
            
            <div>
                <!-- Brand / Logo Header -->
                <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/40">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-black text-lg shadow-md shadow-indigo-500/20">
                            I
                        </div>
                        <div>
                            <span class="font-bold text-white text-base tracking-tight leading-tight block">Inoodex</span>
                            <span class="text-[10px] text-indigo-400 font-semibold tracking-wider uppercase block">Cloud Inventory</span>
                        </div>
                    </a>
                    <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tenant Company Badge -->
                @if(Auth::user()->isSuperAdmin() && Auth::user()->currentTeam)
                    <div class="px-4 py-3 border-b border-amber-500/30 bg-amber-950/40">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Inspecting Tenant</span>
                            <a href="{{ route('admin.exit-tenant') }}" class="text-[10px] font-bold text-amber-300 hover:text-white underline">Exit &rarr;</a>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-md bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-300 font-bold text-xs">
                                {{ substr(Auth::user()->currentTeam->name, 0, 1) }}
                            </div>
                            <span class="font-semibold text-xs text-amber-200 truncate">{{ Auth::user()->currentTeam->name }}</span>
                        </div>
                    </div>
                @elseif(Auth::user()->currentTeam)
                    <div class="px-4 py-3 border-b border-slate-800/60 bg-slate-900/40">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1">Current Organization</div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-md bg-indigo-500/20 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold text-xs">
                                {{ substr(Auth::user()->currentTeam->name, 0, 1) }}
                            </div>
                            <span class="font-semibold text-xs text-slate-200 truncate">{{ Auth::user()->currentTeam->name }}</span>
                        </div>
                    </div>
                @else
                    <div class="px-4 py-3 border-b border-slate-800/60 bg-slate-950/50">
                        <div class="text-[10px] uppercase font-bold text-amber-400 tracking-wider mb-1">Platform Control</div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-md bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 font-bold text-xs">
                                👑
                            </div>
                            <span class="font-semibold text-xs text-slate-200 truncate">Platform Super-Admin</span>
                        </div>
                    </div>
                @endif

                <!-- Navigation Links -->
                <nav class="p-3 space-y-1">
                    @if(Auth::user()->isSuperAdmin() && !Auth::user()->currentTeam)
                        <div class="pt-1 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-amber-400">Master Control</div>
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.*') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-amber-400 hover:bg-slate-800/60 hover:text-amber-300' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Platform Clients & Health</span>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span>Command Dashboard</span>
                        </a>

                        <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Inventory Operations</div>

                        <a href="{{ route('warehouses.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('warehouses.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span>Warehouses & Hubs</span>
                        </a>

                        <a href="{{ route('products.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('products.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Products & Catalog</span>
                        </a>

                        <a href="{{ route('purchases.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('purchases.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                            </svg>
                            <span>Purchases & Inbound</span>
                        </a>

                        <a href="{{ route('sales.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('sales.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Sales & Dispatches</span>
                        </a>

                        <a href="{{ route('reports.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Reports & Valuation</span>
                        </a>

                        <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Settings & Team</div>

                        <a href="{{ route('settings.company') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('settings.company') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Company & Branding</span>
                        </a>

                        @if(Auth::user()->currentTeam)
                            <a href="{{ route('teams.show', Auth::user()->currentTeam->id) }}" 
                               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('teams.show') ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Team & Roles</span>
                            </a>
                        @endif

                        @if(Auth::user()->isSuperAdmin())
                            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-amber-400">Master Control</div>
                            <a href="{{ route('admin.dashboard') }}" 
                               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.*') ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/30' : 'text-amber-400 hover:bg-slate-800/60 hover:text-amber-300' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Platform Admin</span>
                            </a>
                        @endif
                    @endif
                </nav>
            </div>

            <!-- User Quick Card in Sidebar -->
            <div class="p-3 border-t border-slate-800/80 bg-slate-950/30">
                <div class="flex items-center justify-between px-2 py-1.5 rounded-xl bg-slate-800/40 border border-slate-700/40">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-semibold text-slate-200 truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" title="Logout" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-700/50 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 md:pl-64">
            
            <!-- Top Bar -->
            <header class="h-16 bg-white border-b border-slate-200/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="md:hidden text-slate-500 hover:text-slate-700 p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-400">
                        <span>Workspace</span>
                        <span>/</span>
                        <span class="text-slate-800">{{ Auth::user()->currentTeam->name ?? 'Platform Super-Admin' }}</span>
                    </div>
                </div>

                <!-- Right Topbar Controls -->
                <div class="flex items-center gap-3">
                    
                    @if(Auth::user()->isSuperAdmin() && Auth::user()->currentTeam)
                        <a href="{{ route('admin.exit-tenant') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-800 hover:bg-amber-500/20 border border-amber-500/30 transition">
                            <span>👑 Exit Tenant Mode &rarr;</span>
                        </a>
                    @elseif(Auth::user()->isSuperAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-700 hover:bg-amber-500/20 border border-amber-500/30 shadow-2xs transition">
                            <span>👑 Platform Admin</span>
                        </a>
                    @endif

                    <!-- Team Switcher Dropdown (Only for users with active team) -->
                    @if (Auth::user()->currentTeam && Laravel\Jetstream\Jetstream::hasTeamFeatures())
                        <div class="relative" x-data="{ openTeam: false }">
                            <button @click="openTeam = !openTeam" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200/70 text-slate-700 transition">
                                <span>🏢 {{ Auth::user()->currentTeam->name }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="openTeam" 
                                 @click.away="openTeam = false" 
                                 x-transition 
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 text-xs font-medium">
                                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Switch Organization</div>
                                @foreach (Auth::user()->allTeams() as $team)
                                    <form method="POST" action="{{ route('current-team.update') }}">
                                        @csrf
                                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                                        <button type="submit" class="w-full text-left px-3 py-2 flex items-center justify-between hover:bg-slate-50 {{ Auth::user()->currentTeam->id === $team->id ? 'text-indigo-600 font-bold bg-indigo-50/50' : 'text-slate-700' }}">
                                            <span>{{ $team->name }}</span>
                                            @if(Auth::user()->currentTeam->id === $team->id)
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="{{ route('settings.company') }}" class="block px-3 py-1.5 text-slate-600 hover:bg-slate-50">
                                    🏢 Company & Branding
                                </a>
                                @if(Auth::user()->currentTeam)
                                    <a href="{{ route('teams.show', Auth::user()->currentTeam->id) }}" class="block px-3 py-1.5 text-slate-600 hover:bg-slate-50">
                                        ⚙️ Team & Members
                                    </a>
                                @endif
                                @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                    <a href="{{ route('teams.create') }}" class="block px-3 py-1.5 text-indigo-600 hover:bg-slate-50 font-semibold">
                                        ➕ Create New Team
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endif

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ openProfile: false }">
                        <button @click="openProfile = !openProfile" class="flex items-center gap-2 p-1 rounded-full hover:ring-2 hover:ring-slate-200 transition">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        </button>

                        <div x-show="openProfile" 
                             @click.away="openProfile = false" 
                             x-transition 
                             class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 text-xs font-medium">
                            <div class="px-3 py-1.5 border-b border-slate-100">
                                <div class="font-bold text-slate-800">{{ Auth::user()->name }}</div>
                                <div class="text-slate-400 text-[11px] truncate">{{ Auth::user()->email }}</div>
                            </div>
                            <a href="{{ route('profile.show') }}" class="block px-3 py-2 text-slate-700 hover:bg-slate-50">
                                My Profile & Security
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-2 text-rose-600 hover:bg-rose-50 font-semibold">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 pb-16">
                {{ $slot }}
            </main>
        </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>
