<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    page_title: String,
    ticket: Object,
    technicians: Array,
});

const form = useForm({
    device_name: props.ticket.device_name || '',
    device_brand: props.ticket.device_brand || '',
    serial_no: props.ticket.serial_no || '',
    accessories_received: props.ticket.accessories_received || '',
    problem_description: props.ticket.problem_description || '',
    initial_diagnosis: props.ticket.initial_diagnosis || '',
    technician_id: props.ticket.technician_id || '',
    priority: props.ticket.priority || 'normal',
    service_charge: props.ticket.service_charge || 0,
    discount_amount: props.ticket.discount_amount || 0,
    warranty_days: props.ticket.warranty_days || 0,
    estimated_completion_date: props.ticket.estimated_completion_date || '',
    remarks: props.ticket.remarks || '',
});

function submitForm() {
    form.put(route('services.update', props.ticket.id));
}
</script>

<template>
    <AppLayout :title="page_title">
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('services.show', ticket.id)" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                            &larr; Back to Ticket Details
                        </Link>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight mt-1">
                        Edit Service Ticket #{{ ticket.ticket_number }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Update equipment information, technician assignment, and charges</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submitForm" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                <!-- Equipment Profile -->
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Equipment Profile</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Device Name <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.device_name"
                                type="text"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Brand</label>
                            <input
                                v-model="form.device_brand"
                                type="text"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Serial Number / IMEI</label>
                            <input
                                v-model="form.serial_no"
                                type="text"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Accessories Received</label>
                            <input
                                v-model="form.accessories_received"
                                type="text"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Problem & Diagnosis -->
                <div class="border-t border-slate-100 pt-5">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Problem & Diagnosis</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Reported Problem</label>
                            <textarea
                                v-model="form.problem_description"
                                rows="3"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Technician Diagnosis</label>
                            <textarea
                                v-model="form.initial_diagnosis"
                                rows="3"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Logistics & Assignment -->
                <div class="border-t border-slate-100 pt-5">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Assignment & Priority</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Assigned Technician</label>
                            <select
                                v-model="form.technician_id"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">-- Unassigned --</option>
                                <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Priority</label>
                            <select
                                v-model="form.priority"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Warranty Days</label>
                            <input
                                v-model="form.warranty_days"
                                type="number"
                                min="0"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            />
                        </div>
                    </div>
                </div>

                <!-- Charges -->
                <div class="border-t border-slate-100 pt-5">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Service Charges & Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Labor / Service Charge (৳)</label>
                            <input
                                v-model="form.service_charge"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Amount (৳)</label>
                            <input
                                v-model="form.discount_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
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

                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                        <input
                            v-model="form.remarks"
                            type="text"
class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <Link
                        :href="route('services.show', ticket.id)"
                        class="px-5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02] disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
