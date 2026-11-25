<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import { playScanSuccess, playScanError } from '@/Utils/sound';

const props = defineProps({
    modelValue: {
        type: [String, Array],
        default: '',
    },
    requiredQuantity: {
        type: Number,
        default: 0,
    },
    productSku: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: 'Serial Numbers',
    },
    allowGenerate: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

// Active Tab: 'scan' | 'generate' | 'paste'
const activeTab = ref('scan');

// Local Serials List
const serials = ref([]);
const scanInput = ref('');
const scanInputRef = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const dbDuplicates = ref([]);
const isCheckingDb = ref(false);

// Generator Configuration
const genPrefix = ref(props.productSku ? `${props.productSku.trim().toUpperCase()}-` : 'SN-');
const genStartNum = ref(1);
const genPadding = ref(4);
const genSuffix = ref('');
const genCount = ref(props.requiredQuantity > 0 ? props.requiredQuantity : 1);

// Bulk Paste input
const pasteText = ref('');

// Parse incoming modelValue
function parseInputSerials(val) {
    if (!val) return [];
    if (Array.isArray(val)) {
        return val.map(s => String(s).trim()).filter(Boolean);
    }
    return String(val)
        .split(/[\r\n,]+/)
        .map(s => s.trim())
        .filter(Boolean);
}

// Initialize serials from prop
watch(() => props.modelValue, (newVal) => {
    const parsed = parseInputSerials(newVal);
    if (parsed.join('\n') !== serials.value.join('\n')) {
        serials.value = [...parsed];
    }
}, { immediate: true });

// Sync default prefix when productSku changes
watch(() => props.productSku, (newSku) => {
    if (newSku && (genPrefix.value === 'SN-' || !genPrefix.value)) {
        genPrefix.value = `${newSku.trim().toUpperCase()}-`;
    }
});

// Update generator count when requiredQuantity updates
watch(() => props.requiredQuantity, (newQty) => {
    if (newQty > 0) {
        genCount.value = newQty;
    }
});

// Emit updates back to parent
function emitUpdate() {
    const resultString = serials.value.join('\n');
    emit('update:modelValue', resultString);
    emit('change', serials.value);
    checkDbUniqueness();
}

// Check database uniqueness across organization
let checkTimeout = null;
function checkDbUniqueness() {
    clearTimeout(checkTimeout);
    if (serials.value.length === 0) {
        dbDuplicates.value = [];
        return;
    }

    checkTimeout = setTimeout(async () => {
        isCheckingDb.value = true;
        try {
            const res = await axios.post(route('serials.check-uniqueness'), {
                serials: serials.value,
            });
            dbDuplicates.value = res.data.existing || [];
            if (dbDuplicates.value.length > 0) {
                errorMessage.value = `${dbDuplicates.value.length} serial(s) already exist in your inventory database!`;
            } else if (errorMessage.value.includes('already exist in your inventory')) {
                errorMessage.value = '';
            }
        } catch (e) {
            // Silently handle if network fails
        } finally {
            isCheckingDb.value = false;
        }
    }, 400);
}

// ---------------------------------------------------------------------
// 1. SCAN MODE
// ---------------------------------------------------------------------
function addSerialFromScan() {
    const raw = scanInput.value.trim();
    if (!raw) return;

    errorMessage.value = '';
    successMessage.value = '';

    // Check duplicate in current local list
    if (serials.value.includes(raw)) {
        playScanError();
        errorMessage.value = `Duplicate: Serial "${raw}" is already added.`;
        scanInput.value = '';
        return;
    }

    // Check target quantity limit
    if (props.requiredQuantity > 0 && serials.value.length >= props.requiredQuantity) {
        playScanError();
        errorMessage.value = `Target quantity of ${props.requiredQuantity} already reached. Remove an item to add a new one.`;
        scanInput.value = '';
        return;
    }

    serials.value.push(raw);
    scanInput.value = '';
    playScanSuccess();
    emitUpdate();

    // Re-focus scanner input
    nextTick(() => {
        scanInputRef.value?.focus();
    });
}

// ---------------------------------------------------------------------
// 2. 1-CLICK GENERATOR MODE
// ---------------------------------------------------------------------
const generatorPreview = computed(() => {
    const count = Math.max(1, Math.min(Number(genCount.value) || 1, 1000));
    const start = Math.max(0, Number(genStartNum.value) || 1);
    const pad = Math.max(1, Math.min(Number(genPadding.value) || 4, 10));
    const prefix = genPrefix.value || '';
    const suffix = genSuffix.value || '';

    const first = `${prefix}${String(start).padStart(pad, '0')}${suffix}`;
    const second = `${prefix}${String(start + 1).padStart(pad, '0')}${suffix}`;
    const last = `${prefix}${String(start + count - 1).padStart(pad, '0')}${suffix}`;

    if (count === 1) return [first];
    if (count === 2) return [first, second];
    return [first, second, '...', last];
});

function applyGeneratedSerials() {
    errorMessage.value = '';
    successMessage.value = '';

    const count = Math.max(1, Math.min(Number(genCount.value) || 1, 1000));
    const start = Math.max(0, Number(genStartNum.value) || 1);
    const pad = Math.max(1, Math.min(Number(genPadding.value) || 4, 10));
    const prefix = genPrefix.value || '';
    const suffix = genSuffix.value || '';

    const newGenerated = [];
    for (let i = 0; i < count; i++) {
        const numStr = String(start + i).padStart(pad, '0');
        newGenerated.push(`${prefix}${numStr}${suffix}`);
    }

    serials.value = newGenerated;
    playScanSuccess();
    successMessage.value = `Generated ${newGenerated.length} unique serial numbers.`;
    emitUpdate();
    activeTab.value = 'scan';
}

// ---------------------------------------------------------------------
// 3. BULK PASTE MODE
// ---------------------------------------------------------------------
function applyBulkPaste() {
    errorMessage.value = '';
    successMessage.value = '';

    const items = pasteText.value
        .split(/[\r\n,]+/)
        .map(s => s.trim())
        .filter(Boolean);

    if (items.length === 0) {
        errorMessage.value = 'Please paste at least one serial number.';
        return;
    }

    // Deduplicate
    const uniqueItems = Array.from(new Set(items));
    serials.value = uniqueItems;
    pasteText.value = '';
    playScanSuccess();
    successMessage.value = `Imported ${uniqueItems.length} unique serial numbers.`;
    emitUpdate();
    activeTab.value = 'scan';
}

// ---------------------------------------------------------------------
// REMOVAL ACTIONS
// ---------------------------------------------------------------------
function removeSerial(idx) {
    serials.value.splice(idx, 1);
    emitUpdate();
}

function clearAllSerials() {
    serials.value = [];
    errorMessage.value = '';
    successMessage.value = '';
    dbDuplicates.value = [];
    emitUpdate();
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-4">
        <!-- Header & Tab Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-900 tracking-wide uppercase">{{ title }}</span>
                <span 
                    v-if="requiredQuantity > 0" 
                    class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                    :class="serials.length === requiredQuantity ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                >
                    {{ serials.length }} / {{ requiredQuantity }}
                </span>
                <span v-else class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700">
                    {{ serials.length }} total
                </span>
            </div>

            <!-- Tab Buttons -->
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs font-medium">
                <button
                    type="button"
                    @click="activeTab = 'scan'"
                    class="px-3 py-1 rounded-md text-xs font-semibold transition"
                    :class="activeTab === 'scan' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    Scan / Enter
                </button>
                <button
                    v-if="allowGenerate"
                    type="button"
                    @click="activeTab = 'generate'"
                    class="px-3 py-1 rounded-md text-xs font-semibold transition"
                    :class="activeTab === 'generate' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    1-Click Generate
                </button>
                <button
                    type="button"
                    @click="activeTab = 'paste'"
                    class="px-3 py-1 rounded-md text-xs font-semibold transition"
                    :class="activeTab === 'paste' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    Bulk Paste
                </button>
            </div>
        </div>

        <!-- 1. SCAN MODE TAB -->
        <div v-if="activeTab === 'scan'" class="space-y-3">
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input
                        ref="scanInputRef"
                        type="text"
                        v-model="scanInput"
                        @keydown.enter.prevent="addSerialFromScan"
                        placeholder="Scan barcode with gun or type serial, then press Enter..."
                        class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400"
                    />
                </div>
                <button
                    type="button"
                    @click="addSerialFromScan"
                    class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg cursor-pointer transition shadow-xs"
                >
                    Add
                </button>
            </div>
            <p class="text-[11px] text-slate-400">
                Tip: Point USB scanner gun at box barcode. Each scan is automatically verified for uniqueness and added.
            </p>
        </div>

        <!-- 2. GENERATE MODE TAB (Internal Products) -->
        <div v-if="activeTab === 'generate' && allowGenerate" class="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-3">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Prefix</label>
                    <input
                        type="text"
                        v-model="genPrefix"
                        placeholder="e.g. LAPTOP-"
                        class="w-full px-2 py-1 text-xs font-mono rounded border border-slate-300 focus:border-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Start Number</label>
                    <input
                        type="number"
                        min="1"
                        v-model="genStartNum"
                        class="w-full px-2 py-1 text-xs font-mono rounded border border-slate-300 focus:border-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Digits (Padding)</label>
                    <input
                        type="number"
                        min="1"
                        max="8"
                        v-model="genPadding"
                        class="w-full px-2 py-1 text-xs font-mono rounded border border-slate-300 focus:border-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Count</label>
                    <input
                        type="number"
                        min="1"
                        v-model="genCount"
                        class="w-full px-2 py-1 text-xs font-mono rounded border border-slate-300 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Preview & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-slate-200">
                <div class="text-[11px] text-slate-600 font-mono">
                    Preview: <span class="text-indigo-600 font-semibold">{{ generatorPreview.join(', ') }}</span>
                </div>
                <button
                    type="button"
                    @click="applyGeneratedSerials"
                    class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg cursor-pointer transition shadow-xs"
                >
                    Apply {{ genCount }} Generated Serials
                </button>
            </div>
        </div>

        <!-- 3. BULK PASTE TAB -->
        <div v-if="activeTab === 'paste'" class="space-y-3">
            <textarea
                v-model="pasteText"
                rows="3"
                placeholder="Paste list of serial numbers from Excel or invoice (separated by commas or new lines)..."
                class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:border-indigo-500"
            ></textarea>
            <div class="flex justify-end">
                <button
                    type="button"
                    @click="applyBulkPaste"
                    class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg cursor-pointer transition shadow-xs"
                >
                    Process & Import List
                </button>
            </div>
        </div>

        <!-- Alert & Feedback Banners -->
        <div v-if="errorMessage" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700 font-semibold">
            {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-700 font-semibold">
            {{ successMessage }}
        </div>

        <!-- Scanned Serials Chip Cloud -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                    Scanned Serials List ({{ serials.length }})
                </span>
                <button
                    v-if="serials.length > 0"
                    type="button"
                    @click="clearAllSerials"
                    class="text-[11px] font-semibold text-rose-600 hover:text-rose-800 cursor-pointer"
                >
                    Clear All
                </button>
            </div>

            <!-- Chips Container -->
            <div 
                v-if="serials.length > 0" 
                class="max-h-48 overflow-y-auto p-2.5 rounded-lg border border-slate-200 bg-slate-50 flex flex-wrap gap-1.5"
            >
                <div
                    v-for="(sn, idx) in serials"
                    :key="idx"
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium border"
                    :class="dbDuplicates.includes(sn) ? 'bg-rose-100 text-rose-800 border-rose-300' : 'bg-white text-slate-800 border-slate-300'"
                >
                    <span v-if="dbDuplicates.includes(sn)" class="text-[10px] text-rose-600 font-bold" title="Already exists in database!">[DB Duplicate]</span>
                    <span>{{ sn }}</span>
                    <button
                        type="button"
                        @click="removeSerial(idx)"
                        title="Remove"
                        class="text-slate-400 hover:text-rose-600 cursor-pointer text-xs"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <div v-else class="text-center py-4 rounded-lg border border-dashed border-slate-200 bg-slate-50/50">
                <p class="text-xs text-slate-400">No serial numbers entered yet.</p>
            </div>
        </div>
    </div>
</template>
