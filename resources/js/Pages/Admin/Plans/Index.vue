<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import ActionDropdown from '@/Components/ActionDropdown.vue';

const props = defineProps({
    page_title: String,
    plans: Array,
    metrics: Object,
});

const isModalOpen = ref(false);
const editingPlan = ref(null);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    price_bdt: 0,
    annual_price_bdt: 0,
    max_products: 500,
    max_warehouses: 1,
    max_users: 3,
    is_active: true,
    sort_order: 1,
});

function openCreateModal() {
    editingPlan.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.max_products = 500;
    form.max_warehouses = 1;
    form.max_users = 3;
    isModalOpen.value = true;
}

function openEditModal(plan) {
    editingPlan.value = plan;
    form.name = plan.name;
    form.slug = plan.slug;
    form.description = plan.description || '';
    form.price_bdt = plan.price_bdt;
    form.annual_price_bdt = plan.annual_price_bdt;
    form.max_products = plan.max_products;
    form.max_warehouses = plan.max_warehouses;
    form.max_users = plan.max_users;
    form.is_active = Boolean(plan.is_active);
    form.sort_order = plan.sort_order;
    form.clearErrors();
    isModalOpen.value = true;
}

function submit() {
    if (editingPlan.value) {
        form.put(route('admin.plans.update', editingPlan.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                editingPlan.value = null;
            },
        });
    } else {
        form.post(route('admin.plans.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function deletePlan(plan) {
    if (confirm(`Are you sure you want to delete plan '${plan.name}'?`)) {
        router.delete(route('admin.plans.destroy', plan.id));
    }
}
</script>

<template>
    <AppLayout title="Subscription Plans Management">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">SaaS Subscription Plans Manager</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Control dynamic Bangladeshi Taka (৳) subscription tiers, quotas, and packaging</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Plan Tiers</span>
                    <div class="text-2xl font-black text-slate-900 mt-2">
                        {{ metrics?.total_plans || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Public Tiers</span>
                    <div class="text-2xl font-black text-emerald-600 mt-2">
                        {{ metrics?.active_plans || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Tenant Subscriptions</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2">
                        {{ metrics?.total_subscribers || 0 }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Top Tier Package</span>
                    <div class="text-2xl font-black text-amber-600 mt-2">
                        {{ metrics?.top_tier || 'Enterprise' }}
                    </div>
                </div>
            </div>

            <!-- Action Toolbar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Available Subscription Packages</h3>
                    <p class="text-xs text-slate-500">Configure feature limits, quotas, and monthly/annual pricing</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all hover:scale-[1.02] shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Plan Tier</span>
                </button>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Plan Name</th>
                                <th class="py-3 px-4">Slug Identifier</th>
                                <th class="py-3 px-4">Monthly Rate</th>
                                <th class="py-3 px-4">Annual Rate</th>
                                <th class="py-3 px-4 text-center">Products Quota</th>
                                <th class="py-3 px-4 text-center">Warehouses</th>
                                <th class="py-3 px-4 text-center">User Seats</th>
                                <th class="py-3 px-4 text-center">Active Tenants</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="plan in plans"
                                :key="plan.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ plan.name }}</div>
                                    <div v-if="plan.description" class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{{ plan.description }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 font-semibold">{{ plan.slug }}</td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <CurrencyDisplay :amount="plan.price_bdt" />
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <CurrencyDisplay :amount="plan.annual_price_bdt" />
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-700">
                                    {{ plan.max_products === -1 ? 'Unlimited' : plan.max_products }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-700">
                                    {{ plan.max_warehouses === -1 ? 'Unlimited' : plan.max_warehouses }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-semibold text-slate-700">
                                    {{ plan.max_users === -1 ? 'Unlimited' : plan.max_users }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-indigo-600">
                                    {{ plan.subscriptions_count }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        v-if="plan.is_active"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700"
                                    >
                                        Active
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500"
                                    >
                                        Disabled
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <ActionDropdown align="right">
                                            <button
                                                @click="openEditModal(plan)"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50 font-medium transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Plan Tier</span>
                                            </button>
                                            <button
                                                v-if="plan.subscriptions_count === 0 && plan.slug !== 'trial'"
                                                @click="deletePlan(plan)"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50 font-semibold transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Delete Plan</span>
                                            </button>
                                        </ActionDropdown>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Plan Create/Edit Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="lg">
            <div class="p-6 md:p-8 space-y-4">
                <h3 class="text-base font-bold text-slate-900">
                    {{ editingPlan ? `Edit Plan: ${editingPlan.name}` : 'Create Subscription Plan' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Plan Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500 placeholder:text-slate-400 placeholder:text-xs"
                            required
                        />
                        <p v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div v-if="!editingPlan">
                        <label class="block font-semibold text-slate-700 mb-1">Slug Identifier (Optional)</label>
                        <input
                            v-model="form.slug"
                            type="text"
class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:bg-white focus:border-amber-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Monthly Rate (BDT ৳) *</label>
                            <input
                                v-model.number="form.price_bdt"
                                type="number"
                                step="1"
                                min="0"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Annual Rate (BDT ৳) *</label>
                            <input
                                v-model.number="form.annual_price_bdt"
                                type="number"
                                step="1"
                                min="0"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Max Products (-1 = ∞)</label>
                            <input
                                v-model.number="form.max_products"
                                type="number"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Max Hubs (-1 = ∞)</label>
                            <input
                                v-model.number="form.max_warehouses"
                                type="number"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Max Seats (-1 = ∞)</label>
                            <input
                                v-model.number="form.max_users"
                                type="number"
                                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500 placeholder:text-slate-400 placeholder:text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="plan_is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                        />
                        <label for="plan_is_active" class="font-semibold text-slate-700">Plan is Active & Available for Tenants</label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 font-medium text-slate-600 hover:text-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl transition-all shadow-sm"
                        >
                            {{ form.processing ? 'Saving...' : (editingPlan ? 'Update Plan Tier' : 'Create Plan Tier') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
