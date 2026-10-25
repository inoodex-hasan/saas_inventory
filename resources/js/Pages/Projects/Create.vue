<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    page_title: String,
    nextProjectCode: String,
    clients: Array,
    costCategories: Array,
    products: Array,
    warehouses: Array,
    todayDate: String,
});

const form = useForm({
    client_mode: 'existing',
    client_id: '',
    new_client_name: '',
    new_client_company: '',
    new_client_phone: '',
    new_client_email: '',
    new_client_address: '',
    new_client_tax_number: '',
    name: '',
    status: 'planning',
    budget: '',
    discount_amount: 0,
    tax_amount: 0,
    paid_amount: 0,
    start_date: props.todayDate,
    end_date: '',
    description: '',
    items: [],
    costs: [],
});

function addItem() {
    form.items.push({
        product_id: '',
        warehouse_id: props.warehouses.length > 0 ? props.warehouses[0].id : '',
        item_name: '',
        quantity: 1,
        unit_price: 0,
        notes: '',
    });
}

function onProductSelected(index) {
    const item = form.items[index];
    if (!item.product_id) return;
    const prod = props.products.find(p => p.id === parseInt(item.product_id));
    if (prod) {
        item.item_name = prod.name;
        item.unit_price = prod.price || 0;
    }
}

function removeItem(index) {
    form.items.splice(index, 1);
}

function addCost() {
    form.costs.push({
        cost_category_id: props.costCategories.length > 0 ? props.costCategories[0].id : '',
        description: '',
        amount: '',
        cost_date: props.todayDate,
        vendor_name: '',
        receipt_reference: '',
        notes: '',
    });
}

function removeCost(index) {
    form.costs.splice(index, 1);
}

const itemsTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0));
    }, 0);
});

const directCostsTotal = computed(() => {
    return form.costs.reduce((sum, cost) => {
        return sum + (parseFloat(cost.amount) || 0);
    }, 0);
});

const computedBilledTotal = computed(() => {
    const budget = parseFloat(form.budget) || 0;
    const base = (itemsTotal.value > 0) ? (itemsTotal.value + directCostsTotal.value) : budget;
    const discount = parseFloat(form.discount_amount) || 0;
    const tax = parseFloat(form.tax_amount) || 0;
    return Math.max(0, base - discount + tax);
});

const computedDueAmount = computed(() => {
    const paid = parseFloat(form.paid_amount) || 0;
    return Math.max(0, computedBilledTotal.value - paid);
});

function setFullPayment() {
    form.paid_amount = computedBilledTotal.value;
}

function submit() {
    form.post(route('projects.store'));
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('projects.index')" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Create New Project</h1>
                        <p class="text-xs text-slate-500 font-medium">Initiate contract, assign materials, estimate direct expenses, and configure client billing</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Project Code</span>
                    <span class="font-mono text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-800">
                        #{{ nextProjectCode }}
                    </span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Client Selection Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Client / Customer Account</h2>
                            <p class="text-xs text-slate-500">Select an existing registered client or create a new one on the fly</p>
                        </div>
                        <div class="flex items-center bg-slate-100 p-0.5 rounded-lg text-xs font-semibold">
                            <button
                                type="button"
                                @click="form.client_mode = 'existing'"
                                :class="form.client_mode === 'existing' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                                class="px-3 py-1 rounded-md transition"
                            >
                                Existing Client
                            </button>
                            <button
                                type="button"
                                @click="form.client_mode = 'new'"
                                :class="form.client_mode === 'new' ? 'bg-white shadow text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                                class="px-3 py-1 rounded-md transition"
                            >
                                + New Client
                            </button>
                        </div>
                    </div>

                    <!-- Existing Client Select -->
                    <div v-if="form.client_mode === 'existing'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Select Registered Client <span class="text-rose-500">*</span></label>
                        <select
                            v-model="form.client_id"
                            required
                            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">-- Choose Client --</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.company_name ? `(${c.company_name})` : '' }} - {{ c.phone }}
                            </option>
                        </select>
                        <div v-if="form.errors.client_id" class="text-rose-500 text-xs mt-1">{{ form.errors.client_id }}</div>
                    </div>

                    <!-- New Client Inline Fields -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Name <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.new_client_name"
                                type="text"
                                required
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <div v-if="form.errors.new_client_name" class="text-rose-500 text-xs mt-1">{{ form.errors.new_client_name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Company / Organization</label>
                            <input
                                v-model="form.new_client_company"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="form.new_client_phone"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input
                                v-model="form.new_client_email"
                                type="email"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tax / VAT ID</label>
                            <input
                                v-model="form.new_client_tax_number"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Physical Address</label>
                            <input
                                v-model="form.new_client_address"
                                type="text"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                        </div>
                    </div>
                </div>

                <!-- Project Details Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Project Overview & Timeline</h2>
                        <p class="text-xs text-slate-500">Contract deliverables, schedule dates, agreed budget, and status</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Project Title / Contract Name <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
class="w-full text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                            <select
                                v-model="form.status"
                                class="w-full text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="planning">Planning</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contract Agreed Budget</label>
                            <input
                                v-model.number="form.budget"
                                type="number"
                                step="0.01"
class="w-full text-xs font-mono font-bold px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <span class="text-[11px] text-slate-400 mt-1 block">Fixed contract sum (if not billing by items)</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Start Date</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Expected Completion</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Description & Scope of Work</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Bill of Materials (BOM) & Inventory Allocation -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Bill of Materials (BOM) & Inventory Allocation</h2>
                            <p class="text-xs text-slate-500">Assigned stock items will automatically be deducted from warehouse inventory upon creation</p>
                        </div>
                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Material</span>
                        </button>
                    </div>

                    <div v-if="form.items.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
                        No initial materials allocated. You can also issue materials anytime from the project workbench.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                                    <th class="py-2.5 px-3">Inventory Product (Optional)</th>
                                    <th class="py-2.5 px-3">Material Name</th>
                                    <th class="py-2.5 px-3">Warehouse / Hub</th>
                                    <th class="py-2.5 px-3 text-center w-24">Qty</th>
                                    <th class="py-2.5 px-3 text-right w-32">Unit Price</th>
                                    <th class="py-2.5 px-3 text-right w-32">Subtotal</th>
                                    <th class="py-2.5 px-3 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3">
                                        <select
                                            v-model="item.product_id"
                                            @change="onProductSelected(index)"
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        >
                                            <option value="">Custom / Direct Item</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">
                                                {{ p.name }} (Stock: {{ p.current_stock || 0 }})
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model="item.item_name"
                                            type="text"
                                            required
class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 font-medium"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <select
                                            v-model="item.warehouse_id"
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        >
                                            <option value="">None</option>
                                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                                {{ w.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            required
                                            class="w-full text-xs text-center font-mono font-bold px-2 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="w-full text-xs text-right font-mono font-bold px-2 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">
                                        <CurrencyDisplay :amount="(item.quantity || 0) * (item.unit_price || 0)" />
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                            title="Remove item"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="text-right text-xs font-bold text-slate-700 pt-3">
                            Materials Subtotal: <span class="text-slate-900"><CurrencyDisplay :amount="itemsTotal" /></span>
                        </div>
                    </div>
                </div>

                <!-- Direct Project Expenses & Subcontractors -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Direct Expenses & Subcontractor Costs</h2>
                            <p class="text-xs text-slate-500">Log direct non-inventory overheads, labor wages, equipment rent, or external vendors</p>
                        </div>
                        <button
                            type="button"
                            @click="addCost"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl text-xs font-bold transition"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Expense</span>
                        </button>
                    </div>

                    <div v-if="form.costs.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
                        No direct expenses logged yet.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                                    <th class="py-2.5 px-3 w-56">Category</th>
                                    <th class="py-2.5 px-3">Description</th>
                                    <th class="py-2.5 px-3 w-48">Vendor / Payee</th>
                                    <th class="py-2.5 px-3 text-right w-36">Amount</th>
                                    <th class="py-2.5 px-3 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(cost, index) in form.costs" :key="index" class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3">
                                        <select
                                            v-model="cost.cost_category_id"
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        >
                                            <option value="">General Expense</option>
                                            <option v-for="cat in costCategories" :key="cat.id" :value="cat.id">
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model="cost.description"
                                            type="text"
                                            required
class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-medium placeholder:text-slate-400 placeholder:text-xs"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model="cost.vendor_name"
                                            type="text"
class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input
                                            v-model.number="cost.amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            required
class="w-full text-xs text-right font-mono font-bold px-2 py-1.5 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button
                                            type="button"
                                            @click="removeCost(index)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                            title="Remove cost"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="text-right text-xs font-bold text-amber-700 pt-3">
                            Direct Costs Subtotal: <CurrencyDisplay :amount="directCostsTotal" />
                        </div>
                    </div>
                </div>

                <!-- Billing & Initial Payment -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Billing Calculation & Initial Payment</h2>
                        <p class="text-xs text-slate-500">Configure contract pricing adjustments, taxes, and record advance collection</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Inputs Left (7 cols) -->
                        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Amount</label>
                                <input
                                    v-model.number="form.discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
class="w-full text-xs font-mono px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tax / VAT Amount</label>
                                <input
                                    v-model.number="form.tax_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
class="w-full text-xs font-mono px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-700">Advance Payment Received</label>
                                    <button
                                        type="button"
                                        @click="setFullPayment"
                                        class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Pay Full (100%)
                                    </button>
                                </div>
                                <input
                                    v-model.number="form.paid_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="computedBilledTotal"
class="w-full text-xs font-mono font-bold text-emerald-600 px-3 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>
                        </div>

                        <!-- Summary Breakdown Right (5 cols) -->
                        <div class="lg:col-span-5 bg-slate-50 border border-slate-200/80 rounded-2xl p-5 space-y-3">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Claim Summary</div>

                            <div class="flex justify-between text-xs text-slate-600">
                                <span>{{ itemsTotal > 0 ? 'Materials & Direct Costs Base:' : 'Agreed Budget Base:' }}</span>
                                <span class="font-bold text-slate-800">
                                    <CurrencyDisplay :amount="itemsTotal > 0 ? (itemsTotal + directCostsTotal) : (parseFloat(form.budget) || 0)" />
                                </span>
                            </div>

                            <div v-if="parseFloat(form.discount_amount) > 0" class="flex justify-between text-xs text-rose-600">
                                <span>Discount:</span>
                                <span class="font-bold">- <CurrencyDisplay :amount="form.discount_amount || 0" /></span>
                            </div>

                            <div v-if="parseFloat(form.tax_amount) > 0" class="flex justify-between text-xs text-slate-600">
                                <span>Tax / VAT:</span>
                                <span class="font-bold">+ <CurrencyDisplay :amount="form.tax_amount || 0" /></span>
                            </div>

                            <div class="border-t border-slate-200 pt-2.5 flex justify-between text-sm font-extrabold text-slate-900">
                                <span>Total Billed Claim:</span>
                                <span class="text-indigo-600"><CurrencyDisplay :amount="computedBilledTotal" /></span>
                            </div>

                            <div class="flex justify-between text-xs text-emerald-700 font-bold">
                                <span>Paid Advance:</span>
                                <span><CurrencyDisplay :amount="form.paid_amount || 0" /></span>
                            </div>

                            <div class="border-t border-slate-200 pt-2.5 flex justify-between text-xs font-bold" :class="computedDueAmount > 0 ? 'text-rose-600' : 'text-emerald-700'">
                                <span>Estimated Due Balance:</span>
                                <span><CurrencyDisplay :amount="computedDueAmount" /></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('projects.index')"
                        class="px-5 py-2.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition disabled:opacity-50"
                    >
                        Create Project & Initialize
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
