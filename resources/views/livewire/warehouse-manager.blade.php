<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header & Top Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Warehouse & Hub Locations</h1>
                <p class="text-sm text-slate-500 mt-1">Manage multiple fulfillment centers, distribution hubs, and track bin-level inventory capacity.</p>
            </div>
            <button wire:click="create" 
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm hover:shadow transition-all duration-150 gap-2 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Warehouse</span>
            </button>
        </div>

        <!-- Search and Filters bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" 
                       type="text" 
                       placeholder="Search by code, name, city or state..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Total Locations: <span class="text-slate-900 font-semibold">{{ $warehouses->total() }}</span>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Warehouse Grid / Table -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($warehouses as $wh)
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm tracking-wider border border-indigo-100">
                                    {{ $wh->code }}
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $wh->name }}
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        {{ $wh->city ? $wh->city . ($wh->state ? ', ' . $wh->state : '') : 'Address not specified' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                @if($wh->is_default)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Default Hub
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $wh->is_active ? 'bg-slate-100 text-slate-700' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                    {{ $wh->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <!-- Warehouse quick details -->
                        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 block">Phone</span>
                                <span class="font-medium text-slate-700">{{ $wh->phone ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Email</span>
                                <span class="font-medium text-slate-700 truncate block">{{ $wh->email ?? '—' }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-400 block">Address</span>
                                <span class="text-slate-600 truncate block">{{ $wh->address_line1 ? $wh->address_line1 . ($wh->postal_code ? ' (' . $wh->postal_code . ')' : '') : '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Footer -->
                    <div class="px-5 py-3 bg-slate-50/60 border-t border-slate-100 flex items-center justify-between text-xs">
                        <button wire:click="inspectStock({{ $wh->id }})" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            View Stock
                        </button>
                        <div class="flex items-center gap-2">
                            @if(!$wh->is_default)
                                <button wire:click="setAsDefault({{ $wh->id }})" title="Set as primary default warehouse" class="text-slate-500 hover:text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </button>
                            @endif
                            <button wire:click="edit({{ $wh->id }})" class="text-slate-500 hover:text-indigo-600 font-medium">
                                Edit
                            </button>
                            @if(!$wh->is_default)
                                <button wire:click="delete({{ $wh->id }})" wire:confirm="Are you sure you want to delete this warehouse?" class="text-slate-400 hover:text-rose-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-dashed border-slate-300">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-800">No Warehouses Found</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Get started by creating your primary warehouse or fulfillment center to track multi-location inventory.</p>
                    <button wire:click="create" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Warehouse
                    </button>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $warehouses->links() }}
        </div>

        <!-- Create / Edit Modal -->
        @if($isModalOpen)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                        <form wire:submit.prevent="save">
                            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900">
                                    {{ $warehouse_id ? 'Edit Warehouse' : 'New Warehouse' }}
                                </h3>
                                <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Code *</label>
                                        <input type="text" wire:model="code" placeholder="e.g. WH-01" class="w-full text-sm rounded-xl border border-slate-200 uppercase focus:ring-2 focus:ring-indigo-500">
                                        @error('code') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warehouse Name *</label>
                                        <input type="text" wire:model="name" placeholder="Main Central Distribution Hub" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone</label>
                                        <input type="text" wire:model="phone" placeholder="+1 (555) 019-2834" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('phone') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Email</label>
                                        <input type="email" wire:model="email" placeholder="warehouse@company.com" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address</label>
                                    <input type="text" wire:model="address_line1" placeholder="100 Logistics Way, Suite 400" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                    @error('address_line1') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">City</label>
                                        <input type="text" wire:model="city" placeholder="Chicago" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">State / Prov</label>
                                        <input type="text" wire:model="state" placeholder="IL" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Postal Code</label>
                                        <input type="text" wire:model="postal_code" placeholder="60601" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-semibold text-slate-700">Active Operational</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="is_default" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-semibold text-slate-700">Set as Default Hub</span>
                                    </label>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/60 rounded-xl transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition shadow-sm">
                                    {{ $warehouse_id ? 'Update Warehouse' : 'Save Warehouse' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Stock Inspector Drawer/Modal -->
        @if($selectedWarehouse)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="$set('selectedWarehouse', null)"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <span>Stock Inventory</span>
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 text-xs font-semibold">{{ $selectedWarehouse->code }}</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $selectedWarehouse->name }}</p>
                            </div>
                            <button type="button" wire:click="$set('selectedWarehouse', null)" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 max-h-[60vh] overflow-y-auto">
                            @if(count($warehouseStock) > 0)
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                            <th class="pb-3">Product / SKU</th>
                                            <th class="pb-3 text-right">Physical Qty</th>
                                            <th class="pb-3 text-right">Reserved</th>
                                            <th class="pb-3 text-right">Available</th>
                                            <th class="pb-3 text-right">Unit Cost</th>
                                            <th class="pb-3 text-right">Total Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($warehouseStock as $item)
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-3">
                                                    <span class="font-semibold text-slate-800">{{ $item['product_name'] }}</span>
                                                    <span class="text-slate-400 block font-mono text-[11px]">{{ $item['sku'] }}</span>
                                                </td>
                                                <td class="py-3 text-right font-medium text-slate-700">{{ number_format($item['quantity_on_hand']) }}</td>
                                                <td class="py-3 text-right text-amber-600 font-medium">{{ number_format($item['quantity_reserved']) }}</td>
                                                <td class="py-3 text-right font-bold {{ $item['quantity_available'] <= 5 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                    {{ number_format($item['quantity_available']) }}
                                                </td>
                                                <td class="py-3 text-right text-slate-600">${{ number_format($item['cost_price'], 2) }}</td>
                                                <td class="py-3 text-right font-semibold text-slate-900">${{ number_format($item['value'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="py-8 text-center text-slate-400">
                                    <p class="text-sm font-medium">No stock currently held in this warehouse.</p>
                                    <p class="text-xs mt-1">Receive purchase orders or transfer items from another hub.</p>
                                </div>
                            @endif
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                            <button type="button" wire:click="$set('selectedWarehouse', null)" class="px-4 py-2 text-xs font-semibold bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
