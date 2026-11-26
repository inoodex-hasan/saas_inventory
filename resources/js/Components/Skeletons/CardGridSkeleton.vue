<script setup>
defineProps({
    count: {
        type: Number,
        default: 4,
    },
    gridClass: {
        type: String,
        default: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4',
    },
});
</script>

<template>
    <div :class="gridClass" class="animate-pulse" aria-busy="true" aria-label="Loading summary cards">
        <div
            v-for="i in count"
            :key="`card-skel-${i}`"
            class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center gap-4"
        >
            <div class="w-11 h-11 rounded-xl bg-slate-200/80 shrink-0"></div>
            <div class="flex-1 space-y-2">
                <div class="h-2.5 bg-slate-200 rounded w-20"></div>
                <div class="h-5 bg-slate-200/90 rounded w-28"></div>
            </div>
        </div>
    </div>
</template>
