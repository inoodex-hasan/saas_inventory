<script setup>
defineProps({
    rows: {
        type: Number,
        default: 8,
    },
    columns: {
        type: Number,
        default: 6,
    },
    showHeader: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="w-full overflow-hidden animate-pulse" aria-busy="true" aria-label="Loading table data">
        <table class="w-full text-left border-collapse">
            <thead v-if="showHeader" class="bg-slate-50/80 border-b border-slate-200/80">
                <tr>
                    <th v-for="col in columns" :key="`th-${col}`" class="px-4 py-3.5">
                        <div class="h-3 bg-slate-200 rounded-md w-20"></div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="row in rows" :key="`row-${row}`" class="bg-white">
                    <td v-for="col in columns" :key="`cell-${row}-${col}`" class="px-4 py-3.5">
                        <div
                            class="h-3.5 bg-slate-200/80 rounded-md"
                            :class="[
                                col === 1 ? 'w-3/4' : col === columns ? 'w-16 ml-auto' : 'w-2/3',
                                row % 2 === 0 ? 'opacity-80' : 'opacity-100'
                            ]"
                        ></div>
                        <div
                            v-if="col === 1"
                            class="h-2.5 bg-slate-100 rounded-md w-1/2 mt-1.5"
                        ></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
