<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6 text-center">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">{{ __('Confirm Password') }}</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>
        </div>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <div>
                <label for="password" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Password') }}</label>
                <input id="password" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="password" name="password" required autocomplete="current-password" autofocus placeholder="••••••••" />
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all">
                    <span>{{ __('Confirm') }}</span>
                </button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>