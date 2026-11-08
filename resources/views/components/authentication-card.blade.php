<div class="w-full flex flex-col items-center justify-center px-4 sm:px-6">
    @if(isset($logo) && trim($logo) !== '')
        <div class="mb-6">
            {{ $logo }}
        </div>
    @endif

    <div class="w-full sm:max-w-md p-6 sm:p-8 rounded-2xl bg-[#0e1326]/90 border border-white/10 backdrop-blur-xl shadow-2xl shadow-indigo-950/60 text-slate-100">
        {{ $slot }}
    </div>
</div>
