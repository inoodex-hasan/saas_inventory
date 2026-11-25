<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    logo: null,
    favicon: null,
    remove_logo: false,
    remove_favicon: false,
    brand_name: props.settings.brand_name || '',
    brand_tagline: props.settings.brand_tagline || '',
    site_title: props.settings.site_title || '',
    meta_description: props.settings.meta_description || '',
    support_email: props.settings.support_email || '',
    contact_phone: props.settings.contact_phone || '',
    contact_address: props.settings.contact_address || '',
    footer_copyright: props.settings.footer_copyright || '',
    facebook_url: props.settings.facebook_url || '',
    facebook_page: props.settings.facebook_page || '',
    whatsapp_number: props.settings.whatsapp_number || '',
    linkedin_url: props.settings.linkedin_url || '',
    youtube_url: props.settings.youtube_url || '',
    twitter_url: props.settings.twitter_url || '',
    hero_headline: props.settings.hero_headline || '',
    hero_subtext: props.settings.hero_subtext || '',
    hero_chip_text: props.settings.hero_chip_text || '',
    hero_slides: Array.isArray(props.settings.hero_slides) ? JSON.parse(JSON.stringify(props.settings.hero_slides)) : [],
    slide_images: [],
    remove_slide_images: [],
    testimonials: Array.isArray(props.settings.testimonials) ? JSON.parse(JSON.stringify(props.settings.testimonials)) : [],
    faqs: Array.isArray(props.settings.faqs) ? JSON.parse(JSON.stringify(props.settings.faqs)) : [],
    terms_and_conditions: props.settings.terms_and_conditions || '',
    privacy_policy: props.settings.privacy_policy || '',
    maintenance_mode: Boolean(props.settings.maintenance_mode),
    maintenance_title: props.settings.maintenance_title || 'System Upgrade in Progress',
    maintenance_message: props.settings.maintenance_message || 'We are performing scheduled maintenance and database optimizations. The platform will be back online shortly.',
    maintenance_duration: props.settings.maintenance_duration || '15-30 minutes',
    maintenance_bypass_secret: props.settings.maintenance_bypass_secret || '',
});

const copiedBypass = ref(false);
function generateBypassSecret() {
    form.maintenance_bypass_secret = 'bypass-' + Math.random().toString(36).substring(2, 9) + '-' + Math.floor(1000 + Math.random() * 9000);
}
function copyBypassUrl() {
    if (!form.maintenance_bypass_secret) return;
    const url = window.location.origin + '/maintenance-bypass/' + form.maintenance_bypass_secret;
    navigator.clipboard.writeText(url);
    copiedBypass.value = true;
    setTimeout(() => { copiedBypass.value = false; }, 2500);
}

const logoPreview = ref(props.settings.logo_url);
const faviconPreview = ref(props.settings.favicon_url);

const activeTab = ref(typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('tab') || 'branding') : 'branding');

function setTab(tab) {
    activeTab.value = tab;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }
}

function addHeroSlide() {
    form.hero_slides.push({
        headline: 'Intelligent Inventory &',
        headline_highlight: 'Cloud ERP Platform',
        subtext: 'Manage multi-warehouse stock, point-of-sale checkout, and double-entry accounting in one unified platform.',
        primary_cta_text: 'Get Started Free',
        primary_cta_url: '/register',
        image_url: '/landing/images/inomanage-laptop-dashboard.png',
        image_path: null,
        image_preview: null,
    });
}

function removeHeroSlide(index) {
    if (form.hero_slides.length <= 1) {
        alert('You must have at least one hero slide.');
        return;
    }
    form.hero_slides.splice(index, 1);
}

function moveHeroSlideUp(index) {
    if (index > 0) {
        const item = form.hero_slides.splice(index, 1)[0];
        form.hero_slides.splice(index - 1, 0, item);
    }
}

function moveHeroSlideDown(index) {
    if (index < form.hero_slides.length - 1) {
        const item = form.hero_slides.splice(index, 1)[0];
        form.hero_slides.splice(index + 1, 0, item);
    }
}

function handleSlideImageChange(e, index) {
    const file = e.target.files[0];
    if (file) {
        if (!form.slide_images) form.slide_images = [];
        form.slide_images[index] = file;
        if (!form.remove_slide_images) form.remove_slide_images = [];
        form.remove_slide_images[index] = false;
        form.hero_slides[index].image_preview = URL.createObjectURL(file);
    }
}

function removeSlideImage(index) {
    if (form.slide_images) form.slide_images[index] = null;
    if (!form.remove_slide_images) form.remove_slide_images = [];
    form.remove_slide_images[index] = true;
    form.hero_slides[index].image_url = null;
    form.hero_slides[index].image_path = null;
    form.hero_slides[index].image_preview = null;
}

function addTestimonial() {
    form.testimonials.push({
        name: '',
        role: '',
        company: '',
        rating: 5,
        quote: '',
        avatar_text: '',
    });
}

function removeTestimonial(index) {
    form.testimonials.splice(index, 1);
}

function addFaq() {
    form.faqs.push({
        question: '',
        answer: '',
    });
}

function removeFaq(index) {
    form.faqs.splice(index, 1);
}

function handleLogoChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
}

function removeLogo() {
    form.logo = null;
    form.remove_logo = true;
    logoPreview.value = null;
}

function handleFaviconChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.favicon = file;
        form.remove_favicon = false;
        faviconPreview.value = URL.createObjectURL(file);
    }
}

function removeFavicon() {
    form.favicon = null;
    form.remove_favicon = true;
    faviconPreview.value = null;
}

function submit() {
    form.post(route('admin.site-settings.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.logo = null;
            form.favicon = null;
            form.remove_logo = false;
            form.remove_favicon = false;
            form.slide_images = [];
            form.remove_slide_images = [];
        },
    });
}
</script>

<template>
    <AppLayout title="Site Settings & Branding">
        <Head title="Site Settings & Branding" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 border border-indigo-100 rounded-xl text-indigo-600 inline-flex">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <span>Site Settings & Branding</span>
                    </h1>
                </div>

                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                    <a 
                        href="/?preview=1" 
                        target="_blank" 
                        class="flex-1 sm:flex-none justify-center inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm text-center"
                    >
                        <span>Preview Landing</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="flex-1 sm:flex-none justify-center inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-sm text-center cursor-pointer"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving Changes...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-1.5 overflow-x-auto no-scrollbar scroll-smooth">
                <button
                    type="button"
                    @click="setTab('branding')"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0"
                    :class="activeTab === 'branding' 
                        ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>Branding & General</span>
                </button>

                <button
                    type="button"
                    @click="setTab('slider')"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0"
                    :class="activeTab === 'slider' 
                        ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Hero Slider</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="activeTab === 'slider' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-700'"
                    >
                        {{ form.hero_slides?.length || 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setTab('content')"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0"
                    :class="activeTab === 'content' 
                        ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>FAQs & Testimonials</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="activeTab === 'content' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-700'"
                    >
                        {{ (form.faqs?.length || 0) + (form.testimonials?.length || 0) }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setTab('legal')"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0"
                    :class="activeTab === 'legal' 
                        ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Privacy & Terms</span>
                </button>

                <button
                    type="button"
                    @click="setTab('maintenance')"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0"
                    :class="activeTab === 'maintenance' 
                        ? (form.maintenance_mode ? 'bg-rose-600 text-white shadow-xs shadow-rose-600/30' : 'bg-slate-900 text-white shadow-xs') 
                        : (form.maintenance_mode ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Maintenance Mode</span>
                    <span v-if="form.maintenance_mode" class="px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase bg-white/20 text-white animate-pulse">ACTIVE</span>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- TAB 1: BRANDING & GENERAL -->
                <div v-show="activeTab === 'branding'" class="space-y-6">
                    <!-- Section 1: Visual Branding (Logo & Favicon) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            Visual Branding & Identity
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Upload your company logo and browser favicon.</p>
                    </div>

                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Logo Upload -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Site Logo
                            </label>
                            
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                <div class="w-20 h-20 rounded-2xl border border-slate-200 bg-slate-900 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                                    <img 
                                        v-if="logoPreview" 
                                        :src="logoPreview" 
                                        alt="Logo Preview" 
                                        class="w-full h-full object-contain p-2"
                                    />
                                    <div v-else class="text-center p-2">
                                        <div class="w-8 h-8 mx-auto rounded-lg bg-indigo-600 text-white font-black text-sm flex items-center justify-center">
                                            {{ form.brand_name ? form.brand_name.charAt(0) : 'I' }}
                                        </div>
                                        <span class="text-[9px] text-slate-400 mt-1 block">Fallback</span>
                                    </div>
                                </div>

                                <div class="flex-1 space-y-2 w-full text-center sm:text-left">
                                    <input 
                                        type="file" 
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        @change="handleLogoChange"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer"
                                    />
                                    <p class="text-[11px] text-slate-400">
                                        PNG, JPG, WEBP, GIF up to 2MB. Recommended transparent PNG or SVG-style raster.
                                    </p>
                                    <div v-if="logoPreview" class="pt-1">
                                        <button 
                                            type="button" 
                                            @click="removeLogo"
                                            class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1"
                                        >
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove logo (use initial fallback)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.logo" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.logo }}
                            </div>
                        </div>

                        <!-- Favicon Upload -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Browser Tab Favicon
                            </label>
                            
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                <div class="w-16 h-16 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                                    <img 
                                        v-if="faviconPreview" 
                                        :src="faviconPreview" 
                                        alt="Favicon Preview" 
                                        class="w-8 h-8 object-contain"
                                    />
                                    <div v-else class="text-center">
                                        <svg class="w-6 h-6 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                        <span class="text-[9px] text-slate-400 block">None</span>
                                    </div>
                                </div>

                                <div class="flex-1 space-y-2 w-full text-center sm:text-left">
                                    <input 
                                        type="file" 
                                        accept=".ico,image/png"
                                        @change="handleFaviconChange"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer"
                                    />
                                    <p class="text-[11px] text-slate-400">
                                        ICO or square PNG (32x32 or 64x64), up to 512KB.
                                    </p>
                                    <div v-if="faviconPreview" class="pt-1">
                                        <button 
                                            type="button" 
                                            @click="removeFavicon"
                                            class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1"
                                        >
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove favicon
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.favicon" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.favicon }}
                            </div>
                        </div>

                        <!-- Brand Name -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Brand Name <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.brand_name" 
                                type="text" 
                                required
                                maxlength="60"
                                placeholder="e.g. InoManage"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.brand_name" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.brand_name }}
                            </div>
                        </div>

                        <!-- Brand Tagline -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Brand Tagline <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.brand_tagline" 
                                type="text" 
                                required
                                maxlength="80"
                                placeholder="e.g. Cloud Inventory Platform"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.brand_tagline" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.brand_tagline }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: SEO & Browser Metadata -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>
                            SEO & Browser Page Title
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Control the exact text shown in browser tabs and search engine snippets.</p>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Browser Page Title (Title Bar) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.site_title" 
                                type="text" 
                                required
                                maxlength="120"
                                placeholder="e.g. InoManage Cloud Inventory Management Software"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <p class="text-[11px] text-slate-400">
                                Displayed in browser tabs on the landing page and authentication views.
                            </p>
                            <div v-if="form.errors.site_title" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.site_title }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Meta Description (SEO)
                            </label>
                            <textarea 
                                v-model="form.meta_description" 
                                rows="3"
                                maxlength="500"
                                placeholder="Short overview for Google search results..."
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            ></textarea>
                            <div v-if="form.errors.meta_description" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.meta_description }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Contact & Support Information -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            Contact & Support Channels
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Renders in the footer of the public landing page.</p>
                    </div>

                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Support Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input 
                                    v-model="form.support_email" 
                                    type="email" 
                                    maxlength="100"
                                    placeholder="support@example.com"
                                    class="w-full pl-10 text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                                />
                            </div>
                            <div v-if="form.errors.support_email" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.support_email }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Contact Phone
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input 
                                    v-model="form.contact_phone" 
                                    type="text" 
                                    maxlength="30"
                                    placeholder="+880 1700-000000"
                                    class="w-full pl-10 text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                                />
                            </div>
                            <div v-if="form.errors.contact_phone" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.contact_phone }}
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Office / Contact Address
                            </label>
                            <textarea 
                                v-model="form.contact_address" 
                                rows="2"
                                maxlength="300"
                                placeholder="Headquarters, Level 4, Motijheel Commercial Area, Dhaka 1000"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            ></textarea>
                            <div v-if="form.errors.contact_address" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.contact_address }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4B: Social Media & Live Chat Support -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            Social Media &amp; Live Chat
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Configure your WhatsApp, Facebook Messenger floating widgets, and footer social links.</p>
                    </div>

                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <!-- WhatsApp Support Number -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                WhatsApp Support Number
                            </label>
                            <input 
                                v-model="form.whatsapp_number" 
                                type="text" 
                                maxlength="50"
                                placeholder="+880 1957-890788"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <p class="text-[11px] text-slate-400">
                                Powers the floating WhatsApp chat widget and hotline chat link (e.g. wa.me).
                            </p>
                            <div v-if="form.errors.whatsapp_number" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.whatsapp_number }}
                            </div>
                        </div>

                        <!-- Facebook Messenger Username / ID -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Messenger Page Username / ID
                            </label>
                            <div class="flex rounded-xl shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-xs">
                                    m.me/
                                </span>
                                <input 
                                    v-model="form.facebook_page" 
                                    type="text" 
                                    maxlength="100"
                                    placeholder="inomanage"
                                    class="w-full text-xs sm:text-sm rounded-r-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                                />
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Page username used for the floating Facebook Messenger button.
                            </p>
                            <div v-if="form.errors.facebook_page" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.facebook_page }}
                            </div>
                        </div>

                        <!-- Facebook Page URL -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Facebook Page URL
                            </label>
                            <input 
                                v-model="form.facebook_url" 
                                type="text" 
                                maxlength="255"
                                placeholder="https://facebook.com/inomanage"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.facebook_url" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.facebook_url }}
                            </div>
                        </div>

                        <!-- LinkedIn URL -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                LinkedIn Profile / Company URL
                            </label>
                            <input 
                                v-model="form.linkedin_url" 
                                type="text" 
                                maxlength="255"
                                placeholder="https://linkedin.com/company/inomanage"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.linkedin_url" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.linkedin_url }}
                            </div>
                        </div>

                        <!-- YouTube URL -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                YouTube Channel URL
                            </label>
                            <input 
                                v-model="form.youtube_url" 
                                type="text" 
                                maxlength="255"
                                placeholder="https://youtube.com/@inomanage"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.youtube_url" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.youtube_url }}
                            </div>
                        </div>

                        <!-- Twitter / X URL -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                X (Twitter) URL <span class="text-slate-400 font-normal lowercase">(optional)</span>
                            </label>
                            <input 
                                v-model="form.twitter_url" 
                                type="text" 
                                maxlength="255"
                                placeholder="https://x.com/inomanage"
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <div v-if="form.errors.twitter_url" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.twitter_url }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4C: Footer Copyright Statement -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-600"></span>
                            Footer Copyright Statement
                        </h2>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="space-y-1 max-w-xl">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Footer Copyright Notice <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                v-model="form.footer_copyright" 
                                type="text" 
                                required
                                maxlength="120"
                                placeholder="e.g. InoManage. All rights reserved."
                                class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm"
                            />
                            <p class="text-[11px] text-slate-400">
                                The current year will be automatically appended: &copy; {{ new Date().getFullYear() }} [Notice]
                            </p>
                            <div v-if="form.errors.footer_copyright" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.footer_copyright }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Bar for Tab 1 -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium text-center sm:text-left">Visual branding, contact information, and footer copyright statement.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs text-center cursor-pointer shrink-0"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Branding & Footer' }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 2: HERO SLIDER -->
            <div v-show="activeTab === 'slider'" class="space-y-6">
                <!-- Section 4: Dynamic Landing Page Hero Slider -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-600"></span>
                                Dynamic Hero Slider (Welcome Page)
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Configure the rotating slides featured at the very top of the landing page.
                            </p>
                        </div>

                        <button 
                            type="button" 
                            @click="addHeroSlide" 
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-cyan-700 bg-cyan-50 border border-cyan-200 hover:bg-cyan-100 transition shadow-sm self-start sm:self-auto"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Hero Slide</span>
                        </button>
                    </div>

                    <div class="p-5 sm:p-6 space-y-6">
                        <div 
                            v-for="(slide, sIdx) in form.hero_slides" 
                            :key="sIdx" 
                            class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4 relative group"
                        >
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-cyan-600 text-white font-bold text-xs flex items-center justify-center">
                                        {{ sIdx + 1 }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-800">
                                        Slide #{{ sIdx + 1 }}: {{ slide.headline ? (slide.headline + ' ' + (slide.headline_highlight || '')) : (slide.headline_highlight || 'Untitled Slide') }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="moveHeroSlideUp(sIdx)" 
                                        :disabled="sIdx === 0" 
                                        class="p-1.5 text-slate-400 hover:text-slate-700 disabled:opacity-30 rounded-lg hover:bg-white transition"
                                        title="Move Up"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="moveHeroSlideDown(sIdx)" 
                                        :disabled="sIdx === form.hero_slides.length - 1" 
                                        class="p-1.5 text-slate-400 hover:text-slate-700 disabled:opacity-30 rounded-lg hover:bg-white transition"
                                        title="Move Down"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="removeHeroSlide(sIdx)" 
                                        class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 transition ms-1"
                                        title="Remove Slide"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Headline (Dark text) -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Headline (Main Title)
                                    </label>
                                    <input 
                                        v-model="slide.headline" 
                                        type="text" 
                                        placeholder="e.g. Intelligent Inventory &"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>

                                <!-- Headline Highlight (Emerald / Accent text) -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Headline Highlight (Emerald Accent)
                                    </label>
                                    <input 
                                        v-model="slide.headline_highlight" 
                                        type="text" 
                                        placeholder="e.g. Cloud ERP Platform"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white font-semibold text-emerald-700"
                                    />
                                </div>

                                <!-- Subtext / Description -->
                                <div class="md:col-span-2 space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Subtitle / Description
                                    </label>
                                    <textarea 
                                        v-model="slide.subtext" 
                                        rows="2" 
                                        placeholder="Describe the key advantages of this slide..."
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    ></textarea>
                                </div>

                                <!-- Primary CTA Text & URL -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Primary Button Text
                                    </label>
                                    <input 
                                        v-model="slide.primary_cta_text" 
                                        type="text" 
                                        placeholder="e.g. Get Started Free"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Primary Button Link / URL
                                    </label>
                                    <input 
                                        v-model="slide.primary_cta_url" 
                                        type="text" 
                                        placeholder="e.g. /register"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>

                                <!-- Slide Image Field -->
                                <div class="md:col-span-2 space-y-2 p-3.5 rounded-xl bg-white border border-slate-200">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>Slide Image (Dashboard Visual)</span>
                                        </label>
                                        <span class="text-[11px] text-slate-400">Recommended: PNG / WebP dashboard mockup</span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-1">
                                        <!-- Image Preview Thumbnail -->
                                        <div class="w-32 h-20 rounded-xl border border-slate-200 bg-slate-900 flex items-center justify-center overflow-hidden shrink-0 shadow-inner relative group/thumb">
                                            <img 
                                                v-if="slide.image_preview || slide.image_url" 
                                                :src="slide.image_preview || slide.image_url" 
                                                alt="Slide Image Preview" 
                                                class="w-full h-full object-contain p-1"
                                            />
                                            <div v-else class="text-center p-2 text-slate-500 text-[10px]">
                                                <svg class="w-6 h-6 mx-auto mb-1 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>Default Image</span>
                                            </div>
                                        </div>

                                        <div class="flex-1 space-y-2 w-full">
                                            <!-- File upload -->
                                            <div>
                                                <input 
                                                    type="file" 
                                                    accept="image/png,image/jpeg,image/webp,image/gif"
                                                    @change="(e) => handleSlideImageChange(e, sIdx)"
                                                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition cursor-pointer"
                                                />
                                            </div>

                                            <!-- Direct Image URL alternative -->
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] text-slate-400 font-semibold shrink-0">OR URL:</span>
                                                <input 
                                                    v-model="slide.image_url" 
                                                    type="text" 
                                                    placeholder="/landing/images/inomanage-laptop-dashboard.png"
                                                    class="flex-1 text-xs rounded-lg border-slate-200 focus:border-cyan-500 focus:ring-cyan-500 py-1"
                                                />
                                                <button 
                                                    v-if="slide.image_url || slide.image_preview || slide.image_path" 
                                                    type="button" 
                                                    @click="removeSlideImage(sIdx)"
                                                    class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg border border-rose-200 transition"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Bar for Tab 2 -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium text-center sm:text-left">Hero carousel slides, CTAs, trust badges, and software mockups.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs text-center cursor-pointer shrink-0"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Hero Slides' }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 3: CONTENT & FAQS -->
            <div v-show="activeTab === 'content'" class="space-y-6">
                <!-- Section 5: Customer Reviews & Carousel Testimonials -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                Customer Reviews Carousel
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Manage customer testimonials displayed in the auto-playing carousel.</p>
                        </div>
                        <button 
                            type="button" 
                            @click="addTestimonial"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition border border-indigo-200 self-start sm:self-auto cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Review</span>
                        </button>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">
                        <div v-if="form.testimonials.length === 0" class="text-center py-6 text-xs text-slate-400 italic">
                            No custom reviews added yet. Default built-in reviews will be shown on the landing page.
                        </div>

                        <div 
                            v-for="(item, idx) in form.testimonials" 
                            :key="idx" 
                            class="p-4 rounded-xl border border-slate-200 bg-slate-50/30 space-y-3 relative group"
                        >
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-indigo-600 text-white font-mono text-[10px] flex items-center justify-center font-bold">
                                        {{ idx + 1 }}
                                    </span>
                                    <span>Review #{{ idx + 1 }}</span>
                                </span>
                                <button 
                                    type="button" 
                                    @click="removeTestimonial(idx)" 
                                    class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1 hover:underline"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Remove</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Customer Name</label>
                                    <input 
                                        v-model="item.name" 
                                        type="text" 
                                        placeholder="Rahim Chowdhury" 
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Designation / Role</label>
                                    <input 
                                        v-model="item.role" 
                                        type="text" 
                                        placeholder="Managing Director" 
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Company / Business</label>
                                    <input 
                                        v-model="item.company" 
                                        type="text" 
                                        placeholder="Apex Retail Ltd" 
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Initials / Avatar</label>
                                    <input 
                                        v-model="item.avatar_text" 
                                        type="text" 
                                        maxlength="4" 
                                        placeholder="RC" 
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm uppercase font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Star Rating (1-5)</label>
                                    <select 
                                        v-model.number="item.rating" 
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    >
                                        <option :value="5">5 Stars &starf;&starf;&starf;&starf;&starf;</option>
                                        <option :value="4">4 Stars &starf;&starf;&starf;&starf;</option>
                                        <option :value="3">3 Stars &starf;&starf;&starf;</option>
                                        <option :value="2">2 Stars &starf;&starf;</option>
                                        <option :value="1">1 Star &starf;</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Quote / Review</label>
                                    <textarea 
                                        v-model="item.quote" 
                                        rows="2" 
                                        placeholder="Switching to this software solved our stock mismatch permanently..." 
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Frequently Asked Questions (FAQ) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>
                                Frequently Asked Questions (FAQ)
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Customize the landing page Q&A accordion.</p>
                        </div>
                        <button 
                            type="button" 
                            @click="addFaq"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-violet-600 bg-violet-50 hover:bg-violet-100 transition border border-violet-200 self-start sm:self-auto cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add FAQ</span>
                        </button>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">
                        <div v-if="form.faqs.length === 0" class="text-center py-6 text-xs text-slate-400 italic">
                            No custom FAQs added yet. Built-in defaults will be shown on the landing page.
                        </div>

                        <div 
                            v-for="(faq, fIdx) in form.faqs" 
                            :key="fIdx" 
                            class="p-4 rounded-xl border border-slate-200 bg-slate-50/30 space-y-3 relative group"
                        >
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md bg-violet-600 text-white font-mono text-[10px] flex items-center justify-center font-bold">
                                        Q{{ fIdx + 1 }}
                                    </span>
                                    <span>Question #{{ fIdx + 1 }}</span>
                                </span>
                                <button 
                                    type="button" 
                                    @click="removeFaq(fIdx)" 
                                    class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1 hover:underline"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Remove</span>
                                </button>
                            </div>

                            <div class="space-y-2">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Question</label>
                                    <input 
                                        v-model="faq.question" 
                                        type="text" 
                                        placeholder="e.g. Is our company data completely isolated?" 
                                        class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-violet-500 focus:ring-violet-500 shadow-sm font-semibold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Answer</label>
                                    <textarea 
                                        v-model="faq.answer" 
                                        rows="2" 
                                        placeholder="Detailed explanation for the customer..." 
                                        class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-violet-500 focus:ring-violet-500 shadow-sm"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Bar for Tab 3 -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium text-center sm:text-left">Customer reviews carousel and frequently asked questions accordion.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs text-center cursor-pointer shrink-0"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving...' : 'Save FAQs & Reviews' }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 4: LEGAL POLICIES -->
            <div v-show="activeTab === 'legal'" class="space-y-6">
                <!-- Section 7: Legal & Compliance (Terms & Conditions, Privacy Policy) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                Legal &amp; Compliance Documents
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Customize the public Terms and Conditions and Privacy Policy pages. Supports Markdown.</p>
                        </div>
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                            <a 
                                href="/terms-and-conditions" 
                                target="_blank" 
                                class="flex-1 sm:flex-none justify-center inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm text-center"
                            >
                                <span>Terms Page</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            <a 
                                href="/privacy-policy" 
                                target="_blank" 
                                class="flex-1 sm:flex-none justify-center inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm text-center"
                            >
                                <span>Privacy Page</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-6">
                        <!-- Terms and Conditions Rich Text Editor -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Terms and Conditions (Rich Text Editor)
                                </label>
                                <span class="text-[11px] text-slate-400 font-medium">Leave empty to use built-in default terms</span>
                            </div>
                            <RichTextEditor 
                                v-model="form.terms_and_conditions" 
                                placeholder="Compose custom Terms and Conditions with headings, formatting, and lists..."
                                min-height="240px"
                            />
                            <p class="text-[11px] text-slate-400">
                                Format with H1/H2/H3 headings, bold, underline, lists, quotes, and links using the WYSIWYG toolbar.
                            </p>
                            <div v-if="form.errors.terms_and_conditions" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.terms_and_conditions }}
                            </div>
                        </div>

                        <!-- Privacy Policy Rich Text Editor -->
                        <div class="space-y-2 pt-4 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Privacy Policy (Rich Text Editor)
                                </label>
                                <span class="text-[11px] text-slate-400 font-medium">Leave empty to use built-in default policy</span>
                            </div>
                            <RichTextEditor 
                                v-model="form.privacy_policy" 
                                placeholder="Compose custom Privacy Policy with headings, formatting, and lists..."
                                min-height="240px"
                            />
                            <p class="text-[11px] text-slate-400">
                                Format with H1/H2/H3 headings, bold, underline, lists, quotes, and links using the WYSIWYG toolbar.
                            </p>
                            <div v-if="form.errors.privacy_policy" class="text-xs text-rose-600 font-medium">
                                {{ form.errors.privacy_policy }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Bar for Tab 4 -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium text-center sm:text-left">Public legal policy documents formatted with Rich Text Editor.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs text-center cursor-pointer shrink-0"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Legal Documents' }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 5: PLATFORM MAINTENANCE MODE -->
            <div v-show="activeTab === 'maintenance'" class="space-y-6">
                <!-- Status Banner Card -->
                <div 
                    class="rounded-3xl p-6 sm:p-8 border shadow-sm relative overflow-hidden transition-all"
                    :class="form.maintenance_mode 
                        ? 'bg-gradient-to-br from-rose-950/80 via-slate-900 to-rose-900/60 border-rose-500/40 text-white' 
                        : 'bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border-emerald-500/30 text-white'"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div 
                                class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border"
                                :class="form.maintenance_mode 
                                    ? 'bg-rose-500/20 border-rose-500/40 text-rose-400' 
                                    : 'bg-emerald-500/20 border-emerald-500/40 text-emerald-400'"
                            >
                                <svg v-if="form.maintenance_mode" class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h2 class="text-lg font-black tracking-tight">
                                        {{ form.maintenance_mode ? 'Platform Maintenance Mode is ACTIVE' : 'Platform is ONLINE & Fully Operational' }}
                                    </h2>
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                                        :class="form.maintenance_mode ? 'bg-rose-500 text-white animate-pulse' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'"
                                    >
                                        {{ form.maintenance_mode ? 'Active' : 'Live' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 max-w-xl leading-relaxed">
                                    {{ form.maintenance_mode 
                                        ? 'All public visitors and non-admin tenants are redirected to the branded 503 maintenance page. Only Super Admins and users with the bypass link can access the platform.' 
                                        : 'All systems are operating normally. All tenants, POS terminals, and customers have unrestricted access.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Toggle Switch -->
                        <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                            <span class="text-xs font-bold text-slate-300">
                                {{ form.maintenance_mode ? 'Enabled' : 'Disabled' }}
                            </span>
                            <button
                                type="button"
                                @click="form.maintenance_mode = !form.maintenance_mode"
                                class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                                :class="form.maintenance_mode ? 'bg-rose-600' : 'bg-slate-700'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
                                    :class="form.maintenance_mode ? 'translate-x-7' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Settings Configuration Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Maintenance Screen Customization</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Customize what visitors see when maintenance mode is turned on.</p>
                        </div>
                        <a 
                            :href="route('admin.maintenance-preview')" 
                            target="_blank" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Preview 503 Page</span>
                        </a>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Page Headline / Title</label>
                                <input
                                    type="text"
                                    v-model="form.maintenance_title"
                                    class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Estimated Downtime / Duration</label>
                                <input
                                    type="text"
                                    v-model="form.maintenance_duration"
                                    class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Public Explanation Message</label>
                            <textarea
                                v-model="form.maintenance_message"
                                rows="3"
                                class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-y"
                            ></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Displayed clearly in the center of the maintenance screen.</p>
                        </div>

                        <!-- Bypass Secret Section -->
                        <div class="pt-4 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Secret Developer / Staff Bypass Token</label>
                            <p class="text-[11px] text-slate-500 mb-2.5">
                                Anyone who visits with this bypass token can browse and use the live application normally, even while maintenance mode is active.
                            </p>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                <div class="relative flex-1">
                                    <input
                                        type="text"
                                        v-model="form.maintenance_bypass_secret"
                                        placeholder="Enter or generate a secret key..."
                                        class="w-full font-mono text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition pr-24"
                                    />
                                    <button
                                        type="button"
                                        @click="generateBypassSecret"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-indigo-600 hover:text-indigo-800 px-2 py-1 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition cursor-pointer"
                                    >
                                        Generate Key
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    v-if="form.maintenance_bypass_secret"
                                    @click="copyBypassUrl"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border text-xs font-bold transition shrink-0 cursor-pointer"
                                    :class="copiedBypass ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200'"
                                >
                                    <svg v-if="copiedBypass" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ copiedBypass ? 'Bypass URL Copied!' : 'Copy Bypass Link' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Action Bar -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium text-center sm:text-left">
                        {{ form.maintenance_mode ? '🚨 Maintenance mode is currently set to ON.' : 'Platform maintenance settings are ready to save.' }}
                    </span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white transition shadow-xs text-center cursor-pointer shrink-0"
                        :class="form.maintenance_mode ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Updating...' : (form.maintenance_mode ? 'Save & Enforce Maintenance' : 'Save Maintenance Settings') }}</span>
                    </button>
                </div>
            </div>
        </form>
        </div>
    </AppLayout>
</template>

