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

function submitForm() {
    form.post(route('services.store'));
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('services.index')" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            &larr; Back to Services
                        </Link>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight mt-1">
                        Intake Service Job Ticket
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Record device drop-off, initial diagnosis, and preliminary service estimates</p>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-3.5 py-2 text-right">
                    <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider">Ticket Sequence</div>
                    <div class="font-mono text-base font-extrabold text-indigo-700">{{ nextTicketNumber }}</div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Section 1: Customer Information -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-bold text-slate-800">1. Customer Information</h2>
                        </div>

                        <!-- Segmented Mode Toggle -->
                        <div class="inline-flex bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                @click="form.customer_mode = 'existing'"
                                class="px-3 py-1 rounded-lg transition-all"
                                :class="form.customer_mode === 'existing' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            >
                                Existing Customer
                            </button>
                            <button
                                type="button"
                                @click="form.customer_mode = 'new'"
                                class="px-3 py-1 rounded-lg transition-all"
                                :class="form.customer_mode === 'new' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
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
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
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
                                placeholder="Customer Name"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.new_customer_name" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile / Phone <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.new_customer_phone"
                                type="text"
                                placeholder="017XXXXXXXX"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.new_customer_phone" class="text-xs text-rose-500 mt-1">{{ form.errors.new_customer_phone }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input
                                v-model="form.new_customer_email"
                                type="email"
                                placeholder="client@example.com"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address</label>
                            <input
                                v-model="form.new_customer_address"
                                type="text"
                                placeholder="House, Road, City"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Equipment & Device Profile -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">2. Equipment & Device Profile</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Device Name / Model <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.device_name"
                                type="text"
                                placeholder="e.g. iPhone 13 Pro / HP 15s"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                                required
                            />
                            <div v-if="form.errors.device_name" class="text-xs text-rose-500 mt-1">{{ form.errors.device_name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Brand / Manufacturer</label>
                            <input
                                v-model="form.device_brand"
                                type="text"
                                placeholder="e.g. Apple, Dell, Lenovo"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Serial Number / IMEI</label>
                            <input
                                v-model="form.serial_no"
                                type="text"
                                placeholder="Device S/N or IMEI"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Accessories Received</label>
                            <input
                                v-model="form.accessories_received"
                                type="text"
                                placeholder="e.g. Charger, Bag, Case"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Problem Description & Workshop Logistics -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">3. Problem Description & Diagnosis</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Reported Problem <span class="text-rose-500">*</span></label>
                            <textarea
                                v-model="form.problem_description"
                                rows="3"
                                placeholder="Describe symptoms, customer complaints, errors..."
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            ></textarea>
                            <div v-if="form.errors.problem_description" class="text-xs text-rose-500 mt-1">{{ form.errors.problem_description }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Technician Diagnosis Remarks</label>
                            <textarea
                                v-model="form.initial_diagnosis"
                                rows="3"
                                placeholder="Preliminary bench checks, visible damages, power state..."
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Assign Technician</label>
                            <select
                                v-model="form.technician_id"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">-- Unassigned --</option>
                                <option v-for="u in technicians" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Job Priority</label>
                            <select
                                v-model="form.priority"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent / Priority</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Received Date</label>
                            <input
                                v-model="form.received_date"
                                type="date"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Est. Completion Date</label>
                            <input
                                v-model="form.estimated_completion_date"
                                type="date"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Service Warranty Coverage (Days)</label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.warranty_days"
                                type="number"
                                min="0"
                                class="w-32 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            />
                            <div class="flex gap-1.5">
                                <button type="button" @click="form.warranty_days = 0" class="text-[10px] bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded font-semibold text-slate-600">None</button>
                                <button type="button" @click="form.warranty_days = 15" class="text-[10px] bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded font-semibold text-slate-600">15 Days</button>
                                <button type="button" @click="form.warranty_days = 30" class="text-[10px] bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded font-semibold text-indigo-700">30 Days (Standard)</button>
                                <button type="button" @click="form.warranty_days = 90" class="text-[10px] bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded font-semibold text-slate-600">90 Days</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Initial Spare Parts (Optional) -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-800">4. Spare Parts Consumed (Optional)</h2>
                                <p class="text-[11px] text-slate-400">Parts linked to inventory will automatically be deducted from warehouse stock upon saving</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="addPartRow"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-bold transition-all"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>+ Add Spare Part</span>
                        </button>
                    </div>

                    <div v-if="form.parts.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold uppercase text-[10px]">
                                    <th class="py-2 px-3">Inventory Product (Optional)</th>
                                    <th class="py-2 px-3">Part Description</th>
                                    <th class="py-2 px-3">Deduct From Hub</th>
                                    <th class="py-2 px-3 text-center w-20">Qty</th>
                                    <th class="py-2 px-3 text-right w-28">Price (৳)</th>
                                    <th class="py-2 px-3 text-right w-28">Subtotal</th>
                                    <th class="py-2 px-3 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(part, idx) in form.parts" :key="idx" class="hover:bg-slate-50/50">
                                    <td class="py-2 px-3">
                                        <select
                                            v-model="part.product_id"
                                            @change="onProductSelected(idx)"
                                            class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">-- Custom Part / Non-catalog --</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">
                                                {{ p.name }} (৳{{ p.unit_price }})
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-3">
                                        <input
                                            v-model="part.part_name"
                                            type="text"
                                            placeholder="Part name or component..."
                                            class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-medium"
                                            required
                                        />
                                    </td>
                                    <td class="py-2 px-3">
                                        <select
                                            v-model="part.warehouse_id"
                                            :disabled="!part.product_id"
                                            class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-400"
                                        >
                                            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-3">
                                        <input
                                            v-model="part.quantity"
                                            type="number"
                                            min="1"
                                            class="w-full text-xs text-center rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                                            required
                                        />
                                    </td>
                                    <td class="py-2 px-3">
                                        <input
                                            v-model="part.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full text-xs text-right rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                                            required
                                        />
                                    </td>
                                    <td class="py-2 px-3 text-right font-bold text-slate-800">
                                        ৳{{ ((parseFloat(part.quantity) || 0) * (parseFloat(part.unit_price) || 0)).toFixed(2) }}
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="removePartRow(idx)"
                                            class="text-slate-400 hover:text-rose-600 font-bold text-base leading-none"
                                        >
                                            &times;
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

                <!-- Section 5: Billing & Advance Payment Breakdown -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">5. Service Billing & Advance Collection</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Labor & Service Charge (৳) <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.service_charge"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="500.00"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Amount (৳)</label>
                                <input
                                    v-model="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Advance Payment Received Now (৳)</label>
                                <input
                                    v-model="form.paid_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="grandTotal"
                                    placeholder="0.00"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold text-emerald-700"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Internal Remarks</label>
                                <input
                                    v-model="form.remarks"
                                    type="text"
                                    placeholder="Optional note for technicians..."
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- Financial Summary Box -->
                        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-5 space-y-3">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Summary</div>
                            
                            <div class="flex justify-between text-xs text-slate-600">
                                <span>Labor & Diagnosis Fee:</span>
                                <span class="font-bold">৳{{ (parseFloat(form.service_charge) || 0).toFixed(2) }}</span>
                            </div>

                            <div class="flex justify-between text-xs text-slate-600">
                                <span>Spare Parts Total:</span>
                                <span class="font-bold">৳{{ partsTotal.toFixed(2) }}</span>
                            </div>

                            <div v-if="parseFloat(form.discount_amount) > 0" class="flex justify-between text-xs text-rose-600">
                                <span>Discount:</span>
                                <span class="font-bold">- ৳{{ (parseFloat(form.discount_amount) || 0).toFixed(2) }}</span>
                            </div>

                            <div class="border-t border-slate-200 pt-2 flex justify-between text-sm font-extrabold text-slate-800">
                                <span>Grand Total Billed:</span>
                                <span class="text-indigo-600">৳{{ grandTotal.toFixed(2) }}</span>
                            </div>

                            <div class="flex justify-between text-xs text-emerald-700 font-bold">
                                <span>Paid Advance:</span>
                                <span>৳{{ (parseFloat(form.paid_amount) || 0).toFixed(2) }}</span>
                            </div>

                            <div class="border-t border-slate-200 pt-2 flex justify-between text-xs font-bold text-rose-600">
                                <span>Estimated Due Balance:</span>
                                <span>৳{{ remainingDue.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('services.index')"
                        class="px-5 py-2.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02] disabled:opacity-50"
                    >
                        Create Service Ticket & Print Token
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
