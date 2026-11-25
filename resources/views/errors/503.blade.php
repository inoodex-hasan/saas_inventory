<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'System Upgrade in Progress' }} — {{ $brandName ?? 'InoManage' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #020617;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }
        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(99, 102, 241, 0.12) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }
        .glow {
            position: absolute;
            border-radius: 9999px;
            filter: blur(100px);
            pointer-events: none;
            opacity: 0.4;
        }
        .glow-1 { top: -100px; left: 50%; transform: translateX(-50%); width: 400px; height: 400px; background: #4f46e5; }
        .glow-2 { bottom: -150px; right: -50px; width: 350px; height: 350px; background: #06b6d4; }
        .card {
            position: relative;
            z-index: 10;
            max-width: 580px;
            width: 100%;
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(51, 65, 85, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 1.75rem;
            padding: 2.75rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }
        .pulse-dot {
            width: 0.5rem;
            height: 0.5rem;
            background-color: #fbbf24;
            border-radius: 9999px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.3); } }
        .icon-wrap {
            width: 4.5rem;
            height: 4.5rem;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(168, 85, 247, 0.15));
            border: 1px solid rgba(129, 140, 248, 0.25);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #818cf8;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.25);
        }
        .icon-wrap svg {
            width: 2.25rem;
            height: 2.25rem;
            animation: spin-slow 20s linear infinite;
        }
        @keyframes spin-slow { 100% { transform: rotate(360deg); } }
        h1 {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #ffffff;
            margin-bottom: 0.75rem;
            line-height: 1.25;
        }
        p.desc {
            font-size: 0.925rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }
        .info-box {
            background: rgba(2, 6, 23, 0.6);
            border: 1px solid rgba(30, 41, 59, 0.8);
            border-radius: 1rem;
            padding: 1.15rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-around;
            gap: 1rem;
        }
        .info-item {
            text-align: center;
        }
        .info-label {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .info-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            font-weight: 700;
            color: #e2e8f0;
        }
        .refresh-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.825rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.35);
        }
        .refresh-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, #4338ca, #4f46e5);
        }
        .footer-note {
            margin-top: 1.75rem;
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .footer-note a {
            color: #818cf8;
            text-decoration: none;
        }
        .footer-note a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="card">
        <div class="badge">
            <span class="pulse-dot"></span>
            Scheduled Maintenance
        </div>

        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>

        <h1>{{ $title ?? 'System Upgrade in Progress' }}</h1>
        <p class="desc">{{ $message ?? 'We are performing scheduled infrastructure upgrades and database optimizations to provide faster service. All systems will be back online shortly.' }}</p>

        <div class="info-box">
            <div class="info-item">
                <div class="info-label">Platform</div>
                <div class="info-val">{{ $brandName ?? 'InoManage' }}</div>
            </div>
            <div style="width: 1px; height: 32px; background: rgba(51, 65, 85, 0.4);"></div>
            <div class="info-item">
                <div class="info-label">Estimated Time</div>
                <div class="info-val" style="color: #fbbf24;">{{ $duration ?? '10-15 mins' }}</div>
            </div>
            <div style="width: 1px; height: 32px; background: rgba(51, 65, 85, 0.4);"></div>
            <div class="info-item">
                <div class="info-label">Auto-Check In</div>
                <div class="info-val" id="countdown">30s</div>
            </div>
        </div>

        <div>
            <button class="refresh-btn" onclick="window.location.reload();">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Check System Status Now
            </button>
        </div>

        <div class="footer-note">
            @if(!empty($supportEmail))
                <span>Questions? Contact <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></span>
                <span>•</span>
            @endif
            <a href="{{ route('login') }}" style="color: #64748b; font-size: 0.72rem;">Admin Sign In</a>
        </div>
    </div>

    <script>
        let seconds = 30;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds + 's';
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);
    </script>
</body>
</html>
