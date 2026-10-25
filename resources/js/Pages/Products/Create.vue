<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';

const props = defineProps({
    categories: Array,
    brands: Array,
    warehouses: Array,
    defaultWarehouseId: Number,
    initialSku: String,
});

const form = useForm({
    name: '',
    sku: props.initialSku || '',
    barcode: '',
    category_id: '',
    brand_id: '',
    unit: 'pcs',
    cost_price: 0,
    selling_price: 0,
    reorder_level: 10,
    reorder_quantity: 50,
    warranty_days: 0,
    is_serialized: false,
    is_active: true,
    description: '',
    initial_warehouse_id: props.defaultWarehouseId || '',
    initial_stock_quantity: 0,
});

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

// Profit Margin Computations
const profitMargin = computed(() => {
    return (Number(form.selling_price) || 0) - (Number(form.cost_price) || 0);
});

const marginPercentage = computed(() => {
    const sp = Number(form.selling_price) || 0;
    if (sp <= 0) return 0;
    return Math.round((profitMargin.value / sp) * 1000) / 10;
});

function generateSku() {
    let prefix = 'PROD';
    if (form.name && form.name.length >= 3) {
        prefix = form.name.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase();
    }
    const rand = Math.random().toString(36).substring(2, 7).toUpperCase();
    form.sku = `${prefix}-${rand}`;
}

function setWarrantyPreset(days) {
    form.warranty_days = days;
}

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

function onNameBlur() {
    if (!form.sku) {
        generateSku();
    }
}

function submit() {
    form.post(route('products.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Create Product" />

        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('products.index')" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Add New Product</h1>
                        <p class="text-xs text-slate-500 font-medium">Create a catalog item with dynamic SKU, profit margins, and opening stock</p>
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
                            @blur="onNameBlur"
required
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
                        <p v-if="form.errors.name" class="text-[11px] text-rose-600 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- SKU with Auto Generator -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Stock Keeping Unit (SKU) *</label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                v-model="form.sku" 
                                required
                                class="flex-1 px-3 py-2 rounded-xl text-xs font-mono font-bold uppercase border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                            />
                            <button 
                                type="button" 
                                @click="generateSku"
                                title="Regenerate SKU"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors"
                            >
                                Generate
                            </button>
                        </div>
                        <p v-if="form.errors.sku" class="text-[11px] text-rose-600 mt-1">{{ form.errors.sku }}</p>
                    </div>

                    <!-- Barcode / UPC -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Barcode / UPC / EAN</label>
                        <input 
                            type="text" 
                            v-model="form.barcode" 
class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400 placeholder:text-xs"
                        />
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

                    <!-- Unit of Measure -->
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
            </div>

            <!-- 4. Opening Stock Allocation (Initial Creation Only) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px] text-slate-400">
                    Opening Stock Allocation (Optional)
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Fulfillment Hub</label>
                        <select 
                            v-model="form.initial_warehouse_id" 
                            class="w-full px-3 py-2 rounded-xl text-xs border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        >
                            <option value="">Select Warehouse Hub</option>
                            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                {{ wh.name }} {{ wh.is_default ? '(Default)' : '' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Opening Quantity</label>
                        <input 
                            type="number" 
                            step="1" 
                            min="0"
                            v-model="form.initial_stock_quantity" 
                            class="w-full px-3 py-2 rounded-xl text-xs font-mono font-bold border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        />
                        <p class="text-[10px] text-slate-400 mt-1">Automatically logs opening ledger transaction upon creation</p>
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
                    Save & Create Product
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
    </AppLayout>
</template>
