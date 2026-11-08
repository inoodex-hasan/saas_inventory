<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-6 text-center">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">{{ __('Verify Email') }}</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">{{ __('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 font-medium text-xs sm:text-sm text-emerald-400">
                {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
            </div>
        @endif

        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                @csrf

                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all">
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <div class="flex items-center gap-4 text-xs sm:text-sm">
                <a href="{{ route('profile.show') }}" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors">
                    {{ __('Edit Profile') }}
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-rose-400 transition-colors">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>