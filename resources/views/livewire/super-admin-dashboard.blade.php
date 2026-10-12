<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-amber-50 rounded-full blur-3xl pointer-events-none"></div>
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-black text-gray-900 tracking-tight">Platform Super-Admin Control Plane</h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-700 border border-amber-200">Master Access</span>
                    </div>
                    <p class="text-sm text-gray-500">Oversee all renting client companies, monitor system usage, and manage subscription access.</p>
                </div>
            </div>
        </div>

        <!-- <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                System Status: All Nodes Operational
            </span>
        </div> -->
    </div>

    <!-- Feedback Alerts -->
    @if(session()->has('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- 4 High-level KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Total Tenants -->
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Renting Client Companies</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-gray-900 mt-3">{{ number_format($totalTenants) }}</div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mt-1">
                <span class="text-emerald-600 font-bold">{{ $activeTenants }} Active</span>
                <span>&bull;</span>
                <span class="text-rose-600 font-bold">{{ $suspendedTenants }} Suspended</span>
            </div>
        </div>

        <!-- Total Users -->
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Registered Users</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-blue-600 mt-3">{{ number_format($totalUsers) }}</div>
            <div class="text-xs text-gray-400 mt-1">Staff, managers, and owners</div>
        </div>

        <!-- Total Products Managed -->
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Managed SKUs</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-purple-600 mt-3">{{ number_format($totalProducts) }}</div>
            <div class="text-xs text-gray-400 mt-1">Catalog items across all clients</div>
        </div>

        <!-- Total Sales Processed -->
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Orders Processed</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-3">{{ number_format($totalSales) }}</div>
            <div class="text-xs text-gray-400 mt-1">Sales fulfillment orders</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full" style="max-width: 520px;">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" class="w-full bg-white border border-gray-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl pl-10 pr-4 py-2 text-xs text-gray-900 placeholder-gray-400 transition" placeholder="Search company, legal name...">
            </div>

            <select wire:model.live="statusFilter" class="w-full sm:w-44 bg-white border border-gray-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl px-3 py-2 text-xs text-gray-700 transition">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="trial">Trial</option>
            </select>
        </div>

        <div class="text-xs text-gray-400 font-mono">
            Showing {{ $tenants->total() }} client organizations
        </div>
    </div>

    <!-- Client Organizations Table -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3.5">Organization / Client</th>
                        <th class="px-5 py-3.5">Account Owner</th>
                        <th class="px-5 py-3.5 text-center">Warehouses</th>
                        <th class="px-5 py-3.5 text-center">SKUs</th>
                        <th class="px-5 py-3.5 text-center">Plan</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Access Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tenants as $team)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>{{ $team->name }}</span>
                                    @if($team->personal_team)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 border border-gray-200">PERSONAL</span>
                                    @endif
                                </div>
                                <div class="text-gray-400 text-[11px] mt-0.5">
                                    {{ $team->legal_name ?: 'Legal name not set' }}
                                    @if($team->tax_id)
                                        &bull; <span class="font-mono text-indigo-500">VAT: {{ $team->tax_id }}</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">Created: {{ $team->created_at ? $team->created_at->format('M d, Y') : '—' }}</div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-800">{{ $team->owner->name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">{{ $team->owner->email ?? '—' }}</div>
                            </td>

                            <td class="px-5 py-4 text-center font-semibold text-gray-700">
                                {{ $warehouseCounts[$team->id] ?? 0 }}
                            </td>

                            <td class="px-5 py-4 text-center font-semibold text-gray-700">
                                {{ $productsCounts[$team->id] ?? 0 }}
                            </td>

                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-indigo-50 text-indigo-600 border border-indigo-100">
                                    {{ $team->subscription_plan ?: 'Pro' }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-center">
                                @if($team->status === 'suspended')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-600 border border-rose-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                        Suspended
                                    </span>
                                @elseif($team->status === 'trial')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Trial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Active
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Suspend / Activate Toggle -->
                                    <button type="button"
                                            wire:click="toggleStatus({{ $team->id }})"
                                            wire:confirm="{{ $team->status === 'suspended' ? 'Reactivate this client account?' : 'Suspend this client account? Their team will immediately lose workspace access.' }}"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition border {{ $team->status === 'suspended' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-600 hover:text-white hover:border-emerald-600' : 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-600 hover:text-white hover:border-rose-600' }}">
                                        {{ $team->status === 'suspended' ? 'Reactivate' : 'Suspend' }}
                                    </button>

                                    <!-- Enter Workspace -->
                                    <button type="button"
                                            wire:click="switchToTenant({{ $team->id }})"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 transition">
                                        Enter &rarr;
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">No client organizations found matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tenants->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>

</div>
