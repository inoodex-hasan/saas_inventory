<script setup>
defineProps({
    size: {
        type: String,
        default: 'md', // 'xs', 'sm', 'md', 'lg'
    },
    color: {
        type: String,
        default: 'text-current',
    },
});
</script>

<template>
    <svg 
        class="animate-spin shrink-0" 
        :class="[
            color,
            size === 'xs' ? 'h-3 w-3' :
            size === 'sm' ? 'h-3.5 w-3.5' :
            size === 'lg' ? 'h-5 w-5' : 'h-4 w-4'
        ]" 
        fill="none" 
        viewBox="0 0 24 24"
    >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
</template>

