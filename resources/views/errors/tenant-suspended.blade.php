<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workspace Suspended — Inoodex SaaS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 font-sans antialiased text-slate-100">

    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl text-center space-y-6 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Warning Icon -->
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Organization Workspace Suspended</h1>
            <p class="text-sm text-slate-400 mt-2">
                Access for <strong class="text-slate-200">{{ auth()->user()->currentTeam->name ?? 'this workspace' }}</strong> has been temporarily paused by the platform administrator.
            </p>
        </div>

        <div class="p-4 bg-slate-950/80 border border-slate-800 rounded-2xl text-xs text-slate-400 text-left space-y-2">
            <div class="font-bold text-white uppercase tracking-wider text-[10px]">What should I do?</div>
            <p>1. If you are the account owner, please check your subscription status or billing remittance.</p>
            <p>2. Contact platform administration at <strong class="text-indigo-400">support@inoodex.com</strong> to reactivate your workspace.</p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition">
                    Sign Out
                </button>
            </form>
        </div>
    </div>

</body>
</html>
