<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-50 rounded-full blur-3xl pointer-events-none"></div>
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Inventory Analytics &amp; Reports</h1>
                    <p class="text-sm text-gray-500">Real-time asset valuation, low-stock reorder alerts, and comprehensive movement audit trails.</p>
                </div>
            </div>
        </div>

        <!-- Tab Selector in Header -->
        <div class="flex items-center bg-gray-100 p-1.5 rounded-xl border border-gray-200">
            <button type="button" wire:click="setTab('valuation')" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'valuation' ? 'bg-indigo-600 text-white shadow' : 'text-gray-500 hover:text-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Stock Valuation</span>
            </button>

            <button type="button" wire:click="setTab('low_stock')" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'low_stock' ? 'bg-indigo-600 text-white shadow' : 'text-gray-500 hover:text-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Low-Stock Alerts</span>
                @if(count($lowStockItems) > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">{{ count($lowStockItems) }}</span>
                @endif
            </button>

            <button type="button" wire:click="setTab('movements')" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeTab === 'movements' ? 'bg-indigo-600 text-white shadow' : 'text-gray-500 hover:text-gray-800' }}">
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
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Units On Hand</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-gray-900 mt-3">{{ number_format($valuation['total_units']) }}</div>
                    <div class="text-xs text-gray-400 mt-1">Across all active facilities</div>
                </div>

                <!-- Total Cost Valuation -->
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Asset Value (At Cost)</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-indigo-600 mt-3">{{ $currencySymbol }}{{ number_format($valuation['total_cost'], 2) }}</div>
                    <div class="text-xs text-gray-400 mt-1">Capital invested in physical stock</div>
                </div>

                <!-- Retail Value -->
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Potential Sales Value</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-3">{{ $currencySymbol }}{{ number_format($valuation['total_retail'], 2) }}</div>
                    <div class="text-xs text-gray-400 mt-1">Based on active selling prices</div>
                </div>

                <!-- Profit & Margin -->
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Projected Gross Margin</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-purple-600 mt-3">{{ number_format($valuation['margin_percent'], 1) }}%</div>
                    <div class="text-xs text-gray-400 mt-1">+{{ $currencySymbol }}{{ number_format($valuation['potential_profit'], 2) }} profit potential</div>
                </div>
            </div>

            <!-- Breakdown Tables Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Warehouse Breakdown -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Valuation by Warehouse</h2>
                            <p class="text-xs text-gray-500">Inventory assets distributed across storage hubs.</p>
                        </div>
                        <span class="text-xs font-mono text-indigo-600">{{ count($valuation['warehouse_breakdown']) }} Hubs</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600">
                            <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-100">
                                <tr>
                                    <th class="px-5 py-3">Warehouse Hub</th>
                                    <th class="px-5 py-3 text-center">Units</th>
                                    <th class="px-5 py-3 text-right">Cost Value</th>
                                    <th class="px-5 py-3 text-right">Retail Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($valuation['warehouse_breakdown'] as $wh)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-gray-900 text-sm">{{ $wh->name }}</div>
                                            <div class="text-[10px] font-mono text-indigo-500">{{ $wh->code }}</div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-gray-700">{{ number_format($wh->units_count) }}</td>
                                        <td class="px-5 py-3.5 text-right font-bold text-indigo-600">{{ $currencySymbol }}{{ number_format($wh->cost_value, 2) }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">{{ $currencySymbol }}{{ number_format($wh->retail_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-gray-400">No warehouse stock data recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Valuation by Category</h2>
                            <p class="text-xs text-gray-500">Capital allocation across product categories.</p>
                        </div>
                        <span class="text-xs font-mono text-purple-600">{{ count($valuation['category_breakdown']) }} Categories</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600">
                            <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-100">
                                <tr>
                                    <th class="px-5 py-3">Category</th>
                                    <th class="px-5 py-3 text-center">Units</th>
                                    <th class="px-5 py-3 text-right">Cost Value</th>
                                    <th class="px-5 py-3 text-right">Retail Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($valuation['category_breakdown'] as $cat)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-5 py-3.5 font-bold text-gray-900 text-sm">{{ $cat->category_name }}</td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-gray-700">{{ number_format($cat->units_count) }}</td>
                                        <td class="px-5 py-3.5 text-right font-bold text-indigo-600">{{ $currencySymbol }}{{ number_format($cat->cost_value, 2) }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-emerald-600">{{ $currencySymbol }}{{ number_format($cat->retail_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-gray-400">No category stock data recorded yet.</td>
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
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <!-- <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg> -->
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="lowStockSearch" class="w-full bg-white border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2 text-xs text-gray-900 placeholder-gray-400 transition" placeholder="Search low stock SKU or title...">
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                    <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl font-medium flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        {{ count($lowStockItems) }} Items Require Restocking
                    </span>
                    <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shadow-sm">
                        + Create Purchase Order
                    </a>
                </div>
            </div>

            <!-- Low Stock Table -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3.5">Product &amp; SKU</th>
                                <th class="px-5 py-3.5">Category</th>
                                <th class="px-5 py-3.5 text-center">Current Stock</th>
                                <th class="px-5 py-3.5 text-center">Reorder Threshold</th>
                                <th class="px-5 py-3.5 text-center">Shortage / Deficit</th>
                                <th class="px-5 py-3.5 text-right">Unit Cost</th>
                                <th class="px-5 py-3.5 text-right">Estimated Restock Cost</th>
                                <th class="px-5 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($lowStockItems as $item)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-gray-900 text-sm">{{ $item['product']->name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ $item['product']->sku }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-500">{{ $item['product']->category->name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $item['current_stock'] <= 0 ? 'bg-rose-100 text-rose-600 border border-rose-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }}">
                                            {{ number_format($item['current_stock']) }} {{ $item['product']->unit ?? 'units' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center font-semibold text-gray-600">
                                        {{ number_format($item['reorder_level']) }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="font-bold text-rose-600">-{{ number_format($item['deficit']) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-medium text-gray-500">{{ $currencySymbol }}{{ number_format($item['cost_price'], 2) }}</td>
                                    <td class="px-5 py-4 text-right font-bold text-amber-600">{{ $currencySymbol }}{{ number_format($item['estimated_restock_cost'], 2) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('purchases.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition">
                                            <span>Restock</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                            <div class="text-gray-700 font-bold text-sm">All Inventory Levels Healthy</div>
                                            <div class="text-xs text-gray-400">No products are currently at or below their reorder threshold.</div>
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
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <!-- Search input -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <!-- <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg> -->
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="movementSearch" class="w-full bg-white border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-10 pr-4 py-2 text-xs text-gray-900 placeholder-gray-400 transition" placeholder="Search reason or SKU...">
                    </div>

                    <!-- Type Filter -->
                    <select wire:model.live="movementType" class="w-full sm:w-44 bg-white border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3 py-2 text-xs text-gray-700 transition">
                        <option value="">All Movement Types</option>
                        <option value="in">Inbound (Stock In)</option>
                        <option value="out">Outbound (Stock Out)</option>
                        <option value="transfer">Warehouse Transfer</option>
                        <option value="adjustment">Manual Adjustment</option>
                    </select>

                    <!-- Warehouse Filter -->
                    <select wire:model.live="movementWarehouseId" class="w-full sm:w-44 bg-white border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3 py-2 text-xs text-gray-700 transition">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-xs text-gray-400 font-mono">
                    Showing {{ $movements->total() }} recorded audit events
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3.5">Timestamp</th>
                                <th class="px-5 py-3.5 text-center">Type</th>
                                <th class="px-5 py-3.5">Product &amp; SKU</th>
                                <th class="px-5 py-3.5">Warehouse Hub</th>
                                <th class="px-5 py-3.5 text-center">Quantity</th>
                                <th class="px-5 py-3.5 text-right">Unit Cost</th>
                                <th class="px-5 py-3.5">Reference / Reason</th>
                                <th class="px-5 py-3.5">Operator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($movements as $m)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap text-gray-400 font-mono">
                                        {{ $m->created_at ? $m->created_at->format('M d, Y H:i:s') : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($m->type === 'in')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                + Stock In
                                            </span>
                                        @elseif($m->type === 'out')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-600 border border-rose-100">
                                                - Stock Out
                                            </span>
                                        @elseif($m->type === 'transfer')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-sky-50 text-sky-600 border border-sky-100">
                                                ⇄ Transfer
                                            </span>
                                        @else
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-100">
                                                ⚙ Adjust
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-gray-900 text-sm">{{ $m->product->name ?? 'Product' }}</div>
                                        <div class="text-[10px] font-mono text-indigo-500">SKU: {{ $m->product->sku ?? '—' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-600 font-medium">
                                        {{ $m->warehouse->name ?? '—' }} ({{ $m->warehouse->code ?? 'WH' }})
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-extrabold text-sm {{ in_array($m->type, ['in']) ? 'text-emerald-600' : (in_array($m->type, ['out']) ? 'text-rose-600' : 'text-gray-700') }}">
                                        {{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '-' : '') }}{{ number_format($m->quantity) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono text-gray-400">
                                        {{ $m->unit_cost ? $currencySymbol . number_format($m->unit_cost, 2) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 max-w-xs truncate">
                                        <div class="text-gray-800 font-medium truncate">{{ $m->reason ?: 'Standard stock transaction' }}</div>
                                        @if($m->reference_type)
                                            <div class="text-[10px] text-gray-400 font-mono">{{ class_basename($m->reference_type) }} #{{ $m->reference_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-400 text-[11px] whitespace-nowrap">
                                        {{ $m->creator->name ?? 'System' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">No stock movements recorded matching your filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                    <div class="p-4 border-t border-gray-100 bg-gray-50">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
