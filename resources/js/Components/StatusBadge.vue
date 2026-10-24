<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const config = computed(() => {
    const s = (props.status || '').toLowerCase();
    switch (s) {
        case 'active':
        case 'paid':
        case 'received':
        case 'completed':
        case 'approved':
        case 'in_stock':
            return {
                bg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dot: 'bg-emerald-500',
                label: format(props.status),
            };
        case 'in_progress':
            return {
                bg: 'bg-blue-50 text-blue-700 border-blue-200',
                dot: 'bg-blue-500',
                label: format(props.status),
            };
        case 'planning':
        case 'draft':
        case 'pending':
            return {
                bg: 'bg-slate-100 text-slate-700 border-slate-200',
                dot: 'bg-slate-400',
                label: format(props.status),
            };
        case 'on_hold':
        case 'paused':
        case 'partially_paid':
        case 'partially_received':
        case 'ordered':
        case 'expiring_soon':
            return {
                bg: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500',
                label: format(props.status),
            };
        case 'unpaid':
        case 'defective':
        case 'rejected':
        case 'cancelled':
        case 'expired':
            return {
                bg: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-500',
                label: format(props.status),
            };
        case 'confirmed':
        case 'dispatched':
            return {
                bg: 'bg-indigo-50 text-indigo-700 border-indigo-200',
                dot: 'bg-indigo-500',
                label: format(props.status),
            };
        default:
            return {
                bg: 'bg-slate-50 text-slate-600 border-slate-200',
                dot: 'bg-slate-400',
                label: format(props.status),
            };
    }
});

function format(str) {
    if (!str) return '';
    return str.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<template>
    <span 
        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border"
        :class="config.bg"
    >
        <span class="w-1.5 h-1.5 rounded-full" :class="config.dot"></span>
        {{ config.label }}
    </span>
</template>
