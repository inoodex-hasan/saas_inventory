<script setup>
import { computed } from 'vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    date: {
        type: [String, Date, Number, null],
        default: null,
    },
    fallback: {
        type: String,
        default: '—',
    },
});

const formatted = computed(() => formatDate(props.date, props.fallback));
</script>

<template>
    <span>{{ formatted }}</span>
</template>
