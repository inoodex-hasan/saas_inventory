<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    status: String,
    sessions: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// -------------------------------------------------------------
// Form 1: Profile Information
// -------------------------------------------------------------
const profileForm = useForm({
    name: user.value.name || '',
    email: user.value.email || '',
});

const profileSaved = ref(false);

function submitProfile() {
    profileForm.put(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            profileSaved.value = true;
            setTimeout(() => {
                profileSaved.value = false;
            }, 3000);
        },
    });
}

function getProfileError(field) {
    return profileForm.errors[field]
        || page.props.errors?.updateProfileInformation?.[field]
        || page.props.errors?.[field];
}

// -------------------------------------------------------------
// Form 2: Update Password
// -------------------------------------------------------------
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const passwordSaved = ref(false);

function submitPassword() {
    passwordForm.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            passwordSaved.value = true;
            setTimeout(() => {
                passwordSaved.value = false;
            }, 3000);
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
}

function getPasswordError(field) {
    return passwordForm.errors[field]
        || page.props.errors?.updatePassword?.[field]
        || page.props.errors?.[field];
}
</script>

<template>
    <AppLayout>
        <Head title="Account Profile Settings" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Account Profile Settings</h1>
                        <span 
                            v-if="user.is_super_admin" 
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200"
                        >
                            Platform Super-Admin
                        </span>
                        <span 
                            v-else-if="page.props.auth?.team"
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200"
                        >
                            {{ page.props.auth.team.name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Update your personal identity credentials, platform email, and secure access passwords
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-10 py-2">
            <!-- SECTION 1: Profile Information -->
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <!-- Left Column: Title & Description -->
                <div class="md:col-span-1">
                    <h2 class="text-base font-bold text-slate-900">Profile Information</h2>
                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                        Update your account's profile information and email address.
                    </p>
                </div>

                <!-- Right Column: Card & Form -->
                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form @submit.prevent="submitProfile" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="p-6 space-y-5">
                            <!-- Name -->
                            <div>
                                <label for="profile_name" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Name
                                </label>
                                <input
                                    id="profile_name"
                                    type="text"
                                    v-model="profileForm.name"
                                    required
                                    autocomplete="name"
                                    class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                />
                                <div v-if="getProfileError('name')" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ getProfileError('name') }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="profile_email" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Email
                                </label>
                                <input
                                    id="profile_email"
                                    type="email"
                                    v-model="profileForm.email"
                                    required
                                    autocomplete="username"
                                    class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                />
                                <div v-if="getProfileError('email')" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ getProfileError('email') }}
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-3">
                            <span v-if="profileSaved" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Saved.</span>
                            </span>

                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="inline-flex items-center justify-center px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-sm disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="profileForm.processing">Saving...</span>
                                <span v-else>Save</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-slate-200/80"></div>

            <!-- SECTION 2: Update Password -->
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <!-- Left Column: Title & Description -->
                <div class="md:col-span-1">
                    <h2 class="text-base font-bold text-slate-900">Update Password</h2>
                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                        Ensure your account is using a long, random password to stay secure.
                    </p>
                </div>

                <!-- Right Column: Card & Form -->
                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form @submit.prevent="submitPassword" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="p-6 space-y-5">
                            <!-- Current Password -->
                            <div>
                                <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Current Password
                                </label>
                                <input
                                    id="current_password"
                                    type="password"
                                    v-model="passwordForm.current_password"
                                    autocomplete="current-password"
                                    class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                />
                                <div v-if="getPasswordError('current_password')" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ getPasswordError('current_password') }}
                                </div>
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    New Password
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    v-model="passwordForm.password"
                                    autocomplete="new-password"
                                    class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                />
                                <div v-if="getPasswordError('password')" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ getPasswordError('password') }}
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Confirm Password
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    v-model="passwordForm.password_confirmation"
                                    autocomplete="new-password"
                                    class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
                                />
                                <div v-if="getPasswordError('password_confirmation')" class="mt-1 text-xs text-rose-600 font-medium">
                                    {{ getPasswordError('password_confirmation') }}
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-3">
                            <span v-if="passwordSaved" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Saved.</span>
                            </span>

                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="inline-flex items-center justify-center px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-sm disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="passwordForm.processing">Saving...</span>
                                <span v-else>Save</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
