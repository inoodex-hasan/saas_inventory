<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    page_title: String,
    employees: Object,
    metrics: Object,
    departments: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const department = ref(props.filters.department || '');

// Employee Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const activeEmployeeId = ref(null);

const form = useForm({
    name: '',
    employee_code: '',
    designation: '',
    department: '',
    phone: '',
    email: '',
    joining_date: new Date().toISOString().slice(0, 10),
    basic_salary: 0,
    bank_name: '',
    bank_account_no: '',
    mobile_banking_no: '',
    status: 'active',
    notes: '',
});

function openCreateModal() {
    isEditing.value = false;
    activeEmployeeId.value = null;
    form.reset();
    form.status = 'active';
    form.joining_date = new Date().toISOString().slice(0, 10);
    isModalOpen.value = true;
}

function openEditModal(emp) {
    isEditing.value = true;
    activeEmployeeId.value = emp.id;
    form.name = emp.name;
    form.employee_code = emp.employee_code;
    form.designation = emp.designation;
    form.department = emp.department || '';
    form.phone = emp.phone || '';
    form.email = emp.email || '';
    form.joining_date = emp.joining_date ? emp.joining_date.slice(0, 10) : '';
    form.basic_salary = emp.basic_salary;
    form.bank_name = emp.bank_name || '';
    form.bank_account_no = emp.bank_account_no || '';
    form.mobile_banking_no = emp.mobile_banking_no || '';
    form.status = emp.status;
    form.notes = emp.notes || '';
    isModalOpen.value = true;
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('employees.update', activeEmployeeId.value), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('employees.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
}

function deleteEmployee(emp) {
    if (confirm(`Are you sure you want to remove employee "${emp.name}"?`)) {
        router.delete(route('employees.destroy', emp.id));
    }
}

function applyFilters() {
    router.get(route('employees.index'), {
        search: search.value,
        status: status.value,
        department: department.value,
    }, { preserveState: true, replace: true });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    department.value = '';
    applyFilters();
}
</script>

<template>
    <AppLayout>
        <Head :title="page_title" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>👥</span>
                        <span>{{ page_title }}</span>
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Manage staff profiles, designations, salary baselines, and bank credentials</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add New Staff</span>
                </button>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Headcount</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">{{ metrics.total_employees }} Staff</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Across all departments</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Staff</div>
                    <div class="text-xl font-extrabold text-emerald-600 mt-1">{{ metrics.active_employees }} Active</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Currently on payroll</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Monthly Base Payroll</div>
                    <div class="text-xl font-extrabold text-indigo-600 mt-1">
                        <CurrencyDisplay :amount="metrics.total_payroll_cost" />
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Estimated gross commitment</div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                    <div class="relative w-full md:w-64">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search name, code, designation, phone..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select
                        v-model="department"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[140px]"
                    >
                        <option value="">All Departments</option>
                        <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                    </select>

                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 min-w-[130px]"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="on_leave">On Leave</option>
                        <option value="terminated">Terminated</option>
                    </select>

                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition"
                    >
                        Filter
                    </button>

                    <button
                        v-if="search || status || department"
                        @click="resetFilters"
                        class="px-3 py-2 text-slate-500 hover:text-slate-800 text-xs font-semibold transition"
                    >
                        Reset
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto shrink-0">
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs shadow-indigo-600/30 transition-all whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add New Staff</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-4">Staff Member</th>
                                <th class="py-3 px-4">Role & Dept</th>
                                <th class="py-3 px-4">Contact</th>
                                <th class="py-3 px-4">Joining Date</th>
                                <th class="py-3 px-4 text-right">Basic Salary</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ emp.name }}</div>
                                    <div class="font-mono text-[11px] text-indigo-600 font-semibold">
                                        #{{ emp.employee_code }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800">{{ emp.designation }}</div>
                                    <div v-if="emp.department" class="text-[11px] text-slate-400">{{ emp.department }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div>{{ emp.phone || '-' }}</div>
                                    <div v-if="emp.email" class="text-[11px] text-slate-400">{{ emp.email }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ emp.joining_date ? new Date(emp.joining_date).toLocaleDateString() : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900">
                                    <CurrencyDisplay :amount="emp.basic_salary" />
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <StatusBadge :status="emp.status" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(emp)"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition"
                                            title="Edit Profile"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteEmployee(emp)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="employees.data.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    No staff members registered. Click "+ Add New Staff" to add team members.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create / Edit Employee Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        {{ isEditing ? 'Edit Staff Profile' : 'Add New Employee' }}
                    </h3>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Designation / Role *</label>
                            <input
                                v-model="form.designation"
                                type="text"
                                required
                                placeholder="e.g. Senior Network Engineer"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Department</label>
                            <input
                                v-model="form.department"
                                type="text"
                                placeholder="e.g. Technical Operations"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Basic Salary *</label>
                            <input
                                v-model.number="form.basic_salary"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full rounded-xl border border-slate-200 py-2 px-3 font-bold"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Joining Date</label>
                            <input
                                v-model="form.joining_date"
                                type="date"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select
                                v-model="form.status"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            >
                                <option value="active">Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bank Name</label>
                            <input
                                v-model="form.bank_name"
                                type="text"
                                placeholder="e.g. City Bank Ltd"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bank Account Number</label>
                            <input
                                v-model="form.bank_account_no"
                                type="text"
                                placeholder="Account #"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block font-semibold text-slate-700 mb-1">Mobile Banking (bKash / Nagad)</label>
                            <input
                                v-model="form.mobile_banking_no"
                                type="text"
                                placeholder="Mobile wallet number"
                                class="w-full rounded-xl border border-slate-200 py-2 px-3"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-sm transition disabled:opacity-50"
                        >
                            {{ isEditing ? 'Save Profile' : 'Register Employee' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
