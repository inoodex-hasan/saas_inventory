<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object, null],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    valueKey: {
        type: String,
        default: 'id',
    },
    labelKey: {
        type: String,
        default: 'name',
    },
    sublabelKey: {
        type: String,
        default: '',
    },
    groupKey: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    searchPlaceholder: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    clearable: {
        type: Boolean,
        default: true,
    },
    error: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'normal', // 'compact' | 'normal'
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const containerRef = ref(null);
const searchInputRef = ref(null);
const listRef = ref(null);
const dropdownPanelRef = ref(null);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const coords = ref({ top: 0, bottom: 0, left: 0, width: 0 });
const placement = ref('bottom'); // 'bottom' | 'top'

// Normalize incoming options into a unified format
const normalizedOptions = computed(() => {
    return (props.options || []).map((opt, idx) => {
        if (typeof opt !== 'object' || opt === null) {
            return {
                raw: opt,
                value: opt,
                label: String(opt),
                sublabel: '',
                group: '',
                index: idx,
            };
        }

        const value = opt.value !== undefined ? opt.value : (opt[props.valueKey] !== undefined ? opt[props.valueKey] : opt.id);
        const label = opt.label !== undefined ? opt.label : (opt[props.labelKey] !== undefined ? opt[props.labelKey] : (opt.name || opt.title || String(value)));
        
        let sublabel = '';
        if (opt.sublabel !== undefined) {
            sublabel = opt.sublabel;
        } else if (props.sublabelKey && opt[props.sublabelKey]) {
            sublabel = opt[props.sublabelKey];
        }

        const group = opt.group !== undefined ? opt.group : (props.groupKey && opt[props.groupKey] ? opt[props.groupKey] : '');

        return {
            raw: opt,
            value,
            label: String(label ?? ''),
            sublabel: String(sublabel ?? ''),
            group: String(group ?? ''),
            index: idx,
        };
    });
});

// Currently selected option object
const selectedOption = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }
    return normalizedOptions.value.find(opt => String(opt.value) === String(props.modelValue)) || null;
});

// Filtered options based on search query
const filteredOptions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
        return normalizedOptions.value;
    }
    return normalizedOptions.value.filter(opt => {
        const inLabel = opt.label.toLowerCase().includes(q);
        const inSublabel = opt.sublabel.toLowerCase().includes(q);
        const inGroup = opt.group.toLowerCase().includes(q);
        return inLabel || inSublabel || inGroup;
    });
});

// Grouped filtered options (if groupKey or group present)
const hasGroups = computed(() => {
    return normalizedOptions.value.some(opt => Boolean(opt.group));
});

const groupedOptions = computed(() => {
    if (!hasGroups.value) {
        return [{ group: null, items: filteredOptions.value }];
    }
    const groupsMap = new Map();
    filteredOptions.value.forEach(opt => {
        const gName = opt.group || 'Other';
        if (!groupsMap.has(gName)) {
            groupsMap.set(gName, []);
        }
        groupsMap.get(gName).push(opt);
    });

    return Array.from(groupsMap.entries()).map(([group, items]) => ({
        group,
        items,
    }));
});

function updatePosition() {
    if (!containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    if (rect.width === 0 && rect.height === 0) return;

    const estHeight = 260;
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    if (spaceBelow < estHeight && spaceAbove > spaceBelow) {
        placement.value = 'top';
    } else {
        placement.value = 'bottom';
    }

    const minW = Math.max(rect.width, 240);
    const width = Math.min(minW, window.innerWidth - 16);

    let left = rect.left;
    if (left + width > window.innerWidth - 8) {
        left = Math.max(8, window.innerWidth - 8 - width);
    }
    left = Math.max(8, left);

    coords.value = {
        top: Math.round(rect.bottom + 4),
        bottom: Math.round(window.innerHeight - rect.top + 4),
        left: Math.round(left),
        width: Math.round(width),
    };
}

function openDropdown() {
    if (props.disabled) return;
    updatePosition();
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
    nextTick(() => {
        updatePosition();
        searchInputRef.value?.focus();
    });
}

function closeDropdown() {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
}

function toggleDropdown() {
    if (isOpen.value) {
        closeDropdown();
    } else {
        openDropdown();
    }
}

function selectOption(option) {
    emit('update:modelValue', option.value);
    emit('change', option.raw);
    closeDropdown();
}

function clearSelection(e) {
    e.stopPropagation();
    emit('update:modelValue', '');
    emit('change', null);
}

function onKeyDown(e) {
    if (!isOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openDropdown();
        }
        return;
    }

    const items = filteredOptions.value;
    if (e.key === 'Escape') {
        e.preventDefault();
        closeDropdown();
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (items.length === 0) return;
        highlightedIndex.value = (highlightedIndex.value + 1) % items.length;
        scrollHighlightedIntoView();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (items.length === 0) return;
        highlightedIndex.value = (highlightedIndex.value - 1 + items.length) % items.length;
        scrollHighlightedIntoView();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (highlightedIndex.value >= 0 && highlightedIndex.value < items.length) {
            selectOption(items[highlightedIndex.value]);
        } else if (items.length === 1) {
            selectOption(items[0]);
        }
    }
}

function scrollHighlightedIntoView() {
    nextTick(() => {
        const el = listRef.value?.querySelector(`[data-index="${highlightedIndex.value}"]`);
        if (el) {
            el.scrollIntoView({ block: 'nearest' });
        }
    });
}

function handleClickOutside(e) {
    if (!isOpen.value) return;
    const inTrigger = containerRef.value && containerRef.value.contains(e.target);
    const inDropdown = dropdownPanelRef.value && dropdownPanelRef.value.contains(e.target);
    if (!inTrigger && !inDropdown) {
        closeDropdown();
    }
}

function handleScrollOrResize(e) {
    if (!isOpen.value) return;
    if (e && dropdownPanelRef.value && dropdownPanelRef.value.contains(e.target)) return;
    updatePosition();
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    window.removeEventListener('scroll', handleScrollOrResize, true);
    window.removeEventListener('resize', handleScrollOrResize);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full text-left" @keydown="onKeyDown">
        <!-- Trigger Button -->
        <button
            type="button"
            :disabled="disabled"
            @click="toggleDropdown"
            class="w-full flex items-center justify-between transition-all outline-hidden border bg-white min-w-0"
            :class="[
                size === 'compact' ? 'px-2.5 h-[34px] min-h-[34px] max-h-[34px] text-xs rounded-lg' : 'px-3 h-[38px] min-h-[38px] max-h-[38px] text-xs rounded-xl',
                error ? 'border-rose-300 focus:border-rose-500 focus:ring-1 focus:ring-rose-500' : 'border-slate-200 hover:border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500',
                disabled ? 'bg-slate-100 cursor-not-allowed text-slate-400' : 'cursor-pointer text-slate-800'
            ]"
        >
            <div class="truncate flex-1 min-w-0 pr-1 text-left whitespace-nowrap">
                <span v-if="selectedOption" class="font-medium truncate block">
                    {{ selectedOption.label }}
                    <span v-if="selectedOption.sublabel" class="text-slate-400 text-[11px] ml-1 font-normal">
                        ({{ selectedOption.sublabel }})
                    </span>
                </span>
                <span v-else class="text-slate-400 truncate block">
                    {{ placeholder }}
                </span>
            </div>

            <div class="flex items-center gap-1 shrink-0 text-slate-400">
                <!-- Clear Button -->
                <span
                    v-if="clearable && selectedOption && !disabled"
                    @click.stop="clearSelection"
                    class="p-0.5 hover:text-slate-600 rounded transition shrink-0"
                    title="Clear selection"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>

                <!-- Chevron Icon -->
                <svg
                    class="w-4 h-4 transition-transform duration-150"
                    :class="isOpen ? 'rotate-180 text-indigo-600' : 'text-slate-400'"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <!-- Dropdown Panel (Teleported to body to prevent table overflow clipping and z-index overlap) -->
        <teleport to="body">
            <div
                v-if="isOpen"
                ref="dropdownPanelRef"
                class="fixed z-[99999] bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-100"
                :style="{
                    top: placement === 'bottom' ? `${coords.top}px` : 'auto',
                    bottom: placement === 'top' ? `${coords.bottom}px` : 'auto',
                    left: `${coords.left}px`,
                    width: `${coords.width}px`,
                }"
                @keydown="onKeyDown"
            >
                <!-- Search Input Box -->
                <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                    <div class="relative">
                        <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden placeholder:text-slate-400"
                            @click.stop
                        />
                    </div>
                </div>

                <!-- Options List -->
                <div ref="listRef" class="max-h-56 overflow-y-auto divide-y divide-slate-50 py-1">
                    <template v-if="filteredOptions.length > 0">
                        <template v-for="grp in groupedOptions" :key="grp.group || 'all'">
                            <!-- Group Header -->
                            <div
                                v-if="grp.group"
                                class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50/80 sticky top-0 backdrop-blur-xs z-10"
                            >
                                {{ grp.group }}
                            </div>

                            <!-- Group Items -->
                            <div
                                v-for="opt in grp.items"
                                :key="String(opt.value)"
                                :data-index="filteredOptions.indexOf(opt)"
                                @click="selectOption(opt)"
                                class="px-3 py-2 text-xs flex items-center justify-between cursor-pointer transition-colors"
                                :class="[
                                    String(opt.value) === String(modelValue) ? 'bg-indigo-50/80 text-indigo-900 font-semibold' : 'text-slate-700 hover:bg-slate-50',
                                    filteredOptions.indexOf(opt) === highlightedIndex ? 'bg-indigo-50 text-indigo-900' : ''
                                ]"
                            >
                                <div class="truncate flex-1 pr-2">
                                    <div class="truncate">{{ opt.label }}</div>
                                    <div v-if="opt.sublabel" class="text-[11px] text-slate-400 font-normal truncate mt-0.5">
                                        {{ opt.sublabel }}
                                    </div>
                                </div>

                                <svg
                                    v-if="String(opt.value) === String(modelValue)"
                                    class="w-4 h-4 text-indigo-600 shrink-0"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </template>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="px-4 py-6 text-center text-xs text-slate-400">
                        No results found for "<span class="font-semibold text-slate-600">{{ searchQuery }}</span>"
                    </div>
                </div>
            </div>
        </teleport>

        <!-- Optional Error Message -->
        <p v-if="error" class="text-xs text-rose-500 mt-1">{{ error }}</p>
    </div>
</template>
