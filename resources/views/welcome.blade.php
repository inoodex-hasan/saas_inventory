<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Inoodex Inventory & ERP — Enterprise Cloud Inventory, Accounting & Supply Chain Platform</title>
        <meta name="description" content="Next-generation multi-tenant ERP platform for modern businesses. Multi-warehouse tracking, atomic stock locking, double-entry accounting, POS billing, workshop service ticketing, and vectorized PDF invoicing.">

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
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
                        I
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-extrabold text-lg text-white tracking-tight leading-tight">Inoodex</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">ERP</span>
                        </div>
                        <span class="text-[10px] font-semibold tracking-wider text-slate-400 uppercase block">Cloud Inventory Platform</span>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#features" class="hover:text-white transition-colors">Features</a>
                    <a href="#modules" class="hover:text-white transition-colors">ERP Modules</a>
                    <a href="#architecture" class="hover:text-white transition-colors">Architecture</a>
                    <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
                    <a href="#faq" class="hover:text-white transition-colors">FAQ</a>
                </nav>

                <!-- Auth Action Buttons -->
                <div class="flex items-center gap-3.5">
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
                                class="hidden sm:inline-flex px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/[0.05] rounded-xl transition-colors"
                            >
                                Sign In
                            </a>
                            @if (Route::has('register'))
                                <a 
                                    href="{{ route('register') }}" 
                                    id="btn-nav-register"
                                    class="inline-flex items-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    <span>Get Started Free</span>
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
                <a href="#modules" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">ERP Modules</a>
                <a href="#architecture" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Architecture</a>
                <a href="#pricing" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Pricing</a>
                <a href="#faq" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">FAQ</a>
                <div class="pt-2 border-t border-white/[0.06] flex flex-col gap-2">
                    <a href="{{ route('login') }}" class="w-full text-center py-2.5 rounded-lg bg-white/[0.05] text-xs font-bold text-white">Sign In</a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-lg bg-indigo-600 text-xs font-bold text-white">Get Started Free</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- 1. Hero Section -->
            <section class="relative pt-12 pb-24 md:pt-20 md:pb-32 overflow-hidden">
                <div class="absolute inset-0 bg-grid-mesh pointer-events-none"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                    <!-- Notification Chip -->
                    <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold mb-8 backdrop-blur-md">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        <span>Enterprise Multi-Tenant SaaS &bull; 99.99% Uptime SLA &bull; Zero Race Conditions</span>
                    </div>

                    <!-- Main H1 Headline -->
                    <h1 class="font-heading font-extrabold text-4xl sm:text-5xl md:text-6xl lg:text-7xl tracking-tight leading-[1.08] max-w-5xl mx-auto mb-6">
                        The Operating System for <br class="hidden sm:inline" />
                        <span class="gradient-accent">Modern Inventory & Supply Chains</span>
                    </h1>

                    <!-- Subheadline -->
                    <p class="text-base sm:text-lg md:text-xl text-slate-400 max-w-3xl mx-auto mb-10 leading-relaxed font-normal">
                        Empower your organization with multi-warehouse tracking, atomic stock reservation, double-entry financial accounting, POS checkout, workshop service ticketing, and vectorized PDF invoicing — completely tenant-isolated.
                    </p>

                    <!-- Call To Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                        @auth
                            <a 
                                href="{{ route('dashboard') }}" 
                                id="btn-hero-workspace"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                            >
                                <span>Go to Dashboard</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        @else
                            <a 
                                href="{{ route('register') }}" 
                                id="btn-hero-trial"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                            >
                                <span>Start 14-Day Free Trial</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <a 
                                href="#modules" 
                                id="btn-hero-explore"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white/[0.04] hover:bg-white/[0.08] text-slate-200 border border-white/[0.1] font-semibold text-sm transition-all"
                            >
                                <span>Explore Modules</span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </a>
                        @endauth
                    </div>

                    <!-- Trust Indicators -->
                    <div class="flex flex-wrap items-center justify-center gap-y-3 gap-x-8 text-xs font-medium text-slate-400">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>No Credit Card Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Instant Setup & Seeding</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Pessimistic Row-Locking</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>bKash, Nagad & Cards Supported</span>
                        </div>
                    </div>

                    <!-- Interactive Browser Mockup Window -->
                    <div class="mt-14 max-w-5xl mx-auto rounded-2xl p-1 sm:p-2 bg-gradient-to-b from-white/[0.12] to-transparent shadow-2xl shadow-indigo-950/80">
                        <div class="rounded-xl bg-[#0a0f24] border border-white/[0.08] overflow-hidden shadow-2xl">
                            <!-- Window Top Bar -->
                            <div class="px-4 py-3 bg-[#070b1a] border-b border-white/[0.06] flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                                    <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                                    <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                                    <div class="ml-3 hidden sm:flex items-center gap-2 px-3 py-1 rounded-md bg-white/[0.04] text-[11px] font-mono text-slate-400 border border-white/[0.04]">
                                        <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span>https://app.inoodex.com/dashboard</span>
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
                </div>
            </section>

            <!-- 2. Architectural Metrics Bar -->
            <section id="architecture" class="border-y border-white/[0.06] bg-white/[0.015] py-14">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center divide-y lg:divide-y-0 lg:divide-x divide-white/[0.06]">
                        <div class="pt-4 lg:pt-0">
                            <div class="font-heading font-extrabold text-3xl sm:text-4xl text-white tracking-tight">100%</div>
                            <div class="text-xs font-bold uppercase tracking-wider text-indigo-400 mt-1">Tenant Isolation</div>
                            <div class="text-xs text-slate-400 mt-1">Scoped at Eloquent query &amp; DB level</div>
                        </div>
                        <div class="pt-4 lg:pt-0">
                            <div class="font-heading font-extrabold text-3xl sm:text-4xl text-emerald-400 tracking-tight">0</div>
                            <div class="text-xs font-bold uppercase tracking-wider text-emerald-400 mt-1">Race Conditions</div>
                            <div class="text-xs text-slate-400 mt-1">Pessimistic row locking on all movements</div>
                        </div>
                        <div class="pt-4 lg:pt-0">
                            <div class="font-heading font-extrabold text-3xl sm:text-4xl text-white tracking-tight">99.99%</div>
                            <div class="text-xs font-bold uppercase tracking-wider text-indigo-400 mt-1">Uptime Reliability</div>
                            <div class="text-xs text-slate-400 mt-1">High-availability cloud infrastructure</div>
                        </div>
                        <div class="pt-4 lg:pt-0">
                            <div class="font-heading font-extrabold text-3xl sm:text-4xl text-violet-400 tracking-tight">&lt; 40ms</div>
                            <div class="text-xs font-bold uppercase tracking-wider text-violet-400 mt-1">Median Response</div>
                            <div class="text-xs text-slate-400 mt-1">High-throughput indexed database engine</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Comprehensive 10-Module ERP Suite -->
            <section id="modules" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-4 border border-indigo-500/20">
                        Unified Business Operating System
                    </div>
                    <h2 class="font-heading font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight">
                        Ten Enterprise Engines.<br>
                        <span class="gradient-accent">Zero Third-Party Clutter.</span>
                    </h2>
                    <p class="text-slate-400 mt-4 text-base sm:text-lg">
                        Replace messy spreadsheets and fragmented apps. Inoodex brings warehouse operations, sales, double-entry accounting, workshop services, and payroll into one cohesive platform.
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
                    <!-- <div class="glass-panel rounded-2xl p-7 relative group md:col-span-2 lg:col-span-3 bg-gradient-to-r from-amber-500/[0.04] via-indigo-500/[0.04] to-violet-500/[0.04]">
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
                    </div> -->
                </div>
            </section>

            <!-- 4. Deep-Dive Spotlight Feature (Pessimistic Concurrency) -->
            <section class="py-20 border-t border-white/[0.06] bg-white/[0.01]">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-4 border border-emerald-500/20">
                                Zero Data Drift
                            </div>
                            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-white tracking-tight leading-tight mb-6">
                                Industrial Concurrency Engine.<br>
                                <span class="gradient-accent">Never Oversell Again.</span>
                            </h2>
                            <p class="text-slate-400 text-sm sm:text-base leading-relaxed mb-6">
                                When multiple counter cashiers, sales agents, and wholesale channels confirm orders at the exact same second, ordinary inventory software crashes into negative stock and dirty reads.
                            </p>
                            <div class="space-y-4 text-xs sm:text-sm text-slate-300">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">&check;</div>
                                    <div><strong>Hardware-Grade Atomic Lock Queue</strong>: Eliminates simultaneous conflicting writes by serializing stock deductions in microsecond queues.</div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">&check;</div>
                                    <div><strong>Immutable Stock Movement Ledger</strong>: Every single transaction creates a permanent timestamped record with user ID, model reference, and before/after counts.</div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">&check;</div>
                                    <div><strong>Two-Stage Reservation Pipeline</strong>: Sales order confirmation instantly reserves available stock, while physical loading dock dispatch deducts on-hand balances.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Live Visual Concurrency Simulation (No raw code!) -->
                        <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-white/[0.08] shadow-2xl relative overflow-hidden">
                            <div class="flex items-center justify-between pb-4 mb-6 border-b border-white/[0.06]">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                                    <span class="font-heading font-bold text-sm text-white">Active Concurrency Guard</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 text-[11px] font-bold border border-emerald-500/20">
                                    100% Stock Equilibrium
                                </span>
                            </div>

                            <!-- Live Visual Timeline / Flow -->
                            <div class="space-y-4">
                                <!-- Order Request A -->
                                <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06] flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold text-xs shrink-0">
                                            POS
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-white">Counter Cashier 1 &bull; Checkout 5 Units</div>
                                            <div class="text-[11px] text-slate-400">SKU: PROD-RTX4080 &bull; Timestamp: 14:02:18.102</div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 text-[10px] font-bold border border-emerald-500/20 shrink-0">
                                        Reserved &amp; Locked
                                    </span>
                                </div>

                                <!-- Order Request B (Simultaneous) -->
                                <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06] flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-violet-500/15 border border-violet-500/30 flex items-center justify-center text-violet-400 font-bold text-xs shrink-0">
                                            WEB
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-white">E-Commerce Channel &bull; Checkout 8 Units</div>
                                            <div class="text-[11px] text-slate-400">SKU: PROD-RTX4080 &bull; Timestamp: 14:02:18.104</div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 text-[10px] font-bold border border-indigo-500/20 shrink-0">
                                        Queued (0.2ms)
                                    </span>
                                </div>

                                <!-- Transaction Resolution Indicator -->
                                <div class="p-4 rounded-xl bg-gradient-to-r from-emerald-500/[0.08] to-indigo-500/[0.08] border border-emerald-500/25">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-emerald-300">Warehouse Inventory Audit</span>
                                        <span class="text-[11px] text-slate-300 font-mono">Real-Time Balance</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center pt-1">
                                        <div class="p-2 rounded-lg bg-white/[0.03]">
                                            <div class="text-[10px] uppercase text-slate-400 font-semibold">Physical Stock</div>
                                            <div class="text-base font-extrabold text-white mt-0.5">25 Units</div>
                                        </div>
                                        <div class="p-2 rounded-lg bg-white/[0.03]">
                                            <div class="text-[10px] uppercase text-amber-400 font-semibold">Reserved</div>
                                            <div class="text-base font-extrabold text-amber-300 mt-0.5">13 Units</div>
                                        </div>
                                        <div class="p-2 rounded-lg bg-white/[0.03]">
                                            <div class="text-[10px] uppercase text-emerald-400 font-semibold">Available</div>
                                            <div class="text-base font-extrabold text-emerald-300 mt-0.5">12 Units</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Safety Guarantee Badges -->
                                <div class="pt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400 font-medium">
                                    <div class="flex items-center gap-1.5 text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Zero Negative Stock</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Zero Race Conditions</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Permanent Audit Log</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Transparent Bangladeshi SaaS Pricing Section -->
            <section id="pricing" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

            <!-- 6. Frequently Asked Questions (Accordion) -->
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
                        <!-- FAQ 1 -->
                        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
                            <button 
                                type="button" 
                                class="w-full flex items-center justify-between text-left text-sm sm:text-base font-bold text-white focus:outline-none"
                                onclick="toggleFaq(1)"
                            >
                                <span>Is our company data completely isolated from other tenants?</span>
                                <svg id="faq-icon-1" class="w-5 h-5 text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="faq-answer-1" class="hidden mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed pt-2 border-t border-white/[0.04]">
                                Yes, absolutely. Every query executed in Inoodex passes through an automatic global tenant scope (`BelongsToTenant`) that enforces `WHERE team_id = current_team_id`. Cross-tenant ID enumeration returns 403 Forbidden or 404 Not Found at all times.
                            </div>
                        </div>

                        <!-- FAQ 2 -->
                        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
                            <button 
                                type="button" 
                                class="w-full flex items-center justify-between text-left text-sm sm:text-base font-bold text-white focus:outline-none"
                                onclick="toggleFaq(2)"
                            >
                                <span>How does pessimistic row locking prevent overselling?</span>
                                <svg id="faq-icon-2" class="w-5 h-5 text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="faq-answer-2" class="hidden mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed pt-2 border-t border-white/[0.04]">
                                When an order reservation is placed, MySQL row-level locking (`lockForUpdate()`) serializes concurrent access within an ACID database transaction. No two orders can claim the same physical inventory unit simultaneously, preventing dirty reads and negative stock.
                            </div>
                        </div>

                        <!-- FAQ 3 -->
                        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
                            <button 
                                type="button" 
                                class="w-full flex items-center justify-between text-left text-sm sm:text-base font-bold text-white focus:outline-none"
                                onclick="toggleFaq(3)"
                            >
                                <span>How do we pay with bKash, Nagad, or Bank Transfer?</span>
                                <svg id="faq-icon-3" class="w-5 h-5 text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="faq-answer-3" class="hidden mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed pt-2 border-t border-white/[0.04]">
                                In addition to card gateways via SSLCommerz, you can submit manual bKash or Nagad Transaction IDs (TrxID) directly inside your billing portal. Our Super Admin team verifies the transaction in the admin payment portal, instantly updating your plan quotas.
                            </div>
                        </div>

                        <!-- FAQ 4 -->
                        <div class="glass-panel rounded-2xl p-5 border border-white/[0.08]">
                            <button 
                                type="button" 
                                class="w-full flex items-center justify-between text-left text-sm sm:text-base font-bold text-white focus:outline-none"
                                onclick="toggleFaq(4)"
                            >
                                <span>Can we print vector invoices with custom barcodes and logos?</span>
                                <svg id="faq-icon-4" class="w-5 h-5 text-indigo-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="faq-answer-4" class="hidden mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed pt-2 border-t border-white/[0.04]">
                                Yes! Our integrated high-resolution vector PDF engine dynamically renders your company logo, tax registration number, custom currency symbol (৳, $, €, etc.), and barcode tracking for Sales Invoices, Packing Slips, POs, and Service Tokens.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 7. High-Conversion Final Call-To-Action Banner -->
            <section class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl p-8 sm:p-14 bg-gradient-to-r from-indigo-900/60 via-violet-900/50 to-indigo-950/70 border border-indigo-500/30 text-center relative overflow-hidden shadow-2xl">
                    <div class="max-w-2xl mx-auto relative z-10">
                        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-4">
                            Ready to run your operations like an enterprise?
                        </h2>
                        <p class="text-slate-300 text-sm sm:text-base mb-8">
                            Join high-growth retailers, wholesalers, and manufacturers running on Inoodex. Start your 14-day free trial in under 60 seconds.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            @auth
                                <a 
                                    href="{{ route('dashboard') }}" 
                                    class="w-full sm:w-auto px-8 py-4 rounded-xl bg-white text-indigo-950 hover:bg-slate-100 font-extrabold text-sm shadow-xl transition-all hover:scale-[1.02]"
                                >
                                    Open Your Workspace &rarr;
                                </a>
                            @else
                                <a 
                                    href="{{ route('register') }}" 
                                    class="w-full sm:w-auto px-8 py-4 rounded-xl bg-white text-indigo-950 hover:bg-slate-100 font-extrabold text-sm shadow-xl transition-all hover:scale-[1.02]"
                                >
                                    Create Free Account
                                </a>
                                <a 
                                    href="{{ route('login') }}" 
                                    class="w-full sm:w-auto px-8 py-4 rounded-xl bg-white/[0.08] hover:bg-white/[0.12] text-white border border-white/[0.15] font-bold text-sm transition-all"
                                >
                                    Sign In Existing Account
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/[0.06] bg-[#03060f] py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-10 border-b border-white/[0.06]">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-sm">
                                I
                            </div>
                            <span class="font-heading font-extrabold text-lg text-white">Inoodex Inventory</span>
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
                            <li><a href="#modules" class="hover:text-white transition-colors">Double-Entry Accounting</a></li>
                            <li><a href="#modules" class="hover:text-white transition-colors">Workshop Services</a></li>
                            <li><a href="#modules" class="hover:text-white transition-colors">HR &amp; Payroll</a></li>
                            <li><a href="#pricing" class="hover:text-white transition-colors">Pricing Matrix</a></li>
                        </ul>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-white mb-3">Security &amp; Legal</div>
                        <ul class="space-y-2 text-xs text-slate-400">
                            <li><span class="text-slate-500">Tenant Data Isolation</span></li>
                            <li><span class="text-slate-500">Row-Locking Concurrency</span></li>
                            <li><span class="text-slate-500">Daily Automated Backups</span></li>
                            <li><span class="text-slate-500">Terms of Service</span></li>
                            <li><span class="text-slate-500">Privacy Policy</span></li>
                        </ul>
                    </div>
                </div>

                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                    <div>
                        &copy; {{ date('Y') }} Inoodex Inventory. All rights reserved.
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
                const icon = document.getElementById('faq-icon-1' === 'faq-icon-' + id ? 'faq-icon-1' : 'faq-icon-' + id);
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

            // Header Shadow on Scroll
            window.addEventListener('scroll', () => {
                const header = document.getElementById('main-header');
                if (window.scrollY > 30) {
                    header.classList.add('shadow-xl', 'shadow-black/50', 'bg-[#050814]/95');
                } else {
                    header.classList.remove('shadow-xl', 'shadow-black/50', 'bg-[#050814]/95');
                }
            });
        </script>
    </body>
</html>
