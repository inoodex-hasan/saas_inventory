<script setup>
import CardGridSkeleton from './CardGridSkeleton.vue';
import TableSkeleton from './TableSkeleton.vue';
import ChartSkeleton from './ChartSkeleton.vue';

defineProps({
    variant: {
        type: String,
        default: 'table', // 'dashboard' | 'pos' | 'form' | 'report' | 'table'
    },
});
</script>

<template>
    <div class="space-y-6 animate-pulse" aria-busy="true" aria-label="Loading page content">
        <!-- 1. DASHBOARD SKELETON -->
        <template v-if="variant === 'dashboard'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="h-6 bg-slate-200 rounded-lg w-56"></div>
                    <div class="h-3.5 bg-slate-100 rounded-md w-72 max-w-full"></div>
                </div>
                <div class="h-9 bg-slate-200 rounded-xl w-36"></div>
            </div>

            <CardGridSkeleton :count="4" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <ChartSkeleton height="h-72" type="bar" />
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <ChartSkeleton height="h-72" type="donut" />
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <div class="h-4 bg-slate-200 rounded w-44"></div>
                </div>
                <TableSkeleton :rows="5" :columns="5" :show-header="true" />
            </div>
        </template>

        <!-- 2. POS TERMINAL SKELETON -->
        <template v-else-if="variant === 'pos'">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Product Search & Grid -->
                <div class="lg:col-span-8 space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-3">
                        <div class="h-10 bg-slate-100 rounded-xl w-full"></div>
                        <div class="flex gap-2 overflow-hidden">
                            <div v-for="i in 5" :key="`cat-${i}`" class="h-7 bg-slate-100 rounded-lg w-20 shrink-0"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        <div
                            v-for="i in 8"
                            :key="`pos-prod-${i}`"
                            class="bg-white rounded-2xl border border-slate-200/80 p-3 space-y-2.5"
                        >
                            <div class="h-24 bg-slate-100 rounded-xl w-full"></div>
                            <div class="h-3.5 bg-slate-200 rounded w-3/4"></div>
                            <div class="flex items-center justify-between pt-1">
                                <div class="h-4 bg-slate-200 rounded w-16"></div>
                                <div class="h-5 bg-slate-100 rounded-md w-10"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Cart & Checkout Panel -->
                <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 p-4 space-y-4">
                    <div class="h-10 bg-slate-100 rounded-xl w-full"></div>
                    <div class="space-y-3 py-2 border-y border-slate-100">
                        <div v-for="i in 4" :key="`cart-${i}`" class="flex items-center justify-between gap-3">
                            <div class="space-y-1.5 flex-1">
                                <div class="h-3.5 bg-slate-200 rounded w-2/3"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/3"></div>
                            </div>
                            <div class="h-6 bg-slate-100 rounded-lg w-16"></div>
                        </div>
                    </div>
                    <div class="space-y-2 pt-2">
                        <div class="flex justify-between">
                            <div class="h-3 bg-slate-100 rounded w-20"></div>
                            <div class="h-3 bg-slate-200 rounded w-16"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="h-5 bg-slate-200 rounded w-24"></div>
                            <div class="h-5 bg-slate-200 rounded w-24"></div>
                        </div>
                    </div>
                    <div class="h-11 bg-slate-200 rounded-xl w-full"></div>
                </div>
            </div>
        </template>

        <!-- 3. FORM / CREATE / EDIT / SETTINGS SKELETON -->
        <template v-else-if="variant === 'form'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="h-6 bg-slate-200 rounded-lg w-56"></div>
                    <div class="h-3.5 bg-slate-100 rounded-md w-72 max-w-full"></div>
                </div>
                <div class="h-9 bg-slate-100 rounded-xl w-28"></div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div v-for="i in 6" :key="`field-${i}`" class="space-y-2">
                        <div class="h-3 bg-slate-200 rounded w-28"></div>
                        <div class="h-10 bg-slate-100 rounded-xl w-full"></div>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <div class="h-10 bg-slate-100 rounded-xl w-24"></div>
                    <div class="h-10 bg-slate-200 rounded-xl w-32"></div>
                </div>
            </div>
        </template>

        <!-- 4. REPORTS SKELETON -->
        <template v-else-if="variant === 'report'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="h-6 bg-slate-200 rounded-lg w-52"></div>
                    <div class="h-3.5 bg-slate-100 rounded-md w-80 max-w-full"></div>
                </div>
                <div class="flex gap-2">
                    <div class="h-9 bg-slate-100 rounded-xl w-28"></div>
                    <div class="h-9 bg-slate-200 rounded-xl w-28"></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-2 flex gap-2 overflow-hidden">
                <div v-for="i in 5" :key="`tab-${i}`" class="h-8 bg-slate-100 rounded-xl w-32 shrink-0"></div>
            </div>

            <CardGridSkeleton :count="4" />

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <TableSkeleton :rows="7" :columns="6" :show-header="true" />
            </div>
        </template>

        <!-- 5. DEFAULT LIST / TABLE SKELETON -->
        <template v-else>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="h-6 bg-slate-200 rounded-lg w-52"></div>
                    <div class="h-3.5 bg-slate-100 rounded-md w-80 max-w-full"></div>
                </div>
                <div class="h-9 bg-slate-200 rounded-xl w-32"></div>
            </div>

            <CardGridSkeleton :count="4" />

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3">
                    <div class="h-9 bg-slate-100 rounded-xl w-64"></div>
                    <div class="h-9 bg-slate-100 rounded-xl w-36"></div>
                    <div class="h-9 bg-slate-100 rounded-xl w-36"></div>
                </div>
                <TableSkeleton :rows="7" :columns="6" :show-header="true" />
            </div>
        </template>
    </div>
</template>
