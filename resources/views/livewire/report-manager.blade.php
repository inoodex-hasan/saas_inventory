<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Inventory Analytics & Reports</h1>
                    <p class="text-sm text-slate-400">Real-time asset valuation, low-stock reorder alerts, and comprehensive movement audit trails.</p>
                </div>
            </div>
        </div>

        <!-- Tab Selector in Header -->
        <div class="flex items-center bg-slate-950 p-1.5 rounded-xl border border-slate-800">
            <button type="button" wire:click="setTab('valuation')" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'valuation' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Stock Valuation</span>
            </button>

            <button type="button" wire:click="setTab('low_stock')" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'low_stock' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Low-Stock Alerts</span>
                @if(count($lowStockItems) > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">{{ count($lowStockItems) }}</span>
                @endif
            </button>

            <button type="button" wire:click="setTab('movements')" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'movements' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span>Audit Ledger</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: STOCK VALUATION -->
    @if($activeTab === 'valuation')
        <div class="space-y-6">
            <!-- 4 Top KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Units -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Units On Hand</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-white mt-3">{{ number_format($valuation['total_units']) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Across all active facilities</div>
                </div>

                <!-- Total Cost Valuation -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Asset Value (At Cost)</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-indigo-400 mt-3">{{ $currencySymbol }}{{ number_format($valuation['total_cost'], 2) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Capital invested in physical stock</div>
                </div>

                <!-- Retail Value -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Potential Sales Value</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-emerald-400 mt-3">{{ $currencySymbol }}{{ number_format($valuation['total_retail'], 2) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Based on active selling prices</div>
                </div>

                <!-- Profit & Margin -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Projected Gross Margin</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-purple-300 mt-3">{{ number_format($valuation['margin_percent'], 1) }}%</div>
                    <div class="text-xs text-slate-400 mt-1">+{{ $currencySymbol }}{{ number_format($valuation['potential_profit'], 2) }} profit potential</div>
                </div>
            </div>

            <!-- Breakdown Tables Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Warehouse Breakdown -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-white">Valuation by Warehouse</h2>
                            <p class="text-xs text-slate-400">Inventory assets distributed across storage hubs.</p>
                        </div>
                        <span class="text-xs font-mono text-indigo-400">{{ count($valuation['warehouse_breakdown']) }} Hubs</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                                <tr>
                                    <th class="px-5 py-3">Warehouse Hub</th>
                                    <th class="px-5 py-3 text-center">Units</th>
                                    <th class="px-5 py-3 text-right">Cost Value</th>
                                    <th class="px-5 py-3 text-right">Retail Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @forelse($valuation['warehouse_breakdown'] as $wh)
                                    <tr class="hover:bg-slate-800/30 transition">
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-white text-sm">{{ $wh->name }}</div>
                                            <div class="text-[10px] font-mono text-indigo-400">{{ $wh->code }}</div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-slate-200">{{ number_format($wh->units_count) }}</td>
                                        <td class="px-5 py-3.5 text-right font-bold text-indigo-400">{{ $currencySymbol }}{{ number_format($wh->cost_value, 2) }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-400">{{ $currencySymbol }}{{ number_format($wh->retail_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-500">No warehouse stock data recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-white">Valuation by Category</h2>
                            <p class="text-xs text-slate-400">Capital allocation across product categories.</p>
                        </div>
                        <span class="text-xs font-mono text-purple-400">{{ count($valuation['category_breakdown']) }} Categories</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                                <tr>
                                    <th class="px-5 py-3">Category</th>
                                    <th class="px-5 py-3 text-center">Units</th>
                                    <th class="px-5 py-3 text-right">Cost Value</th>
                                    <th class="px-5 py-3 text-right">Retail Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @forelse($valuation['category_breakdown'] as $cat)
                                    <tr class="hover:bg-slate-800/30 transition">
                                        <td class="px-5 py-3.5 font-bold text-white text-sm">{{ $cat->category_name }}</td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-slate-200">{{ number_format($cat->units_count) }}</td>
                                        <td class="px-5 py-3.5 text-right font-bold text-indigo-400">{{ $currencySymbol }}{{ number_format($cat->cost_value, 2) }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-400">{{ $currencySymbol }}{{ number_format($cat->retail_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-500">No category stock data recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 2: LOW-STOCK ALERTS -->
    @if($activeTab === 'low_stock')
        <div class="space-y-6">
            <!-- Search & Filters -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <input type="text" wire:model.live.debounce.300ms="lowStockSearch" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-slate-500 transition" placeholder="Search low stock SKU or title...">
                    <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                    <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-3 py-1.5 rounded-xl font-medium flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                        {{ count($lowStockItems) }} Items Require Restocking
                    </span>
                    <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition shadow-md shadow-indigo-500/20">
                        + Create Purchase Order
                    </a>
                </div>
            </div>

            <!-- Low Stock Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">Product & SKU</th>
                                <th class="px-5 py-3.5">Category</th>
                                <th class="px-5 py-3.5 text-center">Current Stock</th>
                                <th class="px-5 py-3.5 text-center">Reorder Threshold</th>
                                <th class="px-5 py-3.5 text-center">Shortage / Deficit</th>
                                <th class="px-5 py-3.5 text-right">Unit Cost</th>
                                <th class="px-5 py-3.5 text-right">Estimated Restock Cost</th>
                                <th class="px-5 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($lowStockItems as $item)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-white text-sm">{{ $item['product']->name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-400">SKU: {{ $item['product']->sku }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-400">{{ $item['product']->category->name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $item['current_stock'] <= 0 ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                            {{ number_format($item['current_stock']) }} {{ $item['product']->unit ?? 'units' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center font-semibold text-slate-300">
                                        {{ number_format($item['reorder_level']) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="font-bold text-rose-400">-{{ number_format($item['deficit']) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-medium text-slate-400">{{ $currencySymbol }}{{ number_format($item['cost_price'], 2) }}</td>
                                    <td class="px-5 py-4 text-right font-bold text-amber-300">{{ $currencySymbol }}{{ number_format($item['estimated_restock_cost'], 2) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('purchases.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-600 hover:text-white transition">
                                            <span>Restock</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <div class="w-12 h-12 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                            <div class="text-slate-300 font-bold text-sm">All Inventory Levels Healthy</div>
                                            <div class="text-xs text-slate-500">No products are currently at or below their reorder threshold.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 3: STOCK MOVEMENT AUDIT LEDGER -->
    @if($activeTab === 'movements')
        <div class="space-y-6">
            <!-- Filter Toolbar -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <!-- Search input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" wire:model.live.debounce.300ms="movementSearch" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-slate-500 transition" placeholder="Search reason or SKU...">
                        <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Type Filter -->
                    <select wire:model.live="movementType" class="w-full sm:w-44 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3 py-2 text-xs text-white transition">
                        <option value="">All Movement Types</option>
                        <option value="in">Inbound (Stock In)</option>
                        <option value="out">Outbound (Stock Out)</option>
                        <option value="transfer">Warehouse Transfer</option>
                        <option value="adjustment">Manual Adjustment</option>
                    </select>

                    <!-- Warehouse Filter -->
                    <select wire:model.live="movementWarehouseId" class="w-full sm:w-44 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3 py-2 text-xs text-white transition">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-xs text-slate-400 font-mono">
                    Showing {{ $movements->total() }} recorded audit events
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">Timestamp</th>
                                <th class="px-5 py-3.5 text-center">Type</th>
                                <th class="px-5 py-3.5">Product & SKU</th>
                                <th class="px-5 py-3.5">Warehouse Hub</th>
                                <th class="px-5 py-3.5 text-center">Quantity</th>
                                <th class="px-5 py-3.5 text-right">Unit Cost</th>
                                <th class="px-5 py-3.5">Reference / Reason</th>
                                <th class="px-5 py-3.5">Operator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($movements as $m)
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap text-slate-400 font-mono">
                                        {{ $m->created_at ? $m->created_at->format('M d, Y H:i:s') : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($m->type === 'in')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                + Stock In
                                            </span>
                                        @elseif($m->type === 'out')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                - Stock Out
                                            </span>
                                        @elseif($m->type === 'transfer')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                                ⇄ Transfer
                                            </span>
                                        @else
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                                ⚙ Adjust
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-white text-sm">{{ $m->product->name ?? 'Product' }}</div>
                                        <div class="text-[10px] font-mono text-indigo-400">SKU: {{ $m->product->sku ?? '—' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-300 font-medium">
                                        {{ $m->warehouse->name ?? '—' }} ({{ $m->warehouse->code ?? 'WH' }})
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-extrabold text-sm {{ in_array($m->type, ['in']) ? 'text-emerald-400' : (in_array($m->type, ['out']) ? 'text-rose-400' : 'text-slate-200') }}">
                                        {{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '-' : '') }}{{ number_format($m->quantity) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-slate-400">
                                        {{ $m->unit_cost ? $currencySymbol . number_format($m->unit_cost, 2) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 max-w-xs truncate">
                                        <div class="text-white font-medium truncate">{{ $m->reason ?: 'Standard stock transaction' }}</div>
                                        @if($m->reference_type)
                                            <div class="text-[10px] text-slate-500 font-mono">{{ class_basename($m->reference_type) }} #{{ $m->reference_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-400 text-[11px] whitespace-nowrap">
                                        {{ $m->creator->name ?? 'System' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-slate-500">No stock movements recorded matching your filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                    <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
