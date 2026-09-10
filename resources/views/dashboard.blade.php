<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Dashboard Greeting & Quick Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Operational Dashboard
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Real-time overview of inventory valuation, pending dispatches, stock alerts, and fulfillment hubs.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('products.index') }}" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 shadow-2xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Product</span>
                    </a>
                    <a href="{{ route('warehouses.index') }}" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs hover:shadow transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Manage Hubs</span>
                    </a>
                </div>
            </div>

            <!-- Top KPI 4-Metric Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Total Holding Valuation -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs relative overflow-hidden group hover:border-indigo-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Inventory Value</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 tracking-tight">
                            ${{ number_format($totalValuation, 2) }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <span class="font-semibold text-slate-600">{{ number_format($totalProducts) }}</span> unique catalog items
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Sales Volume -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs relative overflow-hidden group hover:border-emerald-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sales Revenue</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 tracking-tight">
                            ${{ number_format($totalSalesVolume, 2) }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <span class="font-semibold text-emerald-600">{{ number_format($totalSalesOrders) }}</span> total orders placed
                        </div>
                    </div>
                </div>

                <!-- Card 3: Open Inbound POs -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs relative overflow-hidden group hover:border-amber-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending POs</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 tracking-tight">
                            {{ number_format($pendingPurchasesCount) }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <span class="font-semibold text-amber-600">${{ number_format($openPurchaseOrders, 2) }}</span> inbound value
                        </div>
                    </div>
                </div>

                <!-- Card 4: Low Stock Alert Radar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs relative overflow-hidden group hover:border-rose-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Low Stock Warnings</span>
                        <div class="w-9 h-9 rounded-xl {{ $lowStockCount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">
                            {{ number_format($lowStockCount) }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1">
                            {{ $lowStockCount > 0 ? 'Require immediate reordering' : 'All items at healthy stock' }}
                        </div>
                    </div>
                </div>

            </div>

            <!-- Warehouse Holding Distribution Grid -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Warehouse Distribution & Holding Values</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Physical units and total inventory capital per hub location.</p>
                    </div>
                    <a href="{{ route('warehouses.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        View All Hubs &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @forelse($warehouseStockBreakdown as $wh)
                        <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/60 flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="font-bold text-xs text-slate-800 block">{{ $wh['name'] }}</span>
                                    <span class="text-[11px] font-mono text-slate-400">{{ $wh['code'] }}</span>
                                </div>
                                @if($wh['is_default'])
                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">DEFAULT</span>
                                @endif
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-200/50 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Physical Units</span>
                                    <span class="font-bold text-slate-700">{{ number_format($wh['total_units']) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400 block text-[10px]">Value</span>
                                    <span class="font-bold text-indigo-600">${{ number_format($wh['total_valuation'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-6 text-xs text-slate-400">
                            No warehouses created yet. <a href="{{ route('warehouses.index') }}" class="text-indigo-600 font-semibold underline">Create a warehouse</a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Two-Column Section: Low Stock Warnings & Recent Sales Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Low Stock Radar Table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                                <h2 class="text-sm font-bold text-slate-900">Low Stock Warning Radar</h2>
                            </div>
                            <span class="text-xs text-slate-400 font-medium">Below reorder threshold</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50/50 border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                                        <th class="py-2.5 px-4">Item & SKU</th>
                                        <th class="py-2.5 px-4 text-right">Available</th>
                                        <th class="py-2.5 px-4 text-right">Reorder Level</th>
                                        <th class="py-2.5 px-4 text-right">Suggested Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($lowStockProducts as $lowItem)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-800">{{ $lowItem->name }}</div>
                                                <div class="text-[11px] font-mono text-slate-400">{{ $lowItem->sku }}</div>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <span class="font-bold text-rose-600">{{ number_format($lowItem->total_on_hand) }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-right text-slate-500">{{ number_format($lowItem->reorder_level) }}</td>
                                            <td class="py-3 px-4 text-right font-semibold text-indigo-600">{{ number_format($lowItem->reorder_quantity) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                                ✅ All stock levels are in healthy surplus.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/50 border-t border-slate-100 text-right">
                        <a href="{{ route('products.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            Manage Full Catalog &rarr;
                        </a>
                    </div>
                </div>

                <!-- Recent Sales Orders Pipeline -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-sm font-bold text-slate-900">Recent Sales Orders</h2>
                            <span class="text-xs text-slate-400 font-medium">Customer pipeline</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50/50 border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                                        <th class="py-2.5 px-4">Order Ref</th>
                                        <th class="py-2.5 px-4">Customer</th>
                                        <th class="py-2.5 px-4 text-right">Amount</th>
                                        <th class="py-2.5 px-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($recentSalesOrders as $order)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-3 px-4">
                                                <span class="font-mono font-bold text-slate-800">{{ $order->order_number }}</span>
                                                <span class="text-[10px] text-slate-400 block">{{ $order->order_date ? $order->order_date->format('M d, Y') : '' }}</span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-slate-700">{{ $order->customer->name ?? 'Direct' }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-right font-bold text-slate-900">
                                                ${{ number_format($order->total_amount, 2) }}
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                @php
                                                    $statusClasses = [
                                                        'draft' => 'bg-slate-100 text-slate-700',
                                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                                        'dispatched' => 'bg-purple-100 text-purple-800',
                                                        'delivered' => 'bg-emerald-100 text-emerald-800',
                                                        'cancelled' => 'bg-rose-100 text-rose-800',
                                                    ];
                                                @endphp
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                                No sales orders recorded yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/50 border-t border-slate-100 text-right">
                        <span class="text-xs text-slate-400 font-medium">Real-time fulfillment tracking</span>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
