<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6 text-center">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">{{ __('Create your account') }}</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">{{ __('Get started with a 14-day free trial, no credit card required') }}</p>
        </div>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Full Name') }}</label>
                <input id="name" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe" />
            </div>

            <div>
                <label for="email" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Email Address') }}</label>
                <input id="email" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@company.com" />
            </div>

            <div>
                <label for="password" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Password') }}</label>
                <input id="password" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="password" name="password" required autocomplete="new-password" placeholder="At least 8 characters" />
            </div>

            <div>
                <label for="password_confirmation" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Confirm Password') }}</label>
                <input id="password_confirmation" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="pt-1">
                    <label for="terms" class="flex items-start cursor-pointer select-none">
                        <input type="checkbox" name="terms" id="terms" required class="mt-0.5 w-4 h-4 rounded border-slate-700 bg-slate-900/90 text-indigo-600 focus:ring-indigo-500/40 focus:ring-offset-0 focus:ring-offset-slate-900 cursor-pointer" />
                        <span class="ms-2.5 text-xs text-slate-300 leading-snug">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                    'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-indigo-400 hover:text-indigo-300 font-medium">'.__('Terms of Service').'</a>',
                                    'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-indigo-400 hover:text-indigo-300 font-medium">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </span>
                    </label>
                </div>
            @endif

            <div class="pt-2">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all">
                    <span>{{ __('Create Free Account') }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>

            <div class="text-center pt-3 border-t border-white/[0.08]">
                <span class="text-xs sm:text-sm text-slate-400">{{ __('Already have an account?') }}</span>
                <a href="{{ route('login') }}" class="ms-1.5 text-xs sm:text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                    {{ __('Sign In') }} &rarr;
                </a>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>