<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 11pt;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-title {
            font-size: 20pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .invoice-title {
            font-size: 22pt;
            font-weight: 800;
            color: #4f46e5;
            text-align: right;
            text-transform: uppercase;
        }
        .info-box {
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .meta-table td {
            padding: 3px 0;
            font-size: 10pt;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
        }
        .items-table {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10pt;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .total-box {
            width: 45%;
            float: right;
            margin-top: 10px;
        }
        .total-table td {
            padding: 5px 8px;
            font-size: 10pt;
        }
        .grand-total {
            background-color: #e0e7ff;
            color: #3730a3;
            font-size: 13pt;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-partial {
            background-color: #fef3c7;
            color: #92400e;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-title">{{ $team->name ?? 'Inoodex SaaS' }}</div>
                <div style="font-size: 9pt; color: #64748b; margin-top: 2px;">
                    Cloud Inventory & Supply Chain Management<br>
                    Warehouse: {{ $order->warehouse->name ?? 'Primary Hub' }} ({{ $order->warehouse->code ?? 'WH' }})
                </div>
            </td>
            <td style="width: 45%;">
                <div class="invoice-title">INVOICE</div>
                <table style="width: 100%; text-align: right; margin-top: 4px;">
                    <tr>
                        <td class="meta-label">Invoice Ref:</td>
                        <td style="font-family: monospace; font-weight: bold; color: #0f172a;">{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Date:</td>
                        <td>{{ $order->order_date ? $order->order_date->format('M d, Y') : date('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Payment Status:</td>
                        <td>
                            @if($order->payment_status === 'paid')
                                <span class="badge badge-paid">PAID</span>
                            @elseif($order->payment_status === 'partial')
                                <span class="badge badge-partial">PARTIAL</span>
                            @else
                                <span class="badge badge-unpaid">UNPAID</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px;"></div>

    <!-- Bill To / Ship To -->
    <table>
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div class="info-box">
                    <div style="font-size: 9pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Customer / Billed To</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a;">{{ $order->customer->name ?? 'Walk-in Customer' }}</div>
                    @if($order->customer?->email)
                        <div style="font-size: 9pt; color: #475569;">Email: {{ $order->customer->email }}</div>
                    @endif
                    @if($order->customer?->phone)
                        <div style="font-size: 9pt; color: #475569;">Phone: {{ $order->customer->phone }}</div>
                    @endif
                    @if($order->customer?->tax_number)
                        <div style="font-size: 9pt; color: #475569;">Tax/VAT: {{ $order->customer->tax_number }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div class="info-box">
                    <div style="font-size: 9pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Shipping / Fulfillment Hub</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a;">{{ $order->warehouse->name ?? 'Central Distribution' }}</div>
                    <div style="font-size: 9pt; color: #475569;">
                        {{ $order->shipping_address ?: ($order->warehouse->address_line1 ?? 'Standard Dispatch') }}
                    </div>
                    <div style="font-size: 9pt; color: #475569; margin-top: 3px;">
                        Fulfillment: <strong>{{ ucfirst($order->status) }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">#</th>
                <th style="width: 47%; text-align: left;">Product Item & SKU</th>
                <th style="width: 15%; text-align: right;">Unit Price</th>
                <th style="width: 12%; text-align: center;">Qty</th>
                <th style="width: 18%; text-align: right;">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
                <tr>
                    <td style="text-align: center; color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $item->product->name ?? 'Product' }}</div>
                        <div style="font-family: monospace; font-size: 8pt; color: #6366f1;">SKU: {{ $item->product->sku ?? '—' }}</div>
                    </td>
                    <td style="text-align: right; color: #475569;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($item->quantity) }}</td>
                    <td style="text-align: right; font-weight: bold; color: #0f172a;">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals and Payment Summary -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                @if($order->notes)
                    <div class="info-box" style="margin-right: 15px;">
                        <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Notes & Instructions</div>
                        <div style="font-size: 9pt; color: #334155; margin-top: 2px;">{{ $order->notes }}</div>
                    </div>
                @endif
                <div style="margin-top: 15px; font-size: 8pt; color: #94a3b8;">
                    <strong>Payment Terms:</strong> Thank you for your business. Please include invoice ref <strong>{{ $order->order_number }}</strong> with your remittance.
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="total-table" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <tr>
                        <td class="meta-label">Subtotal:</td>
                        <td style="text-align: right; font-weight: 600;">${{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->tax_amount > 0)
                        <tr>
                            <td class="meta-label">Tax / VAT:</td>
                            <td style="text-align: right; font-weight: 600;">${{ number_format($order->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($order->shipping_cost > 0)
                        <tr>
                            <td class="meta-label">Shipping:</td>
                            <td style="text-align: right; font-weight: 600;">${{ number_format($order->shipping_cost, 2) }}</td>
                        </tr>
                    @endif
                    @if($order->discount_amount > 0)
                        <tr>
                            <td class="meta-label">Discount:</td>
                            <td style="text-align: right; color: #dc2626;">-${{ number_format($order->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>Total Due:</td>
                        <td style="text-align: right;">${{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Amount Paid:</td>
                        <td style="text-align: right; color: #16a34a; font-weight: bold;">${{ number_format($order->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Balance Remaining:</td>
                        <td style="text-align: right; font-weight: bold; color: {{ ($order->total_amount - $order->paid_amount) > 0 ? '#dc2626' : '#16a34a' }};">
                            ${{ number_format(max(0, $order->total_amount - $order->paid_amount), 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by {{ $team->name ?? 'Inoodex SaaS' }} on {{ date('Y-m-d H:i') }} &bull; Page {PAGENO} of {nbpg}
    </div>

</body>
</html>
