<script setup>
import { ref, computed } from 'vue';
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
    is_serialized: Boolean(props.product.is_serialized),
    is_active: Boolean(props.product.is_active),
    image: null,
    remove_image: false,
    description: props.product.description || '',
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
