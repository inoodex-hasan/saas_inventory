<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Packing Slip - {{ $order->order_number }}</title>
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
            vertical-align: middle;
        }
        .title {
            font-size: 18pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .info-box {
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #334155;
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
            vertical-align: middle;
        }
        .check-box {
            width: 14px;
            height: 14px;
            border: 2px solid #94a3b8;
            margin: 0 auto;
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

    <!-- Header Section with Barcode -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div style="font-size: 11pt; font-weight: bold; color: #4f46e5; text-transform: uppercase;">{{ $team->displayName() }}</div>
                <div class="title">PACKING & DISPATCH SLIP</div>
                <div style="font-size: 9pt; color: #64748b; margin-top: 2px;">
                    Origin Hub: <strong>{{ $order->warehouse->name ?? 'Default Hub' }} ({{ $order->warehouse->code ?? 'WH' }})</strong>
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <barcode code="{{ $order->order_number }}" type="C128A" height="0.8" text="1" />
                <div style="font-size: 9pt; color: #64748b; margin-top: 2px;">Order Date: {{ $order->order_date ? $order->order_date->format('M d, Y') : date('M d, Y') }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px;"></div>

    <!-- Customer & Destination Details -->
    <table>
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div class="info-box">
                    <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Customer Details</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a;">{{ $order->customer->name ?? 'Walk-in Customer' }}</div>
                    @if($order->customer?->phone)
                        <div style="font-size: 9pt; color: #475569;">Contact: {{ $order->customer->phone }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div class="info-box">
                    <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Delivery Address</div>
                    <div style="font-size: 10pt; color: #0f172a; font-weight: 500;">
                        {{ $order->shipping_address ?: 'Direct Warehouse Pickup' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Picking & Packing Line Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">Pick</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 15%; text-align: center;">Barcode</th>
                <th style="width: 17%; text-align: center;">Qty to Pack</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td style="text-align: center;">
                        <div class="check-box"></div>
                    </td>
                    <td style="font-family: monospace; font-weight: bold; color: #4f46e5;">
                        {{ $item->product->sku ?? '—' }}
                    </td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $item->product->name ?? 'Product' }}</div>
                        @if($item->product?->barcode)
                            <div style="font-size: 8pt; color: #64748b;">UPC/EAN: {{ $item->product->barcode }}</div>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($item->product?->sku)
                            <barcode code="{{ $item->product->sku }}" type="C128A" height="0.5" />
                        @endif
                    </td>
                    <td style="text-align: center; font-size: 12pt; font-weight: bold; color: #0f172a;">
                        {{ number_format($item->quantity) }} <span style="font-size: 9pt; font-weight: normal; color: #64748b;">{{ $item->product->unit ?? 'pcs' }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Warehouse Sign-off Block -->
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 33%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 6px;">
                <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Picked By:</div>
                <div style="margin-top: 25px; border-bottom: 1px solid #94a3b8;"></div>
            </td>
            <td style="width: 33%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 6px; margin-left: 8px;">
                <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Packed / Verified By:</div>
                <div style="margin-top: 25px; border-bottom: 1px solid #94a3b8;"></div>
            </td>
            <td style="width: 33%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 6px; margin-left: 8px;">
                <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Carrier / Dispatch Sign:</div>
                <div style="margin-top: 25px; border-bottom: 1px solid #94a3b8;"></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $team->displayName() }} &bull; Packing Slip for {{ $order->order_number }} &bull; Page {PAGENO} of {nbpg}
    </div>

</body>
</html>
