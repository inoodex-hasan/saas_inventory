<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sales Orders & Dispatches</h1>
                <p class="text-sm text-slate-500 mt-1">Manage customer orders, reserve warehouse stock, fulfill shipments, and track customer payments.</p>
            </div>
            <button wire:click="openCreateModal" 
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm hover:shadow transition gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>New Sales Order</span>
            </button>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" 
                       type="text" 
                       placeholder="Search by order # or customer name..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-50/50 border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="filter_status" class="py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Fulfillment Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="confirmed">Confirmed (Stock Reserved)</option>
                    <option value="dispatched">Dispatched</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <select wire:model.live="filter_payment_status" class="py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Payment Statuses</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial Paid</option>
                    <option value="paid">Fully Paid</option>
                </select>

                <select wire:model.live="filter_customer" class="py-2 px-3 bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Customers</option>
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}">{{ $cust->name }}</option>
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

        <!-- Sales Orders Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-4">Order Ref & Date</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Fulfillment Hub</th>
                            <th class="py-3.5 px-4 text-right">Order Total</th>
                            <th class="py-3.5 px-4 text-center">Fulfillment</th>
                            <th class="py-3.5 px-4 text-center">Payment</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($salesOrders as $order)
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-slate-100 text-slate-700',
                                    'confirmed' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                    'dispatched' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                    'delivered' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                ];
                                $paymentClasses = [
                                    'unpaid' => 'bg-rose-50 text-rose-700 border border-rose-100',
                                    'partial' => 'bg-amber-50 text-amber-700 border border-amber-100',
                                    'paid' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-slate-900 text-sm">{{ $order->order_number }}</span>
                                    <span class="text-slate-400 block text-[11px]">{{ $order->order_date ? $order->order_date->format('M d, Y') : '—' }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800">{{ $order->customer->name ?? 'Walk-in Customer' }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ $order->customer->phone ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-800">{{ $order->warehouse->name ?? '—' }}</div>
                                    <div class="text-slate-400 text-[11px] font-mono">{{ $order->warehouse->code ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="font-bold text-slate-900 text-sm">${{ number_format($order->total_amount, 2) }}</span>
                                    @if($order->paid_amount > 0)
                                        <span class="text-emerald-600 block text-[11px] font-medium">Paid: ${{ number_format($order->paid_amount, 2) }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $paymentClasses[$order->payment_status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('sales.invoice', $order->id) }}" target="_blank" title="Download PDF Invoice" class="px-2.5 py-1 text-indigo-600 hover:text-indigo-800 font-bold bg-indigo-50 hover:bg-indigo-100 rounded-lg text-xs transition">
                                            📄 Invoice
                                        </a>
                                        <a href="{{ route('sales.packing-slip', $order->id) }}" target="_blank" title="Print Warehouse Packing Slip" class="px-2 py-1 text-slate-600 hover:text-slate-800 font-medium bg-slate-100 hover:bg-slate-200 rounded-lg text-xs transition">
                                            📦 Slip
                                        </a>
                                        <button wire:click="viewDetails({{ $order->id }})" title="View Details" class="px-2.5 py-1 text-slate-600 hover:text-slate-900 font-medium hover:bg-slate-100 rounded-lg">
                                            View
                                        </button>

                                        @if($order->status === 'draft')
                                            <button wire:click="confirmOrder({{ $order->id }})" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-2xs transition">
                                                Confirm & Reserve
                                            </button>
                                            <button wire:click="cancelOrder({{ $order->id }})" wire:confirm="Cancel this draft order?" class="px-2 py-1 text-rose-500 hover:text-rose-700 rounded-lg">
                                                Cancel
                                            </button>
                                        @elseif($order->status === 'confirmed')
                                            <button wire:click="dispatchOrder({{ $order->id }})" wire:confirm="Dispatch items and deduct inventory from {{ $order->warehouse->name }}?" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg shadow-2xs transition">
                                                📦 Dispatch
                                            </button>
                                            <button wire:click="cancelOrder({{ $order->id }})" wire:confirm="Cancel order and release reserved stock?" class="px-2 py-1 text-rose-500 hover:text-rose-700 rounded-lg">
                                                Cancel
                                            </button>
                                        @endif

                                        @if($order->payment_status !== 'paid' && $order->status !== 'cancelled')
                                            <button wire:click="openPaymentModal({{ $order->id }})" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg transition">
                                                💵 Pay
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-semibold text-slate-800">No Sales Orders</h3>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Create sales orders to automatically reserve warehouse stock and fulfill customer dispatches.</p>
                                    <button wire:click="openCreateModal" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Sales Order
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($salesOrders->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $salesOrders->links() }}
                </div>
            @endif
        </div>

        <!-- Create Sales Order Modal -->
        @if($isCreateModalOpen)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                        <form wire:submit.prevent="saveSalesOrder">
                            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-slate-900">New Outbound Sales Order</h3>
                                <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer *</label>
                                        <select wire:model.live="customer_id" wire:change="onCustomerSelected" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select Customer</option>
                                            @foreach($customers as $cust)
                                                <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->phone ?? 'No phone' }})</option>
                                            @endforeach
                                        </select>
                                        @error('customer_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Fulfillment Warehouse *</label>
                                        <select wire:model.live="warehouse_id" wire:change="onWarehouseChanged" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                            <option value="">Select Fulfillment Hub</option>
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
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Shipping Address</label>
                                        <input type="text" placeholder="123 Main St, Springfield" wire:model="shipping_address" class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <!-- Dynamic Line Items Table -->
                                <div class="pt-3 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-xs font-bold text-slate-800 uppercase tracking-wider">Order Items & Inventory Check</label>
                                        <button type="button" wire:click="addItemRow" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                            <span>+ Add Line Item</span>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($items as $index => $item)
                                            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-200/60 grid grid-cols-12 gap-3 items-end">
                                                <div class="col-span-5">
                                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Product *</label>
                                                    <select wire:model.live="items.{{ $index }}.product_id" wire:change="onProductSelected({{ $index }})" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                                        <option value="">Select Item</option>
                                                        @foreach($products as $prod)
                                                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Qty *</label>
                                                    <input type="number" step="1" wire:model.live="items.{{ $index }}.quantity" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Price ($) *</label>
                                                    <input type="number" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="w-full text-xs rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                                <div class="col-span-2 text-center pb-2">
                                                    @if(!empty($item['product_id']))
                                                        <span class="text-[10px] font-semibold block {{ ($item['available_stock'] ?? 0) < ($item['quantity'] ?? 1) ? 'text-rose-600' : 'text-emerald-600' }}">
                                                            Avail: {{ number_format($item['available_stock'] ?? 0) }}
                                                        </span>
                                                    @endif
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
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Packing Instructions</label>
                                    <textarea wire:model="notes" rows="2" placeholder="Fragile, gate code, delivery time..." class="w-full text-sm rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500"></textarea>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/60 rounded-xl transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition shadow-sm">
                                    Create Draft Sales Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Record Payment Modal -->
        @if($isPaymentModalOpen && $payingOrder)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                        <form wire:submit.prevent="recordPayment">
                            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Record Customer Payment</h3>
                                    <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $payingOrder->order_number }}</p>
                                </div>
                                <button type="button" wire:click="closeModal" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs flex justify-between">
                                    <div>
                                        <span class="text-slate-400 block">Total Due</span>
                                        <span class="font-bold text-slate-800">${{ number_format($payingOrder->total_amount, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Already Paid</span>
                                        <span class="font-bold text-emerald-600">${{ number_format($payingOrder->paid_amount, 2) }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-slate-400 block">Balance</span>
                                        <span class="font-bold text-rose-600">${{ number_format(max(0, $payingOrder->total_amount - $payingOrder->paid_amount), 2) }}</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Amount ($) *</label>
                                    <input type="number" step="0.01" wire:model="paymentAmount" class="w-full text-base font-bold rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200/60 rounded-xl transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition shadow-sm">
                                    Confirm Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- View SO Details Modal -->
        @if($viewingOrder)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Sales Order: {{ $viewingOrder->order_number }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Customer: {{ $viewingOrder->customer->name }} &bull; Hub: {{ $viewingOrder->warehouse->name }}</p>
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
                                        <th class="pb-2 text-right">Quantity</th>
                                        <th class="pb-2 text-right">Unit Price</th>
                                        <th class="pb-2 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($viewingOrder->items as $item)
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3">
                                                <span class="font-semibold text-slate-800">{{ $item->product->name }}</span>
                                                <span class="text-slate-400 block font-mono text-[11px]">{{ $item->product->sku }}</span>
                                            </td>
                                            <td class="py-3 text-right font-bold text-slate-700">{{ number_format($item->quantity) }}</td>
                                            <td class="py-3 text-right text-slate-600">${{ number_format($item->unit_price, 2) }}</td>
                                            <td class="py-3 text-right font-bold text-slate-900">${{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold">
                                <span>Total Order Amount</span>
                                <span class="text-base text-slate-900">${{ number_format($viewingOrder->total_amount, 2) }}</span>
                            </div>

                            @if($viewingOrder->shipping_address)
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                    <strong>Shipping Address:</strong> {{ $viewingOrder->shipping_address }}
                                </div>
                            @endif

                            @if($viewingOrder->notes)
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                    <strong>Notes:</strong> {{ $viewingOrder->notes }}
                                </div>
                            @endif
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('sales.invoice', $viewingOrder->id) }}" target="_blank" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-xs flex items-center gap-1.5">
                                    <span>📄 Download Invoice PDF</span>
                                </a>
                                <a href="{{ route('sales.packing-slip', $viewingOrder->id) }}" target="_blank" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 shadow-2xs flex items-center gap-1.5">
                                    <span>📦 Print Packing Slip</span>
                                </a>
                            </div>
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
