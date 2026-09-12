<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $order->order_number }}</title>
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
        }
        .po-title {
            font-size: 22pt;
            font-weight: 800;
            color: #0284c7;
            text-align: right;
            text-transform: uppercase;
        }
        .info-box {
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
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
            background-color: #0369a1;
            color: #ffffff;
            font-size: 9pt;
            text-transform: uppercase;
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
            padding: 6px 8px;
            font-size: 10pt;
        }
        .grand-total {
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 13pt;
            font-weight: bold;
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
                    Procurement & Supply Chain Operations<br>
                    Destination Hub: {{ $order->warehouse->name ?? 'Main Hub' }} ({{ $order->warehouse->code ?? 'WH' }})
                </div>
            </td>
            <td style="width: 45%;">
                <div class="po-title">PURCHASE ORDER</div>
                <table style="width: 100%; text-align: right; margin-top: 4px;">
                    <tr>
                        <td class="meta-label">PO Ref:</td>
                        <td style="font-family: monospace; font-weight: bold; color: #0f172a;">{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Order Date:</td>
                        <td>{{ $order->order_date ? $order->order_date->format('M d, Y') : date('M d, Y') }}</td>
                    </tr>
                    @if($order->expected_date)
                        <tr>
                            <td class="meta-label">Expected Delivery:</td>
                            <td>{{ $order->expected_date->format('M d, Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="meta-label">Status:</td>
                        <td style="font-weight: bold; text-transform: uppercase; color: #0369a1;">{{ str_replace('_', ' ', $order->status) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px;"></div>

    <!-- Vendor / Delivery Hub -->
    <table>
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div class="info-box">
                    <div style="font-size: 9pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Vendor / Supplier</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a;">{{ $order->supplier->name ?? 'Supplier' }}</div>
                    @if($order->supplier?->contact_name)
                        <div style="font-size: 9pt; color: #475569;">Attn: {{ $order->supplier->contact_name }}</div>
                    @endif
                    @if($order->supplier?->email)
                        <div style="font-size: 9pt; color: #475569;">Email: {{ $order->supplier->email }}</div>
                    @endif
                    @if($order->supplier?->phone)
                        <div style="font-size: 9pt; color: #475569;">Phone: {{ $order->supplier->phone }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div class="info-box">
                    <div style="font-size: 9pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Ship To / Receiving Hub</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a;">{{ $order->warehouse->name ?? 'Central Warehouse' }}</div>
                    <div style="font-size: 9pt; color: #475569;">
                        {{ $order->warehouse->address_line1 ?? 'Primary Facility' }}<br>
                        {{ $order->warehouse->city ? $order->warehouse->city . ', ' . $order->warehouse->state . ' ' . $order->warehouse->postal_code : '' }}
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
                <th style="width: 47%;">Product Item & SKU</th>
                <th style="width: 15%; text-align: right;">Unit Cost</th>
                <th style="width: 12%; text-align: center;">Qty</th>
                <th style="width: 18%; text-align: right;">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
                <tr>
                    <td style="text-align: center; color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $item->product->name ?? 'Product' }}</div>
                        <div style="font-family: monospace; font-size: 8pt; color: #0284c7;">SKU: {{ $item->product->sku ?? '—' }}</div>
                    </td>
                    <td style="text-align: right; color: #475569;">${{ number_format($item->unit_cost, 2) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($item->quantity) }}</td>
                    <td style="text-align: right; font-weight: bold; color: #0f172a;">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals and Notes -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                @if($order->notes)
                    <div class="info-box" style="margin-right: 15px;">
                        <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Instructions & Terms</div>
                        <div style="font-size: 9pt; color: #334155; margin-top: 2px;">{{ $order->notes }}</div>
                    </div>
                @endif
                <div style="margin-top: 15px; font-size: 8pt; color: #94a3b8;">
                    Please reference PO# <strong>{{ $order->order_number }}</strong> on all shipments and invoices.
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
                            <td class="meta-label">Tax:</td>
                            <td style="text-align: right; font-weight: 600;">${{ number_format($order->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($order->shipping_cost > 0)
                        <tr>
                            <td class="meta-label">Shipping / Freight:</td>
                            <td style="text-align: right; font-weight: 600;">${{ number_format($order->shipping_cost, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>Total PO Value:</td>
                        <td style="text-align: right;">${{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $team->name ?? 'Inoodex Inventory' }} &bull; Purchase Order {{ $order->order_number }} &bull; Page {PAGENO} of {nbpg}
    </div>

</body>
</html>
