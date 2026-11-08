<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6 text-center">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">{{ __('Welcome Back') }}</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">{{ __('Sign in to access your inventory workspace') }}</p>
        </div>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 font-medium text-xs sm:text-sm text-emerald-400">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Email Address') }}</label>
                <input id="email" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@company.com" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block font-medium text-xs sm:text-sm text-slate-300">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors" href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>
                <input id="password" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label for="remember_me" class="flex items-center cursor-pointer select-none">
                    <input type="checkbox" id="remember_me" name="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-900/90 text-indigo-600 focus:ring-indigo-500/40 focus:ring-offset-0 focus:ring-offset-slate-900 cursor-pointer">
                    <span class="ms-2 text-xs sm:text-sm text-slate-300">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all">
                    <span>{{ __('Sign In to Account') }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>

            @if (Route::has('register'))
                <div class="text-center pt-3 border-t border-white/[0.08]">
                    <span class="text-xs sm:text-sm text-slate-400">{{ __("Don't have an account?") }}</span>
                    <a href="{{ route('register') }}" class="ms-1.5 text-xs sm:text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                        {{ __('Start Free Trial') }} &rarr;
                    </a>
                </div>
            @endif
        </form>
    </x-authentication-card>
</x-guest-layout>
