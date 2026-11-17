<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import BarcodeCameraModal from '@/Components/BarcodeCameraModal.vue';
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner';
import { playScanSuccess, playScanError } from '@/Utils/sound';

const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    warehouses: Array,
});

const form = useForm({
    name: props.product.name,
    sku: props.product.sku,
    barcode: props.product.barcode || '',
    category_id: props.product.category_id || '',
    brand_id: props.product.brand_id || '',
    unit: props.product.unit || 'pcs',
    cost_price: Number(props.product.cost_price),
    selling_price: Number(props.product.selling_price),
    reorder_level: Number(props.product.reorder_level),
    reorder_quantity: Number(props.product.reorder_quantity),
    warranty_days: Number(props.product.warranty_days || 0),
    has_variants: Boolean(props.product.has_variants),
    is_serialized: Boolean(props.product.is_serialized),
    is_active: Boolean(props.product.is_active),
    image: null,
    remove_image: false,
    description: props.product.description || '',
    variants: (props.product.variants || []).map(v => ({
        id: v.id,
        name: v.name,
        sku: v.sku,
        barcode: v.barcode || '',
        attributes: v.attributes || {},
        cost_price: Number(v.cost_price ?? props.product.cost_price),
        selling_price: Number(v.unit_price ?? props.product.selling_price),
        total_stock: Number(v.total_stock ?? 0),
        is_active: Boolean(v.is_active),
    })),
});

const imagePreview = ref(props.product.image_url || null);
const imageLoadFailed = ref(false);

function handleImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        form.remove_image = false;
        imageLoadFailed.value = false;
        imagePreview.value = URL.createObjectURL(file);
    }
}

function removeImage() {
    form.image = null;
    form.remove_image = true;
    imagePreview.value = null;
    imageLoadFailed.value = false;
}

// Quick Add State
const isQuickCategoryOpen = ref(false);
const quickCategoryName = ref('');
const quickCategoryLoading = ref(false);
const quickCategoryError = ref('');

const isQuickBrandOpen = ref(false);
const quickBrandName = ref('');
const quickBrandLoading = ref(false);
const quickBrandError = ref('');

const categoriesList = ref([...props.categories]);
const brandsList = ref([...props.brands]);

// Barcode Scanning & Generation
const isCameraModalOpen = ref(false);
const barcodeWarning = ref('');

function generateBarcode() {
    const rand = Math.floor(100000000 + Math.random() * 900000000);
    form.barcode = `890${rand}`;
    playScanSuccess();
    checkBarcodeDuplicate(form.barcode);
}

async function checkBarcodeDuplicate(code) {
    if (!code) {
        barcodeWarning.value = '';
        return;
    }
    try {
        const res = await axios.get(route('products.lookup-barcode'), { 
            params: { barcode: code, exclude_id: props.product.id } 
        });
        if (res.data.found) {
            barcodeWarning.value = `Already used by: ${res.data.product.name} (${res.data.product.sku})`;
            playScanError();
        } else {
            barcodeWarning.value = '';
        }
    } catch (e) {
        barcodeWarning.value = '';
    }
}

function handleBarcodeScanned(code) {
    if (!code) return;
    form.barcode = code.trim();
    playScanSuccess();
    checkBarcodeDuplicate(form.barcode);
}

// Hardware Barcode Scanner listener (USB / Bluetooth / Wireless Gun)
useBarcodeScanner({
    onScan: (code) => {
        handleBarcodeScanned(code);
    },
});

function openQuickCategory() {
    quickCategoryName.value = '';
    quickCategoryError.value = '';
    isQuickCategoryOpen.value = true;
}

function openQuickBrand() {
    quickBrandName.value = '';
    quickBrandError.value = '';
    isQuickBrandOpen.value = true;
}

async function submitQuickCategory() {
    const name = quickCategoryName.value.trim();
    if (!name || quickCategoryLoading.value) return;

    quickCategoryLoading.value = true;
    quickCategoryError.value = '';
    try {
        const response = await axios.post(route('products.categories.store'), { name });
        const newCategory = response.data?.category || response.data;
        if (newCategory && newCategory.id) {
            if (!categoriesList.value.some(c => c.id === newCategory.id)) {
                categoriesList.value.push(newCategory);
            }
            form.category_id = newCategory.id;
        }
        isQuickCategoryOpen.value = false;
        quickCategoryName.value = '';
    } catch (e) {
        console.error(e);
        quickCategoryError.value = e.response?.data?.message || e.response?.data?.errors?.name?.[0] || 'Failed to create category.';
    } finally {
        quickCategoryLoading.value = false;
    }
}

async function submitQuickBrand() {
    const name = quickBrandName.value.trim();
    if (!name || quickBrandLoading.value) return;

    quickBrandLoading.value = true;
    quickBrandError.value = '';
    try {
        const response = await axios.post(route('products.brands.store'), { name });
        const newBrand = response.data?.brand || response.data;
        if (newBrand && newBrand.id) {
            if (!brandsList.value.some(b => b.id === newBrand.id)) {
                brandsList.value.push(newBrand);
            }
            form.brand_id = newBrand.id;
        }
        isQuickBrandOpen.value = false;
        quickBrandName.value = '';
    } catch (e) {
        console.error(e);
        quickBrandError.value = e.response?.data?.message || e.response?.data?.errors?.name?.[0] || 'Failed to create brand.';
    } finally {
        quickBrandLoading.value = false;
    }
}

// Profit Margin Computations
const profitMargin = computed(() => {
    return (Number(form.selling_price) || 0) - (Number(form.cost_price) || 0);
});

const marginPercentage = computed(() => {
    const sp = Number(form.selling_price) || 0;
    if (sp <= 0) return 0;
    return Math.round((profitMargin.value / sp) * 1000) / 10;
});

function setWarrantyPreset(days) {
    form.warranty_days = days;
}

// ==========================================
// VARIANT MATRIX GENERATOR
// ==========================================
const variantOptions = ref([
    { name: 'Color', values: ['Black', 'White'] },
    { name: 'Size', values: ['M', 'L', 'XL'] },
]);
const newOptionName = ref('');
const newOptionValueInputs = ref({});

function toggleVariants() {
    form.has_variants = !form.has_variants;
    if (form.has_variants && form.variants.length === 0) {
        generateVariantMatrix();
    }
}

function addOption() {
    const name = newOptionName.value.trim();
    if (!name) return;
    variantOptions.value.push({ name, values: [] });
    newOptionName.value = '';
}

function removeOption(idx) {
    variantOptions.value.splice(idx, 1);
}

function addOptionValue(optIdx) {
    const val = (newOptionValueInputs.value[optIdx] || '').trim();
    if (!val) return;
    if (!variantOptions.value[optIdx].values.includes(val)) {
        variantOptions.value[optIdx].values.push(val);
    }
    newOptionValueInputs.value[optIdx] = '';
}

function removeOptionValue(optIdx, valIdx) {
    variantOptions.value[optIdx].values.splice(valIdx, 1);
}

function generateVariantMatrix() {
    const validOptions = variantOptions.value.filter(o => o.values && o.values.length > 0);
    if (validOptions.length === 0) return;

    function cartesian(arr) {
        return arr.reduce((acc, curr) => {
            return acc.flatMap(x => curr.values.map(y => [...x, { option: curr.name, value: y }]));
        }, [[]]);
    }

    const combinations = cartesian(validOptions);
    const baseSku = (form.sku || props.product.sku || 'PROD').trim().toUpperCase();
    const baseCost = Number(form.cost_price) || 0;
    const basePrice = Number(form.selling_price) || 0;

    combinations.forEach(comb => {
        const name = comb.map(c => c.value).join(' / ');
        const skuSuffix = comb.map(c => c.value.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase()).join('-');
        const attributes = {};
        comb.forEach(c => { attributes[c.option] = c.value; });

        const existing = form.variants.find(v => v.name === name);
        if (!existing) {
            form.variants.push({
                id: null,
                name,
                sku: `${baseSku}-${skuSuffix}`,
                barcode: '',
                attributes,
                cost_price: baseCost,
                selling_price: basePrice,
                total_stock: 0,
                is_active: true,
            });
        }
    });
}

function addSingleVariant() {
    const baseSku = (form.sku || props.product.sku || 'PROD').trim().toUpperCase();
    const idx = form.variants.length + 1;
    form.variants.push({
        id: null,
        name: `Variant ${idx}`,
        sku: `${baseSku}-V${idx}`,
        barcode: '',
        attributes: {},
        cost_price: Number(form.cost_price) || 0,
        selling_price: Number(form.selling_price) || 0,
        total_stock: 0,
        is_active: true,
    });
}

function removeVariant(index) {
    form.variants.splice(index, 1);
}

function applyDefaultPricesToAllVariants() {
    const baseCost = Number(form.cost_price) || 0;
    const basePrice = Number(form.selling_price) || 0;
    form.variants.forEach(v => {
        v.cost_price = baseCost;
        v.selling_price = basePrice;
    });
}

// ==========================================
// AUTO-UPDATE VARIANT SKU FROM VARIANT NAME
// ==========================================
function formatVariantSkuSuffix(name) {
    if (!name) return '';
    return name
        .split(/[\/\s,]+/)
        .filter(Boolean)
        .map(p => p.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase())
        .filter(Boolean)
        .join('-');
}

function onVariantNameInput(v, idx) {
    if (v.isSkuCustom) return;
    const base = (form.sku || props.product.sku || 'PROD').trim().toUpperCase();
    const suffix = formatVariantSkuSuffix(v.name);
    v.sku = suffix ? `${base}-${suffix}` : `${base}-V${idx + 1}`;
}

function onVariantSkuInput(v) {
    v.isSkuCustom = true;
}

function resetVariantSkuToName(v, idx) {
    v.isSkuCustom = false;
    const base = (form.sku || props.product.sku || 'PROD').trim().toUpperCase();
    const suffix = formatVariantSkuSuffix(v.name);
    v.sku = suffix ? `${base}-${suffix}` : `${base}-V${idx + 1}`;
}

watch(() => form.sku, (newSku) => {
    const base = (newSku || props.product.sku || 'PROD').trim().toUpperCase();
    form.variants.forEach((v, idx) => {
        if (!v.isSkuCustom) {
            const suffix = formatVariantSkuSuffix(v.name);
            v.sku = suffix ? `${base}-${suffix}` : `${base}-V${idx + 1}`;
        }
    });
});

function submit() {
    form.post(route('products.update.post', props.product.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Product: ${product.name}`" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('products.index')" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Edit Product</h1>
                        <p class="text-xs text-slate-500 font-medium font-mono">{{ product.sku }} &bull; {{ product.name }}</p>
                    </div>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. General Product Information -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                    Product Identity
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
                        <input 
                            type="text" 
                            v-model="form.name" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                        <p v-if="form.errors.name" class="text-[11px] text-rose-600 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Product Image Upload -->
                    <div class="md:col-span-2 pt-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Photo / Thumbnail</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                <img
                                    v-if="imagePreview && !imageLoadFailed"
                                    :src="imagePreview"
                                    @error="imageLoadFailed = true"
                                    alt="Preview"
                                    class="w-full h-full object-cover"
                                />
                                <svg v-else class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageChange"
                                        class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                    />
                                    <button
                                        v-if="imagePreview"
                                        type="button"
                                        @click="removeImage"
                                        class="px-2.5 py-1 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors font-medium border border-rose-200"
                                    >
                                        Remove Photo
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">PNG, JPG, or WebP up to 3MB. Displayed on the POS terminal cards.</p>
                                <p v-if="form.errors.image" class="text-[11px] text-rose-600 mt-0.5">{{ form.errors.image }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SKU -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Stock Keeping Unit (SKU) *</label>
                        <input 
                            type="text" 
                            v-model="form.sku" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold uppercase border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                        <p v-if="form.errors.sku" class="text-[11px] text-rose-600 mt-1">{{ form.errors.sku }}</p>
                    </div>

                    <!-- Barcode / UPC -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-slate-700">Barcode / UPC / EAN</label>
                            <span v-if="barcodeWarning" class="text-[10px] font-bold text-rose-600 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ barcodeWarning }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                v-model="form.barcode" 
                                @blur="checkBarcodeDuplicate(form.barcode)"
                                placeholder="Scan package or click Generate..."
                                class="flex-1 px-3 py-2 rounded-xl text-xs font-mono border focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                                :class="barcodeWarning ? 'border-rose-400 bg-rose-50/50' : 'border-slate-200'"
                            />
                            <!-- Camera Scan Button -->
                            <button 
                                type="button" 
                                @click="isCameraModalOpen = true"
                                title="Scan Barcode with Camera"
                                class="px-3 py-2 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 text-xs font-semibold rounded-xl transition-colors border border-slate-200 flex items-center gap-1.5 shrink-0"
                            >
                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Scan</span>
                            </button>
                            <!-- Generate Button -->
                            <button 
                                type="button" 
                                @click="generateBarcode"
                                title="Generate Random Barcode"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors border border-slate-200 shrink-0"
                            >
                                Generate
                            </button>
                        </div>
                        <p v-if="form.errors.barcode" class="text-[11px] text-rose-600 mt-1">{{ form.errors.barcode }}</p>
                    </div>
                </div>

                <!-- Category, Brand, Unit -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <!-- Category -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-slate-700">Category</label>
                            <button 
                                type="button" 
                                @click="openQuickCategory"
                                class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                            >
                                + Quick Add
                            </button>
                        </div>
                        <select 
                            v-model="form.category_id" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">Uncategorized</option>
                            <option v-for="cat in categoriesList" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <!-- Brand -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-slate-700">Brand / Maker</label>
                            <button 
                                type="button" 
                                @click="openQuickBrand"
                                class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800"
                            >
                                + Quick Add
                            </button>
                        </div>
                        <select 
                            v-model="form.brand_id" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">No Brand</option>
                            <option v-for="b in brandsList" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Unit of Measure *</label>
                        <select 
                            v-model="form.unit" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 uppercase"
                        >
                            <option value="pcs">Pieces (PCS)</option>
                            <option value="box">Box (BOX)</option>
                            <option value="set">Set (SET)</option>
                            <option value="kg">Kilogram (KG)</option>
                            <option value="m">Meter (M)</option>
                            <option value="pkt">Packet (PKT)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Pricing & Margin Radar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                    Pricing & Profit Margin Calculator
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cost Price (Purchase/COGS) *</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            v-model="form.cost_price" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price (Unit Rate) *</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            v-model="form.selling_price" 
                            required
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold text-indigo-600 border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                    </div>

                    <!-- Calculated Margin Card -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Profit Margin</span>
                        <div class="flex items-baseline justify-between mt-1">
                            <span class="text-sm font-black font-mono text-emerald-600">
                                <CurrencyDisplay :amount="profitMargin" />
                            </span>
                            <span 
                                class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="marginPercentage >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ marginPercentage }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Warranty & Unit Serial Tracking -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                    Warranty & Serial Tracking
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warranty Coverage (Days)</label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="number" 
                                v-model="form.warranty_days" 
                                min="0"
                                class="w-28 px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                            <button 
                                type="button" 
                                @click="setWarrantyPreset(0)" 
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border border-slate-200 hover:bg-slate-100"
                            >
                                None
                            </button>
                            <button 
                                type="button" 
                                @click="setWarrantyPreset(365)" 
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border border-slate-200 hover:bg-slate-100"
                            >
                                1 Year
                            </button>
                            <button 
                                type="button" 
                                @click="setWarrantyPreset(730)" 
                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border border-slate-200 hover:bg-slate-100"
                            >
                                2 Years
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-white transition-all">
                            <input 
                                type="checkbox" 
                                v-model="form.is_serialized" 
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Unit Serial Number Tracking</span>
                                <span class="text-[10px] text-slate-500">Require individual unique serial number ingestion on receipt & dispatch</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Reorder Alert Level</label>
                        <input 
                            type="number" 
                            v-model="form.reorder_level" 
                            min="0"
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Reorder Suggested Quantity</label>
                        <input 
                            type="number" 
                            v-model="form.reorder_quantity" 
                            min="0"
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                    </div>
                </div>
            </div>

            <!-- Product Variants & Attributes Generator Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-100">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>
                            Product Variants & Options
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Manage individual product variations (e.g., sizes, colors, capacities) with distinct SKUs and price points.
                        </p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input 
                            type="checkbox" 
                            :checked="form.has_variants" 
                            @change="toggleVariants"
                            class="sr-only peer"
                        />
                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-violet-600 relative"></div>
                        <span class="text-xs font-bold" :class="form.has_variants ? 'text-violet-700' : 'text-slate-500'">
                            {{ form.has_variants ? 'Variants Enabled' : 'Single Product' }}
                        </span>
                    </label>
                </div>

                <div v-if="form.errors.variants" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ form.errors.variants }}</span>
                </div>

                <!-- When Variants Enabled -->
                <div v-if="form.has_variants" class="space-y-6">
                    <!-- Option Tags Generator Section -->
                    <div class="p-4 rounded-xl bg-violet-50/50 border border-violet-100 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-bold text-violet-950 uppercase tracking-wider">
                                Define New Options & Generate Matrix
                            </div>
                            <button
                                type="button"
                                @click="generateVariantMatrix"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 shadow-xs cursor-pointer transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Generate Combinations</span>
                            </button>
                        </div>

                        <!-- Options List -->
                        <div class="space-y-3">
                            <div 
                                v-for="(opt, optIdx) in variantOptions" 
                                :key="optIdx" 
                                class="p-3 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-800">{{ opt.name }}</span>
                                    <button 
                                        type="button" 
                                        @click="removeOption(optIdx)" 
                                        class="text-slate-400 hover:text-rose-600 text-xs cursor-pointer"
                                    >
                                        Remove
                                    </button>
                                </div>

                                <!-- Value Tags -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span 
                                        v-for="(val, valIdx) in opt.values" 
                                        :key="valIdx"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-violet-50 text-violet-700 border border-violet-200"
                                    >
                                        {{ val }}
                                        <button 
                                            type="button" 
                                            @click="removeOptionValue(optIdx, valIdx)" 
                                            class="text-violet-400 hover:text-rose-600 ml-0.5"
                                        >
                                            &times;
                                        </button>
                                    </span>

                                    <!-- Add Value Input -->
                                    <div class="inline-flex items-center gap-1">
                                        <input 
                                            type="text" 
                                            v-model="newOptionValueInputs[optIdx]"
                                            @keydown.enter.prevent="addOptionValue(optIdx)"
                                            :placeholder="`Add ${opt.name}...`"
                                            class="w-28 px-2 py-1 text-xs rounded-lg border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                        />
                                        <button 
                                            type="button" 
                                            @click="addOptionValue(optIdx)"
                                            class="px-2 py-1 text-xs font-bold text-violet-700 bg-violet-100 hover:bg-violet-200 rounded-lg cursor-pointer"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Option Row -->
                        <div class="flex items-center gap-2 pt-1">
                            <input 
                                type="text" 
                                v-model="newOptionName" 
                                @keydown.enter.prevent="addOption"
                                placeholder="New option name (e.g. Material, Storage)..." 
                                class="flex-1 max-w-xs px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                            />
                            <button 
                                type="button" 
                                @click="addOption" 
                                class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 cursor-pointer"
                            >
                                + Add Option
                            </button>
                        </div>
                    </div>

                    <!-- Variants Matrix Table -->
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                    Variants Matrix ({{ form.variants.length }} Variants)
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Each variant has its own unique SKU, barcode, and pricing.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="applyDefaultPricesToAllVariants"
                                    class="text-[11px] font-semibold text-slate-600 hover:text-indigo-600 px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
                                >
                                    Sync Base Prices
                                </button>
                                <button
                                    type="button"
                                    @click="addSingleVariant"
                                    class="text-[11px] font-bold text-violet-700 bg-violet-50 hover:bg-violet-100 border border-violet-200 px-3 py-1 rounded-lg cursor-pointer"
                                >
                                    + Add Variant
                                </button>
                            </div>
                        </div>

                        <div v-if="form.variants.length > 0" class="overflow-x-auto border border-slate-200/80 rounded-xl custom-scrollbar">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        <th class="py-2.5 px-3">Variant Name *</th>
                                        <th class="py-2.5 px-3">SKU *</th>
                                        <th class="py-2.5 px-3">Barcode</th>
                                        <th class="py-2.5 px-3 w-28">Cost Price</th>
                                        <th class="py-2.5 px-3 w-28">Selling Price</th>
                                        <th class="py-2.5 px-3 text-center w-24">Current Stock</th>
                                        <th class="py-2.5 px-2 text-center w-12">Active</th>
                                        <th class="py-2.5 px-2 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(v, vIdx) in form.variants" :key="vIdx" class="hover:bg-slate-50/50">
                                        <td class="py-2 px-3">
                                            <input 
                                                type="text" 
                                                v-model="v.name" 
                                                @input="onVariantNameInput(v, vIdx)"
                                                required
                                                placeholder="e.g. Red / XL"
                                                class="w-full px-2 py-1 text-xs font-semibold rounded-lg border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                            />
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="relative flex items-center">
                                                <input 
                                                    type="text" 
                                                    v-model="v.sku" 
                                                    @input="onVariantSkuInput(v)"
                                                    required
                                                    placeholder="SKU"
                                                    class="w-full px-2 py-1 text-xs font-mono font-bold uppercase rounded-lg border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 pr-6"
                                                />
                                                <button 
                                                    v-if="v.isSkuCustom"
                                                    type="button" 
                                                    @click="resetVariantSkuToName(v, vIdx)" 
                                                    title="Auto-update SKU from variant name"
                                                    class="absolute right-1 text-slate-400 hover:text-violet-600 p-0.5 cursor-pointer"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <input 
                                                type="text" 
                                                v-model="v.barcode" 
                                                placeholder="Barcode"
                                                class="w-full px-2 py-1 text-xs font-mono rounded-lg border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                            />
                                        </td>
                                        <td class="py-2 px-3">
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                v-model="v.cost_price" 
                                                class="w-full px-2 py-1 text-xs font-mono rounded-lg border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                            />
                                        </td>
                                        <td class="py-2 px-3">
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                v-model="v.selling_price" 
                                                class="w-full px-2 py-1 text-xs font-mono font-bold text-indigo-600 rounded-lg border border-slate-200 focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                                            />
                                        </td>
                                        <td class="py-2 px-3 text-center align-middle font-mono">
                                            <span 
                                                class="inline-block px-2 py-0.5 rounded text-[11px] font-bold"
                                                :class="(v.total_stock ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500'"
                                            >
                                                {{ v.total_stock ?? 0 }} {{ form.unit }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-2 text-center align-middle">
                                            <input 
                                                type="checkbox" 
                                                v-model="v.is_active" 
                                                class="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                            />
                                        </td>
                                        <td class="py-2 px-2 text-center align-middle">
                                            <button 
                                                type="button" 
                                                @click="removeVariant(vIdx)" 
                                                title="Remove Variant"
                                                class="p-1 text-slate-400 hover:text-rose-600 rounded-md transition cursor-pointer"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-8 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
                            <p class="text-xs text-slate-500">No variants defined.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Click "Generate Combinations" above or "+ Add Variant" to build your variant matrix.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <Link 
                    :href="route('products.index')" 
                    class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors"
                >
                    Cancel
                </Link>
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                >
                    Update Product
                </button>
            </div>
        </form>

        <!-- Quick Add Category Modal -->
        <Modal :show="isQuickCategoryOpen" @close="isQuickCategoryOpen = false" maxWidth="sm">
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-900 text-sm">Quick Add Category</h3>
                    <button type="button" @click="isQuickCategoryOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Category Name *</label>
                        <input 
                            type="text" 
                            v-model="quickCategoryName" 
                            @keydown.enter.prevent="submitQuickCategory"
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            :disabled="quickCategoryLoading"
                            autofocus
                        />
                        <p v-if="quickCategoryError" class="text-[11px] text-rose-600 mt-1 font-medium">{{ quickCategoryError }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button 
                            type="button" 
                            @click="isQuickCategoryOpen = false" 
                            :disabled="quickCategoryLoading"
                            class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-800 font-semibold"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            @click="submitQuickCategory" 
                            :disabled="quickCategoryLoading || !quickCategoryName.trim()"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-sm"
                        >
                            <svg v-if="quickCategoryLoading" class="animate-spin -ml-0.5 mr-1 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ quickCategoryLoading ? 'Saving...' : 'Add & Select' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Quick Add Brand Modal -->
        <Modal :show="isQuickBrandOpen" @close="isQuickBrandOpen = false" maxWidth="sm">
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-900 text-sm">Quick Add Brand</h3>
                    <button type="button" @click="isQuickBrandOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Brand / Maker Name *</label>
                        <input 
                            type="text" 
                            v-model="quickBrandName" 
                            @keydown.enter.prevent="submitQuickBrand"
class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            :disabled="quickBrandLoading"
                            autofocus
                        />
                        <p v-if="quickBrandError" class="text-[11px] text-rose-600 mt-1 font-medium">{{ quickBrandError }}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button 
                            type="button" 
                            @click="isQuickBrandOpen = false" 
                            :disabled="quickBrandLoading"
                            class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-800 font-semibold"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            @click="submitQuickBrand" 
                            :disabled="quickBrandLoading || !quickBrandName.trim()"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-sm"
                        >
                            <svg v-if="quickBrandLoading" class="animate-spin -ml-0.5 mr-1 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ quickBrandLoading ? 'Saving...' : 'Add & Select' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Camera Barcode Scanner Modal -->
        <BarcodeCameraModal
            v-model:open="isCameraModalOpen"
            @scan="handleBarcodeScanned"
        />
    </AppLayout>
</template>
