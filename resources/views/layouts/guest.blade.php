<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $site['site_title'] ?? 'InoManage' }}</title>
        @if(!empty($site['favicon_url']))
        <link rel="icon" href="{{ $site['favicon_url'] }}">
        @endif

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Vite Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --bg-deep: #050814;
                --bg-surface: #0a0e22;
                --bg-card: rgba(14, 20, 44, 0.75);
                --border-subtle: rgba(255, 255, 255, 0.08);
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
            .ambient-glow {
                position: fixed;
                border-radius: 50%;
                filter: blur(140px);
                pointer-events: none;
                z-index: 0;
            }
            .glow-primary {
                width: 600px;
                height: 600px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
                top: -150px;
                left: -100px;
            }
            .glow-secondary {
                width: 500px;
                height: 500px;
                background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 70%);
                bottom: 10%;
                right: -100px;
            }
            .glass-panel {
                background: var(--bg-card);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid var(--border-subtle);
            }
        </style>

        @livewireStyles
    </head>

    <body class="min-h-screen flex flex-col justify-between relative selection:bg-indigo-600 selection:text-white">
        <!-- Ambient Glowing Aura -->
        <div class="ambient-glow glow-primary"></div>
        <div class="ambient-glow glow-secondary"></div>

        <!-- Sticky Navigation Header -->
        <header id="main-header" class="sticky top-0 z-50 border-b border-white/[0.06] bg-[#050814]/85 backdrop-blur-xl transition-all duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="/" class="flex items-center gap-3.5 group">
                    @if(!empty($site['logo_url']))
                        <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] }}" class="w-10 h-10 rounded-xl object-contain shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform bg-white/10 p-1">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($site['brand_name'] ?? 'I', 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-heading font-extrabold text-lg text-white tracking-tight leading-tight">{{ $site['brand_name'] ?? 'InoManage' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">ERP</span>
                        </div>
                        <span class="text-[10px] font-semibold tracking-wider text-slate-400 uppercase block">{{ $site['brand_tagline'] ?? 'Cloud Inventory Platform' }}</span>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-slate-300">
                    <a href="{{ url('/#features') }}" class="hover:text-white transition-colors">Features</a>
                    <a href="{{ url('/#pricing') }}" class="hover:text-white transition-colors">Pricing</a>
                    <a href="{{ url('/#reviews') }}" class="hover:text-white transition-colors">Review</a>
                    <a href="{{ url('/#faq') }}" class="hover:text-white transition-colors">FAQ</a>

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
                            @if(request()->routeIs('login'))
                                @if (Route::has('register'))
                                    <a 
                                        href="{{ route('register') }}" 
                                        id="btn-nav-register"
                                        class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                                    >
                                        <span>Free Trail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            @elseif(request()->routeIs('register'))
                                <a 
                                    href="{{ route('login') }}" 
                                    id="btn-nav-login"
                                    class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 rounded-xl bg-white/[0.08] hover:bg-white/[0.12] text-white text-xs font-bold border border-white/[0.15] transition-all hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    <span>Sign In</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
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
                                        <span>Free Trail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            @endif
                        @endauth
                    @endif

                    <!-- Mobile Menu Button -->
                    <button 
                        id="btn-mobile-menu"
                        class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/[0.05] focus:outline-none"
                        aria-label="Toggle Navigation Menu"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Dropdown Navigation -->
            <div id="mobile-menu" class="hidden lg:hidden border-t border-white/[0.06] bg-[#070b1a]/95 backdrop-blur-xl px-4 pt-3 pb-6 space-y-3">
                <a href="{{ url('/#features') }}" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Features</a>
                <a href="{{ url('/#pricing') }}" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Pricing</a>
                <a href="{{ url('/#reviews') }}" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">Review</a>
                <a href="{{ url('/#faq') }}" class="block py-2 text-sm font-medium text-slate-300 hover:text-white">FAQ</a>
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
                    <a href="{{ route('register') }}" class="w-full text-center py-2.5 rounded-lg bg-indigo-600 text-xs font-bold text-white">Free Trail</a>
                </div>
            </div>
        </header>

        <!-- Main Auth Content -->
        <main class="flex-grow flex flex-col justify-center py-10 sm:py-16 relative z-10">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/[0.06] bg-[#03060f] py-14 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 pb-10 border-b border-white/[0.06]">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            @if(!empty($site['logo_url']))
                                <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] ?? 'Logo' }}" class="w-8 h-8 rounded-lg object-contain bg-white/10 p-0.5">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-sm">
                                    {{ strtoupper(substr($site['brand_name'] ?? 'I', 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-heading font-extrabold text-lg text-white">{{ $site['brand_name'] ?? 'InoManage' }}</span>
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
                            <li><a href="{{ url('/#features') }}" class="hover:text-white transition-colors">Warehouse Engine</a></li>
                            <li><a href="{{ url('/#features') }}" class="hover:text-white transition-colors">Double-Entry Accounting</a></li>
                            <li><a href="{{ url('/#features') }}" class="hover:text-white transition-colors">Workshop Services</a></li>
                            <li><a href="{{ url('/#features') }}" class="hover:text-white transition-colors">HR &amp; Payroll</a></li>
                            <li><a href="{{ url('/#pricing') }}" class="hover:text-white transition-colors">Pricing Matrix</a></li>
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
                        &copy; {{ date('Y') }} {{ $site['footer_copyright'] ?? 'InoManage. All rights reserved.' }}
                    </div>
                    <div class="flex items-center gap-4">
                        <span>Designed for high-scale enterprise supply chains</span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Interactive Script for Mobile & Language Menu -->
        <script>
            // Mobile Menu Toggle
            const btnMobileMenu = document.getElementById('btn-mobile-menu');
            const mobileMenu = document.getElementById('mobile-menu');
            if (btnMobileMenu && mobileMenu) {
                btnMobileMenu.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // Language Dropdown Toggle
            function toggleLanguageMenu() {
                const menu = document.getElementById('lang-menu');
                if (menu) menu.classList.toggle('hidden');
            }

            const guestDictionary = {
                // Nav
                'Features': 'ফিচারসমূহ',
                'Pricing': 'প্রাইসিং',
                'Review': 'রিভিউ',
                'FAQ': 'প্রশ্নোত্তর',
                'Language': 'ভাষা',
                'Sign In': 'সাইন ইন',
                'Free Trail': 'ফ্রি ট্রায়াল',
                'Free Trial': 'ফ্রি ট্রায়াল',
                'Open Workspace': 'ওয়ার্কস্পেস খুলুন',

                // Auth form elements
                'Welcome Back': 'স্বাগতম!',
                'Sign in to access your inventory workspace': 'আপনার ইনভেন্টরি ওয়ার্কস্পেসে প্রবেশ করতে সাইন ইন করুন',
                'Email Address': 'ইমেইল অ্যাড্রেস',
                'Password': 'পাসওয়ার্ড',
                'Remember me': 'আমাকে মনে রাখুন',
                'Forgot password?': 'পাসওয়ার্ড ভুলে গেছেন?',
                'Forgot Password': 'পাসওয়ার্ড পুনরুদ্ধার',
                'Sign In to Account': 'অ্যাকাউন্টে সাইন ইন করুন',
                "Don't have an account?": 'অ্যাকাউন্ট নেই?',
                'Start Free Trial': 'ফ্রি ট্রায়াল শুরু করুন',
                'Create your account': 'নতুন অ্যাকাউন্ট তৈরি করুন',
                'Get started with a 14-day free trial, no credit card required': '১৪ দিনের ফ্রি ট্রায়াল শুরু করুন, কোনো কার্ড লাগবে না',
                'Full Name': 'পুরো নাম',
                'Confirm Password': 'পাসওয়ার্ড নিশ্চিত করুন',
                'Confirm New Password': 'নতুন পাসওয়ার্ড নিশ্চিত করুন',
                'Create Free Account': 'ফ্রি অ্যাকাউন্ট তৈরি করুন',
                'Already have an account?': 'ইতিমধ্যে অ্যাকাউন্ট আছে?',
                'I agree to the': 'আমি সম্মতি দিচ্ছি',
                'Terms of Service': 'ব্যবহারের শর্তাবলি',
                'and': 'এবং',
                'Privacy Policy': 'গোপনীয়তা নীতিমালা',
                'Reset Password': 'পাসওয়ার্ড রিসেট করুন',
                'Email Password Reset Link': 'পাসওয়ার্ড রিসেট লিংক পাঠান',
                'Back to Sign In': 'সাইন ইন-এ ফিরে যান',

                // Footer
                'Platform': 'প্ল্যাটফর্ম',
                'Modules': 'মডিউলসমূহ',
                'Pricing Plans': 'মূল্য তালিকা',
                'Customer Reviews': 'গ্রাহক মতামত',
                'Security & Legal': 'নিরাপত্তা ও শর্তাবলি',
                'Terms & Conditions': 'নিয়ম ও শর্তাবলি',
                'Support & Inquiries': 'সহায়তা ও যোগাযোগ',
                '99.99% Uptime SLA Verified': '৯৯.৯৯% আপটাইম এসএলএ নিশ্চিত',
                'All rights reserved.': 'সর্বস্বত্ব সংরক্ষিত।'
            };

            const guestReverseDict = {};
            for (const [k, v] of Object.entries(guestDictionary)) {
                guestReverseDict[v] = k;
            }

            function applyLanguage(lang) {
                const isBn = (lang === 'bn' || lang === 'বাংলা');
                const targetMap = isBn ? guestDictionary : guestReverseDict;

                const textEl = document.getElementById('current-lang-text');
                if (textEl) textEl.textContent = isBn ? 'বাংলা' : 'English';

                const checkEn = document.getElementById('lang-check-en');
                const checkBn = document.getElementById('lang-check-bn');
                if (checkEn) checkEn.classList.toggle('hidden', isBn);
                if (checkBn) checkBn.classList.toggle('hidden', !isBn);

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

                const walker = document.createTreeWalker(
                    document.body,
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
                            const tag = node.parentElement ? node.parentElement.tagName.toLowerCase() : '';
                            if (tag === 'script' || tag === 'style') return NodeFilter.FILTER_REJECT;
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

                    if (targetMap[originalText]) {
                        node.nodeValue = node.nodeValue.replace(originalText, targetMap[originalText]);
                        return;
                    }

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

            // Header Shadow on Scroll
            window.addEventListener('scroll', () => {
                const header = document.getElementById('main-header');
                if (header) {
                    if (window.scrollY > 30) {
                        header.classList.add('shadow-xl', 'shadow-black/50', 'bg-[#050814]/95');
                    } else {
                        header.classList.remove('shadow-xl', 'shadow-black/50', 'bg-[#050814]/95');
                    }
                }
            });
        </script>

        @livewireScripts
    </body>
</html>
