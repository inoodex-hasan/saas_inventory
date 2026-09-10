<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Inoodex Inventory - Next-Gen Multi-Tenant SaaS Inventory Platform</title>

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; }
        </style>
    </head>
    <body class="h-full text-slate-100 bg-slate-950 antialiased selection:bg-indigo-500 selection:text-white flex flex-col justify-between">
        
        <!-- Header / Navigation -->
        <header class="max-w-7xl mx-auto w-full px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/25">
                    I
                </div>
                <div>
                    <span class="font-bold text-white text-lg tracking-tight block">Inoodex</span>
                    <span class="text-[10px] text-indigo-400 font-semibold tracking-wider uppercase block">Cloud Inventory</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                            Open Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-900 transition">
                            Sign In
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                                Get Started Free
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </header>

        <!-- Hero Section -->
        <main class="max-w-7xl mx-auto w-full px-6 py-12 lg:py-20 flex-1 flex flex-col items-center justify-center text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-6">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                <span>Multi-Tenant Architecture &bull; Atomic DB Locking</span>
            </div>

            <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white max-w-4xl leading-tight sm:leading-none">
                Enterprise Multi-Warehouse <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-purple-300 to-pink-400">Inventory Engine</span>
            </h1>

            <p class="mt-6 text-slate-400 text-base sm:text-lg max-w-2xl leading-relaxed">
                Streamline procurement, sales order dispatches, bin-level warehouse stock, and automated low-stock warnings in one unified multi-tenant platform.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm transition shadow-xl shadow-indigo-600/40">
                        Go to Your Workspace &rarr;
                    </a>
                @else
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm transition shadow-xl shadow-indigo-600/40">
                        Create Organization Account
                    </a>
                    <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 font-semibold text-sm border border-slate-800 transition">
                        Sign In Existing Team
                    </a>
                @endauth
            </div>

            <!-- Feature Pills -->
            <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-6 w-full max-w-4xl text-left">
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold mb-4">
                        🏬
                    </div>
                    <h3 class="font-bold text-white text-base">Multi-Warehouse Hubs</h3>
                    <p class="text-xs text-slate-400 mt-1">Track physical on-hand, reserved, and available stock across distribution hubs with zero drift.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold mb-4">
                        ⚡
                    </div>
                    <h3 class="font-bold text-white text-base">Pessimistic Locking</h3>
                    <p class="text-xs text-slate-400 mt-1">Industrial-grade DB row locking prevents race conditions, overselling, and negative stock.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/10 text-pink-400 flex items-center justify-center font-bold mb-4">
                        🏢
                    </div>
                    <h3 class="font-bold text-white text-base">Strict Multi-Tenancy</h3>
                    <p class="text-xs text-slate-400 mt-1">Full database and query isolation with Jetstream Teams, Spatie RBAC, and scoped events.</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-7xl mx-auto w-full px-6 py-6 border-t border-slate-900 text-center text-xs text-slate-600">
            &copy; {{ date('Y') }} Inoodex Inventory. All rights reserved. Powered by Laravel 11, Livewire 3 & Tailwind CSS.
        </footer>
    </body>
</html>
