<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $site['site_title'] }}</title>
        <meta name="description" content="{{ $site['meta_description'] }}">
        @if(!empty($site['favicon_url']))
        <link rel="icon" href="{{ $site['favicon_url'] }}">
        @endif

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Vite Asset Bundle -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --bg-deep: #050814;
                --bg-surface: #0a0e22;
                --bg-card: rgba(14, 20, 44, 0.75);
                --border-subtle: rgba(255, 255, 255, 0.08);
                --border-highlight: rgba(99, 102, 241, 0.35);
                --indigo-glow: rgba(99, 102, 241, 0.18);
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: var(--bg-deep);
                color: #e2e8f0;
                overflow-x: hidden;
                -webkit-font-smoothing: antialiased;
            }

            .font-heading {
                font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            }

            /* Ambient Glow Background Orbs */
            .ambient-glow {
                position: fixed;
                border-radius: 50%;
                filter: blur(140px);
                pointer-events: none;
                z-index: 0;
            }
            .glow-primary {
                width: 650px;
                height: 650px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
                top: -200px;
                left: -150px;
                animation: floatGlow 12s ease-in-out infinite alternate;
            }
            .glow-secondary {
                width: 550px;
                height: 550px;
                background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 70%);
                top: 35%;
                right: -150px;
                animation: floatGlow 14s ease-in-out infinite alternate-reverse;
            }
            .glow-accent {
                width: 500px;
                height: 500px;
                background: radial-gradient(circle, rgba(6, 182, 212, 0.10) 0%, transparent 70%);
                bottom: 5%;
                left: 15%;
                animation: floatGlow 16s ease-in-out infinite alternate;
            }

            @keyframes floatGlow {
                0% { transform: translate(0, 0) scale(1); }
                100% { transform: translate(40px, 30px) scale(1.08); }
            }

            /* Subtle Grid Background Pattern */
            .bg-grid-mesh {
                background-image: 
                    linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
                background-size: 50px 50px;
                mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 100%);
                -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 100%);
            }

            /* Glassmorphism Card Style */
            .glass-panel {
                background: var(--bg-card);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid var(--border-subtle);
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .glass-panel:hover {
                border-color: rgba(99, 102, 241, 0.3);
                box-shadow: 0 16px 40px -10px rgba(0, 0, 0, 0.5), 0 0 25px 0 rgba(99, 102, 241, 0.08);
            }

            /* Text Gradients */
            .gradient-title {
                background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 45%, #a5b4fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .gradient-accent {
                background: linear-gradient(135deg, #818cf8 0%, #c084fc 50%, #f472b6 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            /* Scroll Animations */
            .reveal {
                opacity: 0;
                transform: translateY(24px);
                transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .reveal.active {
                opacity: 1;
                transform: translateY(0);
            }

            /* Custom Pill Switch */
            .toggle-active {
                background: #6366f1;
                color: #ffffff;
                box-shadow: 0 0 16px rgba(99, 102, 241, 0.4);
            }
        </style>
    </head>

    <body class="min-h-screen relative selection:bg-indigo-600 selection:text-white">
        <!-- Ambient Glowing Aura -->
        <div class="ambient-glow glow-primary"></div>
        <div class="ambient-glow glow-secondary"></div>
        <div class="ambient-glow glow-accent"></div>

        <!-- Sticky Navigation Header -->
        <header id="main-header" class="sticky top-0 z-50 border-b border-white/[0.06] bg-[#050814]/85 backdrop-blur-xl transition-all duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="/" class="flex items-center gap-3.5 group">
                    @if(!empty($site['logo_url']))
                        <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] }}" class="w-10 h-10 rounded-xl object-contain shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform bg-white/10 p-1">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($site['brand_name'], 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-extrabold text-lg text-white tracking-tight leading-tight">{{ $site['brand_name'] }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">ERP</span>
                        </div>
                        <span class="text-[10px] font-semibold tracking-wider text-slate-400 uppercase block">{{ $site['brand_tagline'] }}</span>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-5 lg:gap-7 text-sm font-medium text-slate-300">
                    <a href="#features" class="hover:text-white transition-colors">Features</a>
                    <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
                    <a href="#reviews" class="hover:text-white transition-colors">Review</a>
                    <a href="#faq" class="hover:text-white transition-colors">FAQ</a>

                    <!-- Language Dropdown -->
                    <div class="relative" id="lang-dropdown-wrapper">
                        <button 
                            type="button" 
                            id="btn-lang-dropdown"
                            onclick="toggleLanguageMenu()"
                            class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-slate-300 hover:text-white border border-white/[0.08] transition-colors"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span id="current-lang-text">Language</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="lang-menu" class="hidden absolute right-0 mt-2 w-36 rounded-xl bg-[#0a0f24] border border-white/[0.1] shadow-2xl py-1.5 z-50 text-xs">
                            <button type="button" onclick="selectLanguage('English')" class="w-full text-left px-3.5 py-2 hover:bg-indigo-600/20 text-white flex items-center justify-between">
                                <span>English</span>
                                <span id="lang-check-en" class="text-[10px] text-emerald-400 font-bold">&check;</span>
                            </button>
                            <button type="button" onclick="selectLanguage('বাংলা')" class="w-full text-left px-3.5 py-2 hover:bg-indigo-600/20 text-slate-300 hover:text-white flex items-center justify-between">
                                <span>বাংলা</span>
                                <span id="lang-check-bn" class="hidden text-[10px] text-emerald-400 font-bold">&check;</span>
                            </button>
                        </div>
                    </div>
                </nav>

                <!-- Auth Action Buttons -->
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a 
                                href="{{ url('/dashboard') }}" 
                                id="btn-nav-workspace"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                            >
                                <span>Open Workspace</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        @else
                            <a 
                                href="{{ route('login') }}" 
                                id="btn-nav-login"
                                class="hidden sm:inline-flex px-3.5 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/[0.05] rounded-xl transition-colors"
                            >
                                Sign In
                            </a>
                            @if (Route::has('register'))
                                <a 
                                    href="{{ route('register') }}" 
                                    id="btn-nav-register"
                                    class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    <span>Free Trial</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endif
                        @endauth
                    @endif

                    <!-- Mobile Menu Button -->
                    <button 
                        id="btn-mobile-menu"
                        class="md:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/[0.05] focus:outline-none"
                        aria-label="Toggle Navigation Menu"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Dropdown Navigation -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-white/[0.06] bg-[#070b1a]/95 backdrop-blur-xl px-4 pt-3 pb-6 space-y-3">
                <a href="#features" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Features</a>
                <a href="#pricing" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Pricing</a>
                <a href="#reviews" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Review</a>
                <a href="#faq" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">FAQ</a>
                <div class="py-2 flex items-center justify-between border-y border-white/[0.06] text-xs text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        Language
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-mobile-lang-en" onclick="selectLanguage('English')" class="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 font-bold">EN</button>
                        <button type="button" id="btn-mobile-lang-bn" onclick="selectLanguage('বাংলা')" class="px-2 py-1 rounded bg-white/[0.05] text-slate-300">বাংলা</button>
                    </div>
                </div>
                <div class="pt-2 flex flex-col gap-2">
                    <a href="{{ route('login') }}" class="w-full text-center py-2.5 rounded-lg bg-white/[0.05] text-xs font-bold text-white">Sign In</a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-lg bg-indigo-600 text-xs font-bold text-white">Free Trial</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- 1. Dynamic Hero Slider Section -->
            @php
                $heroSlides = $site['hero_slides'] ?? [];
                if (empty($heroSlides)) {
                    $heroSlides = [
                        [
                            'chip'               => 'Enterprise Multi-Tenant SaaS • 99.99% Uptime SLA • Zero Race Conditions',
                            'headline_prefix'    => 'The Operating System for',
                            'headline_highlight' => 'Modern Inventory & Supply Chains',
                            'subtext'            => 'Empower your organization with multi-warehouse tracking, atomic stock reservation, double-entry financial accounting, POS checkout, workshop service ticketing, and vectorized PDF invoicing — completely tenant-isolated.',
                            'primary_cta_text'   => 'Start 14-Day Free Trial',
                            'primary_cta_url'    => route('register'),
                            'secondary_cta_text' => 'Explore Modules',
                            'secondary_cta_url'  => '#features',
                            'mockup_type'        => 'inventory',
                            'trust_badges'       => [
                                'No Credit Card Required',
                                'Instant Setup & Seeding',
                                'Pessimistic Row-Locking',
                                'bKash, Nagad & Cards Supported',
                            ],
                        ],
                    ];
                }
                $slideCount = count($heroSlides);
            @endphp

            <section class="relative pt-6 pb-20 md:pt-12 md:pb-28 overflow-hidden select-none">
                <div class="absolute inset-0 bg-grid-mesh pointer-events-none"></div>

                <!-- Hero Slider Container -->
                <div id="hero-slider-container" class="relative z-10 w-full overflow-hidden" data-slide-count="{{ $slideCount }}">
                    <!-- Track containing all slides -->
                    <div id="hero-track" class="flex will-change-transform" style="transition: transform 0.65s cubic-bezier(0.25, 1, 0.5, 1);">
                        @foreach ($heroSlides as $sIdx => $slide)
                            <div class="hero-slide w-full flex-shrink-0 px-4 sm:px-6 lg:px-8 text-center" data-index="{{ $sIdx }}">
                                <div class="max-w-7xl mx-auto">
                                    <!-- Notification Chip -->
                                    <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold mb-6 backdrop-blur-md">
                                        <span class="flex h-2 w-2 relative">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                                        </span>
                                        <span>{{ $slide['chip'] ?? 'Enterprise Multi-Tenant Platform' }}</span>
                                    </div>

                                    <!-- Main H1 Headline -->
                                    <h1 class="font-heading font-extrabold text-3xl sm:text-5xl md:text-6xl lg:text-7xl tracking-tight leading-[1.08] max-w-5xl mx-auto mb-6">
                                        <span>{{ $slide['headline_prefix'] ?? 'The Operating System for' }}</span>
                                        <br class="hidden sm:inline" />
                                        <span class="gradient-accent">{{ $slide['headline_highlight'] ?? ($site['brand_name'] ?? 'InoManage') }}</span>
                                    </h1>

                                    <!-- Subheadline -->
                                    <p class="text-sm sm:text-base md:text-lg lg:text-xl text-slate-400 max-w-3xl mx-auto mb-8 leading-relaxed font-normal">
                                        {{ $slide['subtext'] ?? '' }}
                                    </p>

                                    <!-- Call To Action Buttons -->
                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-10">
                                        @auth
                                            <a 
                                                href="{{ route('dashboard') }}" 
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                                            >
                                                <span>Go to Dashboard</span>
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </a>
                                        @else
                                            <a 
                                                href="{{ !empty($slide['primary_cta_url']) ? $slide['primary_cta_url'] : route('register') }}" 
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                                            >
                                                <span>{{ !empty($slide['primary_cta_text']) ? $slide['primary_cta_text'] : 'Start 14-Day Free Trial' }}</span>
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </a>
                                            <a 
                                                href="{{ !empty($slide['secondary_cta_url']) ? $slide['secondary_cta_url'] : '#features' }}" 
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] text-slate-200 border border-white/[0.1] font-semibold text-sm transition-all"
                                            >
                                                <span>{{ !empty($slide['secondary_cta_text']) ? $slide['secondary_cta_text'] : 'Explore Modules' }}</span>
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </a>
                                        @endauth
                                    </div>

                                    <!-- Trust Indicators -->
                                    @if (!empty($slide['trust_badges']) && is_array($slide['trust_badges']))
                                        <div class="flex flex-wrap items-center justify-center gap-y-2.5 gap-x-6 text-xs font-medium text-slate-400 mb-10">
                                            @foreach ($slide['trust_badges'] as $badge)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span>{{ $badge }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Dynamic Mockup Screen or Custom Uploaded Image -->
                                    @php $mockupType = $slide['mockup_type'] ?? 'inventory'; @endphp

                                    @if (!empty($slide['image_url']))
                                        <!-- Custom Uploaded Slide Image in Browser Mockup Frame -->
                                        <div class="mt-8 max-w-5xl mx-auto rounded-2xl p-1 sm:p-2 bg-gradient-to-b from-indigo-500/25 via-violet-500/10 to-transparent shadow-2xl shadow-indigo-950/70">
                                            <div class="rounded-xl bg-[#0a0f24] border border-white/[0.08] overflow-hidden shadow-2xl">
                                                <!-- Window Top Bar -->
                                                <div class="px-4 py-3 bg-[#070b1a] border-b border-white/[0.06] flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                                                        <div class="ml-3 hidden sm:flex items-center gap-2 px-3 py-1 rounded-md bg-white/[0.04] text-[11px] font-mono text-slate-400 border border-white/[0.04]">
                                                            <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                            <span>https://inomanage.com/modules</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-300 font-semibold border border-indigo-500/30 text-[10px]">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                                                            {{ $slide['headline_highlight'] ?? 'Cloud Platform Showcase' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Image Display Interior -->
                                                <div class="p-3 sm:p-5 bg-slate-950/60 flex items-center justify-center overflow-hidden">
                                                    <img 
                                                        src="{{ $slide['image_url'] }}" 
                                                        alt="{{ $slide['headline_highlight'] ?? 'Slide Visual' }}" 
                                                        class="w-full max-h-[540px] object-contain rounded-lg shadow-2xl hover:scale-[1.01] transition-transform duration-300"
                                                        loading="lazy"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                    @elseif ($mockupType === 'pos')
                                        <!-- Mockup 2: High-Speed POS Billing Terminal -->
                                        <div class="mt-8 max-w-5xl mx-auto rounded-2xl p-1 sm:p-2 bg-gradient-to-b from-cyan-500/20 via-indigo-500/10 to-transparent shadow-2xl shadow-cyan-950/60">
                                            <div class="rounded-xl bg-[#0a0f24] border border-white/[0.08] overflow-hidden shadow-2xl">
                                                <!-- Window Top Bar -->
                                                <div class="px-4 py-3 bg-[#070b1a] border-b border-white/[0.06] flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                                                        <div class="ml-3 hidden sm:flex items-center gap-2 px-3 py-1 rounded-md bg-white/[0.04] text-[11px] font-mono text-slate-400 border border-white/[0.04]">
                                                            <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                            <span>https://inomanage.com/pos/terminal-01</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20 text-[10px]">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                                                            POS Terminal Active • Thermal Print Ready
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- POS Workspace Interior -->
                                                <div class="p-4 sm:p-6 text-left grid grid-cols-1 lg:grid-cols-12 gap-5">
                                                    <!-- Left: Product Quick Selector & Barcode -->
                                                    <div class="lg:col-span-7 space-y-4">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                                                <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                                                <span>Fast Catalog & Barcode Reader</span>
                                                            </div>
                                                            <span class="text-[11px] text-slate-400 font-mono">Scan or Click item</span>
                                                        </div>

                                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                                            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.08] hover:border-cyan-500/40 transition cursor-pointer">
                                                                <div class="text-[10px] text-cyan-400 font-mono">SKU-8821</div>
                                                                <div class="text-xs font-bold text-white mt-1">Wireless Barcode Scanner</div>
                                                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-white/[0.06]">
                                                                    <span class="text-xs font-black text-cyan-300">৳ 2,450</span>
                                                                    <span class="text-[10px] text-slate-400">18 in stock</span>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.08] hover:border-cyan-500/40 transition cursor-pointer">
                                                                <div class="text-[10px] text-cyan-400 font-mono">SKU-3190</div>
                                                                <div class="text-xs font-bold text-white mt-1">Thermal Receipt Paper 80mm</div>
                                                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-white/[0.06]">
                                                                    <span class="text-xs font-black text-cyan-300">৳ 350</span>
                                                                    <span class="text-[10px] text-slate-400">140 rolls</span>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.08] hover:border-cyan-500/40 transition cursor-pointer">
                                                                <div class="text-[10px] text-cyan-400 font-mono">SKU-6045</div>
                                                                <div class="text-xs font-bold text-white mt-1">Heavy-Duty Cash Drawer</div>
                                                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-white/[0.06]">
                                                                    <span class="text-xs font-black text-cyan-300">৳ 4,200</span>
                                                                    <span class="text-[10px] text-slate-400">9 in stock</span>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.08] hover:border-cyan-500/40 transition cursor-pointer">
                                                                <div class="text-[10px] text-cyan-400 font-mono">SKU-1092</div>
                                                                <div class="text-xs font-bold text-white mt-1">Industrial Label Printer</div>
                                                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-white/[0.06]">
                                                                    <span class="text-xs font-black text-cyan-300">৳ 18,500</span>
                                                                    <span class="text-[10px] text-slate-400">4 in stock</span>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.08] hover:border-cyan-500/40 transition cursor-pointer">
                                                                <div class="text-[10px] text-cyan-400 font-mono">SKU-7740</div>
                                                                <div class="text-xs font-bold text-white mt-1">Customer Display Monitor</div>
                                                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-white/[0.06]">
                                                                    <span class="text-xs font-black text-cyan-300">৳ 7,800</span>
                                                                    <span class="text-[10px] text-slate-400">12 in stock</span>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.08] hover:border-cyan-500/40 transition cursor-pointer">
                                                                <div class="text-[10px] text-cyan-400 font-mono">SKU-5501</div>
                                                                <div class="text-xs font-bold text-white mt-1">POS Touch Terminal Hub</div>
                                                                <div class="flex items-center justify-between mt-2 pt-1 border-t border-white/[0.06]">
                                                                    <span class="text-xs font-black text-cyan-300">৳ 32,000</span>
                                                                    <span class="text-[10px] text-slate-400">6 in stock</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Right: Live Active Order & Payment Tender -->
                                                    <div class="lg:col-span-5 p-4 rounded-xl bg-white/[0.02] border border-white/[0.06] flex flex-col justify-between space-y-4">
                                                        <div>
                                                            <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                                                                <div class="text-xs font-bold text-white">Current Order #POS-4891</div>
                                                                <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-400 font-bold">Walk-in Customer</span>
                                                            </div>

                                                            <div class="divide-y divide-white/[0.04] text-xs pt-2 space-y-2">
                                                                <div class="flex items-center justify-between py-1">
                                                                    <div>
                                                                        <div class="font-semibold text-slate-200">Wireless Barcode Scanner</div>
                                                                        <div class="text-[10px] text-slate-400">৳ 2,450 x 2</div>
                                                                    </div>
                                                                    <span class="font-bold text-white">৳ 4,900</span>
                                                                </div>
                                                                <div class="flex items-center justify-between py-1">
                                                                    <div>
                                                                        <div class="font-semibold text-slate-200">Thermal Receipt Paper 80mm</div>
                                                                        <div class="text-[10px] text-slate-400">৳ 350 x 5</div>
                                                                    </div>
                                                                    <span class="font-bold text-white">৳ 1,750</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="space-y-2 pt-3 border-t border-white/[0.06]">
                                                            <div class="flex justify-between text-xs text-slate-400">
                                                                <span>Subtotal</span>
                                                                <span class="text-slate-200">৳ 6,650</span>
                                                            </div>
                                                            <div class="flex justify-between text-xs text-slate-400">
                                                                <span>VAT (5%)</span>
                                                                <span class="text-slate-200">৳ 332.50</span>
                                                            </div>
                                                            <div class="flex justify-between text-xs text-emerald-400">
                                                                <span>Instant Discount</span>
                                                                <span>- ৳ 200</span>
                                                            </div>
                                                            <div class="flex justify-between text-base font-black text-white pt-1 border-t border-white/[0.06]">
                                                                <span>Grand Total</span>
                                                                <span class="text-cyan-400">৳ 6,782.50</span>
                                                            </div>

                                                            <!-- Tender buttons -->
                                                            <div class="grid grid-cols-4 gap-1.5 pt-2">
                                                                <span class="text-center py-1.5 rounded-lg bg-pink-500/15 border border-pink-500/30 text-[11px] font-bold text-pink-400">bKash</span>
                                                                <span class="text-center py-1.5 rounded-lg bg-amber-500/15 border border-amber-500/30 text-[11px] font-bold text-amber-400">Nagad</span>
                                                                <span class="text-center py-1.5 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-[11px] font-bold text-emerald-400">Cash</span>
                                                                <span class="text-center py-1.5 rounded-lg bg-indigo-500/15 border border-indigo-500/30 text-[11px] font-bold text-indigo-400">Card</span>
                                                            </div>

                                                            <div class="w-full text-center py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs shadow-lg shadow-cyan-600/30">
                                                                Complete Checkout & Print Receipt &rarr;
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    @elseif ($mockupType === 'accounting')
                                        <!-- Mockup 3: Real-Time Double-Entry Accounting & Ledger -->
                                        <div class="mt-8 max-w-5xl mx-auto rounded-2xl p-1 sm:p-2 bg-gradient-to-b from-violet-500/20 via-purple-500/10 to-transparent shadow-2xl shadow-purple-950/60">
                                            <div class="rounded-xl bg-[#0a0f24] border border-white/[0.08] overflow-hidden shadow-2xl">
                                                <!-- Window Top Bar -->
                                                <div class="px-4 py-3 bg-[#070b1a] border-b border-white/[0.06] flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                                                        <div class="ml-3 hidden sm:flex items-center gap-2 px-3 py-1 rounded-md bg-white/[0.04] text-[11px] font-mono text-slate-400 border border-white/[0.04]">
                                                            <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                            <span>https://inomanage.com/reports/general-ledger</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-violet-500/10 text-violet-400 font-semibold border border-violet-500/20 text-[10px]">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse"></span>
                                                            Double-Entry Engine • Real-Time Balanced
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Accounting Dashboard Interior -->
                                                <div class="p-4 sm:p-6 text-left space-y-6">
                                                    <!-- KPI Metrics -->
                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Assets</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-white mt-1">৳ 64,800,000</div>
                                                            <div class="text-[11px] font-medium text-emerald-400 mt-1">&uarr; 18.2% YoY growth</div>
                                                        </div>
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Liabilities</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-rose-400 mt-1">৳ 14,250,000</div>
                                                            <div class="text-[11px] font-medium text-slate-400 mt-1">Payables in order</div>
                                                        </div>
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Net Profit Margin</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-emerald-400 mt-1">29.4%</div>
                                                            <div class="text-[11px] font-medium text-emerald-400 mt-1">&uarr; 4.1% efficiency</div>
                                                        </div>
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Trial Balance Status</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-violet-400 mt-1">Balanced</div>
                                                            <div class="text-[11px] font-medium text-violet-400/80 mt-1">Debit = Credit (0 discrepancy)</div>
                                                        </div>
                                                    </div>

                                                    <!-- Auto-Posted Journal Entries Table -->
                                                    <div class="p-4 sm:p-5 rounded-xl bg-white/[0.02] border border-white/[0.06] space-y-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-xs font-bold text-white uppercase tracking-wider">Automated Journal Vouchers (Live Feed)</div>
                                                            <span class="text-[11px] text-violet-400 font-semibold">Audit-Grade Traceability</span>
                                                        </div>

                                                        <div class="overflow-x-auto">
                                                            <table class="w-full text-xs text-left">
                                                                <thead>
                                                                    <tr class="text-slate-400 border-b border-white/[0.06]">
                                                                        <th class="pb-2 font-medium">Voucher #</th>
                                                                        <th class="pb-2 font-medium">Source Transaction</th>
                                                                        <th class="pb-2 font-medium">Account Affected</th>
                                                                        <th class="pb-2 font-medium text-right">Debit (৳)</th>
                                                                        <th class="pb-2 font-medium text-right">Credit (৳)</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-white/[0.04] text-slate-200">
                                                                    <tr>
                                                                        <td class="py-2.5 font-mono text-violet-400">JV-2026-0812</td>
                                                                        <td class="py-2.5">POS Invoice #4891</td>
                                                                        <td class="py-2.5">Accounts Receivable (1100)</td>
                                                                        <td class="py-2.5 text-right font-mono text-emerald-400">৳ 6,782.50</td>
                                                                        <td class="py-2.5 text-right font-mono text-slate-500">—</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="py-2.5 font-mono text-violet-400">JV-2026-0812</td>
                                                                        <td class="py-2.5">POS Invoice #4891</td>
                                                                        <td class="py-2.5">Sales Revenue (4000)</td>
                                                                        <td class="py-2.5 text-right font-mono text-slate-500">—</td>
                                                                        <td class="py-2.5 text-right font-mono text-cyan-400">৳ 6,450.00</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="py-2.5 font-mono text-violet-400">JV-2026-0812</td>
                                                                        <td class="py-2.5">POS Invoice #4891</td>
                                                                        <td class="py-2.5">Output VAT Payable (2200)</td>
                                                                        <td class="py-2.5 text-right font-mono text-slate-500">—</td>
                                                                        <td class="py-2.5 text-right font-mono text-cyan-400">৳ 332.50</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="py-2.5 font-mono text-violet-400">JV-2026-0813</td>
                                                                        <td class="py-2.5">Supplier PO-2026-0412</td>
                                                                        <td class="py-2.5">Inventory Asset (1200)</td>
                                                                        <td class="py-2.5 text-right font-mono text-emerald-400">৳ 145,000.00</td>
                                                                        <td class="py-2.5 text-right font-mono text-slate-500">—</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    @else
                                        <!-- Mockup 1: Inventory & Multi-Warehouse Allocation -->
                                        <div class="mt-8 max-w-5xl mx-auto rounded-2xl p-1 sm:p-2 bg-gradient-to-b from-white/[0.12] to-transparent shadow-2xl shadow-indigo-950/80">
                                            <div class="rounded-xl bg-[#0a0f24] border border-white/[0.08] overflow-hidden shadow-2xl">
                                                <!-- Window Top Bar -->
                                                <div class="px-4 py-3 bg-[#070b1a] border-b border-white/[0.06] flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                                                        <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                                                        <div class="ml-3 hidden sm:flex items-center gap-2 px-3 py-1 rounded-md bg-white/[0.04] text-[11px] font-mono text-slate-400 border border-white/[0.04]">
                                                            <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                            <span>https://inomanage.com/dashboard</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20 text-[10px]">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                            Live Tenant Session
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Mockup Application Interior -->
                                                <div class="p-4 sm:p-6 text-left space-y-6">
                                                    <!-- Top KPI Metrics Strip -->
                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Inventory Valuation</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-white mt-1">৳ 48,250,000</div>
                                                            <div class="text-[11px] font-medium text-emerald-400 mt-1 flex items-center gap-1">
                                                                <span>&uarr; 14.8%</span>
                                                                <span class="text-slate-500">vs last quarter</span>
                                                            </div>
                                                        </div>
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Monthly Net Revenue</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-indigo-400 mt-1">৳ 12,480,000</div>
                                                            <div class="text-[11px] font-medium text-emerald-400 mt-1 flex items-center gap-1">
                                                                <span>&uarr; 9.2%</span>
                                                                <span class="text-slate-500">824 orders fulfilled</span>
                                                            </div>
                                                        </div>
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Active Warehouses</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-white mt-1">4 Hubs</div>
                                                            <div class="text-[11px] font-medium text-slate-400 mt-1">Dhaka, Ctg &amp; Sylhet</div>
                                                        </div>
                                                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Low Stock Radar</div>
                                                            <div class="text-xl sm:text-2xl font-extrabold text-amber-400 mt-1">3 Items</div>
                                                            <div class="text-[11px] font-medium text-amber-400/80 mt-1">Reorder threshold reached</div>
                                                        </div>
                                                    </div>

                                                    <!-- Middle Panels: Stock Ledger & Orders -->
                                                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                                        <!-- Stock Allocation Chart -->
                                                        <div class="lg:col-span-7 p-4 sm:p-5 rounded-xl bg-white/[0.02] border border-white/[0.06] space-y-3">
                                                            <div class="flex items-center justify-between">
                                                                <div class="text-xs font-bold text-white uppercase tracking-wider">Warehouse Distribution</div>
                                                                <span class="text-[11px] text-indigo-400 font-semibold">Real-Time Sync</span>
                                                            </div>
                                                            <div class="space-y-3 pt-2">
                                                                <div>
                                                                    <div class="flex justify-between text-xs mb-1 font-medium">
                                                                        <span class="text-slate-300">Central Hub — Motijheel (MCH-01)</span>
                                                                        <span class="text-white font-bold">14,800 units (74%)</span>
                                                                    </div>
                                                                    <div class="w-full h-2 rounded-full bg-white/[0.05] overflow-hidden">
                                                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-400 rounded-full" style="width: 74%"></div>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="flex justify-between text-xs mb-1 font-medium">
                                                                        <span class="text-slate-300">Chittagong Port Terminal (CPT-02)</span>
                                                                        <span class="text-white font-bold">8,450 units (56%)</span>
                                                                    </div>
                                                                    <div class="w-full h-2 rounded-full bg-white/[0.05] overflow-hidden">
                                                                        <div class="h-full bg-gradient-to-r from-violet-500 to-purple-400 rounded-full" style="width: 56%"></div>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="flex justify-between text-xs mb-1 font-medium">
                                                                        <span class="text-slate-300">Sylhet Regional Depot (SRD-03)</span>
                                                                        <span class="text-white font-bold">3,200 units (32%)</span>
                                                                    </div>
                                                                    <div class="w-full h-2 rounded-full bg-white/[0.05] overflow-hidden">
                                                                        <div class="h-full bg-gradient-to-r from-cyan-500 to-teal-400 rounded-full" style="width: 32%"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Recent Dispatches List -->
                                                        <div class="lg:col-span-5 p-4 sm:p-5 rounded-xl bg-white/[0.02] border border-white/[0.06] space-y-3">
                                                            <div class="flex items-center justify-between">
                                                                <div class="text-xs font-bold text-white uppercase tracking-wider">Live Fulfillment Feed</div>
                                                                <span class="text-[11px] text-slate-400 font-mono">PO / SO</span>
                                                            </div>
                                                            <div class="space-y-2 pt-1 text-xs">
                                                                <div class="p-2.5 rounded-lg bg-white/[0.02] flex items-center justify-between border border-white/[0.04]">
                                                                    <div>
                                                                        <div class="font-bold text-slate-200">SO-2026-0894</div>
                                                                        <div class="text-[11px] text-slate-400">Apex Traders Ltd &bull; 40 units</div>
                                                                    </div>
                                                                    <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 font-bold text-[10px] border border-emerald-500/20">Dispatched</span>
                                                                </div>
                                                                <div class="p-2.5 rounded-lg bg-white/[0.02] flex items-center justify-between border border-white/[0.04]">
                                                                    <div>
                                                                        <div class="font-bold text-slate-200">PO-2026-0412</div>
                                                                        <div class="text-[11px] text-slate-400">Lenovo Global &bull; Inbound dock</div>
                                                                    </div>
                                                                    <span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-400 font-bold text-[10px] border border-amber-500/20">Dock Receiving</span>
                                                                </div>
                                                                <div class="p-2.5 rounded-lg bg-white/[0.02] flex items-center justify-between border border-white/[0.04]">
                                                                    <div>
                                                                        <div class="font-bold text-slate-200">SRV-2026-0158</div>
                                                                        <div class="text-[11px] text-slate-400">Workshop &bull; GPU replacement</div>
                                                                    </div>
                                                                    <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 font-bold text-[10px] border border-indigo-500/20">In Repair</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Floating Navigation Controls -->
                    @if ($slideCount > 1)
                        <button 
                            type="button" 
                            id="btn-hero-prev" 
                            class="absolute left-3 sm:left-8 top-1/3 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-slate-900/80 border border-white/10 text-slate-300 hover:text-white flex items-center justify-center backdrop-blur-md hover:bg-indigo-600 transition-all hover:scale-110 shadow-xl shadow-black/40 focus:outline-none"
                            aria-label="Previous Slide"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button 
                            type="button" 
                            id="btn-hero-next" 
                            class="absolute right-3 sm:right-8 top-1/3 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-slate-900/80 border border-white/10 text-slate-300 hover:text-white flex items-center justify-center backdrop-blur-md hover:bg-indigo-600 transition-all hover:scale-110 shadow-xl shadow-black/40 focus:outline-none"
                            aria-label="Next Slide"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- Bottom Dots / Slide Indicators -->
                        <div class="flex items-center justify-center gap-3 mt-10 relative z-20">
                            <div id="hero-dots" class="flex items-center gap-2"></div>
                        </div>
                    @endif
                </div>
            </section>

            <!-- 2. Features: Comprehensive 10-Module ERP Suite -->
            <section id="features" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-white/[0.06]">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-4 border border-indigo-500/20">
                        Unified Business Operating System
                    </div>
                    <h2 class="font-heading font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight">
                        Ten Enterprise Engines.<br>
                        <span class="gradient-accent">Zero Third-Party Clutter.</span>
                    </h2>
                    <p class="text-slate-400 mt-4 text-base sm:text-lg">
                        Replace messy spreadsheets and fragmented apps. {{ $site['brand_name'] }} brings warehouse operations, sales, double-entry accounting, workshop services, and payroll into one cohesive platform.
                    </p>
                </div>

                <!-- 10 Modules Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Module 1 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">1. Multi-Warehouse Stock Engine</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Unlimited distribution hubs, default fulfillment routing, inter-warehouse atomic transfers, and cycle-count stock adjustments with audit reasons.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-indigo-400">
                            <span>Pessimistic Row-Locking</span> &bull; <span>Bin Locations</span>
                        </div>
                    </div>

                    <!-- Module 2 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-violet-500/15 border border-violet-500/30 flex items-center justify-center text-violet-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">2. Procurement &amp; Vendor Dues</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Supplier directory, multi-stage purchase orders (Draft ➔ Ordered ➔ Received), advance deposits, partial dock stock-in, and printable payment vouchers.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-violet-400">
                            <span>Vendor Ledger</span> &bull; <span>Payables Tracking</span>
                        </div>
                    </div>

                    <!-- Module 3 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">3. Sales, POS &amp; Customer CRM</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Quotations with 1-click PO/SO conversion, fast-checkout POS counter with barcode support, stock reservations upon confirmation, and customer due ledgers.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-cyan-400">
                            <span>Stock Reservation</span> &bull; <span>Credit Limits</span>
                        </div>
                    </div>

                    <!-- Module 4 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">4. Double-Entry Accounting</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Standardized Chart of Accounts (COA), automated journal entries from sales/inventory, Trial Balance, real-time Profit &amp; Loss, and Balance Sheet generation.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-emerald-400">
                            <span>General Ledger</span> &bull; <span>Balanced Debits/Credits</span>
                        </div>
                    </div>

                    <!-- Module 5 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">5. Bank &amp; Account Reconciliation</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Bank and cash account ledgers, interactive statement audits, discrepancy variance calculation, and audit state verification (`In Progress`, `Reconciled`).
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-amber-400">
                            <span>Audit Logs</span> &bull; <span>Statement Verification</span>
                        </div>
                    </div>

                    <!-- Module 6 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">6. Workshop &amp; Repair Services</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Complete service ticketing pipeline, technician assignment, spare parts consumption automatically deducting inventory, printable tokens, and repair invoices.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-rose-400">
                            <span>Spare Inventory Sync</span> &bull; <span>Service Slips</span>
                        </div>
                    </div>

                    <!-- Module 7 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">7. Warranties &amp; Returns (RMA)</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Serial-number warranty lookups, customer defective claim registration, supplier return dispatch, and scrap or restock routing.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-blue-400">
                            <span>Serial Validation</span> &bull; <span>RMA Claims</span>
                        </div>
                    </div>

                    <!-- Module 8 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center text-orange-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">8. Manufacturing &amp; Assembly (BOM)</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Bill of Materials (BOM) recipes, raw material consumption runs, finished goods production orders, scrap tracking, and output valuation.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-orange-400">
                            <span>Assembly Recipes</span> &bull; <span>Yield Calculations</span>
                        </div>
                    </div>

                    <!-- Module 9 -->
                    <div class="glass-panel rounded-2xl p-7 relative group">
                        <div class="w-12 h-12 rounded-xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-teal-400 mb-5 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h3 class="font-heading font-bold text-lg text-white mb-2">9. HR, Staff &amp; Automated Payroll</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                            Employee records, department taxonomy, attendance tracking, 1-click monthly salary generation, deductions, bonuses, and downloadable PDF payslips.
                        </p>
                        <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-2 text-[11px] font-semibold text-teal-400">
                            <span>Salary Slips</span> &bull; <span>Attendance Auditing</span>
                        </div>
                    </div>

                    <!-- Module 10 -->
                    <div class="glass-panel rounded-2xl p-7 relative group md:col-span-2 lg:col-span-3 bg-gradient-to-r from-amber-500/[0.04] via-indigo-500/[0.04] to-violet-500/[0.04]">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                            <div>
                                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-[10px] font-bold uppercase tracking-wider mb-2 border border-amber-500/30">
                                    Platform Operator Hub
                                </div>
                                <h3 class="font-heading font-bold text-xl text-white mb-2">10. Super Admin Platform Control Plane</h3>
                                <p class="text-slate-400 text-xs sm:text-sm max-w-3xl leading-relaxed">
                                    Complete platform management: monitor all client tenants, suspend/reactivate non-paying organizations, approve manual offline payments (bKash, Nagad TrxID), configure plans, and jump into any tenant workspace via safe Impersonation Mode.
                                </p>
                            </div>
                            <div class="shrink-0 flex items-center gap-3">
                                <span class="px-3 py-1.5 rounded-xl bg-white/[0.04] border border-white/[0.08] text-xs font-mono text-slate-300">
                                    /admin &bull; Master Mode
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Transparent SaaS Pricing Section -->
            <section id="pricing" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-white/[0.06]">
                <div class="text-center max-w-3xl mx-auto mb-14">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-4 border border-indigo-500/20">
                        Transparent Pricing
                    </div>
                    <h2 class="font-heading font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight">
                        Predictable Plans for <br>
                        <span class="gradient-accent">Growing Bangladeshi Enterprises</span>
                    </h2>
                    <p class="text-slate-400 mt-4 text-sm sm:text-base">
                        Start with a full 14-day trial. Upgrade seamlessly with bKash, Nagad, or Bank Transfer. No hidden fees or per-user lock-in.
                    </p>

                    <!-- Billing Cycle Toggle -->
                    <div class="mt-8 inline-flex items-center p-1.5 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
                        <button 
                            type="button"
                            id="toggle-monthly"
                            class="px-5 py-2 rounded-xl text-xs font-bold transition-all toggle-active"
                            onclick="setBillingCycle('monthly')"
                        >
                            Monthly Billing
                        </button>
                        <button 
                            type="button"
                            id="toggle-annual"
                            class="px-5 py-2 rounded-xl text-xs font-bold transition-all text-slate-400 hover:text-white flex items-center gap-1.5"
                            onclick="setBillingCycle('annual')"
                        >
                            <span>Annual Billing</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Save 17%</span>
                        </button>
                    </div>
                </div>

                <!-- Plans Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
                    @forelse ($plans->where('slug', '!=', 'trial') as $plan)
                        @php
                            $isPopular = $plan->slug === 'professional';
                            $monthlyPrice = (float) $plan->price_bdt;
                            $annualPrice = (float) $plan->annual_price_bdt;
                            $effectiveAnnualMonthly = round($annualPrice / 12);
                        @endphp
                        <div class="glass-panel rounded-3xl p-8 relative flex flex-col justify-between {{ $isPopular ? 'border-indigo-500/50 shadow-2xl shadow-indigo-600/15 bg-gradient-to-b from-indigo-950/30 via-slate-900/60 to-slate-950/80 ring-1 ring-indigo-500/40' : '' }}">
                            @if ($isPopular)
                                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 text-white text-[11px] font-extrabold uppercase tracking-wider shadow-md">
                                    Most Popular Choice
                                </div>
                            @endif

                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-2">{{ $plan->name }}</div>
                                <p class="text-xs text-slate-400 min-h-[36px]">{{ $plan->description }}</p>

                                <div class="mt-6 mb-6 pb-6 border-b border-white/[0.06]">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-bold text-slate-400">৳</span>
                                        <span class="font-heading font-black text-4xl sm:text-5xl text-white tracking-tight plan-price" data-monthly="{{ number_format($monthlyPrice, 0) }}" data-annual="{{ number_format($effectiveAnnualMonthly, 0) }}">
                                            {{ number_format($monthlyPrice, 0) }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-semibold plan-period">/ month</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1 font-medium plan-subtext" data-monthly="Billed monthly" data-annual="৳{{ number_format($annualPrice, 0) }} billed annually">
                                        Billed monthly
                                    </div>
                                </div>

                                <!-- Plan Limits Quotas -->
                                <div class="space-y-3 mb-8 text-xs text-slate-300">
                                    <div class="flex items-center gap-2 font-semibold">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $plan->max_warehouses === -1 ? 'Unlimited' : $plan->max_warehouses }} Warehouse {{ $plan->max_warehouses === 1 ? 'Hub' : 'Hubs' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 font-semibold">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $plan->max_products === -1 ? 'Unlimited' : number_format($plan->max_products) }} Products &amp; SKUs</span>
                                    </div>
                                    <div class="flex items-center gap-2 font-semibold">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }} Team Staff Users</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-400">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>POS Billing &amp; Barcode Printing</span>
                                    </div>
                                    <div class="flex items-center gap-2 {{ !empty($plan->features['accounting']) ? 'text-slate-300' : 'text-slate-600' }}">
                                        @if (!empty($plan->features['accounting']))
                                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="font-medium">Double-Entry Accounting &amp; Ledger</span>
                                        @else
                                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span class="line-through">Double-Entry Accounting</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 {{ !empty($plan->features['payroll']) ? 'text-slate-300' : 'text-slate-600' }}">
                                        @if (!empty($plan->features['payroll']))
                                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="font-medium">HR, Attendance &amp; Automated Payroll</span>
                                        @else
                                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span class="line-through">HR &amp; Automated Payroll</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 {{ !empty($plan->features['service_workshop']) ? 'text-slate-300' : 'text-slate-600' }}">
                                        @if (!empty($plan->features['service_workshop']))
                                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="font-medium">Workshop Repair &amp; Service Ticketing</span>
                                        @else
                                            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span class="line-through">Workshop Repair Services</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <a 
                                href="{{ route('register') }}" 
                                class="w-full text-center py-3.5 rounded-xl font-bold text-xs shadow-md transition-all {{ $isPopular ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/30' : 'bg-white/[0.06] hover:bg-white/[0.1] text-slate-200 border border-white/[0.1]' }}"
                            >
                                {{ $isPopular ? 'Choose Professional Plan' : 'Select Plan' }}
                            </a>
                        </div>
                    @empty
                        <!-- Fallback Hardcoded Clean Pricing if DB unseeded -->
                        <div class="glass-panel rounded-3xl p-8 flex flex-col justify-between">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-2">Starter (ছোট ব্যবসা)</div>
                                <p class="text-xs text-slate-400 mb-6">Ideal for single retail shops &amp; computer vendors.</p>
                                <div class="text-4xl font-black text-white mb-6">৳ 999 <span class="text-xs text-slate-400 font-normal">/ mo</span></div>
                                <div class="space-y-3 text-xs text-slate-300 mb-8">
                                    <div>&check; 1 Warehouse Hub</div>
                                    <div>&check; Up to 500 Products</div>
                                    <div>&check; 3 Team Staff Users</div>
                                    <div>&check; POS Billing &amp; Invoices</div>
                                </div>
                            </div>
                            <a href="{{ route('register') }}" class="w-full text-center py-3.5 rounded-xl bg-white/[0.08] text-white text-xs font-bold">Select Plan</a>
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- 4. Customer Reviews (Carousel) Section -->
            <section id="reviews" class="py-24 border-t border-white/[0.06] bg-[#070b1a]/60 relative overflow-hidden">
                <div class="w-full max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-10 relative z-10">
                    <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                        <div class="max-w-2xl">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-4 border border-emerald-500/20">
                                Verified Customer Feedback
                            </div>
                            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight">
                                Loved by Modern Retailers &amp;<br>
                                <span class="gradient-accent">Growing Supply Chains</span>
                            </h2>
                            <p class="text-slate-400 mt-4 text-base sm:text-lg">
                                Real businesses across Bangladesh running multi-branch retail, wholesale distribution, and workshop repair on {{ $site['brand_name'] }}.
                            </p>
                        </div>

                        <!-- Carousel Prev / Next Controls -->
                        <div class="flex items-center gap-3 shrink-0">
                            <button 
                                type="button"
                                id="btn-review-prev"
                                onclick="prevReviewSlide()"
                                aria-label="Previous review"
                                class="w-11 h-11 rounded-xl bg-white/[0.05] hover:bg-white/[0.12] border border-white/[0.1] text-slate-300 hover:text-white flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-md"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button 
                                type="button"
                                id="btn-review-next"
                                onclick="nextReviewSlide()"
                                aria-label="Next review"
                                class="w-11 h-11 rounded-xl bg-indigo-600 hover:bg-indigo-500 border border-indigo-500/30 text-white flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-lg shadow-indigo-600/30"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Carousel Container Window -->
                    @php
                        $testimonialsList = $site['testimonials'] ?? [];
                        $originalCount = count($testimonialsList);
                        // Ensure at least 8-12 items for seamless infinite sliding
                        $displayList = $originalCount > 0 
                            ? array_merge($testimonialsList, $testimonialsList, $testimonialsList)
                            : [];
                    @endphp
                    <div id="reviews-carousel-container" class="relative overflow-hidden -mx-3 py-4" data-original-count="{{ $originalCount }}">
                        <div id="reviews-track" class="flex will-change-transform" style="transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);">
                            @foreach($displayList as $index => $testimonial)
                                <div class="review-slide w-full md:w-1/2 lg:w-1/3 flex-shrink-0 px-3" data-slide-index="{{ $index }}">
                                    <div class="glass-panel h-full rounded-2xl p-7 flex flex-col justify-between border border-white/[0.08] relative group hover:border-indigo-500/40 transition-all shadow-xl">
                                        <div>
                                            <!-- Star Rating -->
                                            <div class="flex items-center gap-1 text-amber-400 mb-4">
                                                @for($s = 0; $s < ($testimonial['rating'] ?? 5); $s++)
                                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                @endfor
                                            </div>
                                            <!-- Quote -->
                                            <p class="text-slate-300 text-sm leading-relaxed mb-6 font-normal">
                                                &ldquo;{{ $testimonial['quote'] }}&rdquo;
                                            </p>
                                        </div>
                                        <!-- Author Info -->
                                        <div class="flex items-center gap-3 pt-4 border-t border-white/[0.06]">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-md">
                                                {{ $testimonial['avatar_text'] ?? strtoupper(substr($testimonial['name'] ?? 'U', 0, 2)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-white leading-tight truncate">{{ $testimonial['name'] }}</h4>
                                                <p class="text-[11px] text-slate-400 truncate">
                                                    {{ $testimonial['role'] }} @if(!empty($testimonial['company'])) &bull; {{ $testimonial['company'] }} @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Carousel Pagination Indicators -->
                    <div id="reviews-dots" class="flex items-center justify-center gap-2 mt-8">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </section>

            <!-- 5. Frequently Asked Questions (Accordion) -->
            <section id="faq" class="py-20 border-t border-white/[0.06] bg-white/[0.01]">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-14">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-4 border border-indigo-500/20">
                            Got Questions?
                        </div>
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-white tracking-tight leading-tight">
                            Frequently Asked Questions
                        </h2>
                    </div>

                    <div class="space-y-4">
                        @foreach($site['faqs'] as $index => $faq)
                        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
                            <button 
                                type="button" 
                                class="w-full flex items-center justify-between text-left text-sm sm:text-base font-bold text-white focus:outline-none"
                                onclick="toggleFaq({{ $index }})"
                            >
                                <span>{{ $faq['question'] }}</span>
                                <svg id="faq-icon-{{ $index }}" class="w-5 h-5 text-indigo-400 transition-transform duration-200 shrink-0 ml-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="faq-answer-{{ $index }}" class="hidden mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed pt-2 border-t border-white/[0.04]">
                                {{ $faq['answer'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/[0.06] bg-[#03060f] py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 pb-10 border-b border-white/[0.06]">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            @if(!empty($site['logo_url']))
                                <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] }}" class="w-8 h-8 rounded-lg object-contain bg-white/10 p-0.5">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-sm">
                                    {{ strtoupper(substr($site['brand_name'], 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-heading font-extrabold text-lg text-white">{{ $site['brand_name'] }}</span>
                        </div>
                        <p class="text-xs text-slate-400 max-w-sm leading-relaxed mb-4">
                            Cloud inventory, supply chain management, and double-entry accounting software designed for resilient enterprises.
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-semibold flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                99.99% Guaranteed SLA Uptime
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-white/[0.04] text-slate-300 border border-white/[0.06] text-[11px] font-semibold">
                                256-Bit SSL Encrypted
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-white mb-3">Platform</div>
                        <ul class="space-y-2 text-xs text-slate-400">
                            <li><a href="#features" class="hover:text-white transition-colors">Warehouse Engine</a></li>
                            <li><a href="#features" class="hover:text-white transition-colors">Double-Entry Accounting</a></li>
                            <li><a href="#features" class="hover:text-white transition-colors">Workshop Services</a></li>
                            <li><a href="#features" class="hover:text-white transition-colors">HR &amp; Payroll</a></li>
                            <li><a href="#pricing" class="hover:text-white transition-colors">Pricing Matrix</a></li>
                        </ul>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-white mb-3">Security &amp; Legal</div>
                        <ul class="space-y-2 text-xs text-slate-400">
                            <li><span class="text-slate-500">Tenant Data Isolation</span></li>
                            <li><span class="text-slate-500">Row-Locking Concurrency</span></li>
                            <li><span class="text-slate-500">Daily Automated Backups</span></li>
                            <li><a href="{{ route('terms.show') }}" class="hover:text-white transition-colors">Terms of Service</a></li>
                            <li><a href="{{ route('policy.show') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        </ul>
                    </div>

                    @if(!empty($site['support_email']) || !empty($site['contact_phone']) || !empty($site['contact_address']))
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-white mb-3">Contact Us</div>
                        <ul class="space-y-2 text-xs text-slate-400">
                            @if(!empty($site['support_email']))
                            <li class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <a href="mailto:{{ $site['support_email'] }}" class="hover:text-white transition-colors truncate">{{ $site['support_email'] }}</a>
                            </li>
                            @endif
                            @if(!empty($site['contact_phone']))
                            <li class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <a href="tel:{{ $site['contact_phone'] }}" class="hover:text-white transition-colors">{{ $site['contact_phone'] }}</a>
                            </li>
                            @endif
                            @if(!empty($site['contact_address']))
                            <li class="flex items-start gap-2 pt-1">
                                <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-slate-400 leading-relaxed text-[11px]">{{ $site['contact_address'] }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    @endif
                </div>

                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                    <div>
                        &copy; {{ date('Y') }} {{ $site['footer_copyright'] }}
                    </div>
                    <div class="flex items-center gap-4">
                        <span>Designed for high-scale enterprise supply chains</span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Interactive Script for Toggle and Accordion -->
        <script>
            // Mobile Menu Toggle
            const btnMobileMenu = document.getElementById('btn-mobile-menu');
            const mobileMenu = document.getElementById('mobile-menu');
            if (btnMobileMenu && mobileMenu) {
                btnMobileMenu.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // Billing Cycle Toggle (Monthly vs Annual)
            function setBillingCycle(cycle) {
                const btnMonthly = document.getElementById('toggle-monthly');
                const btnAnnual = document.getElementById('toggle-annual');
                const prices = document.querySelectorAll('.plan-price');
                const periods = document.querySelectorAll('.plan-period');
                const subtexts = document.querySelectorAll('.plan-subtext');

                if (cycle === 'annual') {
                    btnAnnual.classList.add('toggle-active');
                    btnAnnual.classList.remove('text-slate-400');
                    btnMonthly.classList.remove('toggle-active');
                    btnMonthly.classList.add('text-slate-400');

                    prices.forEach(el => {
                        el.textContent = el.getAttribute('data-annual');
                    });
                    periods.forEach(el => {
                        el.textContent = '/ mo (annual)';
                    });
                    subtexts.forEach(el => {
                        el.textContent = el.getAttribute('data-annual');
                    });
                } else {
                    btnMonthly.classList.add('toggle-active');
                    btnMonthly.classList.remove('text-slate-400');
                    btnAnnual.classList.remove('toggle-active');
                    btnAnnual.classList.add('text-slate-400');

                    prices.forEach(el => {
                        el.textContent = el.getAttribute('data-monthly');
                    });
                    periods.forEach(el => {
                        el.textContent = '/ month';
                    });
                    subtexts.forEach(el => {
                        el.textContent = el.getAttribute('data-monthly');
                    });
                }
            }

            // FAQ Accordion
            function toggleFaq(id) {
                const answer = document.getElementById('faq-answer-' + id);
                const icon = document.getElementById('faq-icon-' + id);
                if (!answer) return;

                const isHidden = answer.classList.contains('hidden');
                // Close all
                document.querySelectorAll('[id^="faq-answer-"]').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('[id^="faq-icon-"]').forEach(el => el.classList.remove('rotate-180'));

                if (isHidden) {
                    answer.classList.remove('hidden');
                    if (icon) icon.classList.add('rotate-180');
                }
            }

            // Infinite Auto-Sliding Review Carousel
            (function() {
                const track = document.getElementById('reviews-track');
                const container = document.getElementById('reviews-carousel-container');
                const dotsContainer = document.getElementById('reviews-dots');
                if (!track || !container) return;

                const originalCount = parseInt(container.getAttribute('data-original-count'), 10) || 4;
                if (originalCount === 0) return;

                let currentIndex = 0;
                let isTransitioning = false;
                let autoplayTimer = null;
                const transitionDuration = 600; // ms
                const slideInterval = 3500; // ms auto slide

                function getVisibleCount() {
                    if (window.innerWidth >= 1024) return 3;
                    if (window.innerWidth >= 768) return 2;
                    return 1;
                }

                function renderDots() {
                    if (!dotsContainer) return;
                    dotsContainer.innerHTML = '';
                    for (let i = 0; i < originalCount; i++) {
                        const dot = document.createElement('button');
                        dot.type = 'button';
                        dot.setAttribute('aria-label', 'Go to testimonial ' + (i + 1));
                        dot.className = (i === (currentIndex % originalCount))
                            ? 'h-2.5 rounded-full transition-all duration-300 w-8 bg-indigo-500 shadow-sm shadow-indigo-500/50'
                            : 'h-2.5 rounded-full transition-all duration-300 w-2.5 bg-white/20 hover:bg-white/40';
                        dot.onclick = () => {
                            goToSlide(i);
                            resetAutoplay();
                        };
                        dotsContainer.appendChild(dot);
                    }
                }

                function updateDots() {
                    if (!dotsContainer) return;
                    const activeIndex = currentIndex % originalCount;
                    const dots = dotsContainer.querySelectorAll('button');
                    dots.forEach((dot, idx) => {
                        if (idx === activeIndex) {
                            dot.className = 'h-2.5 rounded-full transition-all duration-300 w-8 bg-indigo-500 shadow-sm shadow-indigo-500/50';
                        } else {
                            dot.className = 'h-2.5 rounded-full transition-all duration-300 w-2.5 bg-white/20 hover:bg-white/40';
                        }
                    });
                }

                function moveTrack(animate = true) {
                    const visible = getVisibleCount();
                    const percent = currentIndex * (100 / visible);
                    if (animate) {
                        track.style.transition = `transform ${transitionDuration}ms cubic-bezier(0.25, 1, 0.5, 1)`;
                    } else {
                        track.style.transition = 'none';
                    }
                    track.style.transform = `translateX(-${percent}%)`;
                    updateDots();
                }

                function nextSlide() {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    currentIndex++;
                    moveTrack(true);
                }

                function prevSlide() {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    if (currentIndex <= 0) {
                        currentIndex = originalCount;
                        moveTrack(false);
                        void track.offsetHeight;
                    }
                    currentIndex--;
                    moveTrack(true);
                }

                function goToSlide(targetIndex) {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    currentIndex = targetIndex;
                    moveTrack(true);
                }

                track.addEventListener('transitionend', (e) => {
                    if (e.target !== track) return;
                    isTransitioning = false;
                    if (currentIndex >= originalCount) {
                        currentIndex = currentIndex % originalCount;
                        moveTrack(false);
                        void track.offsetHeight;
                    }
                });

                function startAutoplay() {
                    stopAutoplay();
                    autoplayTimer = setInterval(() => {
                        nextSlide();
                    }, slideInterval);
                }

                function stopAutoplay() {
                    if (autoplayTimer) {
                        clearInterval(autoplayTimer);
                        autoplayTimer = null;
                    }
                }

                function resetAutoplay() {
                    stopAutoplay();
                    startAutoplay();
                }

                // Global handlers for Prev/Next button onclicks
                window.nextReviewSlide = function() {
                    nextSlide();
                    resetAutoplay();
                };

                window.prevReviewSlide = function() {
                    prevSlide();
                    resetAutoplay();
                };

                // Pause on hover
                container.addEventListener('mouseenter', stopAutoplay);
                container.addEventListener('mouseleave', startAutoplay);

                // Touch swipe support
                let touchStartX = 0;
                let touchEndX = 0;

                container.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                    stopAutoplay();
                }, { passive: true });

                container.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    const diff = touchStartX - touchEndX;
                    if (Math.abs(diff) > 40) {
                        if (diff > 0) {
                            nextSlide();
                        } else {
                            prevSlide();
                        }
                    }
                    startAutoplay();
                }, { passive: true });

                // Responsive resize
                window.addEventListener('resize', () => {
                    moveTrack(false);
                });

                // Init
                renderDots();
                moveTrack(false);
                startAutoplay();
            })();

            // Dynamic Hero Slider Controller
            (function() {
                const track = document.getElementById('hero-track');
                const container = document.getElementById('hero-slider-container');
                const dotsContainer = document.getElementById('hero-dots');
                const btnPrev = document.getElementById('btn-hero-prev');
                const btnNext = document.getElementById('btn-hero-next');

                if (!track || !container) return;

                const slideCount = parseInt(container.getAttribute('data-slide-count'), 10) || 1;
                if (slideCount <= 1) return;

                let currentIndex = 0;
                let isTransitioning = false;
                let autoplayTimer = null;
                const slideInterval = 5500; // ms

                function renderDots() {
                    if (!dotsContainer) return;
                    dotsContainer.innerHTML = '';
                    for (let i = 0; i < slideCount; i++) {
                        const dot = document.createElement('button');
                        dot.type = 'button';
                        dot.setAttribute('aria-label', 'Go to hero slide ' + (i + 1));
                        dot.className = (i === currentIndex)
                            ? 'h-2.5 rounded-full transition-all duration-300 w-8 bg-indigo-500 shadow-md shadow-indigo-500/50'
                            : 'h-2.5 rounded-full transition-all duration-300 w-2.5 bg-white/20 hover:bg-white/40';
                        dot.onclick = () => {
                            goToSlide(i);
                            resetAutoplay();
                        };
                        dotsContainer.appendChild(dot);
                    }
                }

                function updateDots() {
                    if (!dotsContainer) return;
                    const dots = dotsContainer.querySelectorAll('button');
                    dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                            dot.className = 'h-2.5 rounded-full transition-all duration-300 w-8 bg-indigo-500 shadow-md shadow-indigo-500/50';
                        } else {
                            dot.className = 'h-2.5 rounded-full transition-all duration-300 w-2.5 bg-white/20 hover:bg-white/40';
                        }
                    });
                }

                function goToSlide(index, animate = true) {
                    currentIndex = (index + slideCount) % slideCount;
                    if (animate) {
                        track.style.transition = 'transform 0.65s cubic-bezier(0.25, 1, 0.5, 1)';
                    } else {
                        track.style.transition = 'none';
                    }
                    track.style.transform = `translateX(-${currentIndex * 100}%)`;
                    updateDots();
                }

                function nextSlide() {
                    goToSlide(currentIndex + 1);
                }

                function prevSlide() {
                    goToSlide(currentIndex - 1);
                }

                function startAutoplay() {
                    stopAutoplay();
                    autoplayTimer = setInterval(nextSlide, slideInterval);
                }

                function stopAutoplay() {
                    if (autoplayTimer) {
                        clearInterval(autoplayTimer);
                        autoplayTimer = null;
                    }
                }

                function resetAutoplay() {
                    stopAutoplay();
                    startAutoplay();
                }

                if (btnPrev) {
                    btnPrev.onclick = () => {
                        prevSlide();
                        resetAutoplay();
                    };
                }

                if (btnNext) {
                    btnNext.onclick = () => {
                        nextSlide();
                        resetAutoplay();
                    };
                }

                container.addEventListener('mouseenter', stopAutoplay);
                container.addEventListener('mouseleave', startAutoplay);

                // Touch swipe
                let touchStartX = 0;
                let touchEndX = 0;

                container.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                    stopAutoplay();
                }, { passive: true });

                container.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    const diff = touchStartX - touchEndX;
                    if (Math.abs(diff) > 40) {
                        if (diff > 0) {
                            nextSlide();
                        } else {
                            prevSlide();
                        }
                    }
                    startAutoplay();
                }, { passive: true });

                // Init
                renderDots();
                goToSlide(0, false);
                startAutoplay();
            })();

            // Header Shadow on Scroll
            window.addEventListener('scroll', () => {
                const header = document.getElementById('main-header');
                if (window.scrollY > 30) {
                    header.classList.add('shadow-xl', 'shadow-black/50', 'bg-[#050814]/95');
                } else {
                    header.classList.remove('shadow-xl', 'shadow-black/50', 'bg-[#050814]/95');
                }
            });

            // Language Dropdown Toggle
            function toggleLanguageMenu() {
                const menu = document.getElementById('lang-menu');
                if (menu) menu.classList.toggle('hidden');
            }

            // Comprehensive Bangla & English Translation Map
            const i18nDictionary = {
                // Main Menus & Auth Links
                'Features': 'ফিচারসমূহ',
                'Pricing': 'প্রাইসিং',
                'Review': 'রিভিউ',
                'FAQ': 'প্রশ্নোত্তর',
                'Language': 'ভাষা',
                'Sign In': 'সাইন ইন',
                'Signin': 'সাইন ইন',
                'Free Trail': 'ফ্রি ট্রায়াল',
                'Free Trial': 'ফ্রি ট্রায়াল',
                'Open Workspace': 'ওয়ার্কস্পেস খুলুন',
                'Go to Dashboard': 'ড্যাশবোর্ডে যান',

                // Hero & CTA Actions
                'Start 14-Day Free Trial': '১৪ দিনের ফ্রি ট্রায়াল শুরু করুন',
                'Explore Modules': 'মডিউলগুলো দেখুন',
                'Start Free POS Trial': 'ফ্রি পিওএস ট্রায়াল নিন',
                'See POS Capabilities': 'পিওএস ফিচারগুলো দেখুন',
                'Try Accounting Free': 'অ্যাকাউন্টিং ট্রায়াল নিন',
                'View Financial Reports': 'আর্থিক রিপোর্ট দেখুন',
                'Get Started Now': 'এখনই শুরু করুন',
                'Learn More': 'বিস্তারিত জানুন',

                // Trust Badges & Guarantee Pills
                'No Credit Card Required': 'কোনো ক্রেডিট কার্ড লাগবে না',
                'Instant Setup & Seeding': 'ইনস্ট্যান্ট সেটআপ ও সিডিং',
                'Pessimistic Row-Locking': 'জিরো স্টক মিসম্যাচ গ্যারান্টি',
                'bKash, Nagad & Cards Supported': 'বিকাশ, নগদ ও কার্ড সাপোর্টেড',
                'Thermal Printing Ready': 'থার্মাল প্রিন্ট প্রস্তুত',
                'Split Payments Supported': 'স্প্লিট পেমেন্ট সুবিধা',
                'Barcode & QR Scanning': 'বারকোড ও কিউআর স্ক্যানার',
                'Offline-Resilient Ledger': 'অফলাইন রেজিলিয়েন্ট লেজার',
                'Debit/Credit Balance Validation': 'ডেবিট/ক্রেডিট সমতা নিশ্চিত',
                'Automated Journal Entries': 'স্বয়ংক্রিয় জার্নাল ভাউচার',
                'Real-Time Profit & Loss': 'রিয়েল-টাইম লাভ-ক্ষতি হিসাব',
                'Tax & VAT Ready': 'ট্যাক্স ও ভ্যাট প্রস্তুত',

                // Section Headers & Badges
                'Unified Business Operating System': 'সমন্বিত বিজনেস অপারেটিং সিস্টেম',
                'Ten Enterprise Engines.': '১০টি শক্তিশালী বিজনেস ইঞ্জিন।',
                'Zero Third-Party Clutter.': 'অতিরিক্ত সফটওয়্যার ছাড়া স্বয়ংসম্পূর্ণ।',
                'Transparent Pricing': 'সহজ ও সাশ্রয়ী মূল্য তালিকা',
                'Predictable Plans for': 'সাশ্রয়ী সাবস্ক্রিপশন প্ল্যান',
                'Growing Bangladeshi Enterprises': 'বাংলাদেশের সেরা ব্যবসায়ী প্রতিষ্ঠানের জন্য',
                'Monthly Billing': 'মাসিক বিলিং',
                'Annual Billing': 'বাৎসরিক বিলিং',
                'Save 17%': '১৭% সাশ্রয়',
                'Save 20%': '২০% সাশ্রয়',
                'Most Popular Choice': 'সবচেয়ে জনপ্রিয়',
                'Billed monthly': 'মাসিক বিলিং',
                '/ month': '/ মাস',
                'Select Plan': 'প্ল্যান নির্বাচন করুন',
                'Choose Professional Plan': 'প্রফেশনাল প্ল্যান বেছে নিন',
                'Unlimited': 'আনলিমিটেড',
                'Products & SKUs': 'পণ্য ও এসকেইউ',
                'Team Staff Users': 'টিম স্টাফ ইউজার',
                'POS Billing & Barcode Printing': 'পিওএস বিলিং ও বারকোড প্রিন্টিং',
                'Double-Entry Accounting & Ledger': 'ডাবল-এন্ট্রি অ্যাকাউন্টিং ও লেজার',
                'HR, Attendance & Automated Payroll': 'এইচআর ও স্বয়ংক্রিয় পে-রোল',
                'Workshop Repair & Service Ticketing': 'ওয়ার্কশপ সার্ভিস টিকেটিং',
                'Customer Testimonials': 'গ্রাহকদের মূল্যায়ন ও প্রতিক্রিয়া',
                'Trusted by Leading Importers,': 'বাংলাদেশের শীর্ষস্থানীয় আমদানিকারক,',
                'Distributors & Retail Chains.': 'পাইকারি ও খুচরা ব্যবসায়ীদের আস্থা।',
                'Got Questions?': 'সাধারণ জিজ্ঞাসা',
                'Frequently Asked Questions': 'সচরাচর জিজ্ঞাসিত প্রশ্নাবলি',

                // Mockup interior terms
                'Live Tenant Session': 'সচল লাইভ সেশন',
                'Inventory Valuation': 'ইনভেন্টরি মূল্যায়ন',
                'Monthly Net Revenue': 'মাসিক নিট আয়',
                'Active Warehouses': 'সচল ওয়্যারহাউজ',
                'Low Stock Radar': 'লো-স্টক সতর্কতা',
                'Warehouse Distribution': 'ওয়্যারহাউজ বণ্টন',
                'Real-Time Sync': 'রিয়েল-টাইম সিঙ্ক',
                'Live Fulfillment Feed': 'লাইভ ডেলিভারি ফিড',
                'Dispatched': 'ডেলিভারি সম্পন্ন',
                'Dock Receiving': 'পণ্য গ্রহণ',
                'In Repair': 'মেরামত চলছে',
                'Fast Catalog & Barcode Reader': 'দ্রুত ক্যাটালগ ও বারকোড রিডার',
                'Scan or Click item': 'স্ক্যান বা ক্লিক করুন',
                'in stock': 'মজুদ আছে',
                'Current Order': 'চলতি অর্ডার',
                'Walk-in Customer': 'সাধারণ কাস্টমার',
                'Subtotal': 'সাবটোটাল',
                'Instant Discount': 'তাত্ক্ষণিক ছাড়',
                'Grand Total': 'সর্বমোট বিল',
                'Complete Checkout & Print Receipt →': 'বিল সম্পন্ন করুন ও রসিদ প্রিন্ট করুন →',
                'Complete Checkout & Print Receipt &rarr;': 'বিল সম্পন্ন করুন ও রসিদ প্রিন্ট করুন →',
                'Double-Entry Engine • Real-Time Balanced': 'ডাবল-এন্ট্রি ইঞ্জিন • স্বয়ংক্রিয় ব্যালেন্সড',
                'Total Assets': 'মোট সম্পদ',
                'Total Liabilities': 'মোট দায়',
                'Net Profit Margin': 'নিট মুনাফার হার',
                'Trial Balance Status': 'রেওয়ামিল স্ট্যাটাস',
                'Balanced': 'ব্যালেন্সড (সমান)',
                'Automated Journal Vouchers (Live Feed)': 'স্বয়ংক্রিয় জার্নাল ভাউচার (লাইভ ফিড)',
                'Audit-Grade Traceability': 'অডিট-গ্রেড নির্ভরযোগ্যতা',
                'Voucher #': 'ভাউচার নং',
                'Source Transaction': 'উৎস লেনদেন',
                'Account Affected': 'হিসাব খাত',
                'Debit (৳)': 'ডেবিট (৳)',
                'Credit (৳)': 'ক্রেডিট (৳)',

                // Footer
                'Platform': 'প্ল্যাটফর্ম',
                'Modules': 'মডিউলসমূহ',
                'Pricing Plans': 'মূল্য তালিকা',
                'Customer Reviews': 'গ্রাহক মতামত',
                'Security & Legal': 'নিরাপত্তা ও শর্তাবলি',
                'Terms & Conditions': 'নিয়ম ও শর্তাবলি',
                'Privacy Policy': 'গোপনীয়তা নীতিমালা',
                'Support & Inquiries': 'সহায়তা ও যোগাযোগ',
                '99.99% Uptime SLA Verified': '৯৯.৯৯% আপটাইম এসএলএ নিশ্চিত',
                'All rights reserved.': 'সর্বস্বত্ব সংরক্ষিত।'
            };

            const reverseDictionary = {};
            for (const [enKey, bnVal] of Object.entries(i18nDictionary)) {
                reverseDictionary[bnVal] = enKey;
            }

            function applyLanguage(lang) {
                const isBn = (lang === 'bn' || lang === 'বাংলা');
                const targetMap = isBn ? i18nDictionary : reverseDictionary;

                // Update text indicator
                const textEl = document.getElementById('current-lang-text');
                if (textEl) textEl.textContent = isBn ? 'বাংলা' : 'English';

                // Update checkmarks in dropdown
                const checkEn = document.getElementById('lang-check-en');
                const checkBn = document.getElementById('lang-check-bn');
                if (checkEn) checkEn.classList.toggle('hidden', isBn);
                if (checkBn) checkBn.classList.toggle('hidden', !isBn);

                // Update mobile buttons
                const btnMobileEn = document.getElementById('btn-mobile-lang-en');
                const btnMobileBn = document.getElementById('btn-mobile-lang-bn');
                if (btnMobileEn && btnMobileBn) {
                    if (isBn) {
                        btnMobileEn.className = 'px-2 py-1 rounded bg-white/[0.05] text-slate-300';
                        btnMobileBn.className = 'px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 font-bold';
                    } else {
                        btnMobileEn.className = 'px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 font-bold';
                        btnMobileBn.className = 'px-2 py-1 rounded bg-white/[0.05] text-slate-300';
                    }
                }

                // Traverse text nodes across page
                const walker = document.createTreeWalker(
                    document.body,
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
                            const tag = node.parentElement ? node.parentElement.tagName.toLowerCase() : '';
                            if (tag === 'script' || tag === 'style' || tag === 'textarea') return NodeFilter.FILTER_REJECT;
                            return NodeFilter.FILTER_ACCEPT;
                        }
                    }
                );

                const textNodes = [];
                while (walker.nextNode()) {
                    textNodes.push(walker.currentNode);
                }

                textNodes.forEach(node => {
                    const originalText = node.nodeValue.trim();
                    if (!originalText) return;

                    // Direct match
                    if (targetMap[originalText]) {
                        node.nodeValue = node.nodeValue.replace(originalText, targetMap[originalText]);
                        return;
                    }

                    // Partial match for distinct words/phrases
                    for (const [src, dst] of Object.entries(targetMap)) {
                        if (src.length >= 3 && node.nodeValue.includes(src)) {
                            node.nodeValue = node.nodeValue.split(src).join(dst);
                        }
                    }
                });

                try {
                    localStorage.setItem('welcome_lang', isBn ? 'bn' : 'en');
                } catch(e) {}
            }

            function selectLanguage(lang) {
                const isBn = (lang === 'bn' || lang === 'বাংলা');
                applyLanguage(isBn ? 'bn' : 'en');
                const menu = document.getElementById('lang-menu');
                if (menu) menu.classList.add('hidden');
            }

            // Restore user language on load
            try {
                const saved = localStorage.getItem('welcome_lang');
                if (saved === 'bn') {
                    applyLanguage('bn');
                }
            } catch(e) {}

            document.addEventListener('click', function(e) {
                const wrapper = document.getElementById('lang-dropdown-wrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    const menu = document.getElementById('lang-menu');
                    if (menu) menu.classList.add('hidden');
                }
            });
        </script>
    </body>
</html>
