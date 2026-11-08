@if(!empty($site['logo_url']))
    <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] ?? 'Logo' }}" {{ $attributes->merge(['class' => 'h-10 w-auto object-contain']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white font-black text-lg shadow-md shadow-indigo-500/20">
            {{ strtoupper(substr($site['brand_name'] ?? 'I', 0, 1)) }}
        </div>
        <span class="font-bold text-slate-900 text-lg tracking-tight">{{ $site['brand_name'] ?? 'InoManage' }}</span>
    </div>
@endif
