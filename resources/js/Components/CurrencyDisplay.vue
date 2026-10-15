<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    amount: {
        type: [Number, String],
        default: 0,
    },
    decimals: {
        type: Number,
        default: 2,
    },
});

const page = usePage();
const currencySymbol = computed(() => page.props.auth?.team?.currency_symbol || '৳');

const formattedAmount = computed(() => {
    const val = Number(props.amount) || 0;
    return val.toLocaleString('en-US', {
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals,
    });
});
</script>

<template>
    <span class="inline-flex items-baseline font-mono whitespace-nowrap">
        <span class="mr-0.5 font-sans font-medium text-slate-500">{{ currencySymbol }}</span>
        <span>{{ formattedAmount }}</span>
    </span>
</template>
