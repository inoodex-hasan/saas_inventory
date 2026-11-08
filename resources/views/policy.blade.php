<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy &bull; {{ $site['brand_name'] ?? 'InoManage' }}</title>
        <meta name="description" content="Privacy Policy and tenant data protection standards for {{ $site['brand_name'] ?? 'InoManage' }}.">
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
            }
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                background-color: var(--bg-deep);
                color: #e2e8f0;
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
                background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, transparent 70%);
                top: -150px;
                left: -100px;
            }
            .glow-secondary {
                width: 500px;
                height: 500px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.10) 0%, transparent 70%);
                bottom: 10%;
                right: -100px;
            }
            .glass-panel {
                background: var(--bg-card);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid var(--border-subtle);
            }
            .gradient-accent {
                background: linear-gradient(135deg, #34d399 0%, #38bdf8 50%, #818cf8 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            /* Reader Prose Styling */
            .legal-content h1 {
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: 2.25rem;
                font-weight: 800;
                color: #ffffff;
                letter-spacing: -0.025em;
                margin-top: 1rem;
                margin-bottom: 1.5rem;
                line-height: 1.15;
            }
            .legal-content h2 {
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: 1.5rem;
                font-weight: 700;
                color: #f8fafc;
                margin-top: 2.5rem;
                margin-bottom: 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                padding-bottom: 0.5rem;
            }
            .legal-content h3 {
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: 1.15rem;
                font-weight: 600;
                color: #e2e8f0;
                margin-top: 1.75rem;
                margin-bottom: 0.75rem;
            }
            .legal-content p {
                font-size: 0.95rem;
                line-height: 1.75;
                color: #94a3b8;
                margin-bottom: 1.25rem;
            }
            .legal-content ul, .legal-content ol {
                margin-bottom: 1.5rem;
                padding-left: 1.5rem;
                color: #94a3b8;
                font-size: 0.95rem;
                line-height: 1.75;
            }
            .legal-content ul {
                list-style-type: disc;
            }
            .legal-content ol {
                list-style-type: decimal;
            }
            .legal-content li {
                margin-bottom: 0.5rem;
            }
            .legal-content strong {
                color: #f1f5f9;
                font-weight: 600;
            }
            .legal-content hr {
                border-color: rgba(255, 255, 255, 0.08);
                margin: 2rem 0;
            }
            .legal-content a {
                color: #38bdf8;
                text-decoration: underline;
                text-underline-offset: 3px;
            }
            .legal-content a:hover {
                color: #7dd3fc;
            }
            .legal-content blockquote {
                border-left: 3px solid #10b981;
                padding-left: 1rem;
                color: #cbd5e1;
                font-style: italic;
                margin: 1.5rem 0;
            }
            .legal-content code {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.85em;
                background-color: rgba(255, 255, 255, 0.06);
                padding: 0.2rem 0.4rem;
                border-radius: 0.375rem;
                color: #cbd5e1;
            }
        </style>
    </head>

    <body class="min-h-screen relative selection:bg-emerald-600 selection:text-white">
        <!-- Ambient Glowing Aura -->
        <div class="ambient-glow glow-primary"></div>
        <div class="ambient-glow glow-secondary"></div>

        <!-- Sticky Navigation Header -->
        <header class="sticky top-0 z-50 border-b border-white/[0.06] bg-[#050814]/85 backdrop-blur-xl">
            <div class="w-full max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-10 h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="/" class="flex items-center gap-3 group">
                    @if(!empty($site['logo_url']))
                        <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] }}" class="w-9 h-9 rounded-xl object-contain bg-white/10 p-1">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 flex items-center justify-center text-white font-black text-lg">
                            {{ strtoupper(substr($site['brand_name'], 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <span class="font-heading font-extrabold text-base text-white tracking-tight leading-tight block">{{ $site['brand_name'] }}</span>
                        <span class="text-[10px] font-semibold tracking-wider text-slate-400 uppercase block">Enterprise ERP</span>
                    </div>
                </a>

                <!-- Nav Action Links -->
                <div class="flex items-center gap-4">
                    <a href="/" class="text-xs font-semibold text-slate-300 hover:text-white transition flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Back to Home</span>
                    </a>
                    <a href="{{ route('terms.show') }}" class="hidden sm:inline-block text-xs font-semibold text-slate-300 hover:text-white transition">
                        Terms &amp; Conditions
                    </a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-sm">
                                Open Workspace
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/[0.05] transition">
                                Sign In
                            </a>
                            <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-sm">
                                Free Trial
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-10 sm:py-16 relative z-10">
            <div class="w-full max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-10">
                <!-- Page Header Banner -->
                <div class="mb-10 text-center sm:text-left sm:flex sm:items-end sm:justify-between gap-6 border-b border-white/[0.06] pb-8">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-3 border border-emerald-500/20">
                            Privacy &amp; Data Protection
                        </div>
                        <h1 class="font-heading font-extrabold text-3xl sm:text-4xl lg:text-5xl text-white tracking-tight leading-tight">
                            Privacy Policy
                        </h1>
                        <p class="text-slate-400 mt-2 text-sm">
                            How we protect, isolate, and secure your tenant records, inventory data, and transactions.
                        </p>
                    </div>

                    <div class="mt-4 sm:mt-0 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Active Policy Document
                        </span>
                    </div>
                </div>

                <!-- Legal Content Card -->
                <div class="glass-panel rounded-3xl p-6 sm:p-12 lg:p-16 shadow-2xl w-full">
                    <article class="legal-content w-full max-w-none">
                        {!! $policy !!}
                    </article>

                    <!-- Back to top -->
                    <div class="mt-12 pt-8 border-t border-white/[0.06] flex items-center justify-between text-xs text-slate-400">
                        <a href="{{ route('terms.show') }}" class="hover:text-emerald-400 transition flex items-center gap-1 font-semibold">
                            <span>Read Terms &amp; Conditions</span> &rarr;
                        </a>
                        <button type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="hover:text-white transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            <span>Back to top</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/[0.06] bg-[#03060f] py-10 mt-20 relative z-10">
            <div class="w-full max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div>
                    &copy; {{ date('Y') }} {{ $site['footer_copyright'] }}
                </div>
                <div class="flex items-center gap-6 text-slate-400">
                    <a href="{{ route('terms.show') }}" class="hover:text-white transition">Terms and Conditions</a>
                    <a href="{{ route('policy.show') }}" class="text-white font-semibold">Privacy Policy</a>
                    <a href="/" class="hover:text-white transition">Home</a>
                </div>
            </div>
        </footer>
    </body>
</html>
