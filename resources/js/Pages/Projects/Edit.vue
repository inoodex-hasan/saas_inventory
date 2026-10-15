<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    page_title: String,
    project: Object,
    clients: Array,
});

const form = useForm({
    client_id: props.project.client_id || '',
    name: props.project.name || '',
    status: props.project.status || 'planning',
    budget: props.project.budget || 0,
    discount_amount: props.project.discount_amount || 0,
    tax_amount: props.project.tax_amount || 0,
    start_date: props.project.start_date ? props.project.start_date.slice(0, 10) : '',
    end_date: props.project.end_date ? props.project.end_date.slice(0, 10) : '',
    description: props.project.description || '',
});

function submit() {
    form.put(route('projects.update', props.project.id));
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('projects.show', project.id)" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            &larr; Back to Project Details
                        </Link>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight mt-1">Edit Project #{{ project.project_code }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Modify project parameters, contract budget, timeline, and customer assignment</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-5 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Client *</label>
                    <select
                        v-model="form.client_id"
                        required
                        class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    >
                        <option value="">-- Choose Client --</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">
                            {{ c.name }} {{ c.company_name ? `(${c.company_name})` : '' }}
                        </option>
                    </select>
                    <div v-if="form.errors.client_id" class="text-rose-500 text-[11px] mt-1">{{ form.errors.client_id }}</div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Project Name *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    />
                    <div v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status *</label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="planning">Planning</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Contract Budget *</label>
                        <input
                            v-model.number="form.budget"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Discount Amount</label>
                        <input
                            v-model.number="form.discount_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tax Amount</label>
                        <input
                            v-model.number="form.tax_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Start Date</label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Target Completion Date</label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="w-full rounded-xl border border-slate-200 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Description & Scope</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-xl border border-slate-200 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <Link
                        :href="route('projects.show', project.id)"
                        class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
