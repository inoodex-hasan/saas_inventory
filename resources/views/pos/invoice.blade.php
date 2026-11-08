<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Invoice - {{ $order->order_number ?? 'Receipt' }}</title>
    
    {{-- Google Fonts for luxury typography --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #F3F4F6;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #0A2E33;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .pos-inv-page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            margin: 0 auto;
            background: #F3F4F6;
            padding: 40px 16px 60px;
        }

        /* ---------------- TOP ACTIONS ---------------- */
        .inv-floating-actions {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .inv-float-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all .15s ease;
            border: 1px solid transparent;
            user-select: none;
        }

        .inv-float-back {
            background: #ffffff;
            border-color: #D1D5DB;
            color: #374151;
        }

        .inv-float-back:hover {
            border-color: #9CA3AF;
            background: #F9FAFB;
            color: #111827;
        }

        .inv-float-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .inv-float-pdf {
            background: #ffffff;
            border-color: #D1D5DB;
            color: #DC2626;
        }

        .inv-float-pdf:hover {
            border-color: #F87171;
            background: #FEF2F2;
        }

        .inv-float-thermal {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }

        .inv-float-thermal:hover {
            background: #1F2937;
            border-color: #1F2937;
        }

        .inv-float-print {
            background: #0A6B79;
            color: #ffffff;
        }

        .inv-float-print:hover {
            background: #085560;
        }

        /* ---------------- INVOICE PAPER CARD (STANDARD A4: 210mm x 297mm) ---------------- */
        .inv-paper {
            width: 210mm;
            max-width: 100%;
            min-height: 295mm;
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            position: relative;
            padding: 12mm 15mm 12mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            page-break-after: always;
            break-after: page;
            margin-bottom: 24px;
        }

        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .inv-brand-text h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .inv-brand-subtitle {
            font-size: 11px;
            color: #6B7280;
            font-weight: 500;
            margin-top: 3px;
        }

        .inv-brand-contact {
            font-size: 10.5px;
            color: #4B5563;
            margin-top: 4px;
            line-height: 1.4;
        }

        .inv-header-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 3px;
        }

        .inv-title-large {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #111827;
            line-height: 1;
            margin: 0;
        }

        .inv-order-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #0A6B79;
            margin-top: 2px;
        }

        .inv-paid-seal {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 9.5px;
            font-weight: 700;
            color: #059669;
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: .4px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .inv-date-text {
            font-size: 11px;
            color: #6B7280;
            font-weight: 500;
            margin-top: 2px;
        }

        .inv-divider-line {
            height: 1px;
            background: #E5E7EB;
            margin: 18px 0;
        }

        /* ---------------- TWO-COLUMN META INFO ---------------- */
        .inv-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-col {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .info-col.tr {
            text-align: right;
            align-items: flex-end;
        }

        .info-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: #9CA3AF;
        }

        .info-title {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
        }

        .info-sub {
            font-size: 11.5px;
            color: #4B5563;
        }

        .info-pay-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 2.5px 8px;
            border-radius: 5px;
            background: #E0F2FE;
            color: #0369A1;
            border: 1px solid #BAE6FD;
            width: fit-content;
        }

        /* ---------------- PRODUCTS TABLE ---------------- */
        .inv-table-wrap {
            margin-bottom: 20px;
            border-top: 1px solid #E5E7EB;
            border-bottom: 1px solid #E5E7EB;
        }

        .inv-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inv-table thead th {
            background: #F9FAFB;
            color: #4B5563;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        .inv-table thead th:first-child {
            width: 34px;
            text-align: center;
        }

        .inv-table thead th:last-child {
            text-align: right;
            padding-right: 10px;
        }

        .inv-table thead th.tr { text-align: right; }
        .inv-table thead th.tc { text-align: center; }

        .inv-table tbody tr {
            border-bottom: 1px solid #F3F4F6;
        }

        .inv-table tbody tr:last-child {
            border-bottom: 0;
        }

        .inv-table tbody td {
            padding: 8px 10px;
            font-size: 12px;
            color: #1F2937;
            vertical-align: middle;
        }

        .inv-table tbody td:first-child {
            text-align: center;
            color: #9CA3AF;
            font-size: 11px;
        }

        .inv-table tbody td:last-child {
            text-align: right;
            font-weight: 700;
            color: #111827;
            padding-right: 10px;
        }

        .inv-table tbody td.tr { text-align: right; }
        .inv-table tbody td.tc { text-align: center; }

        .item-meta-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .item-category-tag {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #0A6B79;
        }

        .item-title-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
            line-height: 1.3;
        }

        .item-sku-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9.5px;
            color: #9CA3AF;
        }

        .qty-pill-badge {
            display: inline-block;
            background: #F3F4F6;
            color: #1F2937;
            font-weight: 600;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* ---------------- BOTTOM SUMMARY ---------------- */
        .inv-bottom-grid {
            display: grid;
            grid-template-columns: 1fr 260px;
            gap: 24px;
            margin-bottom: 24px;
            align-items: start;
        }

        .inv-words-simple {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .inv-words-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #9CA3AF;
        }

        .inv-words-val {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            font-style: italic;
            line-height: 1.4;
        }

        .inv-words-note {
            font-size: 11px;
            color: #9CA3AF;
            margin-top: 6px;
        }

        .inv-totals-simple {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .tot-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #4B5563;
        }

        .tot-line b {
            font-weight: 600;
            color: #111827;
        }

        .tot-line.disc b {
            color: #DC2626;
            font-weight: 700;
        }

        .tot-divider {
            height: 1px;
            background: #E5E7EB;
            margin: 4px 0;
        }

        .tot-line-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            font-weight: 800;
            color: #0F172A;
            padding: 6px 0;
            border-top: 1px solid #E5E7EB;
            margin-top: 4px;
        }

        .tot-line-total b {
            font-size: 16.5px;
            font-weight: 900;
            color: #0A6B79;
        }

        .tot-payment-summary {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px dashed #E5E7EB;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .tot-sub-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #6B7280;
        }

        .tot-sub-row b {
            color: #111827;
            font-weight: 600;
        }

        .inv-footer {
            border-top: 1px solid #E5E7EB;
            padding-top: 16px;
            margin-top: auto;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }

        .inv-signature-box {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .inv-signature-line {
            width: 120px;
            height: 1px;
            background: #9CA3AF;
            margin-bottom: 2px;
        }

        .inv-signature-title {
            font-size: 9px;
            font-weight: 600;
            color: #6B7280;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .inv-page-foot {
            margin-top: auto;
            border-top: 1px solid #F3F4F6;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #9CA3AF;
            letter-spacing: .3px;
        }

        /* ---------------- PRINT MEDIA ---------------- */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .pos-inv-page {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .inv-floating-actions {
                display: none !important;
            }

            .inv-paper {
                box-shadow: none !important;
                border: 0 !important;
                border-radius: 0 !important;
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 12mm 15mm 12mm !important;
                page-break-after: auto !important;
            }
        }
    </style>
</head>
<body>

<div class="pos-inv-page">

    {{-- FLOATING TOP ACTION PILLS --}}
    <div class="inv-floating-actions">
        <a href="{{ route('pos.index') }}" class="inv-float-btn inv-float-back" title="Return to POS Terminal">
            <i class="fas fa-arrow-left"></i>
            <span>Back to POS</span>
        </a>

        <div class="inv-float-group">
            <button type="button" class="inv-float-btn inv-float-pdf" id="btnDownloadPdf" title="Download invoice as PDF">
                <i class="fas fa-file-pdf"></i>
                <span>Download PDF</span>
            </button>
            <button type="button" class="inv-float-btn inv-float-thermal" id="btnThermalPrint" title="Print 80mm Thermal Receipt">
                <i class="fas fa-receipt"></i>
                <span>Thermal Print</span>
            </button>
            <button type="button" class="inv-float-btn inv-float-print" id="btnPrint" title="Print standard invoice (A4)">
                <i class="fas fa-print"></i>
                <span>Print Invoice</span>
            </button>
        </div>
    </div>

    {{-- INVOICE PAPER CARD --}}
    <div id="invoicePagesContainer">
        <article class="inv-paper" id="invoicePaper">
            <!-- Header -->
            <header class="inv-header">
                <div class="inv-brand-text">
                    <h2>{{ $team->displayName() }}</h2>
                    <div class="inv-brand-subtitle">Point of Sale &middot; Retail &amp; Wholesale</div>
                    <div class="inv-brand-contact">
                        @if($team->formattedAddress())
                            {{ $team->formattedAddress() }}<br>
                        @endif
                        @if($team->phone || $team->email)
                            {{ collect([$team->phone, $team->email])->filter()->join(' · ') }}
                        @endif
                    </div>
                </div>
                <div class="inv-header-right">
                    <h1 class="inv-title-large">INVOICE</h1>
                    <span class="inv-order-badge" id="invNumber">#{{ $order->order_number }}</span>
                    @if($order->payment_status === 'paid')
                        <span class="inv-paid-seal"><i class="fas fa-check-circle"></i> PAID</span>
                    @elseif($order->payment_status === 'partially_paid')
                        <span class="inv-paid-seal" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A;">PARTIAL</span>
                    @else
                        <span class="inv-paid-seal" style="background:#FEE2E2;color:#991B1B;border-color:#FECACA;">UNPAID</span>
                    @endif
                    <span class="inv-date-text">{{ $order->created_at->format('d M, Y \a\t h:i A') }}</span>
                </div>
            </header>

            <div class="inv-divider-line"></div>

            <!-- Customer & Payment Grid -->
            <div class="inv-info-grid">
                <div class="info-col">
                    <div class="info-label">BILLED TO</div>
                    <div class="info-title">{{ $order->customer->name ?? 'Walk-in Customer' }}</div>
                    <div class="info-sub">
                        {{ $order->customer->phone ?? 'No phone attached' }}
                        @if($order->customer?->address)
                            &middot; {{ $order->customer->address }}
                        @endif
                    </div>
                </div>
                <div class="info-col tr">
                    <div class="info-label">PAYMENT DETAILS</div>
                    <div class="info-title">
                        <span class="info-pay-badge">{{ ucfirst($order->payment_status === 'paid' ? 'Paid Counter Sale' : $order->payment_status) }}</span>
                    </div>
                    <div class="info-sub">Hub: {{ $order->warehouse->name ?? 'Main Store' }}</div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Details</th>
                            <th class="tc">Qty</th>
                            <th class="tr">Unit Price</th>
                            <th class="tr">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div class="item-meta-info">
                                        <span class="item-category-tag">{{ $item->product->category->name ?? 'Merchandise' }}</span>
                                        <span class="item-title-name">{{ $item->product->name ?? 'Item' }}</span>
                                        <span class="item-sku-code">{{ $item->product->sku ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="tc"><span class="qty-pill-badge">{{ number_format($item->quantity) }}</span></td>
                                <td class="tr">{{ $team->currency_symbol ?? '৳' }}{{ number_format($item->unit_price, 2) }}</td>
                                <td class="tr">{{ $team->currency_symbol ?? '৳' }}{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Bottom Summary Grid -->
            <div class="inv-bottom-grid">
                <div class="inv-words-simple">
                    <div class="inv-words-label">Amount in Words:</div>
                    <div class="inv-words-val">{{ numberToWords($order->total_amount) }} Taka Only</div>
                    <div class="inv-words-note">Thank you for your business!</div>
                </div>
                <div class="inv-totals-simple">
                    <div class="tot-line"><span>Subtotal</span><b>{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->subtotal, 2) }}</b></div>
                    @if($order->discount_amount > 0)
                        <div class="tot-line disc"><span>Discount</span><b>- {{ $team->currency_symbol ?? '৳' }}{{ number_format($order->discount_amount, 2) }}</b></div>
                    @endif
                    @if($order->tax_amount > 0)
                        <div class="tot-line"><span>VAT / Tax</span><b>{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->tax_amount, 2) }}</b></div>
                    @endif
                    @if($order->shipping_cost > 0)
                        <div class="tot-line"><span>Shipping</span><b>{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->shipping_cost, 2) }}</b></div>
                    @endif
                    <div class="tot-divider"></div>
                    <div class="tot-line-total"><span>Grand Total:</span><b>{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->total_amount, 2) }}</b></div>
                    <div class="tot-payment-summary">
                        @php
                            $tendered = $order->tendered_amount !== null ? (float)$order->tendered_amount : (float)$order->paid_amount;
                            $change = $order->change_amount !== null ? (float)$order->change_amount : max(0, $tendered - (float)$order->paid_amount);
                            $due = max(0, (float)$order->total_amount - (float)$order->paid_amount);
                        @endphp
                        @if($tendered > 0)
                            <div class="tot-sub-row"><span>Cash Tendered:</span><b>{{ $team->currency_symbol ?? '৳' }}{{ number_format($tendered, 2) }}</b></div>
                        @endif
                        <div class="tot-sub-row"><span>Paid Amount:</span><b>{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 2) }}</b></div>
                        @if($change > 0)
                            <div class="tot-sub-row"><span style="color:#15803D;">Change / Return:</span><b style="color:#15803D;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($change, 2) }}</b></div>
                        @endif
                        @if($due > 0)
                            <div class="tot-sub-row"><span style="color:#DC2626;">Due Balance:</span><b style="color:#DC2626;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($due, 2) }}</b></div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Signature Footer -->
            <footer class="inv-footer">
                <div class="inv-signature-box">
                    <div class="inv-signature-line"></div>
                    <span class="inv-signature-title">Authorized Signature</span>
                </div>
            </footer>

            <div class="inv-page-foot">
                <span>#{{ $order->order_number }} &middot; {{ $team->displayName() }}</span>
                <span>Page 1 of 1</span>
            </div>
        </article>
    </div>

</div>

{{-- Thermal Print Hidden Iframe --}}
<iframe id="thermalPrintFrame" style="position:fixed;right:0;bottom:0;width:0;height:0;border:0;"></iframe>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function() {
    'use strict';

    var inv = {
        order: '#{{ $order->order_number }}',
        date: '{{ $order->created_at->format("d M, Y h:i A") }}',
        customer: '{{ addslashes($order->customer->name ?? "Walk-in Customer") }}',
        customerPhone: '{{ addslashes($order->customer->phone ?? "") }}',
        pay: '{{ ucfirst($order->payment_status === "paid" ? "Cash / Counter" : $order->payment_status) }}',
        sub: {{ (float) $order->subtotal }},
        disc: {{ (float) $order->discount_amount }},
        tax: {{ (float) $order->tax_amount }},
        ship: {{ (float) $order->shipping_cost }},
        total: {{ (float) $order->total_amount }},
        tendered: {{ $order->tendered_amount !== null ? (float) $order->tendered_amount : (float) $order->paid_amount }},
        paid: {{ (float) $order->paid_amount }},
        change: {{ $order->change_amount !== null ? (float) $order->change_amount : max(0, ($order->tendered_amount !== null ? (float) $order->tendered_amount : (float) $order->paid_amount) - (float) $order->paid_amount) }},
        due: {{ (float) max(0, (float)$order->total_amount - (float)$order->paid_amount) }},
        items: [
            @foreach($order->items as $item)
            {
                name: '{{ addslashes($item->product->name ?? "Item") }}',
                qty: {{ (float) $item->quantity }},
                price: {{ (float) $item->unit_price }},
                total: {{ (float) $item->subtotal }},
            },
            @endforeach
        ]
    };

    function money(n) {
        var num = parseFloat(n) || 0;
        return '৳ ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Shwapno-Style 80mm Thermal Receipt Generator
    function buildThermalSlipHtml(d) {
        var itemsHtml = (d.items || []).map(function(it) {
            return '<div class="sw-item">' +
                '<div class="sw-item-title">' + esc(it.name) + '</div>' +
                '<div class="sw-item-calc">' +
                    '<span>' + it.qty + ' x ' + money(it.price) + '</span>' +
                    '<b>' + money(it.total) + '</b>' +
                '</div>' +
            '</div>';
        }).join('');

        var discRow = d.disc > 0 ? '<div class="sw-tot-row" style="color:#DC2626;"><span>Discount:</span><b>- ' + money(d.disc) + '</b></div>' : '';
        var taxRow = d.tax > 0 ? '<div class="sw-tot-row"><span>VAT / Tax:</span><b>' + money(d.tax) + '</b></div>' : '';
        var shipRow = d.ship > 0 ? '<div class="sw-tot-row"><span>Shipping:</span><b>' + money(d.ship) + '</b></div>' : '';

        return '<div class="sw-hd">' +
            '<div class="sw-hd-brand">{{ addslashes($team->displayName()) }}</div>' +
            '<div class="sw-hd-tag">Point of Sale &middot; Retail &amp; Wholesale</div>' +
            '<div class="sw-hd-meta">' +
                '{{ addslashes($team->formattedAddress()) }}<br>' +
                '{{ collect([$team->phone, $team->email])->filter()->join(" · ") }}' +
            '</div>' +
        '</div>' +
        '<div class="sw-title-row">INVOICE</div>' +
        '<div class="sw-meta-row"><span>Bill No:</span><b>' + esc(d.order) + '</b></div>' +
        '<div class="sw-meta-row"><span>Date:</span><span>' + esc(d.date) + '</span></div>' +
        '<div class="sw-meta-row"><span>Customer:</span><span>' + esc(d.customer) + '</span></div>' +
        (d.customerPhone ? '<div class="sw-meta-row"><span>Phone:</span><span>' + esc(d.customerPhone) + '</span></div>' : '') +
        '<div class="sw-line-dashed"></div>' +
        '<div class="sw-tbl-head"><span>ITEMS</span><span>AMOUNT</span></div>' +
        '<div class="sw-line-dashed"></div>' +
        itemsHtml +
        '<div class="sw-line-dashed"></div>' +
        '<div class="sw-tot-row"><span>Subtotal:</span><b>' + money(d.sub) + '</b></div>' +
        discRow +
        taxRow +
        shipRow +
        '<div class="sw-line-double"></div>' +
        '<div class="sw-grand-banner"><span>TOTAL:</span><span>' + money(d.total) + '</span></div>' +
        '<div class="sw-line-double"></div>' +
        '<div class="sw-tot-row"><span>Payment:</span><b>' + esc(d.pay) + '</b></div>' +
        '<div class="sw-tot-row"><span>Status:</span><b style="color:' + (d.due > 0 ? '#DC2626' : '#15803D') + ';">' + (d.due > 0 ? (d.paid > 0 ? 'PARTIALLY PAID' : 'DUE / UNPAID') : 'PAID') + '</b></div>' +
        (d.tendered > 0 ? '<div class="sw-tot-row"><span>Cash Tendered:</span><b>' + money(d.tendered) + '</b></div>' : '') +
        '<div class="sw-tot-row"><span>Paid Amount:</span><b>' + money(d.paid) + '</b></div>' +
        (d.change > 0 ? '<div class="sw-tot-row" style="color:#15803D;"><span>Change / Return:</span><b>' + money(d.change) + '</b></div>' : '') +
        (d.due > 0 ? '<div class="sw-tot-row" style="color:#DC2626;"><span>Due Remaining:</span><b>' + money(d.due) + '</b></div>' : '') +
        '<div class="sw-line-dashed"></div>' +
        '<div class="sw-thanks">Thank You for Shopping with Us!</div>';
    }

    function printThermalNow() {
        var receiptBody = buildThermalSlipHtml(inv);
        var fullHtml = '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>Receipt ' + esc(inv.order) + '</title>' +
            '<style>' +
            '@page{size:portrait;margin:2mm 3mm;}' +
            '@media print{' +
            '  @page{size:portrait;margin:2mm 3mm;}' +
            '  html,body{height:100%!important;max-height:100vh!important;overflow:hidden!important;}' +
            '}' +
            '*{box-sizing:border-box;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}' +
            'html,body{margin:0;padding:0;background:#fff;color:#000;font-family:"Courier New",Courier,monospace,"Segoe UI",sans-serif;font-size:9.5px;line-height:1.2;}' +
            '.shwapno-receipt{width:100%;max-width:76mm;margin:0 auto;padding:1mm 0;box-sizing:border-box;}' +
            '.sw-hd{text-align:center;margin-bottom:1.5px;}' +
            '.sw-hd-brand{font-size:13px;font-weight:900;letter-spacing:0.5px;text-transform:uppercase;margin:0;}' +
            '.sw-hd-tag{font-size:7.5px;color:#333;margin-top:1px;}' +
            '.sw-hd-meta{font-size:7px;color:#444;margin-top:1px;}' +
            '.sw-title-row{text-align:center;font-weight:800;font-size:10px;letter-spacing:0.5px;margin:2px 0;}' +
            '.sw-meta-row{display:flex;justify-content:space-between;font-size:8.5px;margin-bottom:1px;color:#111;}' +
            '.sw-line-dashed{border-top:1px dashed #000;margin:2px 0;}' +
            '.sw-line-double{border-top:1.5px dashed #000;margin:2px 0;}' +
            '.sw-tbl-head{display:flex;justify-content:space-between;font-weight:800;font-size:9px;letter-spacing:0.5px;text-transform:uppercase;padding:1px 0;}' +
            '.sw-item{margin-bottom:1.5px;}' +
            '.sw-item-title{font-weight:700;font-size:9px;line-height:1.15;color:#000;word-break:break-word;}' +
            '.sw-item-calc{display:flex;justify-content:space-between;align-items:baseline;font-size:8.5px;margin-top:0.5px;}' +
            '.sw-tot-row{display:flex;justify-content:space-between;font-size:9px;margin-bottom:1px;}' +
            '.sw-grand-banner{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:900;padding:2px 0;letter-spacing:0.3px;}' +
            '.sw-thanks{text-align:center;font-size:9px;font-weight:800;text-transform:uppercase;margin:4px 0 1px;letter-spacing:0.5px;}' +
            '</style></head><body>' +
            '<div class="shwapno-receipt">' + receiptBody + '</div>' +
            '</body></html>';

        var iframe = document.getElementById('thermalPrintFrame');
        var doc = iframe.contentWindow.document;
        doc.open();
        doc.write(fullHtml);
        doc.close();

        setTimeout(function() {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 150);
    }

    document.getElementById('btnThermalPrint')?.addEventListener('click', printThermalNow);

    document.getElementById('btnPrint')?.addEventListener('click', function() {
        window.print();
    });

    document.getElementById('btnDownloadPdf')?.addEventListener('click', function() {
        var el = document.getElementById('invoicePaper');
        var opt = {
            margin: [0, 0, 0, 0],
            filename: 'Invoice-' + inv.order.replace('#', '') + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(el).save();
    });
})();
</script>

</body>
</html>

