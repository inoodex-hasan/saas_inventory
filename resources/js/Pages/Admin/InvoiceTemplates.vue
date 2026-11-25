<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

const togglingId = ref(null);
const settingDefaultId = ref(null);

function toggleStatus(template) {
    if (template.is_default && template.is_active) {
        alert('Cannot deactivate the system default template. Please set another template as default first.');
        return;
    }
    togglingId.value = template.id;
    router.post(route('admin.invoice-templates.toggle-status', template.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            togglingId.value = null;
        },
    });
}

function setDefault(template) {
    settingDefaultId.value = template.id;
    router.post(route('admin.invoice-templates.set-default', template.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            settingDefaultId.value = null;
        },
    });
}

function previewTemplate(template) {
    window.open(route('admin.invoice-templates.preview', template.id), '_blank');
}
</script>

<template>
    <AppLayout>
        <Head title="Invoice Templates Management - Super Admin" />

        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Invoice Templates Management</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Manage print-ready invoice designs, activate or deactivate them globally for tenants, and inspect layout alignment.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0 font-bold text-lg">
                        {{ stats.total }}
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Templates</div>
                        <div class="text-base font-extrabold text-slate-900 mt-0.5">{{ stats.total }} Designs</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 font-bold text-lg">
                        {{ stats.active }}
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active for Tenants</div>
                        <div class="text-base font-extrabold text-slate-900 mt-0.5">{{ stats.active }} Available</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0 font-bold text-lg">
                        ★
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">System Default</div>
                        <div class="text-base font-extrabold text-slate-900 mt-0.5 truncate">{{ stats.default }}</div>
                    </div>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Curated Invoice Designs Catalog</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tenants can choose from any active template to generate customer sales invoice PDFs or overlay on their official letterhead pads.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="tpl in templates"
                        :key="tpl.id"
                        class="border rounded-2xl overflow-hidden transition duration-150 flex flex-col justify-between"
                        :class="tpl.is_default ? 'border-amber-400 ring-2 ring-amber-300/40 bg-white' : 'border-slate-200 hover:border-slate-300 bg-white'"
                    >
                        <!-- Top Header & Badges -->
                        <div class="p-5 space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-mono font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700">
                                    {{ tpl.key }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        v-if="tpl.is_default"
                                        class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300"
                                    >
                                        System Default
                                    </span>
                                    <span
                                        :class="tpl.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full border"
                                    >
                                        {{ tpl.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">{{ tpl.name }}</h3>
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ tpl.description }}</p>
                            </div>

                            <!-- Highlights List -->
                            <div class="pt-2 border-t border-slate-100 space-y-1.5 text-xs text-slate-500">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Supports Letterhead Pad Background</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Dynamic Charges &amp; Warranty Support</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Dual Signatures &amp; In-Words Amount</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="p-4 bg-slate-50 border-t border-slate-100 space-y-2">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="previewTemplate(tpl)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition shadow-2xs cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Live Preview
                                </button>

                                <button
                                    v-if="!tpl.is_default"
                                    type="button"
                                    @click="setDefault(tpl)"
                                    :disabled="settingDefaultId === tpl.id"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl text-amber-800 bg-amber-50 border border-amber-300 hover:bg-amber-100 transition shadow-2xs cursor-pointer disabled:opacity-50"
                                >
                                    {{ settingDefaultId === tpl.id ? 'Saving...' : 'Set as Default' }}
                                </button>
                            </div>

                            <button
                                type="button"
                                @click="toggleStatus(tpl)"
                                :disabled="togglingId === tpl.id || (tpl.is_default && tpl.is_active)"
                                class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50"
                                :class="tpl.is_active 
                                    ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' 
                                    : 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs shadow-emerald-600/30'"
                            >
                                <span v-if="togglingId === tpl.id">Updating...</span>
                                <span v-else>{{ tpl.is_active ? 'Disable for Tenants' : 'Enable for Tenants' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
