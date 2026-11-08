<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6 text-center">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">{{ __('Two-Factor Authentication') }}</h1>
        </div>

        <div x-data="{ recovery: false }">
            <div class="mb-4 text-xs sm:text-sm text-slate-400" x-show="! recovery">
                {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
            </div>

            <div class="mb-4 text-xs sm:text-sm text-slate-400" x-cloak x-show="recovery">
                {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
            </div>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
                @csrf

                <div x-show="! recovery">
                    <label for="code" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Authentication Code') }}</label>
                    <input id="code" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all font-mono tracking-widest text-center" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" placeholder="123456" />
                </div>

                <div x-cloak x-show="recovery">
                    <label for="recovery_code" class="block font-medium text-xs sm:text-sm text-slate-300 mb-1.5">{{ __('Recovery Code') }}</label>
                    <input id="recovery_code" class="block w-full px-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all font-mono tracking-wider text-center" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" placeholder="xxxx-xxxx-xxxx" />
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <button type="button" class="text-xs sm:text-sm text-indigo-400 hover:text-indigo-300 underline cursor-pointer"
                                    x-show="! recovery"
                                    x-on:click="
                                        recovery = true;
                                        $nextTick(() => { $refs.recovery_code.focus() })
                                    ">
                        {{ __('Use a recovery code') }}
                    </button>

                    <button type="button" class="text-xs sm:text-sm text-indigo-400 hover:text-indigo-300 underline cursor-pointer"
                                    x-cloak
                                    x-show="recovery"
                                    x-on:click="
                                        recovery = false;
                                        $nextTick(() => { $refs.code.focus() })
                                    ">
                        {{ __('Use an authentication code') }}
                    </button>

                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all">
                        {{ __('Log In') }}
                    </button>
                </div>
            </form>
        </div>
    </x-authentication-card>
</x-guest-layout>