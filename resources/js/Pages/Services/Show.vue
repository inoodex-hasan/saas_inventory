<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    page_title: String,
    ticket: Object,
    products: Array,
    warehouses: Array,
    technicians: Array,
});

// Add Part Modal
const isAddPartModalOpen = ref(false);
const partForm = useForm({
    part_name: '',
    product_id: '',
    warehouse_id: props.warehouses[0]?.id || '',
    quantity: 1,
    unit_price: 0,
    notes: '',
});

function onProductSelectedInModal() {
    if (!partForm.product_id) return;
    const prod = props.products.find(p => p.id === parseInt(partForm.product_id));
    if (prod) {
        partForm.part_name = prod.name;
        partForm.unit_price = parseFloat(prod.unit_price) || 0;
    }
}

function submitAddPart() {
    partForm.post(route('services.parts.add', props.ticket.id), {
        onSuccess: () => {
            isAddPartModalOpen.value = false;
            partForm.reset();
        },
    });
}

function removePart(item) {
    if (confirm(`Remove ${item.part_name}? Stock will be adjusted back to warehouse.`)) {
        router.delete(route('services.parts.remove', [props.ticket.id, item.id]));
    }
}

// Payment Modal
const isPayModalOpen = ref(false);
const payForm = useForm({
    amount: props.ticket.due_amount,
    payment_method: 'cash',
    notes: '',
});

function openPayModal() {
    payForm.amount = props.ticket.due_amount;
    payForm.payment_method = 'cash';
    payForm.notes = '';
    isPayModalOpen.value = true;
}

function submitPayment() {
    payForm.post(route('services.pay', props.ticket.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
        },
    });
}

// Status Transition
function transitionTo(status) {
    router.post(route('services.transition', props.ticket.id), { status });
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('services.index')" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            &larr; Back to Services
                        </Link>
                    </div>
                    <div class="flex items-center gap-3 mt-1">
                        <h2 class="text-xl font-black text-slate-800 tracking-tight font-mono">
                            {{ ticket.ticket_number }}
                        </h2>
                        <StatusBadge :status="ticket.status" />
                        <span
                            class="px-2.5 py-0.5 text-[10px] font-bold uppercase rounded-md"
                            :class="{
                                'bg-rose-100 text-rose-700': ticket.priority === 'urgent',
                                'bg-orange-100 text-orange-700': ticket.priority === 'high',
                                'bg-blue-100 text-blue-700': ticket.priority === 'normal',
                                'bg-slate-100 text-slate-600': ticket.priority === 'low'
                            }"
                        >
                            {{ ticket.priority }} Priority
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Intake logged on {{ ticket.received_date }} &bull; Device: <strong class="text-slate-700">{{ ticket.device_name }}</strong>
                    </p>
                </div>

                <!-- Document & Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="route('services.token', ticket.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200/80 rounded-xl text-xs font-bold transition-all shadow-xs"
                    >
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span>Claim Token</span>
                    </a>

                    <a
                        :href="route('services.invoice', ticket.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/80 rounded-xl text-xs font-bold transition-all shadow-xs"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Tax Invoice</span>
                    </a>

                    <Link
                        :href="route('services.edit', ticket.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Edit Ticket</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Status Stepper Bar -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Service Lifecycle Stage</div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs font-bold">
                    <button
                        type="button"
                        @click="transitionTo('received')"
                        class="p-2.5 rounded-xl border transition-all"
                        :class="ticket.status === 'received' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        1. Received / Intake
                    </button>
                    <button
                        type="button"
                        @click="transitionTo('diagnosing')"
                        class="p-2.5 rounded-xl border transition-all"
                        :class="ticket.status === 'diagnosing' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        2. Diagnosing
                    </button>
                    <button
                        type="button"
                        @click="transitionTo('waiting_parts')"
                        class="p-2.5 rounded-xl border transition-all"
                        :class="ticket.status === 'waiting_parts' ? 'bg-purple-600 text-white border-purple-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        3. Waiting Parts
                    </button>
                    <button
                        type="button"
                        @click="transitionTo('repaired')"
                        class="p-2.5 rounded-xl border transition-all"
                        :class="ticket.status === 'repaired' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        4. Repaired (Ready)
                    </button>
                    <button
                        type="button"
                        @click="transitionTo('delivered')"
                        class="p-2.5 rounded-xl border transition-all"
                        :class="ticket.status === 'delivered' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    >
                        5. Delivered / Closed
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Details & Parts -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer & Equipment Split -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Customer Profile -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-2">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Customer / Client</div>
                            <div class="text-base font-extrabold text-slate-800">{{ ticket.customer?.name || 'Walk-in Customer' }}</div>
                            <div class="text-xs text-slate-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>{{ ticket.customer?.phone || 'No phone' }}</span>
                            </div>
                            <div v-if="ticket.customer?.email" class="text-xs text-slate-500">
                                {{ ticket.customer.email }}
                            </div>
                            <div v-if="ticket.customer?.address" class="text-xs text-slate-500">
                                {{ ticket.customer.address }}
                            </div>
                        </div>

                        <!-- Device Profile -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-2">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Equipment Info</div>
                            <div class="text-base font-extrabold text-slate-800">{{ ticket.device_name }}</div>
                            <div class="text-xs text-slate-600">
                                <span class="text-slate-400">Brand:</span> <span class="font-semibold">{{ ticket.device_brand || 'N/A' }}</span>
                            </div>
                            <div class="text-xs text-slate-600 font-mono">
                                <span class="text-slate-400 font-sans">Serial / IMEI:</span> <span class="font-bold">{{ ticket.serial_no || 'N/A' }}</span>
                            </div>
                            <div v-if="ticket.accessories_received" class="text-xs text-slate-500">
                                <span class="text-slate-400">Accessories:</span> {{ ticket.accessories_received }}
                            </div>
                        </div>
                    </div>

                    <!-- Problem Description & Diagnosis -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Issue & Diagnosis</div>
                        <div>
                            <div class="text-xs font-bold text-slate-700">Customer Reported Symptoms:</div>
                            <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl mt-1 border border-slate-100">
                                {{ ticket.problem_description || 'No specific symptoms described.' }}
                            </div>
                        </div>
                        <div v-if="ticket.initial_diagnosis">
                            <div class="text-xs font-bold text-slate-700">Initial Bench Diagnosis:</div>
                            <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl mt-1 border border-slate-100">
                                {{ ticket.initial_diagnosis }}
                            </div>
                        </div>
                        <div v-if="ticket.remarks">
                            <div class="text-xs font-bold text-slate-700">Technician Remarks / Notes:</div>
                            <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl mt-1 border border-slate-100">
                                {{ ticket.remarks }}
                            </div>
                        </div>
                    </div>

                    <!-- Spare Parts Consumed Table -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Spare Parts & Components Used</h3>
                                <p class="text-[11px] text-slate-400">Inventory items are automatically stock-audited and deducted from the assigned warehouse</p>
                            </div>
                            <button
                                type="button"
                                @click="isAddPartModalOpen = true"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-all"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ Add Spare Part</span>
                            </button>
                        </div>

                        <div v-if="ticket.items && ticket.items.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold uppercase text-[10px]">
                                        <th class="py-2.5 px-3">Part Name</th>
                                        <th class="py-2.5 px-3">Warehouse Hub</th>
                                        <th class="py-2.5 px-3 text-center">Qty</th>
                                        <th class="py-2.5 px-3 text-right">Unit Price</th>
                                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                                        <th class="py-2.5 px-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="item in ticket.items" :key="item.id" class="hover:bg-slate-50/50">
                                        <td class="py-2.5 px-3">
                                            <div class="font-bold text-slate-800">{{ item.part_name }}</div>
                                            <div v-if="item.notes" class="text-[11px] text-slate-400">{{ item.notes }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-600">
                                            {{ item.warehouse?.name || 'Non-stock item' }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-bold text-slate-700">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-medium text-slate-600">
                                            <CurrencyDisplay :amount="item.unit_price" />
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-slate-800">
                                            <CurrencyDisplay :amount="item.subtotal" />
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <button
                                                type="button"
                                                @click="removePart(item)"
                                                class="text-slate-400 hover:text-rose-600 text-sm font-bold"
                                                title="Remove part & restore stock"
                                            >
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic py-4 text-center">
                            No spare parts consumed yet. Click "+ Add Spare Part" to add components to this ticket.
                        </div>
                    </div>
                </div>

                <!-- Right Col: Financials & Meta -->
                <div class="space-y-6">
                    <!-- Financial Summary & Collect Payment -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Financial Breakdown</h3>
                            <StatusBadge :status="ticket.payment_status" />
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Labor / Service Charge:</span>
                                <span class="font-bold"><CurrencyDisplay :amount="ticket.service_charge" /></span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Spare Parts Total:</span>
                                <span class="font-bold"><CurrencyDisplay :amount="ticket.parts_total" /></span>
                            </div>
                            <div v-if="parseFloat(ticket.discount_amount) > 0" class="flex justify-between text-rose-600">
                                <span>Discount:</span>
                                <span class="font-bold">- <CurrencyDisplay :amount="ticket.discount_amount" /></span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 flex justify-between text-sm font-black text-slate-800">
                                <span>Grand Total:</span>
                                <span class="text-indigo-600"><CurrencyDisplay :amount="ticket.total_amount" /></span>
                            </div>
                            <div class="flex justify-between text-emerald-700 font-bold">
                                <span>Paid Amount:</span>
                                <span><CurrencyDisplay :amount="ticket.paid_amount" /></span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 flex justify-between font-black text-xs" :class="parseFloat(ticket.due_amount) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                <span>Outstanding Due:</span>
                                <span><CurrencyDisplay :amount="ticket.due_amount" /></span>
                            </div>
                        </div>

                        <div v-if="parseFloat(ticket.due_amount) > 0" class="pt-2">
                            <button
                                type="button"
                                @click="openPayModal"
                                class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-rose-600/30 transition-all text-center flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>Collect Payment (৳{{ ticket.due_amount }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Workshop Meta -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3 text-xs">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Ticket Logistics</div>
                        
                        <div class="flex justify-between">
                            <span class="text-slate-400">Assigned Technician:</span>
                            <span class="font-bold text-slate-800">{{ ticket.technician?.name || 'Unassigned' }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-slate-400">Warranty Coverage:</span>
                            <span class="font-bold text-emerald-700">{{ ticket.warranty_days ? `${ticket.warranty_days} Days` : 'None' }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-slate-400">Received Date:</span>
                            <span class="font-medium text-slate-800">{{ ticket.received_date }}</span>
                        </div>

                        <div v-if="ticket.estimated_completion_date" class="flex justify-between">
                            <span class="text-slate-400">Est. Completion:</span>
                            <span class="font-medium text-slate-800">{{ ticket.estimated_completion_date }}</span>
                        </div>

                        <div v-if="ticket.completed_date" class="flex justify-between">
                            <span class="text-slate-400">Completed Date:</span>
                            <span class="font-medium text-indigo-700 font-bold">{{ ticket.completed_date }}</span>
                        </div>

                        <div v-if="ticket.delivered_date" class="flex justify-between">
                            <span class="text-slate-400">Delivered Date:</span>
                            <span class="font-medium text-emerald-700 font-bold">{{ ticket.delivered_date }}</span>
                        </div>

                        <div v-if="ticket.creator" class="flex justify-between pt-1 border-t border-slate-100">
                            <span class="text-slate-400">Intake by:</span>
                            <span class="text-slate-600">{{ ticket.creator.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Spare Part Modal -->
        <Modal :show="isAddPartModalOpen" @close="isAddPartModalOpen = false" max-width="lg">
            <div class="p-6">
                <h3 class="text-base font-bold text-slate-800 mb-1">
                    Consume Spare Part — #{{ ticket.ticket_number }}
                </h3>
                <p class="text-xs text-slate-500 mb-4">
                    Link to inventory catalog to deduct stock or enter a custom part name.
                </p>

                <form @submit.prevent="submitAddPart" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Select Catalog Product (Optional)</label>
                        <select
                            v-model="partForm.product_id"
                            @change="onProductSelectedInModal"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">-- Non-inventory / Custom part --</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (৳{{ p.unit_price }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Part Description <span class="text-rose-500">*</span></label>
                            <input
                                v-model="partForm.part_name"
                                type="text"
                                placeholder="e.g. 500W Power Supply"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Deduct From Warehouse Hub</label>
                            <select
                                v-model="partForm.warehouse_id"
                                :disabled="!partForm.product_id"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-400"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity <span class="text-rose-500">*</span></label>
                            <input
                                v-model="partForm.quantity"
                                type="number"
                                min="1"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Unit Price (৳) <span class="text-rose-500">*</span></label>
                            <input
                                v-model="partForm.unit_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Part / Line Notes</label>
                        <input
                            v-model="partForm.notes"
                            type="text"
                            placeholder="Optional notes or batch code..."
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddPartModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="partForm.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            Deduct & Add Part
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Collect Payment Modal -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-base font-bold text-slate-800 mb-1">
                    Collect Payment — #{{ ticket.ticket_number }}
                </h3>
                <p class="text-xs text-slate-500 mb-4">
                    Outstanding balance: <strong class="text-rose-600"><CurrencyDisplay :amount="ticket.due_amount" /></strong>
                </p>

                <form @submit.prevent="submitPayment" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Amount (৳) <span class="text-rose-500">*</span></label>
                        <input
                            v-model="payForm.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="ticket.due_amount"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="cash">Cash Counter</option>
                            <option value="mobile_banking">Mobile Banking (bKash / Nagad / Rocket)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card POS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Notes / TrxID</label>
                        <input
                            v-model="payForm.notes"
                            type="text"
                            placeholder="e.g. bKash TrxID, receipt slip #"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
