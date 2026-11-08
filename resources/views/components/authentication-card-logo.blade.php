<a href="/" class="flex flex-col items-center gap-2 group">
    @if(!empty($site['logo_url']))
        <img src="{{ $site['logo_url'] }}" alt="{{ $site['brand_name'] ?? 'Logo' }}" class="h-12 w-auto object-contain group-hover:scale-105 transition-transform bg-white/10 p-1 rounded-xl">
    @else
        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-indigo-500/25 group-hover:scale-105 transition-transform">
            {{ strtoupper(substr($site['brand_name'] ?? 'I', 0, 1)) }}
        </div>
    @endif
    <span class="font-heading font-extrabold text-xl text-white tracking-tight">{{ $site['brand_name'] ?? 'InoManage' }}</span>
</a>
