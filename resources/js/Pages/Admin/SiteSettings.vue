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
});

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
        chip: 'Enterprise SaaS • 99.99% Uptime SLA',
        headline_prefix: 'Scale Operations With',
        headline_highlight: 'Modern Cloud Inventory',
        subtext: 'Manage multi-warehouse stock, point-of-sale checkout, and double-entry accounting in one unified platform.',
        primary_cta_text: 'Start Free Trial',
        primary_cta_url: '/register',
        secondary_cta_text: 'Explore Modules',
        secondary_cta_url: '#features',
        mockup_type: 'inventory',
        trust_badges: ['No Credit Card Required', 'Instant Setup & Seeding', 'Real-Time Sync'],
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

function addTrustBadge(slide) {
    if (!Array.isArray(slide.trust_badges)) {
        slide.trust_badges = [];
    }
    slide.trust_badges.push('Advantage Feature');
}

function removeTrustBadge(slide, badgeIdx) {
    if (Array.isArray(slide.trust_badges)) {
        slide.trust_badges.splice(badgeIdx, 1);
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

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Configure public landing page branding, hero carousel, FAQs, and legal policies.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a 
                        href="/" 
                        target="_blank" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm"
                    >
                        <span>Preview Landing Page</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-sm"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving Changes...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                <button
                    type="button"
                    @click="setTab('branding')"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
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
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
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
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
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
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                    :class="activeTab === 'legal' 
                        ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Privacy & Terms</span>
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
                            
                            <div class="flex items-start gap-4">
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

                                <div class="flex-1 space-y-2">
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
                            
                            <div class="flex items-start gap-4">
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

                                <div class="flex-1 space-y-2">
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

                <!-- Section 4B: Footer Copyright Statement -->
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
                <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium">Visual branding, contact information, and footer copyright statement.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs"
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
                                        Slide #{{ sIdx + 1 }}: {{ slide.headline_highlight || 'Untitled Slide' }}
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
                                <!-- Chip Text -->
                                <div class="md:col-span-2 space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Announcement Chip / Pill Text
                                    </label>
                                    <input 
                                        v-model="slide.chip" 
                                        type="text" 
                                        placeholder="e.g. Enterprise Multi-Tenant SaaS • 99.99% Uptime SLA"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>

                                <!-- Headline Prefix -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Headline Prefix
                                    </label>
                                    <input 
                                        v-model="slide.headline_prefix" 
                                        type="text" 
                                        placeholder="e.g. The Operating System for"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>

                                <!-- Headline Gradient Accent -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Gradient Highlight Text
                                    </label>
                                    <input 
                                        v-model="slide.headline_highlight" 
                                        type="text" 
                                        placeholder="e.g. Modern Inventory & Supply Chains"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white font-semibold text-indigo-600"
                                    />
                                </div>

                                <!-- Subtext -->
                                <div class="md:col-span-2 space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Subheadline / Description
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
                                        placeholder="e.g. Start 14-Day Free Trial"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Primary Button URL
                                    </label>
                                    <input 
                                        v-model="slide.primary_cta_url" 
                                        type="text" 
                                        placeholder="e.g. /register"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>

                                <!-- Secondary CTA Text & URL -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Secondary Button Text
                                    </label>
                                    <input 
                                        v-model="slide.secondary_cta_text" 
                                        type="text" 
                                        placeholder="e.g. Explore Modules"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Secondary Button URL
                                    </label>
                                    <input 
                                        v-model="slide.secondary_cta_url" 
                                        type="text" 
                                        placeholder="e.g. #features"
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    />
                                </div>

                                <!-- Custom Slide Image Field -->
                                <div class="md:col-span-2 space-y-2 p-3.5 rounded-xl bg-white border border-slate-200">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>Slide Custom Image / Banner (Optional)</span>
                                        </label>
                                        <span class="text-[11px] text-slate-400">Replaces mockup preset when provided</span>
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
                                                <span>Using Mockup</span>
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
                                                    placeholder="https://example.com/banner.png"
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

                                <!-- Mockup Screen Preset -->
                                <div class="md:col-span-2 space-y-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Mockup Screen Design
                                    </label>
                                    <select 
                                        v-model="slide.mockup_type" 
                                        class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-cyan-500 focus:ring-cyan-500 transition shadow-sm bg-white"
                                    >
                                        <option value="inventory">Warehouse Inventory Distribution & Low-Stock Radar</option>
                                        <option value="pos">High-Speed Point of Sale (POS) & Retail Checkout Terminal</option>
                                        <option value="accounting">Double-Entry Financial Accounting, General Ledger & Profit Analysis</option>
                                    </select>
                                </div>

                                <!-- Trust Badges -->
                                <div class="md:col-span-2 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                            Feature / Trust Badges
                                        </label>
                                        <button 
                                            type="button" 
                                            @click="addTrustBadge(slide)" 
                                            class="text-[11px] font-bold text-cyan-600 hover:text-cyan-700"
                                        >
                                            + Add Badge
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2">
                                        <div 
                                            v-for="(badge, bIdx) in slide.trust_badges" 
                                            :key="bIdx" 
                                            class="flex items-center gap-1.5 p-1.5 rounded-lg bg-white border border-slate-200"
                                        >
                                            <input 
                                                v-model="slide.trust_badges[bIdx]" 
                                                type="text" 
                                                class="flex-1 text-xs border-0 p-0 focus:ring-0 text-slate-700 font-medium"
                                                placeholder="Badge text"
                                            />
                                            <button 
                                                type="button" 
                                                @click="removeTrustBadge(slide, bIdx)" 
                                                class="text-slate-400 hover:text-rose-500 p-0.5"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Bar for Tab 2 -->
                <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium">Hero carousel slides, CTAs, trust badges, and software mockups.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs"
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
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
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
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition border border-indigo-200"
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
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
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
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-violet-600 bg-violet-50 hover:bg-violet-100 transition border border-violet-200"
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
                <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium">Customer reviews carousel and frequently asked questions accordion.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs"
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
                        <div class="flex items-center gap-2 shrink-0">
                            <a 
                                href="/terms-and-conditions" 
                                target="_blank" 
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm"
                            >
                                <span>Terms Page</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            <a 
                                href="/privacy-policy" 
                                target="_blank" 
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm"
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
                <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs text-slate-500 font-medium">Public legal policy documents formatted with Rich Text Editor.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:opacity-50 transition shadow-xs"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" color="text-white" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Legal Documents' }}</span>
                    </button>
                </div>
            </div>
        </form>
        </div>
    </AppLayout>
</template>

