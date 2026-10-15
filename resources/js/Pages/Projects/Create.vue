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

function submit() {
    form.post(route('projects.store'));
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('projects.index')" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            &larr; Back to Projects
                        </Link>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight mt-1">Create New Project</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Initiate contract, assign materials, estimate direct expenses, and configure client billing</p>
                </div>

                <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-3.5 py-1.5 text-right">
                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider block">Assigned Code</span>
                    <span class="font-mono text-xs font-bold text-indigo-700">#{{ nextProjectCode }}</span>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- 1. Client Selection Card -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span>👤</span>
                            <span>Client / Customer Account</span>
                        </h2>
                        <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                @click="form.client_mode = 'existing'"
                                class="px-3 py-1 rounded-lg transition"
                                :class="form.client_mode === 'existing' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Existing Client
                            </button>
                            <button
                                type="button"
                                @click="form.client_mode = 'new'"
                                class="px-3 py-1 rounded-lg transition"
                                :class="form.client_mode === 'new' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                + New Client
                            </button>
                        </div>
                    </div>

                    <!-- Existing Client Select -->
                    <div v-if="form.client_mode === 'existing'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Select Registered Client *</label>
                        <select
                            v-model="form.client_id"
                            required
                            class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">-- Choose Client --</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.company_name ? `(${c.company_name})` : '' }} - {{ c.phone }}
                            </option>
                        </select>
                        <div v-if="form.errors.client_id" class="text-rose-500 text-[11px] mt-1">{{ form.errors.client_id }}</div>
                    </div>

                    <!-- New Client Inline Fields -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Name *</label>
                            <input
                                v-model="form.new_client_name"
                                type="text"
                                required
                                placeholder="e.g. John Doe"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.new_client_name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.new_client_name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Company / Organization</label>
                            <input
                                v-model="form.new_client_company"
                                type="text"
                                placeholder="e.g. Acme Corp Ltd"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="form.new_client_phone"
                                type="text"
                                placeholder="e.g. +880 1700 000000"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input
                                v-model="form.new_client_email"
                                type="email"
                                placeholder="client@domain.com"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tax / VAT ID</label>
                            <input
                                v-model="form.new_client_tax_number"
                                type="text"
                                placeholder="BIN / TIN #"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Physical Address</label>
                            <input
                                v-model="form.new_client_address"
                                type="text"
                                placeholder="Office / Site address"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. Project Details Card -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span>📋</span>
                        <span>Project Overview & Timeline</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Project Title / Contract Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Enterprise Network Deployment & CCTV Installation"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                            <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                            <select
                                v-model="form.status"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
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
                                placeholder="0.00"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                            <span class="text-[10px] text-slate-400">Fixed contract sum (if not billing by items)</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Start Date</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Expected Completion</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Description & Scope of Work</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Key deliverables, milestones, technical requirements..."
                                class="w-full text-xs rounded-xl border border-slate-200 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- 3. Bill of Materials (BOM) & Inventory Allocation -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span>📦</span>
                                <span>Bill of Materials (BOM) & Inventory Allocation</span>
                            </h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Assigned stock items will be deducted from warehouse inventory upon creation</p>
                        </div>
                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-semibold transition"
                        >
                            + Add Material
                        </button>
                    </div>

                    <div v-if="form.items.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
                        No initial materials allocated. You can also issue materials anytime from the project workbench.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/60 grid grid-cols-1 md:grid-cols-12 gap-2 items-center"
                        >
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Inventory Catalog Product</label>
                                <select
                                    v-model="item.product_id"
                                    @change="onProductSelected(index)"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white"
                                >
                                    <option value="">Custom / Direct Item</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} (Stock: {{ p.current_stock || 0 }})
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Material Name *</label>
                                <input
                                    v-model="item.item_name"
                                    type="text"
                                    required
                                    placeholder="Item name"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Warehouse</label>
                                <select
                                    v-model="item.warehouse_id"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white"
                                >
                                    <option value="">None</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                        {{ w.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Qty</label>
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white text-center"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Unit Price</label>
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white text-right"
                                />
                            </div>

                            <div class="md:col-span-1 flex items-center justify-end pt-3">
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-1 text-slate-400 hover:text-rose-600 transition"
                                    title="Remove item"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="text-right text-xs font-bold text-slate-700 pt-1">
                            Materials Subtotal: <CurrencyDisplay :amount="itemsTotal" />
                        </div>
                    </div>
                </div>

                <!-- 4. Direct Project Expenses & Subcontractors -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span>🧾</span>
                                <span>Direct Expenses & Subcontractor Costs</span>
                            </h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Log direct non-inventory overheads, labor wages, equipment rent, or external vendors</p>
                        </div>
                        <button
                            type="button"
                            @click="addCost"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl text-xs font-semibold transition"
                        >
                            + Add Expense
                        </button>
                    </div>

                    <div v-if="form.costs.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
                        No direct expenses logged yet.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(cost, index) in form.costs"
                            :key="index"
                            class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/60 grid grid-cols-1 md:grid-cols-12 gap-2 items-center"
                        >
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Category</label>
                                <select
                                    v-model="cost.cost_category_id"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white"
                                >
                                    <option value="">General Expense</option>
                                    <option v-for="cat in costCategories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-4">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Description *</label>
                                <input
                                    v-model="cost.description"
                                    type="text"
                                    required
                                    placeholder="e.g. Scaffolding rental or electrician labor"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Vendor / Payee</label>
                                <input
                                    v-model="cost.vendor_name"
                                    type="text"
                                    placeholder="Subcontractor"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Amount *</label>
                                <input
                                    v-model.number="cost.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-xs rounded-lg border border-slate-200 py-1.5 px-2 bg-white text-right"
                                />
                            </div>

                            <div class="md:col-span-1 flex items-center justify-end pt-3">
                                <button
                                    type="button"
                                    @click="removeCost(index)"
                                    class="p-1 text-slate-400 hover:text-rose-600 transition"
                                    title="Remove cost"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="text-right text-xs font-bold text-amber-700 pt-1">
                            Direct Costs Subtotal: <CurrencyDisplay :amount="directCostsTotal" />
                        </div>
                    </div>
                </div>

                <!-- 5. Financial Summary & Advance Payment -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span>💰</span>
                        <span>Billing Calculation & Initial Payment</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Amount</label>
                            <input
                                v-model.number="form.discount_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tax / VAT Amount</label>
                            <input
                                v-model.number="form.tax_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Advance Payment Received</label>
                            <input
                                v-model.number="form.paid_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="computedBilledTotal"
                                placeholder="0.00"
                                class="w-full text-xs rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-bold text-emerald-600"
                            />
                        </div>

                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 flex flex-col justify-center text-right">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Billed Claim</span>
                            <span class="text-base font-extrabold text-indigo-700">
                                <CurrencyDisplay :amount="computedBilledTotal" />
                            </span>
                            <span class="text-[11px] font-semibold text-rose-600 mt-0.5">
                                Due: <CurrencyDisplay :amount="computedDueAmount" />
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('projects.index')"
                        class="px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50 transition"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.01] disabled:opacity-50"
                    >
                        Create Project & Initialize
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
