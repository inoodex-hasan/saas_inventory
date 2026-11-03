<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import CurrencyDisplay from '@/Components/CurrencyDisplay.vue';
import axios from 'axios';

const props = defineProps({
    warehouses: {
        type: Array,
        default: () => [],
    },
    selectedWarehouseId: {
        type: Number,
        default: 0,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    brands: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    defaultCustomerId: {
        type: Number,
        default: null,
    },
    financialAccounts: {
        type: Array,
        default: () => [],
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    nextOrderNumber: {
        type: String,
        default: '#POS-0001',
    },
});

// State
const activeWarehouseId = ref(props.selectedWarehouseId || props.warehouses[0]?.id || null);
const selectedCategory = ref('all');
const selectedBrand = ref('all');
const searchQuery = ref('');
const searchInput = ref(null);

// Toast / Alert Notification System
const toast = ref({
    show: false,
    message: '',
    type: 'error',
    timer: null,
});

function showToast(message, type = 'error') {
    if (toast.value.timer) clearTimeout(toast.value.timer);
    toast.value.message = message;
    toast.value.type = type;
    toast.value.show = true;
    toast.value.timer = setTimeout(() => {
        toast.value.show = false;
    }, 4000);
}

// Clock
const currentTime = ref('');
let clockTimer = null;
function updateClock() {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// Customers
const customersList = ref([...props.customers]);
const activeCustomerId = ref(props.defaultCustomerId || (props.customers[0]?.id ?? null));
const customerSearch = ref('');
const isCustomerDropdownOpen = ref(false);

const selectedCustomer = computed(() => {
    return customersList.value.find(c => c.id === activeCustomerId.value) || null;
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value) return customersList.value.slice(0, 30);
    const q = customerSearch.value.toLowerCase();
    return customersList.value.filter(c =>
        c.name.toLowerCase().includes(q) ||
        (c.phone && c.phone.includes(q))
    ).slice(0, 30);
});

function selectCustomer(cust) {
    activeCustomerId.value = cust.id;
    isCustomerDropdownOpen.value = false;
    customerSearch.value = '';
}

// Financial Accounts
const selectedAccountId = ref(
    props.financialAccounts.find(a => a.is_default)?.id || props.financialAccounts[0]?.id || null
);

// Cart
const cart = ref([]);
const discountType = ref('fixed'); // 'fixed' or 'percent'
const discountValue = ref(0);
const taxPercent = ref(0);
const shippingCost = ref(0);
const roundToInteger = ref(true);
const tenderedAmount = ref(0);
const notes = ref('');

// Load saved cart from localStorage if exists
function loadSavedCart() {
    try {
        const saved = localStorage.getItem('pos_active_cart');
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed) && parsed.length > 0) {
                cart.value = parsed;
            }
        }
    } catch (e) {}
}

function persistCart() {
    try {
        localStorage.setItem('pos_active_cart', JSON.stringify(cart.value));
    } catch (e) {}
}

watch(cart, persistCart, { deep: true });

watch(() => props.products, (newProducts) => {
    if (!newProducts || !Array.isArray(newProducts)) return;
    cart.value.forEach(item => {
        const prod = newProducts.find(p => p.id === item.product_id);
        if (prod) {
            if (item.product_variant_id) {
                const variant = prod.variants?.find(v => v.id === item.product_variant_id);
                if (variant) {
                    item.stock = variant.stock;
                }
            } else {
                item.stock = prod.stock;
            }
        }
    });
}, { deep: true });

// Filtered Products
const filteredProducts = computed(() => {
    let list = props.products;

    if (selectedCategory.value !== 'all') {
        list = list.filter(p => p.category_id === parseInt(selectedCategory.value));
    }

    if (selectedBrand.value !== 'all') {
        list = list.filter(p => p.brand_name === selectedBrand.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.trim().toLowerCase();
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.sku && p.sku.toLowerCase().includes(q)) ||
            (p.barcode && p.barcode.toLowerCase().includes(q))
        );
    }

    return list;
});

// Image fallback handler
const failedImages = ref(new Set());
function onImageError(id) {
    failedImages.value.add(id);
}
function hasValidImage(p) {
    return p.image_url && !failedImages.value.has(p.id);
}

// Variant Selection Modal
const isVariantModalOpen = ref(false);
const activeProductForVariant = ref(null);

function openVariantModal(p) {
    activeProductForVariant.value = p;
    isVariantModalOpen.value = true;
}

function selectVariant(p, v) {
    const vStock = Number(v.stock ?? 0);
    if (vStock <= 0) {
        showToast(`Variant '${v.name}' is currently out of stock in this fulfillment store!`, 'error');
        return;
    }
    addToCart(p, v);
    isVariantModalOpen.value = false;
}

// Add to Cart
function handleProductClick(p) {
    if (p.has_variants && p.variants && p.variants.length > 0) {
        openVariantModal(p);
    } else {
        const pStock = Number(p.stock ?? 0);
        if (pStock <= 0) {
            showToast(`'${p.name}' is currently out of stock in this fulfillment store!`, 'error');
            return;
        }
        addToCart(p, null);
    }
}

function addToCart(p, variant = null) {
    const availableStock = Number(variant ? (variant.stock ?? 0) : (p.stock ?? 0));
    const itemName = variant ? `${p.name} (${variant.name})` : p.name;

    if (availableStock <= 0) {
        showToast(`'${itemName}' is currently out of stock!`, 'error');
        return;
    }

    const key = variant ? `${p.id}-v${variant.id}` : `${p.id}-simple`;
    const existing = cart.value.find(item => item.key === key);

    if (existing) {
        if (existing.quantity >= availableStock) {
            showToast(`Cannot add more. Stock limit of ${availableStock} ${existing.unit} reached for '${itemName}'.`, 'error');
            return;
        }
        existing.quantity += 1;
    } else {
        const price = variant ? variant.unit_price : p.unit_price;
        cart.value.push({
            key,
            product_id: p.id,
            product_variant_id: variant ? variant.id : null,
            name: p.name,
            variant_name: variant ? variant.name : null,
            attributes: variant ? variant.attributes : null,
            sku: variant ? variant.sku : p.sku,
            unit_price: price,
            cost_price: p.cost_price,
            quantity: 1,
            warranty_days: p.warranty_days || 0,
            stock: availableStock,
            unit: p.unit || 'pcs',
            image_url: p.image_url,
        });
    }

    if (searchQuery.value) {
        searchQuery.value = '';
    }
}

function updateQuantity(item, delta) {
    const maxStock = item.stock !== undefined ? Number(item.stock) : Infinity;
    const newQty = Number((item.quantity + delta).toFixed(2));
    const itemName = item.variant_name ? `${item.name} (${item.variant_name})` : item.name;

    if (delta > 0 && newQty > maxStock) {
        showToast(`Cannot increase quantity. Only ${maxStock} ${item.unit} available for '${itemName}'.`, 'error');
        return;
    }

    item.quantity = Math.max(1, newQty);
}

function validateItemQuantity(item) {
    const maxStock = item.stock !== undefined ? Number(item.stock) : Infinity;
    const itemName = item.variant_name ? `${item.name} (${item.variant_name})` : item.name;

    if (!item.quantity || item.quantity < 1) {
        item.quantity = 1;
        return;
    }

    if (item.quantity > maxStock) {
        showToast(`Quantity exceeded available stock! Automatically set to maximum (${maxStock} ${item.unit}) for '${itemName}'.`, 'error');
        item.quantity = maxStock;
    }
}

function removeCartItem(index) {
    cart.value.splice(index, 1);
}

const showClearCartModal = ref(false);

function clearCart() {
    if (cart.value.length === 0) return;
    showClearCartModal.value = true;
}

function executeClearCart() {
    cart.value = [];
    discountValue.value = 0;
    taxPercent.value = 0;
    shippingCost.value = 0;
    tenderedAmount.value = 0;
    notes.value = '';
    localStorage.removeItem('pos_active_cart');
    showClearCartModal.value = false;
}

// Calculations
const subtotal = computed(() => {
    return cart.value.reduce((acc, it) => acc + (it.quantity * it.unit_price), 0);
});

const discountAmount = computed(() => {
    const sub = subtotal.value;
    const val = parseFloat(discountValue.value) || 0;
    if (discountType.value === 'percent') {
        return Math.min(sub, (sub * val) / 100);
    }
    return Math.min(sub, val);
});

const taxAmount = computed(() => {
    const afterDiscount = Math.max(0, subtotal.value - discountAmount.value);
    const pct = parseFloat(taxPercent.value) || 0;
    return (afterDiscount * pct) / 100;
});

const calculatedTotal = computed(() => {
    const raw = Math.max(0, subtotal.value - discountAmount.value + taxAmount.value + (parseFloat(shippingCost.value) || 0));
    return roundToInteger.value ? Math.round(raw) : Number(raw.toFixed(2));
});

const roundingDiff = computed(() => {
    const raw = Math.max(0, subtotal.value - discountAmount.value + taxAmount.value + (parseFloat(shippingCost.value) || 0));
    return calculatedTotal.value - raw;
});

const changeToReturn = computed(() => {
    const tendered = parseFloat(tenderedAmount.value) || 0;
    return Math.max(0, tendered - calculatedTotal.value);
});

// Quick Cash Buttons
function setTendered(amount) {
    if (amount === 'exact') {
        tenderedAmount.value = calculatedTotal.value;
    } else {
        tenderedAmount.value = amount;
    }
}

// Park / Hold Cart
const parkedCarts = ref([]);
const isParkedModalOpen = ref(false);

function loadParkedCarts() {
    try {
        const stored = localStorage.getItem('pos_parked_carts');
        if (stored) {
            parkedCarts.value = JSON.parse(stored);
        }
    } catch (e) {}
}

function parkCurrentCart() {
    if (cart.value.length === 0) return;
    const parkObj = {
        id: Date.now(),
        date: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        items: [...cart.value],
        subtotal: subtotal.value,
        total: calculatedTotal.value,
        customer_id: activeCustomerId.value,
        customer_name: selectedCustomer.value?.name || 'Walk-in Customer',
    };
    parkedCarts.value.unshift(parkObj);
    localStorage.setItem('pos_parked_carts', JSON.stringify(parkedCarts.value));
    cart.value = [];
    localStorage.removeItem('pos_active_cart');
}

function restoreParkedCart(park) {
    cart.value = park.items;
    activeCustomerId.value = park.customer_id;
    parkedCarts.value = parkedCarts.value.filter(p => p.id !== park.id);
    localStorage.setItem('pos_parked_carts', JSON.stringify(parkedCarts.value));
    isParkedModalOpen.value = false;
}

function deleteParkedCart(id) {
    parkedCarts.value = parkedCarts.value.filter(p => p.id !== id);
    localStorage.setItem('pos_parked_carts', JSON.stringify(parkedCarts.value));
}

// Recent Invoices Modal
const isRecentModalOpen = ref(false);

// Quick Add Customer by Mobile
const isAddingCustomer = ref(false);
const customerPhoneInput = ref('');
const customerPhoneInputRef = ref(null);
const isSavingCustomer = ref(false);

function enableAddingCustomer() {
    isAddingCustomer.value = true;
    isCustomerDropdownOpen.value = false;
    customerPhoneInput.value = '';
    nextTick(() => {
        customerPhoneInputRef.value?.focus();
    });
}

function cancelAddingCustomer() {
    isAddingCustomer.value = false;
    customerPhoneInput.value = '';
}

function saveQuickCustomerPhone() {
    const phone = customerPhoneInput.value.trim();
    if (!phone) return;

    isSavingCustomer.value = true;
    axios.post(route('pos.customer.store'), { phone })
        .then(res => {
            if (res.data.success && res.data.customer) {
                const cust = res.data.customer;
                const exists = customersList.value.find(c => c.id === cust.id);
                if (!exists) {
                    customersList.value.unshift(cust);
                }
                activeCustomerId.value = cust.id;
                isAddingCustomer.value = false;
                customerPhoneInput.value = '';
            }
        })
        .catch(err => {
            alert(err.response?.data?.message || 'Failed to save customer phone.');
        })
        .finally(() => {
            isSavingCustomer.value = false;
        });
}

// Checkout & Submit
const isProcessingSale = ref(false);
const checkoutError = ref('');
const isSuccessModalOpen = ref(false);
const lastCompletedOrder = ref(null);

function completeSale() {
    if (cart.value.length === 0) {
        showToast('Cart is empty. Please add items before checking out.', 'error');
        return;
    }

    // Verify stock availability for all cart items
    for (const it of cart.value) {
        const itemName = it.variant_name ? `${it.name} (${it.variant_name})` : it.name;
        if (it.stock !== undefined && it.quantity > it.stock) {
            showToast(`Cannot checkout: '${itemName}' quantity (${it.quantity}) exceeds available stock (${it.stock}).`, 'error');
            return;
        }
        if (it.stock !== undefined && it.stock <= 0) {
            showToast(`Cannot checkout: '${itemName}' is currently out of stock.`, 'error');
            return;
        }
    }

    isProcessingSale.value = true;
    checkoutError.value = '';

    const payload = {
        warehouse_id: activeWarehouseId.value,
        customer_id: isAddingCustomer.value && customerPhoneInput.value.trim() ? null : activeCustomerId.value,
        customer_phone: isAddingCustomer.value && customerPhoneInput.value.trim() ? customerPhoneInput.value.trim() : null,
        items: cart.value.map(it => ({
            product_id: it.product_id,
            product_variant_id: it.product_variant_id,
            quantity: it.quantity,
            unit_price: it.unit_price,
            warranty_days: it.warranty_days,
        })),
        subtotal: subtotal.value,
        discount_amount: discountAmount.value,
        tax_amount: taxAmount.value,
        shipping_cost: parseFloat(shippingCost.value) || 0,
        total_amount: calculatedTotal.value,
        paid_amount: calculatedTotal.value,
        tendered_amount: parseFloat(tenderedAmount.value) || calculatedTotal.value,
        financial_account_id: selectedAccountId.value,
        notes: notes.value,
    };

    axios.post(route('pos.store'), payload)
        .then(res => {
            if (res.data.success) {
                lastCompletedOrder.value = {
                    order_id: res.data.order_id,
                    order_number: res.data.order_number,
                    total_amount: res.data.total_amount,
                    paid_amount: res.data.paid_amount,
                    tendered: parseFloat(tenderedAmount.value) || calculatedTotal.value,
                    change: Math.max(0, (parseFloat(tenderedAmount.value) || calculatedTotal.value) - calculatedTotal.value),
                    pos_slip_url: res.data.pos_slip_url,
                    invoice_url: res.data.invoice_url,
                    pos_invoice_page_url: res.data.pos_invoice_page_url || route('pos.invoice', res.data.order_id),
                };

                // Clear current cart
                cart.value = [];
                discountValue.value = 0;
                taxPercent.value = 0;
                shippingCost.value = 0;
                tenderedAmount.value = 0;
                notes.value = '';
                isAddingCustomer.value = false;
                customerPhoneInput.value = '';
                localStorage.removeItem('pos_active_cart');

                isSuccessModalOpen.value = true;
            }
        })
        .catch(err => {
            checkoutError.value = err.response?.data?.message || 'An error occurred during checkout.';
            showToast(checkoutError.value, 'error');
        })
        .finally(() => {
            isProcessingSale.value = false;
        });
}

function startNewSale() {
    isSuccessModalOpen.value = false;
    lastCompletedOrder.value = null;
    isAddingCustomer.value = false;
    customerPhoneInput.value = '';
    nextTick(() => {
        searchInput.value?.focus();
    });
}

// Print Handlers
function printThermalReceipt(url) {
    // Open high-speed 0ms HTML receipt preview with 1-click thermal printing and PDF download
    const targetUrl = url ? (url.includes('?') ? `${url}&preview=1` : `${url}?preview=1`) : url;
    const win = window.open(targetUrl, '_blank', 'width=460,height=720,menubar=no,toolbar=no,location=no,status=no');
    if (win) {
        win.focus();
    }
}

function printStandardInvoice(url) {
    const win = window.open(url, '_blank');
    if (win) {
        win.focus();
    }
}

// Keyboard Shortcuts
function handleGlobalKeydown(e) {
    if (e.key === 'F2') {
        e.preventDefault();
        searchInput.value?.focus();
    } else if (e.key === 'F8') {
        e.preventDefault();
        if (!isSuccessModalOpen.value && cart.value.length > 0) {
            completeSale();
        }
    } else if (e.key === 'F4') {
        e.preventDefault();
        parkCurrentCart();
    } else if (e.key === 'Escape') {
        isVariantModalOpen.value = false;
        isAddingCustomer.value = false;
        isParkedModalOpen.value = false;
        isRecentModalOpen.value = false;
        isCustomerDropdownOpen.value = false;
    } else if (e.key === 'Enter' && isSuccessModalOpen.value) {
        e.preventDefault();
        startNewSale();
    } else if (e.key === 'Enter' && e.target === searchInput.value && searchQuery.value.trim()) {
        const exactMatch = props.products.find(p =>
            (p.barcode && p.barcode.toLowerCase() === searchQuery.value.trim().toLowerCase()) ||
            (p.sku && p.sku.toLowerCase() === searchQuery.value.trim().toLowerCase())
        );
        if (exactMatch) {
            e.preventDefault();
            handleProductClick(exactMatch);
        }
    }
}

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);
    loadSavedCart();
    loadParkedCarts();
    window.addEventListener('keydown', handleGlobalKeydown);
    nextTick(() => {
        searchInput.value?.focus();
    });
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
    window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <Head title="Point of Sale (POS) Terminal" />

    <div class="h-screen w-screen bg-slate-100 text-slate-800 flex flex-col overflow-hidden font-sans select-none relative">
        <!-- Floating Alert / Toast Notification -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-3 scale-95"
        >
            <div
                v-if="toast.show"
                class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-lg w-[90%] sm:w-auto min-w-[320px] pointer-events-auto"
            >
                <div
                    class="flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-xl border text-xs font-bold"
                    :class="[
                        toast.type === 'error' ? 'bg-rose-600 text-white border-rose-500 shadow-rose-900/30' :
                        toast.type === 'warning' ? 'bg-amber-500 text-white border-amber-400 shadow-amber-900/30' :
                        'bg-emerald-600 text-white border-emerald-500 shadow-emerald-900/30'
                    ]"
                >
                    <svg v-if="toast.type === 'error'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg v-else class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1 leading-snug">{{ toast.message }}</div>
                    <button
                        @click="toast.show = false"
                        class="p-1 hover:bg-black/20 rounded-lg transition shrink-0 ml-1 text-white"
                        title="Dismiss"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </transition>

        <!-- ============================================== -->
        <!-- TOP APP BAR (LIGHT & CLEAN THEME)              -->
        <!-- ============================================== -->
        <header class="h-14 bg-white border-b border-slate-200/90 px-4 flex items-center justify-between shrink-0 z-30 shadow-xs">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-slate-900 border border-slate-200 transition flex items-center gap-1.5 text-xs font-semibold"
                    title="Exit POS & Return to Dashboard"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="hidden sm:inline">Exit POS</span>
                </Link>

                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center font-black text-white text-xs shadow-md shadow-indigo-600/25">
                        POS
                    </div>
                    <div>
                        <div class="text-xs font-extrabold text-slate-900 flex items-center gap-1.5 leading-none">
                            <span>Counter Terminal</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                            Seq: <strong class="text-indigo-600">{{ nextOrderNumber }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Middle: Keyboard Shortcuts Legend -->
            <div class="hidden lg:flex items-center gap-2 text-[11px] text-slate-500">
                <span class="px-2 py-0.5 bg-slate-50 rounded border border-slate-200 font-mono font-bold text-slate-700 shadow-2xs">[F2] Search</span>
                <span class="px-2 py-0.5 bg-slate-50 rounded border border-slate-200 font-mono font-bold text-slate-700 shadow-2xs">[F4] Hold</span>
                <span class="px-2 py-0.5 bg-emerald-50 rounded border border-emerald-200 font-mono font-bold text-emerald-700 shadow-2xs">[F8] Pay</span>
            </div>

            <!-- Header Right: Clock & Triggers -->
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="hidden sm:block text-right">
                    <div class="font-mono text-xs font-bold text-indigo-700 tracking-wider">{{ currentTime }}</div>
                    <div class="text-[9px] text-slate-400 uppercase tracking-widest font-semibold">Terminal Clock</div>
                </div>

                <!-- Parked Carts Trigger -->
                <button
                    @click="isParkedModalOpen = true"
                    class="relative p-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-amber-700 border border-slate-200 transition flex items-center gap-1.5 text-xs font-semibold"
                    title="Parked / Held Carts"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="hidden md:inline">Held Carts</span>
                    <span
                        v-if="parkedCarts.length > 0"
                        class="absolute -top-1 -right-1 px-1.5 py-0.2 rounded-full bg-amber-500 text-white font-black text-[9px] shadow-sm"
                    >
                        {{ parkedCarts.length }}
                    </span>
                </button>

                <!-- Recent Orders Trigger -->
                <button
                    @click="isRecentModalOpen = true"
                    class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 transition flex items-center gap-1.5 text-xs font-semibold"
                    title="Recent Sales & Receipts"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="hidden md:inline">Recent Sales</span>
                </button>
            </div>
        </header>

        <!-- MAIN LAYOUT: LEFT CATALOG + RIGHT CART -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0 bg-slate-100">
            <!-- ==================================================== -->
            <!-- LEFT PANEL: SEARCH & PRODUCT CATALOG WITH IMAGES     -->
            <!-- ==================================================== -->
            <div class="flex-1 flex flex-col overflow-hidden border-r border-slate-200 bg-slate-100">
                <!-- Search & Filters Toolbar -->
                <div class="p-3 bg-white border-b border-slate-200 space-y-2.5 shrink-0 shadow-2xs">
                    <div class="flex items-center gap-2">
                        <!-- Barcode & Search Input -->
                        <div class="relative flex-1">
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                type="text"
                                class="w-full bg-slate-50 border border-slate-200 focus:border-indigo-500 rounded-xl pl-10 pr-9 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:ring-1 focus:ring-indigo-500 font-medium"
                            />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''; searchInput.focus()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                            >
                                &times;
                            </button>
                        </div>

                        <!-- Brand Filter -->
                        <select
                            v-if="brands.length > 0"
                            v-model="selectedBrand"
                            class="bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs py-2 px-3 focus:border-indigo-500 focus:ring-0 max-w-[130px] shrink-0 font-medium"
                        >
                            <option value="all">All Brands</option>
                            <option v-for="b in brands" :key="b.id" :value="b.name">{{ b.name }}</option>
                        </select>
                    </div>

                    <!-- Horizontally Scrollable Category Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 custom-scrollbar text-xs">
                        <button
                            @click="selectedCategory = 'all'"
                            class="px-3 py-1.5 rounded-lg font-bold shrink-0 transition-all text-xs"
                            :class="selectedCategory === 'all' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-50 text-slate-600 hover:bg-slate-200 border border-slate-200'"
                        >
                            All Categories ({{ products.length }})
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            @click="selectedCategory = cat.id"
                            class="px-3 py-1.5 rounded-lg font-semibold shrink-0 transition-all text-xs"
                            :class="selectedCategory === cat.id ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-50 text-slate-600 hover:bg-slate-200 border border-slate-200'"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                </div>

                <!-- Product Catalog Grid with Images -->
                <div class="flex-1 overflow-y-auto p-3 custom-scrollbar">
                    <div
                        v-if="filteredProducts.length > 0"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3"
                    >
                        <div
                            v-for="p in filteredProducts"
                            :key="p.id"
                            @click="handleProductClick(p)"
                            class="group relative bg-white hover:bg-slate-50/80 border border-slate-200/90 hover:border-indigo-400 rounded-2xl p-2.5 flex flex-col justify-between transition-all duration-150 cursor-pointer shadow-xs hover:shadow-md hover:shadow-indigo-500/10 active:scale-[0.98]"
                        >
                            <div>
                                <!-- Product Image Container -->
                                <div class="relative w-full h-28 mb-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center overflow-hidden group-hover:scale-[1.02] transition-transform">
                                    <img
                                        v-if="hasValidImage(p)"
                                        :src="p.image_url"
                                        :alt="p.name"
                                        @error="onImageError(p.id)"
                                        class="w-full h-full object-contain p-1"
                                    />
                                    <!-- Fallback Placeholder when no image -->
                                    <div v-else class="flex flex-col items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ p.category_name }}</span>
                                    </div>

                                    <!-- In-Stock Badge Overlay -->
                                    <span
                                        class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded-md font-black uppercase text-[9px] tracking-tight shadow-xs"
                                        :class="p.stock > 0 ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
                                    >
                                        {{ p.stock > 0 ? `${p.stock}` : 'Out' }}
                                    </span>
                                </div>

                                <!-- Category & Brand -->
                                <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold mb-0.5">
                                    <span class="truncate">{{ p.category_name }}</span>
                                    <span v-if="p.brand_name" class="text-indigo-600 truncate ml-1">{{ p.brand_name }}</span>
                                </div>

                                <!-- Product Title -->
                                <h4 class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 line-clamp-2 leading-tight transition-colors min-h-[2rem]">
                                    {{ p.name }}
                                </h4>

                                <!-- SKU & Warranty info -->
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5 truncate">
                                    {{ p.sku }}
                                    <span v-if="p.warranty_days > 0" class="text-indigo-600 font-sans ml-1">• {{ p.warranty_days }}d War.</span>
                                </div>
                            </div>

                            <!-- Bottom Price & Variant / Add Button -->
                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                                <div class="text-sm font-black text-emerald-700 font-mono">
                                    <CurrencyDisplay :amount="p.unit_price" />
                                </div>
                                <span
                                    v-if="p.has_variants"
                                    class="text-[9px] font-bold px-2 py-0.5 rounded-lg bg-violet-50 text-violet-700 border border-violet-200 shrink-0"
                                >
                                    Variants
                                </span>
                                <span
                                    v-else
                                    class="w-6 h-6 rounded-lg bg-slate-100 group-hover:bg-indigo-600 text-slate-600 group-hover:text-white flex items-center justify-center transition-colors font-bold text-sm shadow-2xs"
                                >
                                    +
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mb-3 text-slate-300 shadow-sm">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-slate-700">No matching products</p>
                        <p class="text-xs text-slate-500 mt-1">Try searching a different keyword or scan a barcode</p>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- RIGHT PANEL: CART & CHECKOUT LANE (CLEAN WHITE) -->
            <!-- ============================================== -->
            <div class="w-full lg:w-[460px] xl:w-[480px] flex flex-col bg-white shrink-0 border-l border-slate-200 shadow-sm">
                <!-- Customer Selection / Quick Mobile Bar -->
                <div class="p-3 bg-white border-b border-slate-200 relative z-20">
                    <div class="flex items-center justify-between gap-2">
                        <!-- Standard Customer Selector (Walk-in Customer / List) -->
                        <div v-if="!isAddingCustomer" class="relative flex-1">
                            <button
                                @click="isCustomerDropdownOpen = !isCustomerDropdownOpen"
                                type="button"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs text-left transition shadow-2xs"
                            >
                                <div class="flex items-center gap-2 truncate">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                                        {{ selectedCustomer?.name?.charAt(0) || 'C' }}
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-slate-900 truncate">{{ selectedCustomer?.name || 'Walk-in Customer' }}</div>
                                        <div class="text-[10px] text-slate-500 truncate">{{ selectedCustomer?.phone || 'Cash Counter' }}</div>
                                    </div>
                                </div>
                                <span class="text-slate-400 text-xs ml-1">&dtrif;</span>
                            </button>

                            <!-- Customer Dropdown Popover -->
                            <div
                                v-if="isCustomerDropdownOpen"
                                class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-2 z-50 text-xs"
                            >
                                <input
                                    v-model="customerSearch"
                                    type="text"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 mb-2 focus:border-indigo-500 focus:ring-0"
                                    autofocus
                                />
                                <div class="max-h-48 overflow-y-auto space-y-1 custom-scrollbar">
                                    <button
                                        v-for="c in filteredCustomers"
                                        :key="c.id"
                                        @click="selectCustomer(c)"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition flex items-center justify-between"
                                        :class="c.id === activeCustomerId ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-700'"
                                    >
                                        <span class="truncate">{{ c.name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ c.phone }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Mobile Number Field (Replaces Walk-in field when + Customer clicked) -->
                        <div v-else class="flex-1 relative flex items-center gap-1.5">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-indigo-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input
                                    ref="customerPhoneInputRef"
                                    v-model="customerPhoneInput"
                                    type="tel"
                                    @keydown.enter.prevent="saveQuickCustomerPhone"
                                    @keydown.esc.prevent="cancelAddingCustomer"
                                    class="w-full pl-8 pr-2 py-2 rounded-xl border-2 border-indigo-500 bg-white text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-200 transition"
                                />
                            </div>
                            <button
                                @click="saveQuickCustomerPhone"
                                type="button"
                                :disabled="!customerPhoneInput.trim() || isSavingCustomer"
                                class="px-2.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition disabled:opacity-40 flex items-center justify-center shrink-0 shadow-xs"
                                title="Confirm Customer"
                            >
                                <svg v-if="!isSavingCustomer" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else class="text-[10px]">...</span>
                            </button>
                        </div>

                        <!-- Toggle Button -->
                        <button
                            v-if="!isAddingCustomer"
                            @click="enableAddingCustomer"
                            type="button"
                            class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-indigo-600 hover:text-indigo-800 border border-slate-200 text-xs font-bold transition shrink-0 flex items-center gap-1 shadow-2xs"
                            title="Add Customer by Mobile"
                        >
                            <span>+ Customer</span>
                        </button>
                        <button
                            v-else
                            @click="cancelAddingCustomer"
                            type="button"
                            class="px-2.5 py-2 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 border border-slate-200 text-xs font-bold transition shrink-0 flex items-center justify-center"
                            title="Cancel"
                        >
                            <span class="text-sm leading-none">&times;</span>
                        </button>
                    </div>
                </div>

                <!-- Cart Items Scrollable List -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar bg-slate-50/50">
                    <div
                        v-for="(item, idx) in cart"
                        :key="item.key"
                        class="bg-white border rounded-xl p-2.5 flex items-center justify-between gap-3 text-xs shadow-xs transition"
                        :class="[
                            item.stock !== undefined && item.quantity > item.stock
                                ? 'border-rose-400 bg-rose-50/20 ring-1 ring-rose-400'
                                : (item.stock !== undefined && item.quantity >= item.stock ? 'border-amber-300 bg-amber-50/10' : 'border-slate-200/90 hover:border-slate-300')
                        ]"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-900 truncate">{{ item.name }}</div>
                            <div v-if="item.variant_name" class="text-[10px] text-violet-600 font-semibold truncate">
                                {{ item.variant_name }}
                            </div>
                            <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 text-[10px] text-slate-400 font-mono mt-0.5">
                                <span>{{ item.sku }} • <CurrencyDisplay :amount="item.unit_price" /> / {{ item.unit }}</span>
                                <span v-if="item.stock !== undefined" :class="item.quantity >= item.stock ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                    (Stock: {{ item.stock }})
                                </span>
                            </div>
                            <div v-if="item.stock !== undefined && item.quantity >= item.stock" class="text-[10px] text-amber-600 font-bold mt-0.5">
                                Max available stock reached
                            </div>
                        </div>

                        <!-- Stepper Quantity Control -->
                        <div class="flex items-center gap-1 shrink-0 bg-slate-50 border border-slate-200 rounded-lg p-0.5">
                            <button
                                @click="updateQuantity(item, -1)"
                                type="button"
                                class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-slate-200 font-bold transition"
                                title="Decrease quantity"
                            >
                                -
                            </button>
                            <input
                                v-model.number="item.quantity"
                                @input="validateItemQuantity(item)"
                                @change="validateItemQuantity(item)"
                                type="number"
                                step="1"
                                min="1"
                                :max="item.stock"
                                class="w-10 text-center bg-transparent border-0 p-0 text-xs font-bold text-slate-900 focus:ring-0"
                            />
                            <button
                                @click="updateQuantity(item, 1)"
                                :disabled="item.stock !== undefined && item.quantity >= item.stock"
                                type="button"
                                class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed font-bold transition"
                                :title="item.stock !== undefined && item.quantity >= item.stock ? `Stock limit of ${item.stock} reached` : 'Increase quantity'"
                            >
                                +
                            </button>
                        </div>

                        <!-- Item Total & Delete -->
                        <div class="text-right shrink-0">
                            <div class="font-extrabold text-slate-900 font-mono">
                                <CurrencyDisplay :amount="item.quantity * item.unit_price" />
                            </div>
                            <button
                                @click="removeCartItem(idx)"
                                type="button"
                                class="text-[10px] text-rose-500 hover:text-rose-700 font-semibold mt-0.5"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <!-- Cart Empty State -->
                    <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mb-2 text-slate-300 shadow-sm">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-600">Your POS cart is empty</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Click products on the left or scan barcode to add</p>
                    </div>
                </div>

                <!-- Financial Breakdown & Fast Checkout Area -->
                <div class="p-3 bg-white border-t border-slate-200 space-y-2 text-xs shadow-sm">
                    <!-- Discount & VAT Row -->
                    <div class="grid grid-cols-2 gap-2 pb-2 border-b border-slate-100">
                        <div>
                            <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
                                <span class="font-semibold">Discount</span>
                                <div class="flex items-center gap-1 font-bold">
                                    <button
                                        @click="discountType = 'fixed'"
                                        class="px-1.5 py-0.2 rounded text-[10px]"
                                        :class="discountType === 'fixed' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                    >৳</button>
                                    <button
                                        @click="discountType = 'percent'"
                                        class="px-1.5 py-0.2 rounded text-[10px]"
                                        :class="discountType === 'percent' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                    >%</button>
                                </div>
                            </div>
                            <input
                                v-model.number="discountValue"
                                type="number"
                                min="0"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-900 focus:border-indigo-500 focus:ring-0 font-semibold"
                            />
                        </div>

                        <div>
                            <div class="text-[11px] font-semibold text-slate-500 mb-1">VAT / Tax (%)</div>
                            <input
                                v-model.number="taxPercent"
                                type="number"
                                min="0"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-900 focus:border-indigo-500 focus:ring-0 font-semibold"
                            />
                        </div>
                    </div>

                    <!-- Summary Lines -->
                    <div class="space-y-1 text-xs text-slate-500">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-semibold text-slate-800"><CurrencyDisplay :amount="subtotal" /></span>
                        </div>
                        <div v-if="discountAmount > 0" class="flex justify-between text-emerald-600 font-semibold">
                            <span>Discount:</span>
                            <span>-<CurrencyDisplay :amount="discountAmount" /></span>
                        </div>
                        <div v-if="taxAmount > 0" class="flex justify-between">
                            <span>Tax:</span>
                            <span>+<CurrencyDisplay :amount="taxAmount" /></span>
                        </div>
                        <div v-if="roundingDiff !== 0" class="flex justify-between text-[10px] text-slate-400">
                            <span>Rounding adjustment:</span>
                            <span>{{ roundingDiff > 0 ? '+' : '' }}{{ roundingDiff.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Grand Total Banner -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Net Payable Total</span>
                            <span class="text-[10px] text-indigo-600 font-semibold">{{ cart.length }} items on ticket</span>
                        </div>
                        <div class="text-2xl font-black text-emerald-600 font-mono">
                            <CurrencyDisplay :amount="calculatedTotal" />
                        </div>
                    </div>

                    <!-- Payment Account & Tendered Input -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Deposit Account</label>
                            <select
                                v-model="selectedAccountId"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg py-1 px-2 text-xs text-slate-900 font-medium focus:border-indigo-500 focus:ring-0 truncate"
                            >
                                <option v-for="acc in financialAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }} ({{ acc.type }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-500 mb-1">
                                <span>Cash Tendered</span>
                                <button @click="setTendered('exact')" class="text-indigo-600 hover:text-indigo-800 font-bold lowercase">exact</button>
                            </div>
                            <input
                                v-model.number="tenderedAmount"
                                type="number"
                                min="0"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg py-1 px-2 text-xs text-slate-900 font-bold text-right focus:border-indigo-500 focus:ring-0"
                            />
                        </div>
                    </div>

                    <!-- Quick Cash Amounts & Return Change -->
                    <div class="flex items-center justify-between gap-1 pt-1 text-[11px]">
                        <div class="flex items-center gap-1">
                            <button
                                v-for="amt in [500, 1000, 2000]"
                                :key="amt"
                                @click="setTendered(amt)"
                                type="button"
                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold border border-slate-200 transition"
                            >
                                {{ amt }}
                            </button>
                        </div>
                        <div v-if="changeToReturn > 0" class="text-right">
                            <span class="text-[10px] text-slate-500 mr-1">Return Change:</span>
                            <span class="font-extrabold text-emerald-600 text-xs font-mono"><CurrencyDisplay :amount="changeToReturn" /></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-4 gap-2 pt-2">
                        <button
                            @click="clearCart"
                            type="button"
                            :disabled="cart.length === 0"
                            class="col-span-1 py-2.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 font-bold text-xs border border-slate-200 transition disabled:opacity-40"
                        >
                            Clear
                        </button>

                        <button
                            @click="parkCurrentCart"
                            type="button"
                            :disabled="cart.length === 0"
                            class="col-span-1 py-2.5 rounded-xl bg-slate-100 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-bold text-xs border border-slate-200 transition disabled:opacity-40"
                            title="Hold Cart [F4]"
                        >
                            Hold [F4]
                        </button>

                        <button
                            @click="completeSale"
                            type="button"
                            :disabled="cart.length === 0 || isProcessingSale"
                            class="col-span-2 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm shadow-md shadow-emerald-600/30 transition-all disabled:opacity-40 flex items-center justify-center gap-1.5"
                        >
                            <svg v-if="isProcessingSale" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span v-else>Pay & Print [F8]</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================== -->
        <!-- MODAL: VARIANT SELECTOR MODAL         -->
        <!-- ===================================== -->
        <Modal :show="isVariantModalOpen" @close="isVariantModalOpen = false" max-width="lg">
            <div class="p-6 bg-white text-slate-900 rounded-2xl border border-slate-200 shadow-xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ activeProductForVariant?.name }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Select a product variant to add to cart</p>
                    </div>
                    <button @click="isVariantModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>

                <div class="mt-4 space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
                    <div
                        v-for="v in activeProductForVariant?.variants"
                        :key="v.id"
                        @click="selectVariant(activeProductForVariant, v)"
                        class="p-3 border rounded-xl flex items-center justify-between transition"
                        :class="[
                            (v.stock ?? 0) > 0 
                                ? 'bg-slate-50 hover:bg-indigo-50/60 border-slate-200 hover:border-indigo-400 cursor-pointer' 
                                : 'bg-slate-100/70 border-slate-200 opacity-60 cursor-not-allowed hover:bg-slate-100/70'
                        ]"
                    >
                        <div>
                            <div class="font-bold text-slate-900 text-xs">{{ v.name }}</div>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">SKU: {{ v.sku }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-emerald-600 text-xs">
                                <CurrencyDisplay :amount="v.unit_price" />
                            </div>
                            <div class="text-[10px] mt-0.5" :class="(v.stock ?? 0) > 0 ? 'text-slate-500 font-semibold' : 'text-rose-600 font-bold'">
                                <span v-if="(v.stock ?? 0) > 0">{{ v.stock }} in stock</span>
                                <span v-else class="px-1.5 py-0.5 rounded bg-rose-50 border border-rose-200 text-rose-600">Out of Stock</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>


        <!-- ===================================== -->
        <!-- MODAL: PARKED / HELD CARTS MODAL      -->
        <!-- ===================================== -->
        <Modal :show="isParkedModalOpen" @close="isParkedModalOpen = false" max-width="lg">
            <div class="p-6 bg-white text-slate-900 rounded-2xl border border-slate-200 shadow-xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Parked / Held Customer Carts</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Resume a previously paused counter transaction</p>
                    </div>
                    <button @click="isParkedModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>

                <div class="mt-4 space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
                    <div
                        v-for="p in parkedCarts"
                        :key="p.id"
                        class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3 text-xs"
                    >
                        <div>
                            <div class="font-bold text-slate-900">{{ p.customer_name }}</div>
                            <div class="text-[10px] text-slate-500 mt-0.5">
                                Held at {{ p.date }} • {{ p.items.length }} items
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="font-bold text-emerald-600 mr-2 font-mono">
                                <CurrencyDisplay :amount="p.total" />
                            </div>
                            <button
                                @click="restoreParkedCart(p)"
                                class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition"
                            >
                                Resume
                            </button>
                            <button
                                @click="deleteParkedCart(p.id)"
                                class="px-2 py-1.5 rounded-lg bg-slate-200 hover:bg-rose-100 hover:text-rose-600 text-slate-600 font-bold transition"
                            >
                                &times;
                            </button>
                        </div>
                    </div>

                    <div v-if="parkedCarts.length === 0" class="py-8 text-center text-slate-400 text-xs">
                        No carts currently held.
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ===================================== -->
        <!-- MODAL: RECENT POS SALES MODAL         -->
        <!-- ===================================== -->
        <Modal :show="isRecentModalOpen" @close="isRecentModalOpen = false" max-width="xl">
            <div class="p-6 bg-white text-slate-900 rounded-2xl border border-slate-200 shadow-xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Recent POS Transactions</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Recent counter orders and print receipts</p>
                    </div>
                    <button @click="isRecentModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>

                <div class="mt-4 space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
                    <div
                        v-for="o in recentOrders"
                        :key="o.id"
                        class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3 text-xs"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-indigo-600">{{ o.order_number }}</span>
                                <span class="text-[10px] text-slate-400">{{ o.date }}</span>
                            </div>
                            <div class="text-[11px] text-slate-700 font-semibold mt-0.5 truncate">
                                {{ o.customer_name }}
                            </div>
                            <div class="text-[10px] text-slate-400 truncate mt-0.5">
                                {{ o.items_summary }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <div class="text-right mr-1">
                                <div class="font-bold text-slate-900 font-mono"><CurrencyDisplay :amount="o.total_amount" /></div>
                                <div class="text-[9px] uppercase font-extrabold" :class="o.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ o.payment_status }}
                                </div>
                            </div>

                            <button
                                @click="printThermalReceipt(o.pos_url)"
                                class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-[11px] transition shadow-2xs"
                                title="Print Thermal Slip"
                            >
                                Thermal
                            </button>
                            <button
                                @click="printStandardInvoice(o.invoice_url)"
                                class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold text-[11px] transition shadow-2xs"
                                title="Print A4 Invoice"
                            >
                                A4
                            </button>
                        </div>
                    </div>

                    <div v-if="recentOrders.length === 0" class="py-8 text-center text-slate-400 text-xs">
                        No previous POS orders found.
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ===================================== -->
        <!-- MODAL: CHECKOUT SUCCESS POPUP         -->
        <!-- ===================================== -->
        <Modal :show="isSuccessModalOpen" @close="startNewSale" max-width="md">
            <div class="p-6 bg-white text-slate-900 rounded-2xl border border-slate-200 text-center shadow-2xl">
                <!-- Checkmark Icon -->
                <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-3 shadow-md shadow-emerald-500/20">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h3 class="text-lg font-black text-slate-900">Payment Received!</h3>
                <p class="text-xs text-slate-500 mt-1 font-mono">Invoice #{{ lastCompletedOrder?.order_number }}</p>

                <!-- Amount Breakdown Card -->
                <div class="my-5 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="flex justify-between text-slate-500">
                        <span>Total Bill:</span>
                        <span class="font-bold text-slate-900 font-mono"><CurrencyDisplay :amount="lastCompletedOrder?.total_amount || 0" /></span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Cash Tendered:</span>
                        <span class="font-bold text-slate-900 font-mono"><CurrencyDisplay :amount="lastCompletedOrder?.tendered || 0" /></span>
                    </div>
                    <div v-if="(lastCompletedOrder?.change || 0) > 0" class="flex justify-between text-emerald-600 pt-2 border-t border-slate-200 font-bold text-sm">
                        <span>Return Change:</span>
                        <span class="font-mono"><CurrencyDisplay :amount="lastCompletedOrder?.change || 0" /></span>
                    </div>
                </div>

                <!-- Print Options -->
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <button
                        @click="printThermalReceipt(lastCompletedOrder?.pos_slip_url)"
                        class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Thermal Slip</span>
                    </button>

                    <button
                        @click="printStandardInvoice(lastCompletedOrder?.invoice_url)"
                        class="py-2.5 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>A4 PDF Invoice</span>
                    </button>
                </div>

                <button
                    v-if="lastCompletedOrder?.pos_invoice_page_url"
                    @click="printStandardInvoice(lastCompletedOrder?.pos_invoice_page_url)"
                    type="button"
                    class="w-full py-2 px-3 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-xs border border-teal-200 flex items-center justify-center gap-1.5 transition mb-3"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>View / Print Interactive Invoice Screen</span>
                </button>

                <!-- Next Sale Button -->
                <button
                    @click="startNewSale"
                    type="button"
                    class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition border border-slate-200"
                >
                    Start Next Sale [Enter]
                </button>
            </div>
        </Modal>

        <!-- Clear POS Cart Confirmation Modal -->
        <ConfirmationModal
            :show="showClearCartModal"
            title="Clear POS Cart"
            variant="warning"
            icon="warning"
            :item-name="`${cart.length} item(s) in cart`"
            message="Are you sure you want to remove all items from the current active register cart? This cannot be undone."
            confirm-text="Clear Cart"
            @confirm="executeClearCart"
            @close="showClearCartModal = false"
        />
    </div>
</template>
