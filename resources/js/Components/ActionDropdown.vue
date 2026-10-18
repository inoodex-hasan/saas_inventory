<script>
import { ref } from 'vue';

// Global active dropdown coordinator across all ActionDropdown instances
const activeDropdownId = ref(null);
let instanceCounter = 0;
</script>

<script setup>
import { ref as vueRef, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right', // 'right' | 'left'
    },
    width: {
        type: String,
        default: 'w-48',
    },
});

const instanceId = ++instanceCounter;
const isOpen = computed(() => activeDropdownId.value === instanceId);

const triggerRef = vueRef(null);
const dropdownRef = vueRef(null);
const coords = vueRef({ top: 0, bottom: 0, left: 0, right: 0 });
const placement = vueRef('bottom'); // 'bottom' | 'top'

function updatePosition() {
    if (!triggerRef.value) return;
    const rect = triggerRef.value.getBoundingClientRect();

    if (rect.width === 0 && rect.height === 0) {
        return;
    }

    const estHeight = 220;
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    if (spaceBelow < estHeight && spaceAbove > spaceBelow) {
        placement.value = 'top';
    } else {
        placement.value = 'bottom';
    }

    const right = Math.max(8, window.innerWidth - rect.right);
    const left = Math.max(8, rect.left);

    coords.value = {
        top: Math.round(rect.bottom + 6),
        bottom: Math.round(window.innerHeight - rect.top + 6),
        right: Math.round(right),
        left: Math.round(left),
    };
}

function toggle() {
    if (activeDropdownId.value === instanceId) {
        activeDropdownId.value = null;
    } else {
        updatePosition();
        activeDropdownId.value = instanceId;
        nextTick(() => {
            updatePosition();
        });
    }
}

function close() {
    if (activeDropdownId.value === instanceId) {
        activeDropdownId.value = null;
    }
}

function handleMenuClick(e) {
    // When a link or button inside the dropdown is clicked,
    // delay closing slightly so the action/navigation fires cleanly.
    if (e.target.closest('a, button')) {
        setTimeout(() => {
            if (activeDropdownId.value === instanceId) {
                activeDropdownId.value = null;
            }
        }, 120);
    }
}

function handleDocClick(e) {
    if (activeDropdownId.value !== instanceId) return;

    // Clicked trigger button itself
    if (triggerRef.value && (triggerRef.value === e.target || triggerRef.value.contains(e.target))) {
        return;
    }

    // Clicked inside dropdown panel
    if (dropdownRef.value && (dropdownRef.value === e.target || dropdownRef.value.contains(e.target))) {
        return;
    }

    // Clicked outside
    activeDropdownId.value = null;
}

function handleKeydown(e) {
    if (e.key === 'Escape' && activeDropdownId.value === instanceId) {
        activeDropdownId.value = null;
    }
}

function handleScrollOrResize(e) {
    if (activeDropdownId.value !== instanceId) return;
    // Don't close or reposition if scrolling inside dropdown itself
    if (e && dropdownRef.value && dropdownRef.value.contains(e.target)) return;
    updatePosition();
}

onMounted(() => {
    document.addEventListener('click', handleDocClick);
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('scroll', handleScrollOrResize, false);
    window.addEventListener('resize', handleScrollOrResize);
});

onUnmounted(() => {
    if (activeDropdownId.value === instanceId) {
        activeDropdownId.value = null;
    }
    document.removeEventListener('click', handleDocClick);
    document.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('scroll', handleScrollOrResize, false);
    window.removeEventListener('resize', handleScrollOrResize);
});
</script>

<template>
    <div class="inline-block relative">
        <button
            ref="triggerRef"
            type="button"
            @click.stop="toggle"
            :class="[
                'p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition focus:outline-none focus:ring-2 focus:ring-indigo-500/20 active:scale-95 cursor-pointer',
                isOpen ? 'bg-slate-100 text-slate-900 ring-1 ring-slate-200' : ''
            ]"
            title="Actions"
            aria-label="Actions"
        >
            <slot name="trigger">
                <!-- 3 vertical dots icon -->
                <svg class="w-5 h-5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
            </slot>
        </button>

        <teleport to="body">
            <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    ref="dropdownRef"
                    @click="handleMenuClick"
                    class="fixed z-[99999] py-1 bg-white rounded-xl shadow-2xl border border-slate-200/90 text-xs text-slate-700 font-medium divide-y divide-slate-100 text-left overflow-hidden min-w-[180px]"
                    :class="props.width"
                    :style="{
                        top: placement === 'bottom' ? `${coords.top}px` : 'auto',
                        bottom: placement === 'top' ? `${coords.bottom}px` : 'auto',
                        right: props.align === 'right' ? `${coords.right}px` : 'auto',
                        left: props.align === 'left' ? `${coords.left}px` : 'auto',
                    }"
                >
                    <slot :close="close" />
                </div>
            </transition>
        </teleport>
    </div>
</template>
