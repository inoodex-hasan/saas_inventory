<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    page_title: String,
    nextTicketNumber: String,
    customers: Array,
    products: Array,
    warehouses: Array,
    technicians: Array,
    todayDate: String,
});

const form = useForm({
    customer_mode: 'existing',
    customer_id: '',
    new_customer_name: '',
    new_customer_phone: '',
    new_customer_email: '',
    new_customer_address: '',

    device_name: '',
    device_brand: '',
    serial_no: '',
    accessories_received: '',
    problem_description: '',
    initial_diagnosis: '',
    technician_id: '',
    priority: 'normal',
    warranty_days: 30,
    received_date: props.todayDate,
    estimated_completion_date: '',
    remarks: '',

    service_charge: 500,
    discount_amount: 0,
    paid_amount: 0,

    parts: [],
});

function addPartRow() {
    form.parts.push({
        part_name: '',
        product_id: '',
        warehouse_id: props.warehouses[0]?.id || '',
        quantity: 1,
        unit_price: 0,
        notes: '',
    });
}

function removePartRow(index) {
    form.parts.splice(index, 1);
}

function onProductSelected(partIndex) {
    const part = form.parts[partIndex];
    if (!part.product_id) return;
    const prod = props.products.find(p => p.id === parseInt(part.product_id));
    if (prod) {
        part.part_name = prod.name;
        part.unit_price = parseFloat(prod.unit_price) || 0;
    }
}

const partsTotal = computed(() => {
    return form.parts.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.unit_price) || 0;
        return sum + (qty * price);
    }, 0);
});

const grandTotal = computed(() => {
    const labor = parseFloat(form.service_charge) || 0;
    const discount = parseFloat(form.discount_amount) || 0;
    return Math.max(0, labor + partsTotal.value - discount);
});

const remainingDue = computed(() => {
    const paid = parseFloat(form.paid_amount) || 0;
    return Math.max(0, grandTotal.value - paid);
});

function setFullPayment() {
    form.paid_amount = grandTotal.value;
}

function submitForm() {
    form.post(route('services.store'));
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('services.index')" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Intake Service Ticket</h1>
                        <p class="text-xs text-slate-500 font-medium">Record device drop-off, initial diagnosis, and preliminary service estimates</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Ticket #</span>
                    <span class="font-mono text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-800">
                        {{ nextTicketNumber }}
                    </span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Section 1: Customer Information -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Customer Information</h2>
                            <p class="text-xs text-slate-500">Select an existing registered client or create a new one on the fly</p>
                        </div>

                        <!-- Segmented Mode Toggle -->
                        <div class="flex items-center bg-slate-100 p-0.5 rounded-lg text-xs font-semibold">
                            <button
                                type="button"
                                @click="form.customer_mode = 'existing'"
                                :class="form.customer_mode === 'existing' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                                class="px-3 py-1 rounded-md transition"
                            >
                                Existing Customer
                            </button>
                            <button
                                type="button"
                                @click="form.customer_mode = 'new'"
                                :class="form.customer_mode === 'new' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                                class="px-3 py-1 rounded-md transition"
                            >
                                + New Customer
                            </button>
                        </div>
                    </div>

                    <!-- Existing Customer Dropdown -->
                    <div v-if="form.customer_mode === 'existing'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Select Customer <span class="text-rose-500">*</span></label>
                        <select
                            v-model="form.customer_id"
                            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            required
                        >
                            <option value="">-- Choose Existing Customer --</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.phone }}) {{ c.address ? '- ' + c.address : '' }}
                            </option>
                        </select>
                        <div v-if="form.errors.customer_id" class="text-xs text-rose-500 mt-1">{{ form.errors.customer_id }}</div>
                    </div>

                    <!-- New Customer Inline Inputs -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.new_customer_name"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                required
                            />
                            <div v-if="form.errors.new_customer_name" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile / Phone <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.new_customer_phone"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                required
                            />
                            <div v-if="form.errors.new_customer_phone" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_phone }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input
                                v-model="form.new_customer_email"
                                type="email"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address</label>
                            <input
                                v-model="form.new_customer_address"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Equipment & Device Profile -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Equipment & Device Profile</h2>
                        <p class="text-xs text-slate-500">Specify hardware model, serial number, and physical accessories handed over</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Device Name / Model <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.device_name"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-semibold placeholder:text-slate-400 placeholder:text-xs"
                                required
                            />
                            <div v-if="form.errors.device_name" class="text-xs text-rose-500 mt-1">{{ form.errors.device_name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Brand / Manufacturer</label>
                            <input
                                v-model="form.device_brand"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Serial Number / IMEI</label>
                            <input
                                v-model="form.serial_no"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Accessories Received</label>
                            <input
                                v-model="form.accessories_received"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Problem Description & Diagnosis -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Problem Description & Diagnosis</h2>
                        <p class="text-xs text-slate-500">Customer complaints, technical observations, scheduling, and warranty policy</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Reported Problem <span class="text-rose-500">*</span></label>
                            <textarea
                                v-model="form.problem_description"
                                rows="3"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                required
                            ></textarea>
                            <div v-if="form.errors.problem_description" class="text-xs text-rose-500 mt-1">{{ form.errors.problem_description }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Technician Diagnosis</label>
                            <textarea
                                v-model="form.initial_diagnosis"
                                rows="3"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            ></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Assign Technician</label>
                            <select
                                v-model="form.technician_id"
                                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            >
                                <option value="">-- Unassigned --</option>
                                <option v-for="u in technicians" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Job Priority</label>
                            <select
                                v-model="form.priority"
                                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 font-semibold"
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent / Priority</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Received Date <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.received_date"
                                type="date"
                                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Est. Completion Date</label>
                            <input
                                v-model="form.estimated_completion_date"
                                type="date"
                                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Service Warranty Coverage (Days)</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <input
                                v-model="form.warranty_days"
                                type="number"
                                min="0"
                                class="w-28 text-xs font-mono font-bold px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="form.warranty_days = 0"
                                    :class="form.warranty_days === 0 ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                    class="px-3 py-1.5 rounded-xl border text-xs font-medium transition"
                                >
                                    None
                                </button>
                                <button
                                    type="button"
                                    @click="form.warranty_days = 15"
                                    :class="form.warranty_days === 15 ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                    class="px-3 py-1.5 rounded-xl border text-xs font-medium transition"
                                >
                                    15 Days
                                </button>
                                <button
                                    type="button"
                                    @click="form.warranty_days = 30"
                                    :class="form.warranty_days === 30 ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                    class="px-3 py-1.5 rounded-xl border text-xs font-medium transition"
                                >
                                    30 Days (Standard)
                                </button>
                                <button
                                    type="button"
                                    @click="form.warranty_days = 90"
                                    :class="form.warranty_days === 90 ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                    class="px-3 py-1.5 rounded-xl border text-xs font-medium transition"
                                >
                                    90 Days
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Spare Parts Consumed (Optional) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Spare Parts Consumed (Optional)</h2>
                            <p class="text-xs text-slate-500">Parts linked to catalog inventory will automatically be deducted from warehouse stock upon saving</p>
                        </div>
                        <button
                            type="button"
                            @click="addPartRow"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Spare Part</span>
                        </button>
                    </div>

                    <div v-if="form.parts.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                                    <th class="py-2.5 px-3">Inventory Product (Optional)</th>
                                    <th class="py-2.5 px-3">Part Description</th>
                                    <th class="py-2.5 px-3">Warehouse / Hub</th>
                                    <th class="py-2.5 px-3 text-center w-24">Qty</th>
                                    <th class="py-2.5 px-3 text-right w-32">Unit Price</th>
                                    <th class="py-2.5 px-3 text-right w-32">Subtotal</th>
                                    <th class="py-2.5 px-3 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(part, idx) in form.parts" :key="idx" class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3">
                                        <select
                                            v-model="part.product_id"
                                            @change="onProductSelected(idx)"
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        >
                                            <option value="">-- Custom Part / Non-catalog --</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">
                                                {{ p.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model="part.part_name"
                                            type="text"
class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 font-medium"
                                            required
                                        />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <select
                                            v-model="part.warehouse_id"
                                            :disabled="!part.product_id"
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-400"
                                        >
                                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                        </select>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model="part.quantity"
                                            type="number"
                                            min="1"
                                            class="w-full text-xs text-center font-mono font-bold px-2 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                            required
                                        />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model="part.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full text-xs text-right font-mono font-bold px-2 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                            required
                                        />
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">
                                        <CurrencyDisplay :amount="(parseFloat(part.quantity) || 0) * (parseFloat(part.unit_price) || 0)" />
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="removePartRow(idx)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-xs text-slate-400 italic py-2">
                        No spare parts added. You can also add parts later while the ticket is under diagnosis or repair.
                    </div>
                </div>

                <!-- Section 5: Billing & Advance Collection -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Service Billing & Advance Collection</h2>
                        <p class="text-xs text-slate-500">Service charge estimation, customer advance deposit, and balance calculations</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Inputs Left (7 cols) -->
                        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Labor & Service Charge <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.service_charge"
                                    type="number"
                                    step="0.01"
                                    min="0"
class="w-full text-xs font-mono font-bold px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Amount</label>
                                <input
                                    v-model="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
class="w-full text-xs font-mono px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-700">Advance Paid Now</label>
                                    <button
                                        type="button"
                                        @click="setFullPayment"
                                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Pay Full (100%)
                                    </button>
                                </div>
                                <input
                                    v-model="form.paid_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="grandTotal"
class="w-full text-xs font-mono font-bold text-emerald-600 px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Internal Remarks</label>
                                <input
                                    v-model="form.remarks"
                                    type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                />
                            </div>
                        </div>

                        <!-- Summary Breakdown Right (5 cols) -->
                        <div class="lg:col-span-5 bg-slate-50 border border-slate-200/80 rounded-2xl p-5 space-y-3">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Summary</div>
                            
                            <div class="flex justify-between text-xs text-slate-600">
                                <span>Labor & Diagnosis Fee:</span>
                                <span class="font-bold text-slate-800"><CurrencyDisplay :amount="form.service_charge || 0" /></span>
                            </div>

                            <div class="flex justify-between text-xs text-slate-600">
                                <span>Spare Parts Total:</span>
                                <span class="font-bold text-slate-800"><CurrencyDisplay :amount="partsTotal" /></span>
                            </div>

                            <div v-if="parseFloat(form.discount_amount) > 0" class="flex justify-between text-xs text-rose-600">
                                <span>Discount:</span>
                                <span class="font-bold">- <CurrencyDisplay :amount="form.discount_amount || 0" /></span>
                            </div>

                            <div class="border-t border-slate-200 pt-2.5 flex justify-between text-sm font-extrabold text-slate-900">
                                <span>Grand Total Billed:</span>
                                <span class="text-indigo-600"><CurrencyDisplay :amount="grandTotal" /></span>
                            </div>

                            <div class="flex justify-between text-xs text-emerald-700 font-bold">
                                <span>Paid Advance:</span>
                                <span><CurrencyDisplay :amount="form.paid_amount || 0" /></span>
                            </div>

                            <div class="border-t border-slate-200 pt-2.5 flex justify-between text-xs font-bold" :class="remainingDue > 0 ? 'text-rose-600' : 'text-emerald-700'">
                                <span>Estimated Due Balance:</span>
                                <span><CurrencyDisplay :amount="remainingDue" /></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('services.index')"
                        class="px-5 py-2.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                    >
                        Create Service Ticket & Print Token
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
