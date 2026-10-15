<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap items-center justify-end gap-1 text-xs">
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg font-medium transition-all"
                :class="link.active ? 'bg-indigo-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100'"
            />
            <span
                v-else
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg text-slate-300 font-medium cursor-not-allowed select-none"
            />
        </template>
    </div>
</template>
