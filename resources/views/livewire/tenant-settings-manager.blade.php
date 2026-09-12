<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Company & Branding Settings</h1>
                    <p class="text-sm text-slate-400">Configure your business profile, tax registration, default currency, and invoice formatting.</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                Active Tenant: {{ auth()->user()->currentTeam->name }}
            </span>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session()->has('success'))
        <div class="p-4 bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 rounded-xl text-sm flex items-center gap-3 shadow-lg">
            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 bg-red-950/40 border border-red-500/30 text-red-300 rounded-xl text-sm flex items-center gap-3 shadow-lg">
            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Navigation Tabs & Branding Live Card -->
        <div class="space-y-6">
            <!-- Navigation Tabs -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shadow-lg space-y-1">
                <button type="button" wire:click="setTab('profile')" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition {{ $activeTab === 'profile' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Company Profile & Legal</span>
                </button>

                <button type="button" wire:click="setTab('localization')" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition {{ $activeTab === 'localization' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Currency & Localization</span>
                </button>

                <button type="button" wire:click="setTab('documents')" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition {{ $activeTab === 'documents' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Document Numbering</span>
                </button>
            </div>

            <!-- Live Document Branding Preview Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Branding Preview</span>
                    <span class="text-xs text-indigo-400 font-mono">PDF & Documents</span>
                </div>
                
                <div class="bg-white rounded-xl p-4 text-slate-800 text-xs shadow-inner space-y-3 font-sans border border-slate-200">
                    <div class="flex justify-between items-start border-b border-slate-200 pb-3">
                        <div>
                            <div class="font-extrabold text-sm text-slate-900">{{ $legal_name ?: ($name ?: 'Your Company Name') }}</div>
                            <div class="text-slate-500 text-[10px]">{{ $address_line1 ? $address_line1 . ', ' . $city : '123 Business Boulevard, Suite 100' }}</div>
                            <div class="text-slate-500 text-[10px]">{{ $email ?: 'billing@company.com' }} &bull; {{ $phone ?: '+1 (555) 019-2834' }}</div>
                            @if($tax_id)
                                <div class="text-indigo-600 font-semibold text-[10px] mt-0.5">Tax / VAT: {{ $tax_id }}</div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-indigo-600 font-black text-xs">INVOICE</div>
                            <div class="font-mono text-[10px] text-slate-600">{{ $invoice_prefix }}2026-0001</div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-[11px] font-bold text-slate-700 pt-1">
                        <span>Total Amount:</span>
                        <span class="text-emerald-700 text-xs">{{ $currency_symbol }}1,450.00 {{ $currency_code }}</span>
                    </div>
                </div>

                <p class="text-xs text-slate-500 italic">This is how your company details will dynamically appear on top of all generated Invoices, Packing Slips, and Purchase Orders.</p>
            </div>
        </div>

        <!-- Right Column: Settings Form -->
        <div class="lg:col-span-2">
            <form wire:submit.prevent="save" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">

                <!-- Tab 1: Company Profile & Legal -->
                @if($activeTab === 'profile')
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Company Identity & Registration</h2>
                            <p class="text-xs text-slate-400">Enter your official business name and contact information displayed to clients and suppliers.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Display Workspace Name <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="name" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="e.g. Acme Corporation">
                                @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Legal Registered Name</label>
                                <input type="text" wire:model="legal_name" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="e.g. Acme International LLC">
                                @error('legal_name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Official Business Email</label>
                                <input type="email" wire:model="email" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="billing@acme.com">
                                @error('email') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Business Phone Number</label>
                                <input type="text" wire:model="phone" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="+1 (555) 234-5678">
                                @error('phone') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Tax / VAT / GST Registration Number</label>
                                <input type="text" wire:model="tax_id" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="e.g. US-EIN 12-3456789 or GB 999 9999 73">
                                @error('tax_id') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="border-t border-slate-800 pt-6">
                            <h3 class="text-sm font-bold text-white mb-1">Registered Business Address</h3>
                            <p class="text-xs text-slate-400 mb-4">Official physical address for invoices, shipments, and legal correspondence.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Address Line 1</label>
                                    <input type="text" wire:model="address_line1" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="Street Address, P.O. Box">
                                    @error('address_line1') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Address Line 2 (Optional)</label>
                                    <input type="text" wire:model="address_line2" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="Apartment, suite, unit, building, floor">
                                    @error('address_line2') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">City</label>
                                    <input type="text" wire:model="city" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="City">
                                    @error('city') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">State / Province</label>
                                    <input type="text" wire:model="state" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="State or Region">
                                    @error('state') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Postal / Zip Code</label>
                                    <input type="text" wire:model="postal_code" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="ZIP / Postal Code">
                                    @error('postal_code') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Country</label>
                                    <input type="text" wire:model="country" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="Country">
                                    @error('country') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Tab 2: Currency & Localization -->
                @if($activeTab === 'localization')
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Currency & Financial Settings</h2>
                            <p class="text-xs text-slate-400">Configure the primary operating currency for sales orders, purchase orders, and stock valuation.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Currency Symbol <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="currency_symbol" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="$">
                                <p class="text-[11px] text-slate-500 mt-1">Example: $, €, £, ¥, ₹, ৳</p>
                                @error('currency_symbol') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">ISO Currency Code <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="currency_code" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white uppercase placeholder-slate-600 transition" placeholder="USD">
                                <p class="text-[11px] text-slate-500 mt-1">Example: USD, EUR, GBP, AUD, CAD, BDT</p>
                                @error('currency_code') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Tab 3: Document Numbering -->
                @if($activeTab === 'documents')
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Document Numbering Prefixes</h2>
                            <p class="text-xs text-slate-400">Define prefix formats for sales invoices and purchase orders.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Sales Invoice Prefix <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="invoice_prefix" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="INV-">
                                <p class="text-[11px] text-slate-500 mt-1">Result: <span class="text-indigo-400 font-mono">{{ $invoice_prefix }}2026-0001</span></p>
                                @error('invoice_prefix') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Purchase Order Prefix <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="po_prefix" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 transition" placeholder="PO-">
                                <p class="text-[11px] text-slate-500 mt-1">Result: <span class="text-indigo-400 font-mono">{{ $po_prefix }}2026-0001</span></p>
                                @error('po_prefix') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Action Buttons -->
                <div class="border-t border-slate-800 pt-6 flex items-center justify-end gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-xl transition shadow-lg shadow-indigo-500/20 active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span wire:loading.remove wire:target="save">Save Company Settings</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>

            </form>
        </div>

    </div>

</div>
