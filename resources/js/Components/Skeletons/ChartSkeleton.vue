<script setup>
defineProps({
    height: {
        type: String,
        default: 'h-64',
    },
    type: {
        type: String,
        default: 'bar', // 'bar' | 'donut'
    },
});
</script>

<template>
    <div :class="height" class="w-full flex flex-col justify-between p-4 animate-pulse" aria-busy="true" aria-label="Loading chart">
        <div class="flex items-center justify-between mb-4">
            <div class="h-4 bg-slate-200 rounded w-36"></div>
            <div class="h-3 bg-slate-100 rounded w-20"></div>
        </div>

        <div v-if="type === 'donut'" class="flex-1 flex items-center justify-center">
            <div class="w-36 h-36 rounded-full border-[18px] border-slate-200/80"></div>
        </div>

        <div v-else class="flex-1 flex items-end gap-3 pt-4 px-2 border-b border-slate-100">
            <div class="flex-1 bg-slate-200/70 rounded-t-md h-2/5"></div>
            <div class="flex-1 bg-slate-200/80 rounded-t-md h-3/5"></div>
            <div class="flex-1 bg-slate-200/70 rounded-t-md h-4/5"></div>
            <div class="flex-1 bg-slate-200/90 rounded-t-md h-1/2"></div>
            <div class="flex-1 bg-slate-200/70 rounded-t-md h-5/6"></div>
            <div class="flex-1 bg-slate-200/80 rounded-t-md h-3/4"></div>
            <div class="flex-1 bg-slate-200/70 rounded-t-md h-2/3"></div>
        </div>
    </div>
</template>
