@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 mb-4']) }}>
        <div class="font-semibold text-sm flex items-center gap-2 text-rose-300">
            <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ __('Whoops! Something went wrong.') }}</span>
        </div>

        <ul class="mt-2.5 list-disc list-inside text-xs text-rose-300/90 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
