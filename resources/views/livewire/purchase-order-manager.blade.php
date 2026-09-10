<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Purchase Orders & Inbound Goods</h1>
                <p class="text-sm text-slate-500 mt-1">Procure inventory from suppliers, track inbound shipments, and receive items directly into destination warehouses.</p>
            </div>
            <button wire:click="openCreateModal" 
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm hover:shadow transition gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>New Purchase Order</span>
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" 
                       type="text" 
                       placeholder="Search by PO number or supplier name..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            </div>

            <div class="flex items-center gap-3">
                <select wire:model.live="filter_status" class="py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All PO Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="ordered">Ordered (Inbound)</option>
                    <option value="partially_received">Partially Received</option>
                    <option value="received">Fully Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <select wire:model.live="filter_supplier" class="py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Flash Notices -->
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

        <!-- Purchase Orders Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-4">PO Number & Date</th>
                            <th class="py-3.5 px-4">Supplier</th>
                            <th class="py-3.5 px-4">Destination Hub</th>
                            <th class="py-3.5 px-4 text-center">Items</th>
                            <th class="py-3.5 px-4 text-right">Total Amount</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($purchaseOrders as $po)
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-slate-100 text-slate-700',
                                    'ordered' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                    'partially_received' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                    'received' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-slate-900 text-sm">{{ $po->order_number }}</span>
                                    <span class="text-slate-400 block text-[11px]">{{ $po->order_date ? $po->order_date->format('M d, Y') : '—' }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800">{{ $po->supplier->name ?? 'Unknown Supplier' }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ $po->supplier->contact_name ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800">{{ $po->warehouse->name ?? '—' }}</div>
                                    <div class="text-slate-400 text-[11px] font-mono">{{ $po->warehouse->code ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold">{{ $po->items->count() }} items</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900 text-sm">
                                    ${{ number_format($po->total_amount, 2) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $statusClasses[$po->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ str_replace('_', ' ', $po->status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button wire:click="viewDetails({{ $po->id }})" title="View Details" class="px-2.5 py-1 text-slate-600 hover:text-slate-900 font-medium hover:bg-slate-100 rounded-lg">
                                            View
                                        </button>

                                        @if($po->status === 'draft')
                                            <button wire:click="markAsOrdered({{ $po->id }})" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg transition">
                                                Mark Ordered
                                            </button>
                                            <button wire:click="cancelOrder({{ $po->id }})" wire:confirm="Cancel this draft purchase order?" class="px-2 py-1 text-rose-500 hover:text-rose-700 rounded-lg">
                                                Cancel
                                            </button>
                                        @elseif(in_array($po->status, ['ordered', 'partially_received']))
                                            <button wire:click="openReceiveModal({{ $po->id }})" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-xs transition">
                                                📥 Receive Goods
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-semibold text-slate-800">No Purchase Orders</h3>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Create a purchase order to record inbound inventory and receive stock into your warehouses.</p>
                                    <button wire:click="openCreateModal" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Purchase Order
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($purchaseOrders->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $purchaseOrders->links() }}
                </div>
            @endif
        </div>

        <!-- Create Purchase Order Modal -->
        @if($isCreateModalOpen)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                        <form wire:submit.prevent="savePurchaseOrder">
                            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900">New Inbound Purchase Order</h3>
                                <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier *</label>
                                        <select wire:model="supplier_id" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select Supplier</option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('supplier_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Warehouse *</label>
                                        <select wire:model="warehouse_id" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select Warehouse Hub</option>
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                                            @endforeach
                                        </select>
                                        @error('warehouse_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Order Date *</label>
                                        <input type="date" wire:model="order_date" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('order_date') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Expected Delivery Date</label>
                                        <input type="date" wire:model="expected_delivery_date" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                        @error('expected_delivery_date') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Dynamic Line Items Table -->
                                <div class="pt-3 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-xs font-bold text-slate-800 uppercase tracking-wider">Order Line Items</label>
                                        <button type="button" wire:click="addItemRow" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                            <span>+ Add Line Item</span>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($items as $index => $item)
                                            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200/60 grid grid-cols-12 gap-3 items-end">
                                                <div class="col-span-6">
                                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Product Item *</label>
                                                    <select wire:model.live="items.{{ $index }}.product_id" wire:change="onProductSelected({{ $index }})" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                                        <option value="">Select Catalog Item</option>
                                                        @foreach($products as $prod)
                                                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Quantity *</label>
                                                    <input type="number" step="1" wire:model.live="items.{{ $index }}.quantity_ordered" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                                <div class="col-span-3">
                                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit Cost ($) *</label>
                                                    <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_cost" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                                <div class="col-span-1 text-center">
                                                    @if(count($items) > 1)
                                                        <button type="button" wire:click="removeItemRow({{ $index }})" class="p-1.5 text-rose-500 hover:text-rose-700">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('items') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Shipping Instructions</label>
                                    <textarea wire:model="notes" rows="2" placeholder="Customs notes, container numbers..." class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/60 rounded-xl transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition shadow-sm">
                                    Create Purchase Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Receive Goods Modal -->
        @if($isReceiveModalOpen && $receivingPo)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                        <form wire:submit.prevent="processReceive">
                            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                        <span>Receive Inbound Stock</span>
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-xs font-bold">{{ $receivingPo->order_number }}</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Destination Hub: <strong class="text-slate-800">{{ $receivingPo->warehouse->name }}</strong></p>
                                </div>
                                <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                                <p class="text-xs text-slate-500">Specify the physical units received at the loading dock. Stock will atomically increase in the destination hub.</p>

                                <div class="space-y-3">
                                    @foreach($receivingPo->items as $item)
                                        @php
                                            $remaining = (float) $item->quantity - (float) $item->received_quantity;
                                        @endphp
                                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="font-bold text-slate-900 text-sm">{{ $item->product->name }}</span>
                                                    <span class="text-slate-400 text-xs block font-mono">{{ $item->product->sku }}</span>
                                                </div>
                                                <div class="text-right text-xs">
                                                    <span class="text-slate-500">Ordered: <strong>{{ number_format($item->quantity) }}</strong></span>
                                                    <span class="text-slate-400 mx-1">|</span>
                                                    <span class="text-emerald-700">Already Received: <strong>{{ number_format($item->received_quantity) }}</strong></span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-200/60">
                                                <div>
                                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Receive Qty (Remaining: {{ number_format($remaining) }})</label>
                                                    <input type="number" step="1" max="{{ $remaining }}" wire:model="receivingItems.{{ $item->id }}.quantity_to_receive" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-emerald-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Batch / Lot # (Optional)</label>
                                                    <input type="text" placeholder="LOT-2026-X" wire:model="receivingItems.{{ $item->id }}.batch_number" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-emerald-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Bin / Shelf Location</label>
                                                    <input type="text" placeholder="Aisle 4, Shelf B" wire:model="receivingItems.{{ $item->id }}.bin_location" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-emerald-500">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/60 rounded-xl transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition shadow-sm">
                                    Confirm Stock In & Record Inbound
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- View PO Details Modal -->
        @if($viewingPo)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Purchase Order: {{ $viewingPo->order_number }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Supplier: {{ $viewingPo->supplier->name }} &bull; Destination: {{ $viewingPo->warehouse->name }}</p>
                            </div>
                            <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                        <th class="pb-2">Product Item</th>
                                        <th class="pb-2 text-right">Ordered</th>
                                        <th class="pb-2 text-right">Received</th>
                                        <th class="pb-2 text-right">Unit Cost</th>
                                        <th class="pb-2 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($viewingPo->items as $item)
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3">
                                                <span class="font-semibold text-slate-800">{{ $item->product->name }}</span>
                                                <span class="text-slate-400 block font-mono text-[11px]">{{ $item->product->sku }}</span>
                                            </td>
                                            <td class="py-3 text-right font-medium text-slate-700">{{ number_format($item->quantity) }}</td>
                                            <td class="py-3 text-right font-bold {{ $item->received_quantity >= $item->quantity ? 'text-emerald-600' : 'text-amber-600' }}">
                                                {{ number_format($item->received_quantity) }}
                                            </td>
                                            <td class="py-3 text-right text-slate-600">${{ number_format($item->unit_cost, 2) }}</td>
                                            <td class="py-3 text-right font-bold text-slate-900">${{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold">
                                <span>Total Order Value</span>
                                <span class="text-base text-slate-900">${{ number_format($viewingPo->total_amount, 2) }}</span>
                            </div>

                            @if($viewingPo->notes)
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                    <strong>Notes:</strong> {{ $viewingPo->notes }}
                                </div>
                            @endif
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
