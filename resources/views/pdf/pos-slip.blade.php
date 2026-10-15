<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Receipt - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 4mm 3mm;
            size: 80mm auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #111827;
            font-size: 8.5pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
            width: 74mm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #4b5563;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .meta-line {
            font-size: 7.5pt;
            color: #374151;
        }
        
        .divider {
            border-top: 1px dashed #4b5563;
            margin: 5px 0;
        }
        .double-divider {
            border-top: 2px solid #111827;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            font-size: 7.5pt;
            font-weight: bold;
            border-bottom: 1px solid #111827;
            padding: 3px 0;
        }
        .items-table td {
            font-size: 8pt;
            padding: 3px 0;
            vertical-align: top;
        }
        
        .totals-table td {
            font-size: 8pt;
            padding: 2px 0;
        }
        .grand-total td {
            font-size: 10pt;
            font-weight: bold;
            border-top: 1px dashed #111827;
            border-bottom: 1px dashed #111827;
            padding: 4px 0;
        }

        .footer {
            text-align: center;
            font-size: 7.5pt;
            color: #4b5563;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #4b5563;
        }
        .serial-tag {
            font-size: 6.5pt;
            color: #4b5563;
            display: block;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name uppercase">{{ $team->displayName() }}</div>
        @if($team->phone ?? false)
            <div class="meta-line">Phone: {{ $team->phone }}</div>
        @endif
        @if($team->email ?? false)
            <div class="meta-line">Email: {{ $team->email }}</div>
        @endif
        @if($team->address ?? false)
            <div class="meta-line">{{ $team->address }}</div>
        @endif
        <div class="meta-line font-bold" style="margin-top: 3px;">*** CASH RECEIPT / POS SLIP ***</div>
    </div>

    <!-- Order & Customer Info -->
    <table style="margin-bottom: 4px;">
        <tr>
            <td class="text-left font-bold" style="font-size: 8pt;">Inv: {{ $order->order_number }}</td>
            <td class="text-right" style="font-size: 7.5pt;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="text-left" style="font-size: 7.5pt;">Hub: {{ $order->warehouse->name ?? 'Main Store' }}</td>
            <td class="text-right" style="font-size: 7.5pt;">Status: {{ strtoupper($order->status) }}</td>
        </tr>
        @if($order->customer)
            <tr>
                <td colspan="2" class="text-left" style="font-size: 7.5pt; padding-top: 2px;">
                    Cust: <strong>{{ $order->customer->name }}</strong>
                    @if($order->customer->phone)
                        ({{ $order->customer->phone }})
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- Items Listing -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 45%;">ITEM</th>
                <th class="text-center" style="width: 15%;">QTY</th>
                <th class="text-right" style="width: 20%;">RATE</th>
                <th class="text-right" style="width: 20%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td class="text-left">
                        <span class="font-bold">{{ $item->product->name ?? 'Product' }}</span>
                        @if($item->warranty_days > 0)
                            <span class="serial-tag">Warranty: {{ $item->warranty_days }} Days</span>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals Calculation -->
    <table class="totals-table">
        <tr>
            <td class="text-left">Subtotal:</td>
            <td class="text-right">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
            <tr>
                <td class="text-left">Discount:</td>
                <td class="text-right">-{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($order->tax_amount > 0)
            <tr>
                <td class="text-left">Tax / VAT:</td>
                <td class="text-right">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($order->shipping_cost > 0)
            <tr>
                <td class="text-left">Delivery:</td>
                <td class="text-right">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->shipping_cost, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="text-left font-bold">NET PAYABLE:</td>
            <td class="text-right font-bold">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="text-left font-bold" style="padding-top: 3px;">Paid Amount:</td>
            <td class="text-right font-bold" style="padding-top: 3px;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="text-left font-bold">Due Balance:</td>
            <td class="text-right font-bold">
                {{ $team->currency_symbol ?? '৳' }}{{ number_format(max(0, $order->total_amount - $order->paid_amount), 2) }}
            </td>
        </tr>
    </table>

    <div class="double-divider"></div>

    <div class="footer">
        <div>Total Items: {{ $order->items->sum('quantity') }}</div>
        <div style="margin-top: 3px;">*** THANK YOU FOR YOUR BUSINESS ***</div>
        <div style="font-size: 6.5pt; color: #6b7280; margin-top: 2px;">Items with warranty coverage require this receipt for claims.</div>
    </div>

</body>
</html>
