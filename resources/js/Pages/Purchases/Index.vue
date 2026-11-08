<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BarcodeCameraModal from '@/Components/BarcodeCameraModal.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { playScanSuccess, playScanError } from '@/Utils/sound';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    orders: Object,
    suppliers: Array,
    warehouses: Array,
    financialAccounts: Array,
    metrics: Object,
    filters: Object,
});

const defaultAccount = (props.financialAccounts || []).find(a => a.is_default) || props.financialAccounts?.[0];

const accountOptions = computed(() => {
    return (props.financialAccounts || []).map(acc => ({
        value: acc.id,
        label: acc.name,
        sublabel: acc.type === 'cash'
            ? `Balance: ৳${Number(acc.current_balance).toFixed(2)}`
            : `${acc.institution_name} (${acc.account_number}) - Bal: ৳${Number(acc.current_balance).toFixed(2)}`,
        group: acc.type === 'cash' ? 'Cash Registers' : (acc.type === 'bank' ? 'Bank Accounts' : 'MFS Wallets'),
    }));
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const supplierId = ref(props.filters?.supplier_id || '');

// Details Modal State
const isDetailsModalOpen = ref(false);
const selectedOrderForDetails = ref(null);

function openDetailsModal(order) {
    selectedOrderForDetails.value = order;
    isDetailsModalOpen.value = true;
}

// Receive Modal State
const isReceiveModalOpen = ref(false);
const receivingPo = ref(null);
const receiveForm = useForm({
    items: [],
});

// Pay Modal State
const isPaymentModalOpen = ref(false);
const payingPo = ref(null);
const paymentForm = useForm({
    amount: '',
    financial_account_id: defaultAccount?.id || '',
    payment_reference: '',
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

function openPaymentModal(order) {
    payingPo.value = order;
    paymentForm.amount = order.due_amount > 0 ? order.due_amount : '';
    paymentForm.financial_account_id = defaultAccount?.id || '';
    paymentForm.payment_reference = '';
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    paymentForm.notes = '';
    paymentForm.clearErrors();
    isPaymentModalOpen.value = true;
}

function submitPayment() {
    if (!payingPo.value) return;
    paymentForm.post(route('purchases.pay', payingPo.value.id), {
        onSuccess: () => {
            isPaymentModalOpen.value = false;
            paymentForm.reset();
        },
    });
}

// Close Order Modal State
const isCloseModalOpen = ref(false);
const closingPo = ref(null);
const closeForm = useForm({
    refund_mode: 'account', // 'account' | 'credit'
    financial_account_id: defaultAccount?.id || '',
    reference_number: '',
    notes: '',
});

function openCloseModal(order) {
    closingPo.value = order;
    closeForm.refund_mode = 'account';
    closeForm.financial_account_id = defaultAccount?.id || '';
    closeForm.reference_number = '';
    closeForm.notes = '';
    closeForm.clearErrors();
    isCloseModalOpen.value = true;
}

const closeCalculation = computed(() => {
    if (!closingPo.value) return { receivedValue: 0, unreceivedCount: 0, overpaid: 0, newDue: 0 };
    const items = closingPo.value.items || [];
    let receivedValue = 0;
    let unreceivedCount = 0;
    items.forEach(it => {
        const rcv = Number(it.received_quantity) || 0;
        const ord = Number(it.quantity) || 0;
        const cost = Number(it.unit_cost) || 0;
        receivedValue += (rcv * cost);
        if (ord > rcv) {
            unreceivedCount += (ord - rcv);
        }
    });

    const paid = Number(closingPo.value.paid_amount) || 0;
    const overpaid = Math.max(0, paid - receivedValue);
    const newDue = Math.max(0, receivedValue - paid);

    return {
        receivedValue,
        unreceivedCount,
        overpaid,
        newDue,
    };
});

function submitCloseOrder() {
    if (!closingPo.value) return;
    closeForm.post(route('purchases.close', closingPo.value.id), {
        onSuccess: () => {
            isCloseModalOpen.value = false;
            closingPo.value = null;
        },
    });
}

function applyFilters() {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (supplierId.value) params.supplier_id = supplierId.value;
    router.get(route('purchases.index'), params, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    supplierId.value = '';
    router.get(route('purchases.index'), {}, { preserveState: false, replace: true });
}

const poActionModal = ref(null);
const isPoActionProcessing = ref(false);

function markOrdered(order) {
    poActionModal.value = {
        order,
        title: 'Mark PO as Ordered',
        variant: 'warning',
        icon: 'info',
        message: 'Mark this Purchase Order as officially ordered and transmitted to vendor?',
        confirmText: 'Mark Ordered',
        routeName: 'purchases.mark-ordered',
    };
}

function cancelOrder(order) {
    poActionModal.value = {
        order,
        title: 'Cancel Purchase Order',
        variant: 'danger',
        icon: 'danger',
        message: 'Are you sure you want to cancel this Purchase Order? Any pending deliveries will be aborted.',
        confirmText: 'Cancel Purchase Order',
        routeName: 'purchases.cancel',
    };
}

function executePoAction() {
    if (!poActionModal.value) return;
    isPoActionProcessing.value = true;
    router.post(route(poActionModal.value.routeName, poActionModal.value.order.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            isPoActionProcessing.value = false;
            poActionModal.value = null;
        },
    });
}

// Inbound Stock Receiving Barcode Scanner
const isReceiveCameraOpen = ref(false);
const receiveScanNotification = ref('');

function handleReceiveBarcodeScanned(rawCode) {
    if (!rawCode || !isReceiveModalOpen.value) return;
    const code = rawCode.trim().toLowerCase();

    const item = receiveForm.items.find(it => 
        (it.barcode && it.barcode.toLowerCase() === code) ||
        (it.sku && it.sku.toLowerCase() === code)
    );

    if (item) {
        if (item.is_serialized) {
            const currentSerials = item.serial_input ? item.serial_input.split(/[\n,]+/).map(s => s.trim()).filter(Boolean) : [];
            if (!currentSerials.includes(rawCode.trim())) {
                currentSerials.push(rawCode.trim());
                item.serial_input = currentSerials.join(', ');
                item.quantity = currentSerials.length;
            }
        } else {
            const current = Number(item.quantity) || 0;
            if (current < item.remaining_quantity) {
                item.quantity = current + 1;
            }
        }
        playScanSuccess();
        receiveScanNotification.value = `Scanned item "${item.product_name}"`;
        setTimeout(() => { receiveScanNotification.value = ''; }, 3000);
        return;
    }

    playScanError();
    receiveScanNotification.value = `Barcode "${rawCode}" does not match any items on this PO!`;
    setTimeout(() => { receiveScanNotification.value = ''; }, 3000);
}

useBarcodeScanner({
    onScan: (code) => {
        if (isReceiveModalOpen.value) {
            handleReceiveBarcodeScanned(code);
        }
    },
});

function openReceiveModal(order) {
    receivingPo.value = order;
    receiveForm.items = (order.items || []).map(item => {
        const remaining = (item.quantity || 0) - (item.received_quantity || 0);
        return {
            item_id: item.id,
            barcode: item.product?.barcode || '',
            product_name: item.product?.name || `Product #${item.product_id}`,
            sku: item.product?.sku || '',
            unit: item.product?.unit || 'pcs',
            is_serialized: item.product?.is_serialized || false,
            quantity_ordered: item.quantity,
            received_quantity: item.received_quantity || 0,
            remaining_quantity: remaining,
            quantity: remaining > 0 ? remaining : 0,
            batch_number: item.batch_number || '',
            serial_input: '',
        };
    });
    isReceiveModalOpen.value = true;
}

function submitReceive() {
    const payload = receiveForm.items.map(it => {
        const serials = it.serial_input
            ? it.serial_input.split(/[\n,]+/).map(s => s.trim()).filter(Boolean)
            : [];
        return {
            item_id: it.item_id,
            quantity: Number(it.quantity) || 0,
            batch_number: it.batch_number,
            serials: serials,
        };
    });

    router.post(route('purchases.receive', receivingPo.value.id), {
        items: payload,
    }, {
        onSuccess: () => {
            isReceiveModalOpen.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Purchases & Inbound" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Procurement & Purchase Orders</h1>
                    <p class="text-xs text-slate-500 font-medium">Inbound shipment tracking, supplier procurement sheets & stock receiving</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Purchase Orders</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_inbound || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Inbound Orders Pending</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.pending_orders || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Payables</span>
                    <div class="text-2xl font-black text-rose-600 mt-2">
                        <CurrencyDisplay :amount="metrics?.total_payables || 0" />
                    </div>
                </div>
            </div>

            <!-- Filter & Action Header -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="applyFilters"
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select 
                        v-model="status" 
                        @change="applyFilters"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="ordered">Ordered</option>
                        <option value="partially_received">Partially Received</option>
                        <option value="received">Received & Stocked</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <select 
                        v-model="supplierId" 
                        @change="applyFilters"
                        class="pl-3 pr-8 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Vendors</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>

                    <button 
                        v-if="search || status || supplierId" 
                        @click="resetFilters" 
                        class="px-2.5 py-2 text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <Link 
                        :href="route('purchases.create')" 
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Purchase Order</span>
                    </Link>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <th class="py-3 px-4 whitespace-nowrap">PO Number</th>
                                <th class="py-3 px-4 whitespace-nowrap">Vendor</th>
                                <th class="py-3 px-4 whitespace-nowrap">Destination Hub</th>
                                <th class="py-3 px-4 whitespace-nowrap">Order Date</th>
                                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Order Total</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Payable Due</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openDetailsModal(order)"
                                        class="text-indigo-600 hover:text-indigo-900 hover:underline font-bold transition cursor-pointer text-left inline-flex items-center gap-1.5"
                                        title="View Purchase Order Details"
                                    >
                                        <span>{{ order.po_number || order.order_number }}</span>
                                        <svg class="w-3.5 h-3.5 text-indigo-400 opacity-60 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 max-w-[180px] lg:max-w-[220px] truncate" :title="order.supplier?.name || 'Direct Vendor'">{{ order.supplier?.name || 'Direct Vendor' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ order.supplier?.phone }}</div>
                                </td>

                                <td class="py-3.5 px-4 text-slate-700 font-medium whitespace-nowrap">
                                    <span class="max-w-[150px] truncate block" :title="order.warehouse?.name">{{ order.warehouse?.name }}</span>
                                </td>

                                <td class="py-3.5 px-4 text-xs text-slate-600 font-medium whitespace-nowrap">
                                    {{ formatDate(order.order_date) }}
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <StatusBadge :status="order.status" />
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <CurrencyDisplay :amount="order.total_amount" />
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div 
                                        class="font-mono font-bold"
                                        :class="order.due_amount > 0 ? 'text-rose-600' : 'text-emerald-600'"
                                    >
                                        <CurrencyDisplay :amount="order.due_amount" />
                                    </div>
                                    <StatusBadge :status="order.payment_status" class="scale-90 origin-right mt-0.5" />
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <ActionDropdown>
                                        <div class="p-1 space-y-0.5">
                                            <!-- View Details -->
                                            <button
                                                @click="openDetailsModal(order)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Details</span>
                                            </button>

                                            <!-- Edit Order -->
                                            <Link
                                                v-if="['draft', 'ordered'].includes(order.status)"
                                                :href="route('purchases.edit', order.id)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Order</span>
                                            </Link>

                                            <!-- Receive Items Button -->
                                            <button 
                                                v-if="['ordered', 'partially_received'].includes(order.status)"
                                                @click="openReceiveModal(order)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Receive Items</span>
                                            </button>

                                            <!-- Close Order (Complete) Button -->
                                            <button 
                                                v-if="order.status === 'partially_received'"
                                                @click="openCloseModal(order)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Close Order</span>
                                            </button>

                                            <!-- Mark Ordered Button -->
                                            <button 
                                                v-if="order.status === 'draft'"
                                                @click="markOrdered(order)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-indigo-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                </svg>
                                                <span>Send / Order</span>
                                            </button>

                                            <!-- Pay Vendor Bill -->
                                            <button 
                                                v-if="order.due_amount > 0 && order.status !== 'cancelled'"
                                                @click="openPaymentModal(order)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>Record Vendor Pay</span>
                                            </button>

                                            <!-- Print Procurement Sheet -->
                                            <a 
                                                :href="route('purchases.pdf', order.id)" 
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                <span>Print PO</span>
                                            </a>

                                            <!-- Cancel PO -->
                                            <button 
                                                v-if="['draft', 'ordered'].includes(order.status)"
                                                @click="cancelOrder(order)"
                                                type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition text-left"
                                            >
                                                <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>Cancel Order</span>
                                            </button>
                                        </div>
                                    </ActionDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.data.length === 0" class="text-center py-16 text-slate-400 text-xs">
                    No purchase orders found.
                </div>

                <!-- Pagination -->
                <div v-if="orders.links && orders.links.length > 3" class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in orders.links" 
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all"
                            :class="link.active ? 'bg-indigo-600 text-white' : (link.url ? 'text-slate-600 hover:bg-slate-200' : 'text-slate-300 cursor-not-allowed')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Receive Goods Modal -->
        <Modal :show="isReceiveModalOpen" @close="isReceiveModalOpen = false" maxWidth="3xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Receive Purchased Items</span>
                            <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                {{ receivingPo?.po_number }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500">
                            Fulfillment Destination: <strong>{{ receivingPo?.warehouse?.name }}</strong>
                        </p>
                    </div>
                    <button @click="isReceiveModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitReceive" class="space-y-4">
                    <!-- Inbound Receiving Barcode Assistant -->
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="font-bold text-slate-700">Barcode Check-In:</span>
                            <span v-if="receiveScanNotification" class="text-indigo-600 font-bold animate-pulse">
                                {{ receiveScanNotification }}
                            </span>
                            <span v-else class="text-slate-400">
                                Scan items with USB gun or camera to increment received count
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="isReceiveCameraOpen = true"
                            class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-indigo-50 border border-slate-200 text-slate-700 hover:text-indigo-600 font-semibold text-xs flex items-center gap-1.5 transition shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Scan with Camera</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-bold">
                                    <th class="py-2.5">Product & SKU</th>
                                    <th class="py-2.5 text-center">Remaining</th>
                                    <th class="py-2.5 text-center">Receive Units *</th>
                                    <th class="py-2.5">Batch / Lot #</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(it, idx) in receiveForm.items" :key="idx" class="hover:bg-slate-50">
                                    <td class="py-3">
                                        <div class="font-bold text-slate-800">{{ it.product_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ it.sku }}</div>
                                        <div v-if="it.is_serialized" class="mt-1">
                                            <input 
                                                type="text" 
                                                v-model="it.serial_input"
class="w-full px-2 py-1 rounded text-[11px] border border-amber-300 bg-amber-50/50"
                                            />
                                        </div>
                                    </td>
                                    <td class="py-3 text-center font-mono font-bold text-slate-600">
                                        {{ it.remaining_quantity }} {{ it.unit }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            :max="it.remaining_quantity"
                                            v-model="it.quantity"
                                            class="w-20 px-2 py-1 text-center rounded-lg border border-slate-200 font-mono font-bold text-xs"
                                        />
                                    </td>
                                    <td class="py-3">
                                        <input 
                                            type="text" 
                                            v-model="it.batch_number"
class="w-24 px-2 py-1 rounded-lg border border-slate-200 text-xs font-mono"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <button 
                                v-if="receivingPo?.status === 'partially_received'"
                                type="button" 
                                @click="isReceiveModalOpen = false; openCloseModal(receivingPo)" 
                                class="px-3.5 py-2 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition flex items-center gap-1.5"
                            >
                                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Stop Receiving & Close Order</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="isReceiveModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm"
                            >
                                Confirm Stock-In
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Record Vendor Payment Modal -->
        <Modal :show="isPaymentModalOpen" @close="isPaymentModalOpen = false" maxWidth="xl">
            <div class="p-6 sm:p-7">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Record Vendor Payment</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            PO #{{ payingPo?.po_number }} • {{ payingPo?.supplier?.name }}
                        </p>
                    </div>
                    <button
                        @click="isPaymentModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 transition"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-4 mt-5 text-xs">
                    <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200/60 flex items-center justify-between text-xs">
                        <span class="text-amber-800 font-medium">Outstanding Bill Due:</span>
                        <span class="font-mono font-bold text-amber-900 text-sm">
                            <CurrencyDisplay :amount="payingPo?.due_amount || 0" />
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                Disbursement Amount <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="payingPo?.due_amount"
                                required
                                class="w-full text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 font-mono font-bold"
                            />
                            <div v-if="paymentForm.errors.amount" class="text-xs text-rose-500 mt-1">
                                {{ paymentForm.errors.amount }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">
                                Pay From Account <span class="text-rose-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="paymentForm.financial_account_id"
                                :options="accountOptions"
                                size="compact"
                                :error="paymentForm.errors.financial_account_id"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Ref / TrxID #</label>
                            <input
                                v-model="paymentForm.payment_reference"
                                type="text"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Payment Date</label>
                            <input
                                v-model="paymentForm.payment_date"
                                type="date"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Remarks / Note</label>
                        <input
                            v-model="paymentForm.notes"
                            type="text"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPaymentModalOpen = false"
                            class="px-4 py-2.5 text-xs font-semibold border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="paymentForm.processing"
                            class="px-6 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition"
                        >
                            Record Disbursement
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Purchase Order Details Modal -->
        <Modal :show="isDetailsModalOpen" max-width="4xl" @close="isDetailsModalOpen = false">
            <div v-if="selectedOrderForDetails" class="p-6 space-y-6">
                <!-- Header -->
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-black text-slate-900 tracking-tight font-mono">
                                {{ selectedOrderForDetails.po_number || selectedOrderForDetails.order_number }}
                            </h3>
                            <StatusBadge :status="selectedOrderForDetails.status" />
                            <StatusBadge :status="selectedOrderForDetails.payment_status" />
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Order Date: <span class="font-medium text-slate-700">{{ formatDate(selectedOrderForDetails.order_date) }}</span> &bull; 
                            Warehouse: <span class="font-semibold text-slate-700">{{ selectedOrderForDetails.warehouse?.name }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="isDetailsModalOpen = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- 3-Column Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Vendor -->
                    <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Vendor / Supplier</div>
                        <div class="font-bold text-slate-900 text-sm">
                            {{ selectedOrderForDetails.supplier?.name || 'Direct Vendor' }}
                        </div>
                        <div class="text-xs text-slate-600 font-mono">
                            {{ selectedOrderForDetails.supplier?.phone || 'No phone listed' }}
                        </div>
                        <div class="text-xs text-slate-500 truncate">
                            {{ selectedOrderForDetails.supplier?.address || 'Address not listed' }}
                        </div>
                    </div>

                    <!-- Logistics -->
                    <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Logistics & Schedule</div>
                        <div class="text-xs text-slate-700">
                            <span class="text-slate-400">Warehouse:</span> <strong class="text-slate-900">{{ selectedOrderForDetails.warehouse?.name }}</strong>
                        </div>
                        <div class="text-xs text-slate-700">
                            <span class="text-slate-400">PO Date:</span> {{ formatDate(selectedOrderForDetails.order_date) }}
                        </div>
                        <div class="text-xs text-slate-700">
                            <span class="text-slate-400">Expected Delivery:</span> {{ selectedOrderForDetails.expected_date ? formatDate(selectedOrderForDetails.expected_date) : 'Not specified' }}
                        </div>
                    </div>

                    <!-- Financial Highlights -->
                    <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/70 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Payment Snapshot</div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Order Total:</span>
                            <span class="font-mono font-bold text-slate-900"><CurrencyDisplay :amount="selectedOrderForDetails.total_amount" /></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Disbursed Paid:</span>
                            <span class="font-mono font-bold text-emerald-600"><CurrencyDisplay :amount="selectedOrderForDetails.paid_amount" /></span>
                        </div>
                        <div class="flex items-center justify-between text-xs border-t border-slate-200 pt-1">
                            <span class="text-slate-700 font-semibold">Balance Due:</span>
                            <span class="font-mono font-black" :class="selectedOrderForDetails.due_amount > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                <CurrencyDisplay :amount="selectedOrderForDetails.due_amount" />
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Itemized Procurement Lines ({{ selectedOrderForDetails.items?.length || 0 }})
                        </h4>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase text-[10px] font-bold">
                                <tr>
                                    <th class="py-2.5 px-3">Product Name</th>
                                    <th class="py-2.5 px-3">SKU</th>
                                    <th class="py-2.5 px-3 text-center">Batch / Expiry</th>
                                    <th class="py-2.5 px-3 text-center">Ordered</th>
                                    <th class="py-2.5 px-3 text-center">Received</th>
                                    <th class="py-2.5 px-3 text-right">Unit Cost</th>
                                    <th class="py-2.5 px-3 text-right">Line Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono">
                                <tr v-for="it in selectedOrderForDetails.items" :key="it.id" class="hover:bg-slate-50/60 font-sans">
                                    <td class="py-2.5 px-3 font-semibold text-slate-800">
                                        {{ it.product?.name || `Product #${it.product_id}` }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-slate-500 text-[11px]">
                                        {{ it.product?.sku || '—' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-600">
                                        <div v-if="it.batch_number">{{ it.batch_number }}</div>
                                        <div v-if="it.expiry_date" class="text-[10px] text-slate-400">Exp: {{ formatDate(it.expiry_date) }}</div>
                                        <span v-if="!it.batch_number && !it.expiry_date" class="text-slate-300">—</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-800">
                                        {{ it.quantity }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono">
                                        <span :class="it.received_quantity >= it.quantity ? 'text-emerald-600 font-bold' : (it.received_quantity > 0 ? 'text-amber-600 font-bold' : 'text-slate-400')">
                                            {{ it.received_quantity || 0 }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-slate-700">
                                        <CurrencyDisplay :amount="it.unit_cost" />
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                        <CurrencyDisplay :amount="(Number(it.quantity) || 0) * (Number(it.unit_cost) || 0)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Remarks / Notes -->
                <div v-if="selectedOrderForDetails.notes" class="p-3 bg-amber-50/70 rounded-xl border border-amber-200/60 text-xs">
                    <span class="font-bold text-amber-900 block mb-0.5">Procurement Notes / Instructions:</span>
                    <p class="text-amber-800 whitespace-pre-line">{{ selectedOrderForDetails.notes }}</p>
                </div>

                <!-- Actions Footer -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <a
                            :href="route('purchases.pdf', selectedOrderForDetails.id)"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-xs"
                        >
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Print PDF</span>
                        </a>

                        <Link
                            v-if="['draft', 'ordered'].includes(selectedOrderForDetails.status)"
                            :href="route('purchases.edit', selectedOrderForDetails.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition shadow-xs"
                        >
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Edit Order</span>
                        </Link>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button
                            v-if="['ordered', 'partially_received'].includes(selectedOrderForDetails.status)"
                            type="button"
                            @click="isDetailsModalOpen = false; openReceiveModal(selectedOrderForDetails)"
                            class="px-3.5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition"
                        >
                            Receive Items
                        </button>

                        <button
                            v-if="selectedOrderForDetails.status === 'partially_received'"
                            type="button"
                            @click="isDetailsModalOpen = false; openCloseModal(selectedOrderForDetails)"
                            class="px-3.5 py-2 text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-xl transition"
                        >
                            Close Order
                        </button>

                        <button
                            v-if="selectedOrderForDetails.due_amount > 0 && selectedOrderForDetails.status !== 'cancelled'"
                            type="button"
                            @click="isDetailsModalOpen = false; openPaymentModal(selectedOrderForDetails)"
                            class="px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition"
                        >
                            Record Vendor Pay
                        </button>

                        <button
                            type="button"
                            @click="isDetailsModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Close Purchase Order Modal -->
        <Modal :show="isCloseModalOpen" @close="isCloseModalOpen = false" maxWidth="xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Close Purchase Order</span>
                            <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                {{ closingPo?.po_number }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500">
                            Finalize order, cancel remaining unreceived items, and balance accounting
                        </p>
                    </div>
                    <button @click="isCloseModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCloseOrder" class="space-y-4">
                    <!-- Breakdown Summary Grid -->
                    <div class="grid grid-cols-3 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 text-xs">
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase font-bold">Original Order</span>
                            <div class="font-mono font-bold text-slate-900 text-sm mt-0.5">
                                <CurrencyDisplay :amount="closingPo?.total_amount || 0" />
                            </div>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase font-bold">Goods Received</span>
                            <div class="font-mono font-bold text-emerald-700 text-sm mt-0.5">
                                <CurrencyDisplay :amount="closeCalculation.receivedValue" />
                            </div>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[10px] uppercase font-bold">Amount Paid</span>
                            <div class="font-mono font-bold text-indigo-700 text-sm mt-0.5">
                                <CurrencyDisplay :amount="closingPo?.paid_amount || 0" />
                            </div>
                        </div>
                    </div>

                    <!-- Notice if unreceived items being cancelled -->
                    <div class="p-3 bg-amber-50/70 border border-amber-200/70 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <span class="font-bold">Unreceived stock will be cancelled:</span>
                            <p class="text-[11px] text-amber-800 mt-0.5">
                                Remaining units will be removed from incoming orders. The final purchase bill will be set to the value of received goods (<CurrencyDisplay :amount="closeCalculation.receivedValue" />).
                            </p>
                        </div>
                    </div>

                    <!-- CASE A: Overpayment Handling -->
                    <div v-if="closeCalculation.overpaid > 0" class="border border-emerald-200 bg-emerald-50/50 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-900 uppercase tracking-wide">Advance Overpayment Detected</span>
                            <span class="font-mono font-extrabold text-sm text-emerald-700">
                                <CurrencyDisplay :amount="closeCalculation.overpaid" />
                            </span>
                        </div>
                        <p class="text-xs text-emerald-800">
                            You paid in full for items that will not be received. How would you like to handle the <strong><CurrencyDisplay :amount="closeCalculation.overpaid" /></strong> refund?
                        </p>

                        <div class="space-y-2 pt-1">
                            <label
                                class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition"
                                :class="closeForm.refund_mode === 'account' ? 'bg-white border-emerald-500 ring-1 ring-emerald-500' : 'bg-white/60 border-slate-200'"
                            >
                                <input type="radio" v-model="closeForm.refund_mode" value="account" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                                <div class="text-xs">
                                    <div class="font-bold text-slate-800">Deposit Refund to Bank / Cash Account</div>
                                    <div class="text-slate-500 text-[11px]">Supplier refunded the money. Deposit directly back into your company account.</div>
                                </div>
                            </label>

                            <label
                                class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition"
                                :class="closeForm.refund_mode === 'credit' ? 'bg-white border-emerald-500 ring-1 ring-emerald-500' : 'bg-white/60 border-slate-200'"
                            >
                                <input type="radio" v-model="closeForm.refund_mode" value="credit" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                                <div class="text-xs">
                                    <div class="font-bold text-slate-800">Keep as Supplier Credit / Advance Balance</div>
                                    <div class="text-slate-500 text-[11px]">Supplier keeps the advance to deduct from your future purchase orders.</div>
                                </div>
                            </label>
                        </div>

                        <!-- Account Selection when refunding to account -->
                        <div v-if="closeForm.refund_mode === 'account'" class="space-y-3 pt-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Deposit Into Account *</label>
                                <SearchableSelect
                                    v-model="closeForm.financial_account_id"
                                    :options="accountOptions"
                                    :error="closeForm.errors.financial_account_id"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Refund Reference / Cheque / TrxID (Optional)</label>
                                <input
                                    v-model="closeForm.reference_number"
                                    type="text"
                                    class="w-full text-xs px-3.5 py-2 bg-white border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- CASE B: Underpayment Notice -->
                    <div v-else-if="closeCalculation.newDue > 0" class="p-3.5 bg-blue-50/70 border border-blue-200/70 rounded-xl text-xs text-blue-900">
                        <div class="flex items-center justify-between font-bold">
                            <span>Adjusted Remaining Payable:</span>
                            <span class="font-mono text-sm"><CurrencyDisplay :amount="closeCalculation.newDue" /></span>
                        </div>
                        <p class="text-[11px] text-blue-700 mt-1">
                            The bill has been lowered to <CurrencyDisplay :amount="closeCalculation.receivedValue" />. You only owe the supplier for received goods.
                        </p>
                    </div>

                    <!-- Close Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Close Reason / Notes (Optional)</label>
                        <textarea
                            v-model="closeForm.notes"
                            rows="2"
                            class="w-full text-xs px-3.5 py-2 bg-white border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="isCloseModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="closeForm.processing"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition disabled:opacity-50"
                        >
                            <span v-if="closeForm.processing">Closing...</span>
                            <span v-else>Confirm & Close Order</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- PO Action Confirmation Modal -->
        <ConfirmationModal
            :show="!!poActionModal"
            :title="poActionModal?.title || ''"
            :variant="poActionModal?.variant || 'danger'"
            :icon="poActionModal?.icon || 'danger'"
            :item-name="poActionModal?.order ? `PO #${poActionModal.order.po_number} - ${poActionModal.order.supplier?.name || 'Vendor'}` : ''"
            :message="poActionModal?.message || ''"
            :confirm-text="poActionModal?.confirmText || 'Confirm'"
            :processing="isPoActionProcessing"
            @confirm="executePoAction"
            @close="poActionModal = null"
        />

        <!-- Camera Barcode Scanner Modal for Inbound Receiving -->
        <BarcodeCameraModal
            v-model:open="isReceiveCameraOpen"
            title="Scan Delivery Box Barcode"
            @scan="handleReceiveBarcodeScanned"
        />
    </AppLayout>
</template>
