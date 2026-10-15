<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    vendors: Array,
    metrics: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filter = ref(props.filters?.filter || 'all_with_dues');

// Pay Due Modal State
const isPayModalOpen = ref(false);
const activeVendor = ref(null);

const payForm = useForm({
    order_id: '',
    amount: 0,
    payment_method: 'bank_transfer',
    notes: '',
});

function applyFilters() {
    router.get(route('vendor-dues.index'), {
        search: search.value,
        filter: filter.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function openPayModal(vendor) {
    activeVendor.value = vendor;
    const dueOrders = vendor.due_orders || [];
    const firstOrder = dueOrders[0];

    payForm.order_id = firstOrder ? firstOrder.id : '';
    payForm.amount = firstOrder ? Number(firstOrder.due_amount) : Number(vendor.outstanding_due);
    payForm.payment_method = 'bank_transfer';
    payForm.notes = '';
    isPayModalOpen.value = true;
}

function onOrderChanged() {
    const order = activeVendor.value?.due_orders?.find(o => o.id === Number(payForm.order_id));
    if (order) {
        payForm.amount = Number(order.due_amount);
    }
}

function setQuickPercent(percent) {
    const order = activeVendor.value?.due_orders?.find(o => o.id === Number(payForm.order_id));
    const baseDue = order ? Number(order.due_amount) : Number(activeVendor.value?.outstanding_due || 0);
    payForm.amount = Math.round((baseDue * (percent / 100)) * 100) / 100;
}

function submitPayment() {
    payForm.post(route('vendor-dues.pay'), {
        onSuccess: () => {
            isPayModalOpen.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Vendor Due Ledger" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Vendor Due Ledger & Payables</h1>
                    <p class="text-xs text-slate-500 font-medium">Track procurement liabilities, advance settlements, and record payments</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Procured</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_procured || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Paid Out</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_paid || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Payables</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.outstanding_due || 0" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Vendors with Due</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.vendors_with_due || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-2">
                <div class="flex flex-col sm:flex-row items-center gap-2 flex-1 w-full">
                    <div class="relative flex-1 w-full max-w-sm">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="applyFilters"
                            placeholder="Search vendor name, contact, mobile..."
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 placeholder:text-slate-400"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Status Filter Pills -->
                    <div class="inline-flex rounded-xl p-0.5 bg-slate-100 border border-slate-200 text-xs shrink-0">
                        <button 
                            @click="filter = 'all_with_dues'; applyFilters()"
                            class="px-3 py-1.5 rounded-lg font-semibold transition-all"
                            :class="filter === 'all_with_dues' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            With Dues Only
                        </button>
                        <button 
                            @click="filter = 'fully_paid'; applyFilters()"
                            class="px-3 py-1.5 rounded-lg font-semibold transition-all"
                            :class="filter === 'fully_paid' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            Fully Settled
                        </button>
                        <button 
                            @click="filter = 'all'; applyFilters()"
                            class="px-3 py-1.5 rounded-lg font-semibold transition-all"
                            :class="filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        >
                            All Vendors
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button 
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button>
                    <Link
                        :href="route('purchases.create')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02] shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Purchase Order</span>
                    </Link>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4">Vendor Partner</th>
                                <th class="py-3 px-4">Contact Phone</th>
                                <th class="py-3 px-4 text-right">Total Invoiced</th>
                                <th class="py-3 px-4 text-right">Total Paid</th>
                                <th class="py-3 px-4 text-right">Outstanding Due</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="v in vendors" :key="v.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ v.name }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ v.contact_name || v.address }}</div>
                                </td>

                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                    {{ v.phone }}
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700">
                                    <CurrencyDisplay :amount="v.total_procured" />
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-semibold text-emerald-600">
                                    <CurrencyDisplay :amount="v.total_paid" />
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold">
                                    <span :class="v.outstanding_due > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                        <CurrencyDisplay :amount="v.outstanding_due" />
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <button 
                                        v-if="v.outstanding_due > 0"
                                        @click="openPayModal(v)"
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-indigo-600/30"
                                    >
                                        Pay Due
                                    </button>
                                    <span v-else class="text-xs text-emerald-600 font-semibold">
                                        Settled &check;
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vendors.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No vendor liabilities match selected filter.
                </div>
            </div>
        </div>

        <!-- Pay Due Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Record Vendor Payment</h2>
                        <p class="text-xs text-slate-500 font-semibold">{{ activeVendor?.name }}</p>
                    </div>
                    <button @click="isPayModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-4">
                    <!-- PO Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Purchase Order *</label>
                        <select 
                            v-model="payForm.order_id" 
                            @change="onOrderChanged"
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 font-mono"
                        >
                            <option v-for="o in activeVendor?.due_orders" :key="o.id" :value="o.id">
                                {{ o.po_number }} &bull; Due: ৳{{ Number(o.due_amount).toFixed(2) }}
                            </option>
                        </select>
                    </div>

                    <!-- Payment Amount -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-slate-700">Payment Amount *</label>
                            <div class="flex items-center gap-1.5">
                                <button 
                                    type="button" 
                                    @click="setQuickPercent(50)"
                                    class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                                >
                                    50% Due
                                </button>
                                <span class="text-slate-300">&bull;</span>
                                <button 
                                    type="button" 
                                    @click="setQuickPercent(100)"
                                    class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                                >
                                    Full Due
                                </button>
                            </div>
                        </div>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0.01"
                            v-model="payForm.amount" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold text-emerald-600 border border-slate-200 focus:border-indigo-500"
                        />
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method *</label>
                        <select 
                            v-model="payForm.payment_method" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500"
                        >
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash in Hand</option>
                            <option value="mobile_banking">Mobile Banking (bKash / Nagad / Rocket)</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Note</label>
                        <input 
                            type="text" 
                            v-model="payForm.notes" 
                            placeholder="e.g. Bank Ref # / Cheque #..."
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500"
                        />
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button 
                            type="button" 
                            @click="isPayModalOpen = false" 
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="payForm.processing || payForm.amount <= 0"
                            class="px-5 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-sm transition-colors disabled:opacity-50"
                        >
                            Confirm Settlement
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
