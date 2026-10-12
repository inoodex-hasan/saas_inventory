<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Inoodex Inventory — Enterprise SaaS Inventory Platform</title>
        <meta name="description" content="Multi-tenant cloud inventory platform with multi-warehouse management, atomic stock control, purchase orders, sales dispatch, and PDF invoicing. Built for teams that mean business.">

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

            :root {
                --bg-base: #050810;
                --bg-card: #0c1120;
                --bg-card-hover: #111827;
                --border: rgba(255,255,255,0.06);
                --text-primary: #f1f5f9;
                --text-secondary: #94a3b8;
                --text-muted: #475569;
                --indigo: #6366f1;
                --indigo-light: #818cf8;
                --violet: #8b5cf6;
            }

            html { scroll-behavior: smooth; }
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg-base);
                color: var(--text-primary);
                line-height: 1.6;
                overflow-x: hidden;
                -webkit-font-smoothing: antialiased;
            }

            /* Ambient glows */
            .ambient-glow {
                position: fixed; border-radius: 50%;
                filter: blur(120px); pointer-events: none; z-index: 0;
                animation: floatGlow 8s ease-in-out infinite;
            }
            .glow-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%); top: -200px; left: -100px; animation-delay: 0s; }
            .glow-2 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(139,92,246,0.10) 0%, transparent 70%); top: 40%; right: -150px; animation-delay: -3s; }
            .glow-3 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(6,182,212,0.08) 0%, transparent 70%); bottom: 10%; left: 20%; animation-delay: -6s; }

            @keyframes floatGlow {
                0%, 100% { transform: translate(0, 0) scale(1); }
                33% { transform: translate(30px, -20px) scale(1.05); }
                66% { transform: translate(-20px, 15px) scale(0.97); }
            }

            .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; position: relative; z-index: 1; }

            /* Navigation */
            nav {
                position: sticky; top: 0; z-index: 100;
                border-bottom: 1px solid var(--border);
                background: rgba(5, 8, 16, 0.85);
                backdrop-filter: blur(20px);
            }
            .nav-inner {
                display: flex; align-items: center; justify-content: space-between;
                padding: 1rem 1.5rem; max-width: 1200px; margin: 0 auto;
            }
            .logo { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
            .logo-icon {
                width: 38px; height: 38px; border-radius: 10px;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                display: flex; align-items: center; justify-content: center;
                font-weight: 900; color: #fff; font-size: 1rem;
                box-shadow: 0 0 20px rgba(99,102,241,0.4); flex-shrink: 0;
            }
            .logo-name { font-weight: 800; font-size: 1rem; color: #fff; letter-spacing: -0.02em; display: block; }
            .logo-sub { font-size: 0.6rem; font-weight: 600; color: var(--indigo-light); text-transform: uppercase; letter-spacing: 0.12em; display: block; }
            .nav-links { display: flex; align-items: center; gap: 0.375rem; }
            .btn-ghost {
                padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600;
                color: var(--text-secondary); text-decoration: none; transition: color 0.2s, background 0.2s;
                border: none; cursor: pointer; background: transparent;
            }
            .btn-ghost:hover { color: #fff; background: rgba(255,255,255,0.06); }
            .btn-primary {
                padding: 0.5rem 1.125rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700;
                color: #fff; text-decoration: none;
                background: linear-gradient(135deg, #6366f1, #7c3aed);
                transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
                box-shadow: 0 4px 15px rgba(99,102,241,0.35);
                display: inline-flex; align-items: center; gap: 0.4rem;
            }
            .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

            /* Hero */
            .hero {
                padding: 5rem 1.5rem 4rem; text-align: center;
                position: relative; z-index: 1; overflow: hidden;
            }
            .hero::before {
                content: ''; position: absolute; inset: 0;
                background-image: linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
                background-size: 60px 60px;
                mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
                -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
                pointer-events: none;
            }
            .hero-badge {
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.35rem 0.9rem; border-radius: 100px;
                background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.25);
                font-size: 0.72rem; font-weight: 600; color: var(--indigo-light);
                margin-bottom: 1.5rem; animation: fadeSlideUp 0.6s ease both;
            }
            .badge-dot {
                width: 6px; height: 6px; border-radius: 50%;
                background: var(--indigo-light); box-shadow: 0 0 6px var(--indigo-light);
                animation: pulse 2s ease-in-out infinite;
            }
            @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(0.85)} }

            .hero-title {
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: clamp(2.4rem, 6vw, 4.25rem);
                font-weight: 800; line-height: 1.12; letter-spacing: -0.03em;
                color: #fff; max-width: 860px; margin: 0 auto 1.25rem;
                animation: fadeSlideUp 0.6s 0.1s ease both;
            }
            .gradient-text {
                background: linear-gradient(135deg, #818cf8 0%, #c084fc 40%, #fb7185 80%);
                -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            }
            .hero-sub {
                font-size: clamp(0.95rem, 2vw, 1.1rem); color: var(--text-secondary);
                max-width: 580px; margin: 0 auto 2.25rem; line-height: 1.7;
                animation: fadeSlideUp 0.6s 0.2s ease both;
            }
            .hero-ctas {
                display: flex; align-items: center; justify-content: center;
                gap: 0.75rem; flex-wrap: wrap;
                animation: fadeSlideUp 0.6s 0.3s ease both; margin-bottom: 3.5rem;
            }
            .btn-cta-primary {
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.8rem 1.75rem; border-radius: 12px;
                background: linear-gradient(135deg, #6366f1, #7c3aed);
                color: #fff; font-weight: 700; font-size: 0.9rem; text-decoration: none;
                box-shadow: 0 8px 30px rgba(99,102,241,0.4);
                transition: all 0.25s;
            }
            .btn-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(99,102,241,0.5); }
            .btn-cta-secondary {
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.8rem 1.75rem; border-radius: 12px;
                background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
                color: var(--text-primary); font-weight: 600; font-size: 0.9rem; text-decoration: none;
                transition: all 0.25s;
            }
            .btn-cta-secondary:hover { background: rgba(255,255,255,0.08); transform: translateY(-2px); }

            /* Dashboard Frame */
            .hero-visual { position: relative; max-width: 900px; margin: 0 auto; animation: fadeSlideUp 0.8s 0.4s ease both; }
            .hero-visual-frame {
                border-radius: 16px; border: 1px solid rgba(255,255,255,0.08);
                background: var(--bg-card); overflow: hidden;
                box-shadow: 0 0 0 1px rgba(99,102,241,0.1), 0 40px 80px rgba(0,0,0,0.6), 0 0 100px rgba(99,102,241,0.08);
            }
            .frame-bar {
                display: flex; align-items: center; gap: 0.5rem;
                padding: 0.7rem 1rem; background: #0a0f1e; border-bottom: 1px solid var(--border);
            }
            .frame-dot { width: 10px; height: 10px; border-radius: 50%; }
            .frame-dot:nth-child(1) { background: #ff5f57; }
            .frame-dot:nth-child(2) { background: #febc2e; }
            .frame-dot:nth-child(3) { background: #28c840; }
            .frame-url {
                flex: 1; background: rgba(255,255,255,0.04); border-radius: 6px;
                padding: 0.25rem 0.75rem; font-size: 0.7rem; color: var(--text-muted);
                text-align: center; margin: 0 0.5rem;
            }
            .frame-content { padding: 1.25rem; }
            .mini-dash { display: grid; gap: 0.75rem; }
            .mini-dash-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem; }
            .mini-dash-title { font-size: 0.85rem; font-weight: 700; color: #fff; }
            .mini-badge { padding: 0.2rem 0.6rem; border-radius: 100px; font-size: 0.65rem; font-weight: 600; }
            .badge-green { background: rgba(16,185,129,0.15); color: #34d399; }
            .badge-amber { background: rgba(245,158,11,0.15); color: #fbbf24; }
            .mini-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }
            .mini-stat {
                padding: 0.85rem; border-radius: 10px;
                background: rgba(255,255,255,0.03); border: 1px solid var(--border);
            }
            .mini-stat-label { font-size: 0.62rem; color: var(--text-muted); font-weight: 500; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.05em; }
            .mini-stat-value { font-size: 1.2rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 0.2rem; }
            .mini-stat-change { font-size: 0.62rem; font-weight: 600; }
            .change-up { color: #34d399; }
            .change-down { color: #f87171; }
            .mini-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
            .mini-panel { border-radius: 10px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); padding: 0.85rem; }
            .mini-panel-title { font-size: 0.65rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.6rem; }
            .stock-bar-wrap { display: flex; flex-direction: column; gap: 0.4rem; }
            .stock-bar-row { display: flex; align-items: center; gap: 0.5rem; }
            .stock-bar-name { font-size: 0.65rem; color: var(--text-secondary); width: 70px; flex-shrink: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .stock-bar-track { flex: 1; height: 5px; border-radius: 100px; background: rgba(255,255,255,0.05); overflow: hidden; }
            .stock-bar-fill { height: 100%; border-radius: 100px; }
            .stock-bar-pct { font-size: 0.6rem; color: var(--text-muted); width: 28px; text-align: right; flex-shrink: 0; }
            .order-list { display: flex; flex-direction: column; gap: 0.4rem; }
            .order-row { display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0.5rem; border-radius: 6px; background: rgba(255,255,255,0.02); }
            .order-id { font-size: 0.65rem; font-weight: 600; color: #fff; font-family: monospace; }
            .order-name { font-size: 0.62rem; color: var(--text-secondary); flex: 1; margin: 0 0.5rem; }
            .order-amount { font-size: 0.65rem; font-weight: 700; color: #fff; }
            .order-status { font-size: 0.58rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 100px; margin-left: 0.4rem; }
            .status-dispatched { background: rgba(16,185,129,0.15); color: #34d399; }
            .status-pending { background: rgba(245,158,11,0.15); color: #fbbf24; }
            .status-draft { background: rgba(148,163,184,0.1); color: #94a3b8; }

            /* Stats strip */
            .stats-strip {
                border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
                background: rgba(255,255,255,0.015); padding: 2rem 1.5rem; position: relative; z-index: 1;
            }
            .stats-inner { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; text-align: center; }
            .stat-num { font-size: clamp(1.6rem, 3vw, 2.25rem); font-weight: 800; color: #fff; letter-spacing: -0.03em; line-height: 1; }
            .stat-num span { background: linear-gradient(135deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
            .stat-label { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.3rem; font-weight: 500; }

            /* Sections */
            .section { padding: 5rem 1.5rem; position: relative; z-index: 1; }
            .section-label {
                display: inline-flex; align-items: center; gap: 0.5rem;
                font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;
                color: var(--indigo-light); margin-bottom: 1rem;
            }
            .section-label::before { content:''; display: block; width: 20px; height: 2px; background: var(--indigo-light); border-radius: 2px; }
            .section-title {
                font-family: 'Plus Jakarta Sans', sans-serif;
                font-size: clamp(1.75rem, 3.5vw, 2.5rem);
                font-weight: 800; letter-spacing: -0.025em; color: #fff; line-height: 1.2; margin-bottom: 1rem;
            }
            .section-sub { font-size: 1rem; color: var(--text-secondary); max-width: 560px; line-height: 1.7; }

            /* Features grid */
            .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--border); border-radius: 16px; overflow: hidden; margin-top: 3rem; }
            .feature-card {
                background: var(--bg-card); padding: 1.75rem;
                transition: background 0.25s; position: relative; overflow: hidden;
            }
            .feature-card::before { content:''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(99,102,241,0.04), transparent); opacity: 0; transition: opacity 0.3s; }
            .feature-card:hover { background: var(--bg-card-hover); }
            .feature-card:hover::before { opacity: 1; }
            .feature-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 1rem; }
            .feature-title { font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
            .feature-desc { font-size: 0.82rem; color: var(--text-secondary); line-height: 1.6; }

            /* Two-col */
            .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
            .two-col.reversed { direction: rtl; }
            .two-col.reversed > * { direction: ltr; }

            /* Benefits */
            .benefit-list { list-style: none; display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem; }
            .benefit-item { display: flex; align-items: flex-start; gap: 0.75rem; }
            .benefit-check { width: 20px; height: 20px; border-radius: 6px; background: rgba(16,185,129,0.15); color: #34d399; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; flex-shrink: 0; margin-top: 2px; }
            .benefit-text { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; }
            .benefit-text strong { color: var(--text-primary); font-weight: 600; }

            /* Mock panels */
            .mock-panel { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(99,102,241,0.08); }
            .mock-panel-header { padding: 0.8rem 1.1rem; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
            .mock-panel-title { font-size: 0.78rem; font-weight: 700; color: #fff; }
            .mock-panel-body { padding: 1.1rem; }
            .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; }
            .kpi-card { padding: 0.65rem 0.75rem; border-radius: 8px; background: rgba(255,255,255,0.025); border: 1px solid var(--border); text-align: center; }
            .kpi-val { font-size: 1.1rem; font-weight: 800; color: #fff; line-height: 1; }
            .kpi-lbl { font-size: 0.6rem; color: var(--text-muted); margin-top: 0.2rem; text-transform: uppercase; letter-spacing: 0.06em; }
            .tenant-list { display: flex; flex-direction: column; gap: 0.5rem; }
            .tenant-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.75rem; border-radius: 8px; background: rgba(255,255,255,0.025); border: 1px solid var(--border); }
            .tenant-avatar { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; color: #fff; flex-shrink: 0; }
            .tenant-info { flex: 1; }
            .tenant-name { font-size: 0.78rem; font-weight: 600; color: #fff; }
            .tenant-plan { font-size: 0.62rem; color: var(--text-muted); }
            .tenant-status { font-size: 0.6rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 100px; }
            .tenant-active { background: rgba(16,185,129,0.15); color: #34d399; }
            .tenant-trial { background: rgba(245,158,11,0.12); color: #fbbf24; }
            .tenant-sus { background: rgba(239,68,68,0.12); color: #f87171; }

            /* CSV visual */
            .csv-visual { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
            .csv-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
            .csv-title { font-size: 0.78rem; font-weight: 700; color: #fff; }
            .csv-table { width: 100%; border-collapse: collapse; font-size: 0.68rem; font-family: monospace; }
            .csv-table th { background: rgba(99,102,241,0.1); color: var(--indigo-light); padding: 0.35rem 0.5rem; text-align: left; font-weight: 700; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.05em; }
            .csv-table td { padding: 0.35rem 0.5rem; color: var(--text-secondary); border-bottom: 1px solid rgba(255,255,255,0.03); }
            .import-result { margin-top: 0.75rem; padding: 0.65rem 0.75rem; border-radius: 8px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); display: flex; align-items: center; gap: 0.5rem; }
            .import-text { font-size: 0.72rem; color: #34d399; font-weight: 600; }

            /* Pricing */
            .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-top: 3rem; }
            .pricing-card { border-radius: 16px; border: 1px solid var(--border); background: var(--bg-card); padding: 2rem; position: relative; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
            .pricing-card:hover { transform: translateY(-4px); box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
            .pricing-card.featured { border-color: rgba(99,102,241,0.4); background: linear-gradient(180deg, rgba(99,102,241,0.08) 0%, var(--bg-card) 100%); box-shadow: 0 0 40px rgba(99,102,241,0.12); }
            .pricing-card.featured::before { content: 'Most Popular'; position: absolute; top: 1.25rem; right: 1.25rem; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; padding: 0.25rem 0.65rem; border-radius: 100px; }
            .plan-name { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 0.75rem; }
            .plan-price { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.5rem; font-weight: 800; color: #fff; letter-spacing: -0.03em; line-height: 1; }
            .plan-price sup { font-size: 1.1rem; font-weight: 600; vertical-align: super; margin-right: 2px; }
            .plan-price sub { font-size: 0.8rem; font-weight: 500; color: var(--text-muted); vertical-align: baseline; margin-left: 2px; }
            .plan-desc { font-size: 0.82rem; color: var(--text-secondary); margin: 0.75rem 0 1.5rem; line-height: 1.5; }
            .plan-divider { height: 1px; background: var(--border); margin-bottom: 1.25rem; }
            .plan-features { list-style: none; display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 1.75rem; }
            .plan-feature { display: flex; align-items: center; gap: 0.6rem; font-size: 0.82rem; color: var(--text-secondary); }
            .plan-feature-check { color: #34d399; font-size: 0.75rem; flex-shrink: 0; }
            .plan-feature-x { color: var(--text-muted); font-size: 0.75rem; flex-shrink: 0; }
            .plan-cta { display: block; text-align: center; padding: 0.7rem; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-decoration: none; transition: all 0.2s; }
            .plan-cta-outline { background: transparent; border: 1px solid var(--border); color: var(--text-primary); }
            .plan-cta-outline:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.15); }
            .plan-cta-filled { background: linear-gradient(135deg, #6366f1, #7c3aed); color: #fff; box-shadow: 0 6px 20px rgba(99,102,241,0.3); }
            .plan-cta-filled:hover { opacity: 0.9; transform: translateY(-1px); }

            /* CTA */
            .cta-section { padding: 5rem 1.5rem; text-align: center; position: relative; z-index: 1; }
            .cta-box {
                max-width: 700px; margin: 0 auto; padding: 3.5rem;
                border-radius: 24px;
                background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.08));
                border: 1px solid rgba(99,102,241,0.2);
                box-shadow: 0 0 60px rgba(99,102,241,0.1);
                position: relative; overflow: hidden;
            }
            .cta-box::before { content: ''; position: absolute; top: -50%; left: 50%; transform: translateX(-50%); width: 400px; height: 300px; background: radial-gradient(ellipse, rgba(99,102,241,0.2) 0%, transparent 70%); pointer-events: none; }
            .cta-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(1.6rem, 3vw, 2.25rem); font-weight: 800; color: #fff; letter-spacing: -0.025em; margin-bottom: 0.75rem; line-height: 1.2; }
            .cta-sub { font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6; }
            .cta-btns { display: flex; align-items: center; justify-content: center; gap: 0.75rem; flex-wrap: wrap; }

            /* Footer */
            footer { border-top: 1px solid var(--border); padding: 2.5rem 1.5rem; position: relative; z-index: 1; }
            .footer-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
            .footer-brand { display: flex; align-items: center; gap: 0.65rem; text-decoration: none; }
            .footer-logo { width: 28px; height: 28px; border-radius: 7px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.75rem; color: #fff; }
            .footer-brand-name { font-weight: 700; font-size: 0.88rem; color: var(--text-secondary); }
            .footer-copy { font-size: 0.78rem; color: var(--text-muted); }
            .footer-tech { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
            .tech-pill { padding: 0.25rem 0.6rem; border-radius: 100px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); font-size: 0.65rem; color: var(--text-muted); font-weight: 500; }

            /* Animations */
            @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
            .animate-in { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
            .animate-in.visible { opacity: 1; transform: translateY(0); }

            /* Responsive */
            @media (max-width: 900px) {
                .mini-stats { grid-template-columns: repeat(2, 1fr); }
                .features-grid { grid-template-columns: repeat(2, 1fr); }
                .pricing-grid { grid-template-columns: 1fr; max-width: 400px; margin-left: auto; margin-right: auto; }
                .two-col { grid-template-columns: 1fr; gap: 2.5rem; }
                .two-col.reversed { direction: ltr; }
                .stats-inner { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 600px) {
                .features-grid { grid-template-columns: 1fr; }
                .mini-row { grid-template-columns: 1fr; }
                .hero { padding: 3rem 1.5rem 2.5rem; }
                .section { padding: 3.5rem 1.5rem; }
                .cta-box { padding: 2rem 1.25rem; }
            }
        </style>
    </head>

    <body>
        <div class="ambient-glow glow-1"></div>
        <div class="ambient-glow glow-2"></div>
        <div class="ambient-glow glow-3"></div>

        <!-- Navigation -->
        <nav>
            <div class="nav-inner">
                <a href="/" class="logo">
                    <div class="logo-icon">I</div>
                    <div>
                        <span class="logo-name">Inoodex</span>
                        <span class="logo-sub">Cloud Inventory</span>
                    </div>
                </a>
                <div class="nav-links">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary">
                                Open Workspace
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-ghost">Sign In</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary">
                                    Get Started
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="hero">
            <div class="container" style="max-width:1100px;">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    Multi-Tenant SaaS &bull; Atomic Stock Control &bull; mPDF Invoicing
                </div>

                <h1 class="hero-title">
                    The Inventory Platform<br>Built to <span class="gradient-text">Scale With Your Business</span>
                </h1>

                <p class="hero-sub">
                    Full-stack multi-tenant inventory management with multi-warehouse stock control, atomic purchase &amp; sales order flows, automated alerts, and PDF document generation — all scoped per client.
                </p>

                <div class="hero-ctas">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-cta-primary">
                            Open My Workspace
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-cta-primary">
                            Start Free &mdash; Create Account
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="btn-cta-secondary">Sign In Existing Team</a>
                    @endauth
                </div>

                <!-- Dashboard preview frame -->
                <div class="hero-visual">
                    <div class="hero-visual-frame">
                        <div class="frame-bar">
                            <div class="frame-dot"></div>
                            <div class="frame-dot"></div>
                            <div class="frame-dot"></div>
                            <div class="frame-url">app.inoodex.com/dashboard</div>
                        </div>
                        <div class="frame-content">
                            <div class="mini-dash">
                                <div class="mini-dash-header">
                                    <span class="mini-dash-title">Inventory Dashboard &mdash; Q4 2026</span>
                                    <span class="mini-badge badge-green">&bull; Live</span>
                                </div>
                                <div class="mini-stats">
                                    <div class="mini-stat">
                                        <div class="mini-stat-label">Stock Value</div>
                                        <div class="mini-stat-value">$284k</div>
                                        <div class="mini-stat-change change-up">&uarr; 12.4%</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-label">Orders Dispatched</div>
                                        <div class="mini-stat-value">1,842</div>
                                        <div class="mini-stat-change change-up">&uarr; 8.1%</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-label">Low-Stock Alerts</div>
                                        <div class="mini-stat-value">7</div>
                                        <div class="mini-stat-change change-down">&uarr; 3 items</div>
                                    </div>
                                    <div class="mini-stat">
                                        <div class="mini-stat-label">Warehouses</div>
                                        <div class="mini-stat-value">4</div>
                                        <div class="mini-stat-change change-up">All active</div>
                                    </div>
                                </div>
                                <div class="mini-row">
                                    <div class="mini-panel">
                                        <div class="mini-panel-title">Top Products by Stock</div>
                                        <div class="stock-bar-wrap">
                                            <div class="stock-bar-row">
                                                <span class="stock-bar-name">Wireless Keyboard</span>
                                                <div class="stock-bar-track"><div class="stock-bar-fill" style="width:82%;background:linear-gradient(90deg,#6366f1,#8b5cf6)"></div></div>
                                                <span class="stock-bar-pct">82%</span>
                                            </div>
                                            <div class="stock-bar-row">
                                                <span class="stock-bar-name">USB-C Hub Pro</span>
                                                <div class="stock-bar-track"><div class="stock-bar-fill" style="width:65%;background:linear-gradient(90deg,#06b6d4,#6366f1)"></div></div>
                                                <span class="stock-bar-pct">65%</span>
                                            </div>
                                            <div class="stock-bar-row">
                                                <span class="stock-bar-name">Laptop Stand Alum</span>
                                                <div class="stock-bar-track"><div class="stock-bar-fill" style="width:48%;background:linear-gradient(90deg,#8b5cf6,#c084fc)"></div></div>
                                                <span class="stock-bar-pct">48%</span>
                                            </div>
                                            <div class="stock-bar-row">
                                                <span class="stock-bar-name">Webcam 4K Auto</span>
                                                <div class="stock-bar-track"><div class="stock-bar-fill" style="width:21%;background:linear-gradient(90deg,#f59e0b,#f43f5e)"></div></div>
                                                <span class="stock-bar-pct">21%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mini-panel">
                                        <div class="mini-panel-title">Recent Sales Orders</div>
                                        <div class="order-list">
                                            <div class="order-row">
                                                <span class="order-id">SO-0291</span>
                                                <span class="order-name">Acme Corp</span>
                                                <span class="order-amount">$4,280</span>
                                                <span class="order-status status-dispatched">Dispatched</span>
                                            </div>
                                            <div class="order-row">
                                                <span class="order-id">SO-0290</span>
                                                <span class="order-name">TechStart Ltd</span>
                                                <span class="order-amount">$1,940</span>
                                                <span class="order-status status-pending">Confirmed</span>
                                            </div>
                                            <div class="order-row">
                                                <span class="order-id">SO-0289</span>
                                                <span class="order-name">GlobalTech Inc</span>
                                                <span class="order-amount">$7,620</span>
                                                <span class="order-status status-dispatched">Dispatched</span>
                                            </div>
                                            <div class="order-row">
                                                <span class="order-id">SO-0288</span>
                                                <span class="order-name">Beta Supplies</span>
                                                <span class="order-amount">$892</span>
                                                <span class="order-status status-draft">Draft</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats strip -->
        <div class="stats-strip animate-in">
            <div class="stats-inner">
                <div>
                    <div class="stat-num"><span>100%</span></div>
                    <div class="stat-label">Tenant Data Isolation</div>
                </div>
                <div>
                    <div class="stat-num"><span>0</span></div>
                    <div class="stat-label">Race Conditions via Row Locking</div>
                </div>
                <div>
                    <div class="stat-num"><span>87+</span></div>
                    <div class="stat-label">Automated Tests Passing</div>
                </div>
                <div>
                    <div class="stat-num"><span>&infin;</span></div>
                    <div class="stat-label">Concurrent Client Tenants</div>
                </div>
            </div>
        </div>

        <!-- Features grid -->
        <section class="section">
            <div class="container">
                <div class="animate-in">
                    <div class="section-label">Platform Features</div>
                    <h2 class="section-title">Everything Your Business Needs,<br>Nothing It Doesn't</h2>
                    <p class="section-sub">From atomic stock transactions to PDF packing slips &mdash; a complete, production-ready inventory engine you can rent to clients immediately.</p>
                </div>
                <div class="features-grid animate-in">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(99,102,241,0.12);">&#127980;</div>
                        <div class="feature-title">Multi-Warehouse Management</div>
                        <div class="feature-desc">Track physical on-hand, reserved, and available quantities per location across unlimited distribution hubs with zero drift.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(139,92,246,0.12);">&#9889;</div>
                        <div class="feature-title">Atomic Stock Locking</div>
                        <div class="feature-desc">Pessimistic DB row locking wrapped in transactions eliminates race conditions, overselling, and negative stock even under high concurrency.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(6,182,212,0.12);">&#127970;</div>
                        <div class="feature-title">Strict Multi-Tenancy</div>
                        <div class="feature-desc">Global query scopes and Jetstream Teams ensure every client's data is fully isolated &mdash; impossible for one tenant to see another's records.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(16,185,129,0.12);">&#128230;</div>
                        <div class="feature-title">Purchase Order Lifecycle</div>
                        <div class="feature-desc">Draft &rarr; Ordered &rarr; Partially Received &rarr; Fully Received. Receiving dock triggers atomic stock-in with full movement ledger records.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(245,158,11,0.12);">&#128666;</div>
                        <div class="feature-title">Sales Order &amp; Dispatch</div>
                        <div class="feature-desc">Confirmation reserves stock, dispatch releases reservation and deducts. Payment tracking with outstanding balance visibility per order.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(244,63,94,0.12);">&#128196;</div>
                        <div class="feature-title">mPDF Document Suite</div>
                        <div class="feature-desc">Vector barcode Sales Invoices, Packing Slips, and Purchase Orders with tenant branding, custom currency symbols, and tax IDs.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(99,102,241,0.12);">&#128202;</div>
                        <div class="feature-title">Inventory Reports</div>
                        <div class="feature-desc">Stock valuation (cost vs. selling price), low-stock deficit alerts, and a filterable chronological stock movement audit ledger.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(139,92,246,0.12);">&#128229;</div>
                        <div class="feature-title">Bulk CSV Import / Export</div>
                        <div class="feature-desc">Import entire catalogs with auto-category creation, opening stock allocation, and duplicate SKU handling. Export in one click.</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon" style="background:rgba(6,182,212,0.12);">&#128081;</div>
                        <div class="feature-title">Super-Admin Control Plane</div>
                        <div class="feature-desc">Platform owner dashboard: monitor all client tenants, suspend/reactivate accounts, switch tenant context, and manage plans.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Super Admin deep dive -->
        <section class="section" style="background: rgba(255,255,255,0.01); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
            <div class="container">
                <div class="two-col">
                    <div class="animate-in">
                        <div class="section-label">Platform Control</div>
                        <h2 class="section-title">You're the Platform Owner. Act Like It.</h2>
                        <p class="section-sub">The Super-Admin Control Plane gives you god-mode visibility over every client tenant on your platform &mdash; from a single, dedicated dashboard.</p>
                        <ul class="benefit-list">
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Platform-wide KPIs</strong> &mdash; total tenants, active / suspended counts, all-client aggregate inventory valuation</div>
                            </li>
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>1-Click Suspend / Reactivate</strong> &mdash; instantly block non-paying clients while exempting yourself from the lockout</div>
                            </li>
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Tenant Context Switch</strong> &mdash; click "Enter Workspace" to jump into any client's inventory as if you were them</div>
                            </li>
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Plan Management</strong> &mdash; update client subscription tier (Starter / Professional / Enterprise) directly</div>
                            </li>
                        </ul>
                    </div>
                    <div class="animate-in">
                        <div class="mock-panel">
                            <div class="mock-panel-header">
                                <span class="mock-panel-title">&#128081; Super-Admin &mdash; /admin</span>
                                <span class="mini-badge badge-green" style="font-size:0.6rem;">Platform Owner</span>
                            </div>
                            <div class="mock-panel-body">
                                <div class="kpi-row">
                                    <div class="kpi-card"><div class="kpi-val">24</div><div class="kpi-lbl">Total Clients</div></div>
                                    <div class="kpi-card"><div class="kpi-val" style="color:#34d399;">21</div><div class="kpi-lbl">Active</div></div>
                                    <div class="kpi-card"><div class="kpi-val" style="color:#f87171;">3</div><div class="kpi-lbl">Suspended</div></div>
                                </div>
                                <div class="tenant-list">
                                    <div class="tenant-row">
                                        <div class="tenant-avatar" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">A</div>
                                        <div class="tenant-info"><div class="tenant-name">Acme Retail Co.</div><div class="tenant-plan">Enterprise &middot; 3 warehouses</div></div>
                                        <span class="tenant-status tenant-active">Active</span>
                                    </div>
                                    <div class="tenant-row">
                                        <div class="tenant-avatar" style="background:linear-gradient(135deg,#06b6d4,#6366f1);">T</div>
                                        <div class="tenant-info"><div class="tenant-name">TechStart Ltd</div><div class="tenant-plan">Professional &middot; 1 warehouse</div></div>
                                        <span class="tenant-status tenant-trial">Trial</span>
                                    </div>
                                    <div class="tenant-row">
                                        <div class="tenant-avatar" style="background:linear-gradient(135deg,#f59e0b,#f43f5e);">G</div>
                                        <div class="tenant-info"><div class="tenant-name">GlobalTech Inc.</div><div class="tenant-plan">Starter &middot; 1 warehouse</div></div>
                                        <span class="tenant-status tenant-sus">Suspended</span>
                                    </div>
                                    <div class="tenant-row">
                                        <div class="tenant-avatar" style="background:linear-gradient(135deg,#10b981,#06b6d4);">B</div>
                                        <div class="tenant-info"><div class="tenant-name">Beta Supplies PH</div><div class="tenant-plan">Professional &middot; 2 warehouses</div></div>
                                        <span class="tenant-status tenant-active">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CSV Import deep dive -->
        <section class="section">
            <div class="container">
                <div class="two-col reversed">
                    <div class="animate-in">
                        <div class="section-label">Instant Onboarding</div>
                        <h2 class="section-title">Onboard an Entire Catalog in Seconds</h2>
                        <p class="section-sub">New clients don't need to type in hundreds of products. Upload a CSV and the platform handles the rest automatically.</p>
                        <ul class="benefit-list">
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Auto-creates product categories</strong> &mdash; if a category doesn't exist it's created on-the-fly during import</div>
                            </li>
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Updates duplicate SKUs</strong> &mdash; re-importing existing products updates records without throwing key errors</div>
                            </li>
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Opening stock allocation</strong> &mdash; specify a warehouse and all imported products get their initial stock registered atomically</div>
                            </li>
                            <li class="benefit-item">
                                <div class="benefit-check">&#10003;</div>
                                <div class="benefit-text"><strong>Detailed import report</strong> &mdash; see exactly how many products were created, updated, or skipped with per-row error logs</div>
                            </li>
                        </ul>
                    </div>
                    <div class="animate-in">
                        <div class="csv-visual">
                            <div class="csv-header">
                                <span style="font-size:1.1rem;">&#128229;</span>
                                <span class="csv-title">product_catalog_import.csv</span>
                                <span class="mini-badge badge-amber" style="margin-left:auto;font-size:0.6rem;">142 rows</span>
                            </div>
                            <table class="csv-table">
                                <thead>
                                    <tr><th>SKU</th><th>Product Name</th><th>Category</th><th>Cost</th><th>Price</th><th>Qty</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td>KB-WL-001</td><td>Wireless Keyboard</td><td>Peripherals</td><td>18.00</td><td>35.00</td><td>200</td></tr>
                                    <tr><td>HB-UC-002</td><td>USB-C Hub Pro</td><td>Accessories</td><td>22.50</td><td>49.99</td><td>150</td></tr>
                                    <tr><td>ST-AL-003</td><td>Aluminium Laptop Stand</td><td>Accessories</td><td>14.00</td><td>29.99</td><td>80</td></tr>
                                    <tr><td>WC-4K-004</td><td>4K Webcam Auto Focus</td><td>Peripherals</td><td>45.00</td><td>89.00</td><td>60</td></tr>
                                    <tr><td colspan="6" style="text-align:center;opacity:0.3;padding:0.5rem;">&middot; &middot; &middot; 137 more rows &middot; &middot; &middot;</td></tr>
                                </tbody>
                            </table>
                            <div class="import-result">
                                <span style="font-size:0.9rem;">&#9989;</span>
                                <span class="import-text">Import complete &mdash; 139 created, 3 updated, 0 skipped. Opening stock allocated to Warehouse A.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section class="section" style="background: rgba(255,255,255,0.01); border-top: 1px solid var(--border);">
            <div class="container">
                <div style="text-align:center;" class="animate-in">
                    <div class="section-label" style="justify-content:center;">Pricing</div>
                    <h2 class="section-title">Transparent Pricing for Every Scale</h2>
                    <p class="section-sub" style="margin:0 auto 0;">Choose a plan that fits your client portfolio. All plans include full multi-tenancy, atomic stock control, and PDF generation.</p>
                </div>
                <div class="pricing-grid animate-in">
                    <div class="pricing-card">
                        <div class="plan-name">Starter</div>
                        <div class="plan-price"><sup>$</sup>29<sub>/mo</sub></div>
                        <div class="plan-desc">Perfect for small teams getting started with cloud inventory management.</div>
                        <div class="plan-divider"></div>
                        <ul class="plan-features">
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> 1 Warehouse</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Up to 5 Users</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> 500 Products</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> CSV Import / Export</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> PDF Invoices &amp; POs</li>
                            <li class="plan-feature"><span class="plan-feature-x">&#10007;</span> <span style="opacity:0.4;">Low-Stock Email Alerts</span></li>
                            <li class="plan-feature"><span class="plan-feature-x">&#10007;</span> <span style="opacity:0.4;">API Access</span></li>
                        </ul>
                        <a href="{{ route('register') }}" class="plan-cta plan-cta-outline">Get Started</a>
                    </div>
                    <div class="pricing-card featured">
                        <div class="plan-name">Professional</div>
                        <div class="plan-price"><sup>$</sup>79<sub>/mo</sub></div>
                        <div class="plan-desc">For growing businesses managing multi-location inventory across teams.</div>
                        <div class="plan-divider"></div>
                        <ul class="plan-features">
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Up to 5 Warehouses</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Unlimited Users</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Unlimited Products</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> CSV Import / Export</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> PDF Invoices &amp; POs</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Low-Stock Email Alerts</li>
                            <li class="plan-feature"><span class="plan-feature-x">&#10007;</span> <span style="opacity:0.4;">API Access</span></li>
                        </ul>
                        <a href="{{ route('register') }}" class="plan-cta plan-cta-filled">Start Free Trial</a>
                    </div>
                    <div class="pricing-card">
                        <div class="plan-name">Enterprise</div>
                        <div class="plan-price"><sup>$</sup>199<sub>/mo</sub></div>
                        <div class="plan-desc">Full-scale deployment for enterprise operations with API access and dedicated support.</div>
                        <div class="plan-divider"></div>
                        <ul class="plan-features">
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Unlimited Warehouses</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Unlimited Users</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Unlimited Products</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> CSV Import / Export</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> PDF Invoices &amp; POs</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> Low-Stock Email Alerts</li>
                            <li class="plan-feature"><span class="plan-feature-check">&#10003;</span> API Access</li>
                        </ul>
                        <a href="{{ route('register') }}" class="plan-cta plan-cta-outline">Contact Sales</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="cta-section animate-in">
            <div class="cta-box">
                <h2 class="cta-title">Ready to run inventory<br>like a platform?</h2>
                <p class="cta-sub">Create your organization account in under 60 seconds. No credit card required.</p>
                <div class="cta-btns">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-cta-primary">
                            Open My Workspace
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn-cta-primary">
                            Create Free Account
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="btn-cta-secondary">Sign In</a>
                    @endauth
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="footer-inner">
                <a href="/" class="footer-brand">
                    <div class="footer-logo">I</div>
                    <span class="footer-brand-name">Inoodex Inventory</span>
                </a>
                <div class="footer-tech">
                    <span class="tech-pill">Laravel 11</span>
                    <span class="tech-pill">Livewire 3</span>
                    <span class="tech-pill">Jetstream Teams</span>
                    <span class="tech-pill">Tailwind CSS</span>
                    <span class="tech-pill">mPDF</span>
                </div>
                <div class="footer-copy">&copy; {{ date('Y') }} Inoodex. All rights reserved.</div>
            </div>
        </footer>

        <script>
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('visible');
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            document.querySelectorAll('.animate-in').forEach(el => observer.observe(el));
        </script>
    </body>
</html>

        <!-- CSV IMPORT -->
        <section class="sectsect">
            <div class="wrap">
                <div class="twocol r fadeup">
                    <div>
                        <div class="sect-eyebrow">Operations</div>
                        <h2 class="sect-ttl">Onboard catalogs in <em class="serif" style="color:var(--text-2)">minutes, not weeks.</em></h2>
                        <p class="sect-sub">Drop a CSV into the importer and we'll create categories, open stock, and match SKUs.</p>
                        <div class="featlist">
                            <div class="fitem">
                                <div class="fcheck">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="ftxt"><b>Auto-create categories</b> — map a column and we'll build the taxonomy</div>
                            </div>
                            <div class="fitem">
                                <div class="fcheck">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="ftxt"><b>Column mapping UI</b> — drag-and-drop header matching</div>
                            </div>
                            <div class="fitem">
                                <div class="fcheck">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="ftxt"><b>SKU dedup + dry-run</b> — preview before committing</div>
                            </div>
                            <div class="fitem">
                                <div class="fcheck">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="ftxt"><b>Batch error reporting</b> — line-numbered issues for quick fixes</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mock">
                            <div class="mock-hd">
                                <span class="mock-ttl">Product Import · products_master.csv</span>
                                <span class="tag tag-g">842 rows</span>
                            </div>
                            <div class="mock-body">
                                <table class="csvtbl">
                                    <thead>
                                        <tr>
                                            <th>SKU</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Cost</th>
                                            <th>Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>KB-AIR-01</td><td>Air Wireless KB</td><td>Peripherals</td><td>$48.00</td><td>820</td></tr>
                                        <tr><td>MS-OPT-04</td><td>Optical Mouse Pro</td><td>Peripherals</td><td>$12.00</td><td>1,440</td></tr>
                                        <tr><td>HD-4K-02</td><td>4K Webcam Ultra</td><td>Cameras</td><td>$76.00</td><td>210</td></tr>
                                        <tr><td>HU-C-240</td><td>USB-C Hub 8-in-1</td><td>Cables</td><td>$22.00</td><td>650</td></tr>
                                        <tr><td>ST-MTL-09</td><td>Metal Laptop Stand</td><td>Office</td><td>$34.00</td><td>480</td></tr>
                                    </tbody>
                                </table>
                                <div class="importok">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    842 rows successfully imported · 3 categories created · 0 SKU collisions
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRICING -->
        <section class="sectsect" id="pricing" style="background:#fafafa;border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
            <div class="wrap">
                <div class="sect-head fadeup" style="text-align:center">
                    <div class="sect-eyebrow" style="justify-content:center">Pricing</div>
                    <h2 class="sect-ttl" style="text-align:center">Simple, scalable pricing.</h2>
                    <p class="sect-sub" style="text-align:center;margin:0 auto">Start free, upgrade as your tenancy grows. No per-seat lock-in.</p>
                </div>

                <div class="price-grid fadeup">
                    <div class="price-card">
                        <div class="pln-nm">Starter</div>
                        <div class="pln-pr">
                            <span class="pln-dol">$</span><span class="pln-num">29</span><span class="pln-per">/mo</span>
                        </div>
                        <div class="pln-desc">For a single client or small team launching their first inventory operation.</div>
                        <div class="pln-div"></div>
                        <ul class="pln-list">
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to 3 tenant accounts
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                2 warehouses per tenant
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Full PDF document suite
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                CSV Import &amp; Export
                            </li>
                            <li class="pln-li muted">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Super-Admin control plane
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="pln-cta o">Get Started</a>
                    </div>

                    <div class="price-card featured">
                        <div class="pln-nm">Professional</div>
                        <div class="pln-pr">
                            <span class="pln-dol">$</span><span class="pln-num">79</span><span class="pln-per">/mo</span>
                        </div>
                        <div class="pln-desc">For consultancies and agencies managing multiple inventory clients.</div>
                        <div class="pln-div"></div>
                        <ul class="pln-list">
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to 20 tenant accounts
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited warehouses
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Full PDF document suite
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Super-Admin control plane
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Priority email support
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="pln-cta s">Start Free Trial</a>
                    </div>

                    <div class="price-card">
                        <div class="pln-nm">Enterprise</div>
                        <div class="pln-pr">
                            <span class="pln-dol">$</span><span class="pln-num">199</span><span class="pln-per">/mo</span>
                        </div>
                        <div class="pln-desc">For companies with large tenancies, custom workflow, and dedicated support.</div>
                        <div class="pln-div"></div>
                        <ul class="pln-list">
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited tenant accounts
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited warehouses
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Custom tenant branding &amp; logos
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Super-Admin control plane
                            </li>
                            <li class="pln-li">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                SLA + dedicated manager
                            </li>
                        </ul>
                        <a href="mailto:hello@inoodex.com" class="pln-cta o">Contact Sales</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FINAL CTA -->
        <section class="fin">
            <div class="cta-box fadeup">
                <h3 class="cta-ttl">Inventory SaaS infrastructure — <em class="serif" style="color:var(--text-2)">ready today.</em></h3>
                <p class="cta-sub">Provision your first tenant in under 2 minutes. No credit card required.</p>
                <div class="cta-btns">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-solid btn-lg">
                                Open Dashboard
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-solid btn-lg">
                                Start Free Trial
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline btn-lg">Sign in</a>
                        @endauth
                    @endif
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer>
            <div class="frow">
                <div class="fleft">
                    <div class="brand-mark" style="width:24px;height:24px;border-radius:6px;font-size:12px">I</div>
                    <span class="fname">Inoodex · Multi-tenant Inventory SaaS</span>
                </div>
                <div class="fcopy">© {{ date('Y') }} Inoodex. All rights reserved.</div>
                <div class="fpills">
                    <span class="fpill">Laravel 11</span>
                    <span class="fpill">Livewire 3</span>
                    <span class="fpill">Jetstream Teams</span>
                    <span class="fpill">Tailwind CSS</span>
                    <span class="fpill">mPDF</span>
                </div>
            </div>
        </footer>

        <script>
            // Fade-up on scroll
            (function () {
                var els = document.querySelectorAll('.fadeup');
                if (!('IntersectionObserver' in window)) {
                    els.forEach(function (el) { el.classList.add('is'); });
                    return;
                }
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.08, rootMargin: '0px 0px -60px 0px' });
                els.forEach(function (el) { io.observe(el); });
            })();
        </script>

    </body>
</html>
