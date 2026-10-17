<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    team: Object,
    members: Array,
    availableRoles: Array,
    canManage: Boolean,
    currentUserId: Number,
});

// Search filter
const search = ref('');

const filteredMembers = computed(() => {
    if (!search.value.trim()) return props.members || [];
    const q = search.value.toLowerCase();
    return (props.members || []).filter(m => 
        m.name?.toLowerCase().includes(q) || 
        m.email?.toLowerCase().includes(q) || 
        m.role?.toLowerCase().includes(q)
    );
});

// Summary Counts
const totalCount = computed(() => props.members?.length || 0);
const adminCount = computed(() => (props.members || []).filter(m => m.role_key === 'admin' || m.is_owner).length);
const editorCount = computed(() => (props.members || []).filter(m => m.role_key === 'editor').length);

// Add Member Modal State
const isAddModalOpen = ref(false);
const addForm = useForm({
    name: '',
    email: '',
    role: 'editor',
});

function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    addForm.role = 'editor';
    isAddModalOpen.value = true;
}

function submitAddMember() {
    addForm.post(route('settings.team.members.add'), {
        preserveScroll: true,
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
}

// Edit Role Modal State
const isRoleModalOpen = ref(false);
const editingMember = ref(null);
const roleForm = useForm({
    role: 'editor',
});

function openRoleModal(member) {
    editingMember.value = member;
    roleForm.role = member.role_key || 'editor';
    roleForm.clearErrors();
    isRoleModalOpen.value = true;
}

function submitRoleUpdate() {
    if (!editingMember.value) return;
    roleForm.put(route('settings.team.members.update', editingMember.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isRoleModalOpen.value = false;
            editingMember.value = null;
        },
    });
}

// Remove Member
function removeMember(member) {
    if (confirm(`Are you sure you want to remove ${member.name} from ${props.team?.name}?`)) {
        router.delete(route('settings.team.members.remove', member.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppLayout title="Team & Roles">
        <Head title="Team & Roles" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Team Members & Access Roles</h1>
                    <p class="text-xs text-slate-500 font-medium">Manage organization members, assign module permissions, and manage collaborator seats</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Summary Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Members</div>
                    <div class="text-xl font-extrabold text-slate-800 mt-1">{{ totalCount }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Active team collaborators</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-xs">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Administrators</div>
                    <div class="text-xl font-extrabold text-indigo-700 mt-1">{{ adminCount }}</div>
                    <div class="text-[10px] text-indigo-600/70 mt-0.5">Full managerial access</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-xs">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Operational Staff</div>
                    <div class="text-xl font-extrabold text-emerald-700 mt-1">{{ editorCount }}</div>
                    <div class="text-[10px] text-emerald-600/70 mt-0.5">Order & inventory editors</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-xs">
                    <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Organization Owner</div>
                    <div class="text-base font-extrabold text-amber-800 mt-1 truncate">{{ team?.owner_name }}</div>
                    <div class="text-[10px] text-amber-600/70 mt-0.5 truncate">{{ team?.owner_email }}</div>
                </div>
            </div>

            <!-- Filter Toolbar & Action Button -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2 items-center justify-between">
                <div class="relative w-full md:w-80">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name, email, or role..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button
                        v-if="canManage"
                        @click="openAddModal"
                        class="px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Team Member</span>
                    </button>
                </div>
            </div>

            <!-- Team Members Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Member Name & Account</th>
                                <th class="py-3 px-4">Assigned Role</th>
                                <th class="py-3 px-4">Membership Since</th>
                                <th class="py-3 px-4 text-right" v-if="canManage">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="member in filteredMembers" :key="member.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
                                            :class="member.is_owner ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-100'"
                                        >
                                            {{ member.name ? member.name.substring(0, 1).toUpperCase() : 'U' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 flex items-center gap-1.5 truncate">
                                                <span>{{ member.name }}</span>
                                                <span v-if="member.id === currentUserId" class="px-1.5 py-0.5 text-[9px] rounded font-bold bg-slate-100 text-slate-600">You</span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono truncate">{{ member.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span v-if="member.is_owner" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                                        <svg class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Owner
                                    </span>
                                    <span v-else-if="member.role_key === 'admin'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                        Administrator
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        Editor / Staff
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                    {{ member.joined_at }}
                                </td>

                                <td class="py-3.5 px-4 text-right" v-if="canManage">
                                    <div v-if="!member.is_owner" class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openRoleModal(member)"
                                            class="px-2.5 py-1 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg text-xs font-semibold transition"
                                            title="Change role"
                                        >
                                            Change Role
                                        </button>
                                        <button
                                            @click="removeMember(member)"
                                            class="px-2.5 py-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg text-xs font-semibold transition"
                                            title="Remove member"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-[11px] italic">Permanent Owner</span>
                                </td>
                            </tr>

                            <tr v-if="filteredMembers.length === 0">
                                <td :colspan="canManage ? 4 : 3" class="py-8 text-center text-slate-400">
                                    No team members found matching your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Role Permissions Reference Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">Roles & Access Rights Matrix</h2>
                    <p class="text-xs text-slate-500">Overview of system privileges granted to each team member role</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="r in availableRoles" :key="r.key" class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 text-sm">{{ r.name }}</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                :class="r.key === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'"
                            >
                                {{ r.badge }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">{{ r.description }}</p>
                        <div class="space-y-1.5 pt-1">
                            <div v-for="(p, pIdx) in r.permissions" :key="pIdx" class="flex items-center gap-2 text-xs text-slate-700">
                                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ p }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        <Modal :show="isAddModalOpen" @close="isAddModalOpen = false" max-width="lg">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Add New Team Member</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Invite a staff member or colleague to collaborate in this workspace</p>
                    </div>
                    <button @click="isAddModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitAddMember" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email Address *</label>
                        <input
                            v-model="addForm.email"
                            type="email"
                            required
                            placeholder="e.g. colleague@company.com"
                            class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <p v-if="addForm.errors.email" class="text-rose-500 text-[11px] mt-1">{{ addForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Full Name (Optional)</label>
                        <input
                            v-model="addForm.name"
                            type="text"
                            placeholder="e.g. John Doe"
                            class="w-full text-xs px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <p v-if="addForm.errors.name" class="text-rose-500 text-[11px] mt-1">{{ addForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Assigned Role *</label>
                        <div class="space-y-2 mt-2">
                            <label
                                v-for="r in availableRoles"
                                :key="r.key"
                                class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                :class="addForm.role === r.key ? 'border-indigo-500 bg-indigo-50/40 ring-1 ring-indigo-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                            >
                                <input
                                    type="radio"
                                    name="role"
                                    :value="r.key"
                                    v-model="addForm.role"
                                    class="mt-0.5 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div>
                                    <div class="font-bold text-slate-900 text-xs">{{ r.name }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ r.description }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            {{ addForm.processing ? 'Adding...' : 'Confirm & Add Member' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Role Modal -->
        <Modal :show="isRoleModalOpen" @close="isRoleModalOpen = false" max-width="md">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Change Member Role</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Update access privileges for {{ editingMember?.name }}</p>
                    </div>
                    <button @click="isRoleModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg leading-none">&times;</button>
                </div>

                <form @submit.prevent="submitRoleUpdate" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Select Role *</label>
                        <div class="space-y-2 mt-2">
                            <label
                                v-for="r in availableRoles"
                                :key="r.key"
                                class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                :class="roleForm.role === r.key ? 'border-indigo-500 bg-indigo-50/40 ring-1 ring-indigo-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                            >
                                <input
                                    type="radio"
                                    name="edit_role"
                                    :value="r.key"
                                    v-model="roleForm.role"
                                    class="mt-0.5 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div>
                                    <div class="font-bold text-slate-900 text-xs">{{ r.name }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ r.description }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isRoleModalOpen = false"
                            class="px-4 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="roleForm.processing"
                            class="px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            {{ roleForm.processing ? 'Saving...' : 'Update Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
