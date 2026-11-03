<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Deletion',
    },
    message: {
        type: String,
        default: 'Are you sure you want to delete this item? This action cannot be undone and will permanently remove this record from the database.',
    },
    itemName: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: 'Delete',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    variant: {
        type: String,
        default: 'danger', // 'danger' | 'warning' | 'info'
    },
    processing: {
        type: Boolean,
        default: false,
    },
    // Optional safety confirmation: user must type this exact text to enable delete button
    requireInputMatch: {
        type: String,
        default: '',
    },
    inputPlaceholder: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
});

const emit = defineEmits(['confirm', 'close']);

const confirmationInput = ref('');

// Reset input whenever modal is opened
watch(() => props.show, (newVal) => {
    if (newVal) {
        confirmationInput.value = '';
    }
});

const isConfirmed = computed(() => {
    if (!props.requireInputMatch) {
        return true;
    }
    return confirmationInput.value.trim() === props.requireInputMatch.trim();
});

const handleConfirm = () => {
    if (!isConfirmed.value || props.processing) {
        return;
    }
    emit('confirm');
};

const handleClose = () => {
    if (!props.processing) {
        emit('close');
    }
};

const themeConfig = computed(() => {
    switch (props.variant) {
        case 'warning':
            return {
                iconBg: 'bg-amber-100 text-amber-600 border-amber-200',
                buttonBg: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
                accentText: 'text-amber-700',
            };
        case 'info':
            return {
                iconBg: 'bg-indigo-100 text-indigo-600 border-indigo-200',
                buttonBg: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
                accentText: 'text-indigo-700',
            };
        case 'danger':
        default:
            return {
                iconBg: 'bg-rose-100 text-rose-600 border-rose-200',
                buttonBg: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
                accentText: 'text-rose-700',
            };
    }
});
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="!processing" @close="handleClose">
        <div class="p-6">
            <!-- Icon + Content Header -->
            <div class="flex items-start gap-4">
                <div 
                    class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 border shadow-sm transition-transform"
                    :class="themeConfig.iconBg"
                >
                    <!-- Danger / Trash Icon -->
                    <svg v-if="variant === 'danger'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <!-- Warning / Exclamation Icon -->
                    <svg v-else-if="variant === 'warning'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <!-- Info Icon -->
                    <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-black text-slate-900 tracking-tight leading-snug">
                        {{ title }}
                    </h3>

                    <!-- Highlighted Item Name Chip -->
                    <div v-if="itemName" class="mt-1.5 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200 break-all font-mono">
                            {{ itemName }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed mt-1">
                        {{ message }}
                    </p>

                    <!-- Type-to-Confirm Input if required -->
                    <div v-if="requireInputMatch" class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Type <span class="font-mono text-rose-600 font-black">"{{ requireInputMatch }}"</span> to confirm:
                        </label>
                        <input
                            v-model="confirmationInput"
                            type="text"
                            :placeholder="inputPlaceholder || requireInputMatch"
                            class="w-full text-xs font-mono px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500 border-slate-300 bg-white"
                            :disabled="processing"
                            @keyup.enter="handleConfirm"
                        />
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons Footer -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-sm disabled:opacity-50"
                    :disabled="processing"
                    @click="handleClose"
                >
                    {{ cancelText }}
                </button>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-xs font-bold transition shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    :class="themeConfig.buttonBg"
                    :disabled="!isConfirmed || processing"
                    @click="handleConfirm"
                >
                    <!-- Spinner when processing -->
                    <svg v-if="processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span>{{ processing ? 'Deleting...' : confirmText }}</span>
                </button>
            </div>
        </div>
    </Modal>
</template>

