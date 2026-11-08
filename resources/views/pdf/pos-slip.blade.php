<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Receipt - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 3mm 3mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Courier New', Courier, monospace, 'Segoe UI', sans-serif;
            color: #000000;
            font-size: 9.5px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
            width: 74mm;
            background: #ffffff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }

        .sw-hd {
            text-align: center;
            margin-bottom: 2px;
        }
        .sw-hd-brand {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .sw-hd-tag {
            font-size: 8px;
            color: #333333;
            margin-top: 1px;
        }
        .sw-hd-meta {
            font-size: 7.5px;
            color: #444444;
            margin-top: 2px;
            line-height: 1.3;
        }
        .sw-title-row {
            text-align: center;
            font-weight: 800;
            font-size: 10px;
            letter-spacing: 0.8px;
            margin: 3px 0 2px;
            text-transform: uppercase;
        }
        .sw-meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            margin-bottom: 1px;
            color: #111111;
        }
        .sw-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 2px;
        }
        .sw-meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .sw-line-dashed {
            border-top: 1px dashed #000000;
            margin: 3px 0;
        }
        .sw-line-double {
            border-top: 1.5px dashed #000000;
            margin: 3px 0;
        }

        .sw-tbl-head {
            width: 100%;
            border-collapse: collapse;
            font-weight: 800;
            font-size: 9px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .sw-tbl-head td {
            padding: 1px 0;
        }

        .sw-item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
        }
        .sw-item-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .sw-item-title {
            font-weight: 700;
            font-size: 9px;
            line-height: 1.2;
            color: #000000;
            word-break: break-word;
        }
        .sw-item-calc {
            font-size: 8.5px;
            color: #111111;
        }
        .sw-item-tag {
            font-size: 7px;
            color: #555555;
            display: block;
            margin-top: 1px;
        }

        .sw-tot-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .sw-tot-table td {
            padding: 1px 0;
        }

        .sw-grand-banner {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.3px;
            padding: 2px 0;
        }

        .sw-thanks {
            text-align: center;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 5px 0 2px;
            letter-spacing: 0.5px;
        }
        .sw-foot-note {
            text-align: center;
            font-size: 7.5px;
            color: #444444;
            margin-top: 2px;
            line-height: 1.3;
        }
    </style>
</head>
<body>

    <!-- Store Header -->
    <div class="sw-hd">
        <div class="sw-hd-brand">{{ $team->displayName() }}</div>
        <div class="sw-hd-tag">Point of Sale &middot; Retail &amp; Wholesale</div>
        <div class="sw-hd-meta">
            @if($team->formattedAddress())
                {{ $team->formattedAddress() }}<br>
            @endif
            @if($team->phone || $team->email)
                {{ collect([$team->phone, $team->email])->filter()->join(' · ') }}<br>
            @endif
            @if($team->tax_id)
                VAT / Tax: {{ $team->tax_id }}
            @endif
        </div>
    </div>

    <div class="sw-title-row">INVOICE</div>

    <!-- Metadata Section -->
    <table class="sw-meta-table">
        <tr>
            <td class="text-left font-bold" style="width: 55%;">Bill No: #{{ $order->order_number }}</td>
            <td class="text-right" style="width: 45%;">{{ $order->created_at->format('d M, Y h:i A') }}</td>
        </tr>
        <tr>
            <td class="text-left">Customer: {{ $order->customer->name ?? 'Walk-in Customer' }}</td>
            <td class="text-right">Hub: {{ $order->warehouse->name ?? 'Main Store' }}</td>
        </tr>
        @if($order->customer?->phone)
        <tr>
            <td colspan="2" class="text-left">Phone: {{ $order->customer->phone }}</td>
        </tr>
        @endif
        @if($order->creator)
        <tr>
            <td colspan="2" class="text-left">Operator: {{ $order->creator->name }}</td>
        </tr>
        @endif
    </table>

    <div class="sw-line-dashed"></div>

    <!-- Items Header -->
    <table class="sw-tbl-head">
        <tr>
            <td class="text-left">ITEMS</td>
            <td class="text-right">AMOUNT</td>
        </tr>
    </table>

    <div class="sw-line-dashed"></div>

    <!-- Items Listing -->
    <table class="sw-item-table">
        @foreach($order->items as $item)
            <tr>
                <td class="text-left" style="width: 70%;">
                    <div class="sw-item-title">{{ $item->product->name ?? 'Product Item' }}</div>
                    <div class="sw-item-calc">
                        {{ number_format($item->quantity) }} x {{ $team->currency_symbol ?? '৳' }}{{ number_format($item->unit_price, 2) }}
                    </div>
                    @if($item->warranty_days > 0)
                        <div class="sw-item-tag">Warranty: {{ $item->warranty_days }} Days</div>
                    @endif
                </td>
                <td class="text-right font-bold" style="width: 30%; vertical-align: top;">
                    {{ $team->currency_symbol ?? '৳' }}{{ number_format($item->subtotal, 2) }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="sw-line-dashed"></div>

    <!-- Totals Breakdown -->
    <table class="sw-tot-table">
        <tr>
            <td class="text-left">Subtotal:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
        <tr style="color: #dc2626;">
            <td class="text-left">Discount:</td>
            <td class="text-right font-bold">- {{ $team->currency_symbol ?? '৳' }}{{ number_format($order->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($order->tax_amount > 0)
        <tr>
            <td class="text-left">VAT / Tax:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->tax_amount, 2) }}</td>
        </tr>
        @endif
        @if($order->shipping_cost > 0)
        <tr>
            <td class="text-left">Shipping / Delivery:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->shipping_cost, 2) }}</td>
        </tr>
        @endif
    </table>

    <div class="sw-line-double"></div>

    <!-- Grand Total Banner -->
    <table class="sw-grand-banner">
        <tr>
            <td class="text-left font-black">TOTAL:</td>
            <td class="text-right font-black">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="sw-line-double"></div>

    <!-- Payment Status Summary -->
    <table class="sw-tot-table">
        <tr>
            <td class="text-left">Payment Status:</td>
            <td class="text-right font-bold">
                @if($order->payment_status === 'paid')
                    <span style="color: #15803d;">PAID</span>
                @elseif($order->payment_status === 'partial')
                    <span style="color: #b45309;">PARTIALLY PAID</span>
                @else
                    <span style="color: #dc2626;">DUE / UNPAID</span>
                @endif
            </td>
        </tr>
        @php
            $tendered = $order->tendered_amount !== null ? (float)$order->tendered_amount : (float)$order->paid_amount;
            $change = $order->change_amount !== null ? (float)$order->change_amount : max(0, $tendered - (float)$order->paid_amount);
            $due = max(0, (float)$order->total_amount - (float)$order->paid_amount);
        @endphp
        @if($tendered > 0)
        <tr>
            <td class="text-left">Cash Tendered:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($tendered, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left">Paid Amount:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 2) }}</td>
        </tr>
        @if($change > 0)
        <tr style="color: #15803d;">
            <td class="text-left font-bold">Change / Return:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($change, 2) }}</td>
        </tr>
        @endif
        @if($due > 0)
        <tr style="color: #dc2626;">
            <td class="text-left font-bold">Due Remaining:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($due, 2) }}</td>
        </tr>
        @endif
    </table>

    <div class="sw-line-dashed"></div>

    <!-- Footer & Return Policy -->
    <div class="sw-thanks">Thank You for Shopping with Us!</div>
    <div class="sw-foot-note">
        Total Items: {{ $order->items->sum('quantity') }}<br>
        Items with warranty coverage require this receipt for claims.
    </div>

</body>
</html>
