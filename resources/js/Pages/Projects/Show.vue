<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    page_title: String,
    project: Object,
    products: Array,
    warehouses: Array,
    costCategories: Array,
    analytics: Object,
});

// Modals state
const isAddItemModalOpen = ref(false);
const isAddCostModalOpen = ref(false);
const isAddBillModalOpen = ref(false);
const isPayModalOpen = ref(false);
const isStatusModalOpen = ref(false);

// 1. Add Item Form
const itemForm = useForm({
    product_id: '',
    warehouse_id: props.warehouses.length > 0 ? props.warehouses[0].id : '',
    item_name: '',
    quantity: 1,
    unit_price: 0,
    notes: '',
});

function onProductSelected() {
    if (!itemForm.product_id) return;
    const prod = props.products.find(p => p.id === parseInt(itemForm.product_id));
    if (prod) {
        itemForm.item_name = prod.name;
        itemForm.unit_price = prod.price || 0;
    }
}

function submitAddItem() {
    itemForm.post(route('projects.items.add', props.project.id), {
        onSuccess: () => {
            isAddItemModalOpen.value = false;
            itemForm.reset();
        },
    });
}

function removeItem(item) {
    if (confirm(`Remove "${item.item_name}" from project? Returned items will be restored to warehouse inventory.`)) {
        router.delete(route('projects.items.remove', [props.project.id, item.id]));
    }
}

// 2. Add Direct Cost Form
const costForm = useForm({
    cost_category_id: props.costCategories.length > 0 ? props.costCategories[0].id : '',
    description: '',
    amount: '',
    cost_date: new Date().toISOString().slice(0, 10),
    vendor_name: '',
    receipt_reference: '',
    notes: '',
});

function submitAddCost() {
    costForm.post(route('projects.costs.add', props.project.id), {
        onSuccess: () => {
            isAddCostModalOpen.value = false;
            costForm.reset();
        },
    });
}

function removeCost(cost) {
    if (confirm(`Remove direct expense "${cost.description}"?`)) {
        router.delete(route('projects.costs.remove', [props.project.id, cost.id]));
    }
}

// 3. Create Milestone Bill Form
const billForm = useForm({
    title: '',
    amount: '',
    due_date: '',
    notes: '',
});

function submitAddBill() {
    billForm.post(route('projects.bills.create', props.project.id), {
        onSuccess: () => {
            isAddBillModalOpen.value = false;
            billForm.reset();
        },
    });
}

// 4. Record Payment Form
const payForm = useForm({
    amount: props.project.due_amount,
    payment_method: 'cash',
    notes: '',
});

function submitPay() {
    payForm.post(route('projects.pay', props.project.id), {
        onSuccess: () => {
            isPayModalOpen.value = false;
            payForm.reset();
        },
    });
}

// 5. Status Transition Form
const statusForm = useForm({
    status: props.project.status,
});

function submitStatus() {
    statusForm.post(route('projects.transition', props.project.id), {
        onSuccess: () => {
            isStatusModalOpen.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('projects.index')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-lg">
                                #{{ project.project_code }}
                            </span>
                            <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ project.name }}</h2>
                            <StatusBadge :status="project.status" />
                            <StatusBadge :status="project.payment_status" />
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Client: <span class="font-semibold text-slate-700">{{ project.client?.name || 'Walk-in / Direct' }}</span>
                            <span v-if="project.client?.company_name" class="text-slate-400"> ({{ project.client.company_name }})</span>
                            <span v-if="project.start_date" class="ml-2 text-slate-400">| Timeline: {{ formatDate(project.start_date) }}</span>
                        </p>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="route('projects.pdf', project.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>PDF Statement</span>
                    </a>

                    <button
                        @click="isStatusModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Change Status</span>
                    </button>

                    <button
                        v-if="project.due_amount > 0"
                        @click="isPayModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs shadow-emerald-600/30 transition hover:scale-[1.02]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Collect Payment</span>
                    </button>

                    <Link
                        :href="route('projects.edit', project.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        <span>Edit Scope</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Financial Analytics & Profitability Banner -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Contract Budget</div>
                        <div class="text-xl font-extrabold text-slate-800 mt-1">
                            <CurrencyDisplay :amount="project.budget" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-[11px] font-semibold text-slate-500 mb-1">
                            <span>Cost Utilized</span>
                            <span>{{ analytics.budget_used_percent }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="analytics.budget_used_percent > 100 ? 'bg-rose-500' : 'bg-indigo-600'"
                                :style="{ width: `${Math.min(100, analytics.budget_used_percent)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Actual Costs Logged</div>
                        <div class="text-xl font-extrabold text-amber-600 mt-1">
                            <CurrencyDisplay :amount="analytics.actual_expenditure" />
                        </div>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-2 space-y-0.5">
                        <div>Materials (BOM): <CurrencyDisplay :amount="analytics.material_cost" /></div>
                        <div>Direct Expenses: <CurrencyDisplay :amount="analytics.direct_costs" /></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Gross Profit</div>
                        <div
                            class="text-xl font-extrabold mt-1"
                            :class="analytics.gross_profit >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            <CurrencyDisplay :amount="analytics.gross_profit" />
                        </div>
                    </div>
                    <div class="text-[11px] mt-2 flex items-center justify-between font-semibold">
                        <span class="text-slate-500">Margin:</span>
                        <span
                            class="px-2 py-0.5 rounded-md"
                            :class="analytics.profit_margin >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                        >
                            {{ analytics.profit_margin }}%
                        </span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Billed & Outstanding</div>
                        <div class="text-xl font-extrabold text-indigo-600 mt-1">
                            <CurrencyDisplay :amount="project.billed_total" />
                        </div>
                    </div>
                    <div class="text-[11px] mt-2 space-y-0.5">
                        <div class="text-emerald-600 font-semibold">Received: <CurrencyDisplay :amount="project.paid_amount" /></div>
                        <div class="text-rose-600 font-bold">Due: <CurrencyDisplay :amount="project.due_amount" /></div>
                    </div>
                </div>
            </div>

            <!-- Main Layout: 2 Columns (Workbench & Project Sidebar) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: BOM, Direct Costs, Milestones -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Bill of Materials & Inventory Issued -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">
                                    Bill of Materials & Inventory Consumed
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Deducted from warehouse stock automatically</p>
                            </div>
                            <button
                                @click="isAddItemModalOpen = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition"
                            >
                                + Issue Inventory
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] font-bold border-b border-slate-100">
                                        <th class="py-2.5 px-3">Item Name</th>
                                        <th class="py-2.5 px-3">Warehouse</th>
                                        <th class="py-2.5 px-3 text-center">Qty</th>
                                        <th class="py-2.5 px-3 text-right">Unit Price</th>
                                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                                        <th class="py-2.5 px-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="item in project.items" :key="item.id" class="hover:bg-slate-50/60">
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">
                                            {{ item.item_name }}
                                            <div v-if="item.notes" class="text-[10px] text-slate-400">{{ item.notes }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500">
                                            {{ item.warehouse?.name || 'Default' }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-medium">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <CurrencyDisplay :amount="item.unit_price" />
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-slate-900">
                                            <CurrencyDisplay :amount="item.subtotal" />
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <button
                                                @click="removeItem(item)"
                                                class="text-rose-500 hover:text-rose-700 text-xs transition"
                                                title="Return to inventory"
                                            >
                                                &times; Return
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="project.items.length === 0">
                                        <td colspan="6" class="py-6 text-center text-slate-400 italic">
                                            No materials issued to this project yet. Click "+ Issue Inventory" to add items from stock.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. Direct Expenses & Subcontractor Costs -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">
                                    Direct Expenses & Subcontractor Invoices
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Non-inventory expenses, equipment rentals, labor, permits</p>
                            </div>
                            <button
                                @click="isAddCostModalOpen = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold transition"
                            >
                                + Log Expense
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] font-bold border-b border-slate-100">
                                        <th class="py-2.5 px-3">Date</th>
                                        <th class="py-2.5 px-3">Category</th>
                                        <th class="py-2.5 px-3">Description</th>
                                        <th class="py-2.5 px-3">Vendor / Ref</th>
                                        <th class="py-2.5 px-3 text-right">Amount</th>
                                        <th class="py-2.5 px-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="cost in project.costs" :key="cost.id" class="hover:bg-slate-50/60">
                                        <td class="py-2.5 px-3 text-slate-500">
                                            {{ formatDate(cost.cost_date, '-') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-600 font-medium">
                                            {{ cost.category?.name || 'General' }}
                                        </td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">
                                            {{ cost.description }}
                                            <div v-if="cost.notes" class="text-[10px] text-slate-400">{{ cost.notes }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500">
                                            <span>{{ cost.vendor_name || '-' }}</span>
                                            <span v-if="cost.receipt_reference" class="text-slate-400 block text-[10px]">#{{ cost.receipt_reference }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-amber-700">
                                            <CurrencyDisplay :amount="cost.amount" />
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <button
                                                @click="removeCost(cost)"
                                                class="text-rose-500 hover:text-rose-700 text-xs transition"
                                            >
                                                &times; Remove
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="project.costs.length === 0">
                                        <td colspan="6" class="py-6 text-center text-slate-400 italic">
                                            No direct expenses recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. Milestone Bills & Payment Claims -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">
                                    Milestone Billing & Payment Claims
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Generate milestone invoices to submit to client</p>
                            </div>
                            <button
                                @click="isAddBillModalOpen = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold transition"
                            >
                                + New Claim
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px] font-bold border-b border-slate-100">
                                        <th class="py-2.5 px-3">Bill #</th>
                                        <th class="py-2.5 px-3">Title / Deliverable</th>
                                        <th class="py-2.5 px-3">Due Date</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3 text-right">Claim Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="bill in project.bills" :key="bill.id" class="hover:bg-slate-50/60">
                                        <td class="py-2.5 px-3 font-mono font-bold text-indigo-600">
                                            {{ bill.bill_number }}
                                        </td>
                                        <td class="py-2.5 px-3 font-semibold text-slate-800">
                                            {{ bill.title }}
                                            <div v-if="bill.notes" class="text-[10px] text-slate-400">{{ bill.notes }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500">
                                            {{ formatDate(bill.due_date, '-') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <StatusBadge :status="bill.status" />
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-slate-900">
                                            <CurrencyDisplay :amount="bill.amount" />
                                        </td>
                                    </tr>

                                    <tr v-if="project.bills.length === 0">
                                        <td colspan="5" class="py-6 text-center text-slate-400 italic">
                                            No milestone claims recorded.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right 1 Col: Client Profile & Scope Meta -->
                <div class="space-y-6">
                    <!-- Client Details Card -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3">
                            Client Profile
                        </h3>

                        <div v-if="project.client" class="space-y-3 text-xs">
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ project.client.name }}</div>
                                <div v-if="project.client.company_name" class="font-semibold text-slate-500">
                                    {{ project.client.company_name }}
                                </div>
                            </div>

                            <div class="space-y-2 text-slate-600">
                                <div v-if="project.client.phone" class="flex items-center justify-between">
                                    <span class="text-slate-400">Phone:</span>
                                    <span class="font-medium text-slate-800">{{ project.client.phone }}</span>
                                </div>
                                <div v-if="project.client.email" class="flex items-center justify-between">
                                    <span class="text-slate-400">Email:</span>
                                    <span class="font-medium text-slate-800">{{ project.client.email }}</span>
                                </div>
                                <div v-if="project.client.address" class="flex items-start justify-between gap-2">
                                    <span class="text-slate-400">Address:</span>
                                    <span class="font-medium text-slate-800 text-right">{{ project.client.address }}</span>
                                </div>
                                <div v-if="project.client.tax_number" class="flex items-center justify-between font-mono">
                                    <span class="text-slate-400">Tax ID:</span>
                                    <span class="font-medium text-slate-800">{{ project.client.tax_number }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-xs text-slate-400 italic">
                            Walk-in or direct counter project.
                        </div>
                    </div>

                    <!-- Scope & Project Info Card -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4 text-xs">
                        <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3">
                            Contract Specs
                        </h3>

                        <div class="space-y-2 text-slate-600">
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Created By:</span>
                                <span class="font-semibold text-slate-800">{{ project.creator?.name || 'Admin' }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Start Date:</span>
                                <span class="font-semibold text-slate-800">{{ formatDate(project.start_date, '-') }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Target Completion:</span>
                                <span class="font-semibold text-slate-800">{{ formatDate(project.end_date, '-') }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Discount:</span>
                                <span class="font-semibold text-slate-800"><CurrencyDisplay :amount="project.discount_amount" /></span>
                            </div>

                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-400">Tax / VAT:</span>
                                <span class="font-semibold text-slate-800"><CurrencyDisplay :amount="project.tax_amount" /></span>
                            </div>
                        </div>

                        <div v-if="project.description" class="pt-2">
                            <span class="text-slate-400 font-bold uppercase text-[10px] block mb-1">Description</span>
                            <p class="text-slate-600 leading-relaxed bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                {{ project.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Modal: Issue Inventory Item -->
        <Modal :show="isAddItemModalOpen" @close="isAddItemModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Issue Material to Project</h3>
                    <button @click="isAddItemModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitAddItem" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Product from Inventory</label>
                        <select
                            v-model="itemForm.product_id"
                            @change="onProductSelected"
                            class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        >
                            <option value="">Custom Item (No inventory deduction)</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }} (Available: {{ p.current_stock || 0 }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Item / Material Name *</label>
                        <input
                            v-model="itemForm.item_name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Warehouse</label>
                            <select
                                v-model="itemForm.warehouse_id"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            >
                                <option value="">None</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Quantity *</label>
                            <input
                                v-model.number="itemForm.quantity"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Billed Unit Price *</label>
                        <input
                            v-model.number="itemForm.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Notes / Specifications</label>
                        <textarea
                            v-model="itemForm.notes"
                            rows="2"
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddItemModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="itemForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Issue Item & Adjust Stock
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 2. Modal: Log Direct Cost -->
        <Modal :show="isAddCostModalOpen" @close="isAddCostModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Log Direct Project Expense</h3>
                    <button @click="isAddCostModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitAddCost" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Cost Category</label>
                        <select
                            v-model="costForm.cost_category_id"
                            class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        >
                            <option value="">General Expense</option>
                            <option v-for="cat in costCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Description *</label>
                        <input
                            v-model="costForm.description"
                            type="text"
                            required
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Amount *</label>
                            <input
                                v-model.number="costForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Date</label>
                            <input
                                v-model="costForm.cost_date"
                                type="date"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Vendor / Payee</label>
                            <input
                                v-model="costForm.vendor_name"
                                type="text"
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Receipt Reference</label>
                            <input
                                v-model="costForm.receipt_reference"
                                type="text"
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddCostModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="costForm.processing"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 3. Modal: Add Milestone Claim -->
        <Modal :show="isAddBillModalOpen" @close="isAddBillModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Create Milestone Billing Claim</h3>
                    <button @click="isAddBillModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitAddBill" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Milestone Deliverable Title *</label>
                        <input
                            v-model="billForm.title"
                            type="text"
                            required
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Claim Amount *</label>
                            <input
                                v-model.number="billForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Payment Due Date</label>
                            <input
                                v-model="billForm.due_date"
                                type="date"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Instructions / Notes</label>
                        <textarea
                            v-model="billForm.notes"
                            rows="2"
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddBillModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="billForm.processing"
                            class="px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Generate Claim
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 4. Modal: Collect Payment -->
        <Modal :show="isPayModalOpen" @close="isPayModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Record Client Payment</h3>
                    <button @click="isPayModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitPay" class="mt-4 space-y-4 text-xs">
                    <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 flex justify-between items-center">
                        <span class="text-indigo-700 font-medium">Outstanding Due:</span>
                        <span class="text-indigo-900 font-bold text-sm">
                            <CurrencyDisplay :amount="project.due_amount" />
                        </span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Amount Received *</label>
                        <input
                            v-model.number="payForm.amount"
                            type="number"
                            step="0.01"
                            :max="project.due_amount"
                            required
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 font-bold text-emerald-700"
                        />
                        <div v-if="payForm.errors.amount" class="text-rose-500 text-[11px] mt-1">{{ payForm.errors.amount }}</div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                        <select
                            v-model="payForm.payment_method"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3"
                        >
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online / Card</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Notes / Receipt Reference</label>
                        <textarea
                            v-model="payForm.notes"
                            rows="2"
class="w-full rounded-xl border border-slate-200 py-2 px-3"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isPayModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 5. Modal: Update Status -->
        <Modal :show="isStatusModalOpen" @close="isStatusModalOpen = false" max-width="sm">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Change Project Lifecycle Status</h3>
                    <button @click="isStatusModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitStatus" class="mt-4 space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Select Status</label>
                        <select
                            v-model="statusForm.status"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3"
                        >
                            <option value="planning">Planning</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isStatusModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="statusForm.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
