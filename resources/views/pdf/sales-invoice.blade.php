<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        @page {
            @if($bgPath = $team->getInvoiceBgAbsolutePath())
                background-image: url('{{ $bgPath }}');
                background-image-resize: 6;
            @endif
            margin-top: {{ $team->invoice_bg_path ? ($team->invoice_margin_top ?? 42) : 14 }}mm;
            margin-bottom: {{ $team->invoice_bg_path ? ($team->invoice_margin_bottom ?? 30) : 14 }}mm;
            margin-left: 14mm;
            margin-right: 14mm;
            footer: invoiceFooter;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            width: 100%;
            margin-bottom: 18px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .customer-card {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .customer-card td {
            padding: 12px 16px;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 10px 12px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 9px 12px;
            font-size: 11px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .summary-card {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
        }

        .summary-card td {
            padding: 14px;
            vertical-align: top;
        }

        .in-words-card {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            margin-top: 16px;
            margin-bottom: 35px;
        }

        .in-words-card td {
            padding: 10px 14px;
            font-size: 11px;
            color: #334155;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            padding: 2.5px 8px;
            border-radius: 4px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .badge-paid {
            color: #059669;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
        }
        .badge-partial {
            color: #d97706;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }
        .badge-unpaid {
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

    @if(!$team->invoice_bg_path)
    <!-- Header Title & Order Meta -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">{{ $team->displayName() }}</h1>
                <div style="font-size: 10.5px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Point of Sale &middot; Retail &amp; Wholesale</div>
                <div style="font-size: 10.5px; color: #475569; line-height: 1.4;">
                    @if($team->formattedAddress())
                        {{ $team->formattedAddress() }}<br>
                    @endif
                    @if($team->phone || $team->email)
                        {{ collect([$team->phone, $team->email])->filter()->join(' · ') }}<br>
                    @endif
                    @if($team->tax_id)
                        Tax / VAT Registration: <strong style="color: #4f46e5;">{{ $team->tax_id }}</strong><br>
                    @endif
                    Warehouse Hub: <strong>{{ $order->warehouse->name ?? 'Main Warehouse' }}</strong>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: 1px;">INVOICE</h1>
                <div style="font-size: 14px; font-weight: 700; color: #4f46e5;">Invoice No: #{{ $order->order_number }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                    Invoice Date: {{ $order->order_date ? $order->order_date->format('d M Y') : date('d M Y') }}
                </div>
                <div style="margin-top: 4px;">
                    @if($order->payment_status === 'paid')
                        <span class="badge badge-paid">&#10003; PAID</span>
                    @elseif($order->payment_status === 'partially_paid')
                        <span class="badge badge-partial">PARTIALLY PAID</span>
                    @else
                        <span class="badge badge-unpaid">UNPAID DUE</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>
    @else
    <!-- Pre-Printed Letterhead Mode -->
    <table class="header-table" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div style="font-size: 10px; color: #64748b; font-weight: 600;">
                    Hub: {{ $order->warehouse->name ?? 'Primary Center' }}
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">INVOICE</h1>
                <div style="font-size: 13px; font-weight: 700; color: #4f46e5;">#{{ $order->order_number }}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                    Date: {{ $order->order_date ? $order->order_date->format('d M Y') : date('d M Y') }}
                </div>
                <div style="margin-top: 3px;">
                    @if($order->payment_status === 'paid')
                        <span class="badge badge-paid">PAID</span>
                    @elseif($order->payment_status === 'partially_paid')
                        <span class="badge badge-partial">PARTIAL</span>
                    @else
                        <span class="badge badge-unpaid">UNPAID</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>
    @endif

    <!-- Customer Info Card (Rounded 3 Columns) -->
    <table class="customer-card">
        <tr>
            <td style="width: 33.33%;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 4px;">CUSTOMER / CLIENT</div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">{{ $order->customer->name ?? 'Walk-in Customer' }}</div>
                @if($order->customer?->company_name)
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">{{ $order->customer->company_name }}</div>
                @endif
            </td>
            <td style="width: 33.33%; border-left: 1px solid #cbd5e1;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 4px;">PHONE NUMBER</div>
                <div style="font-size: 13px; font-weight: 600; color: #0f172a; font-family: monospace;">{{ $order->customer->phone ?? '—' }}</div>
                @if($order->customer?->email)
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">{{ $order->customer->email }}</div>
                @endif
            </td>
            <td style="width: 33.33%; border-left: 1px solid #cbd5e1;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 4px;">ADDRESS / LOCATION</div>
                <div style="font-size: 11.5px; color: #334155; line-height: 1.3;">
                    {{ $order->customer->address ?: ($order->shipping_address ?: 'Counter Sale Delivery') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 48%; text-align: left;">Item Description &amp; Model</th>
                <th style="width: 12%; text-align: center;">Qty</th>
                <th style="width: 17%; text-align: right;">Unit Price</th>
                <th style="width: 17%; text-align: right;">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
                <tr style="background-color: {{ $idx % 2 === 1 ? '#f8fafc' : '#ffffff' }};">
                    <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11.5px;">{{ $item->product->name ?? 'Product Item' }}</strong>
                        @if($item->product?->sku)
                            <div style="font-size: 9.5px; color: #64748b; font-family: monospace; margin-top: 1px;">
                                SKU / Code: {{ $item->product->sku }}
                            </div>
                        @endif
                        @if($item->warranty_days > 0)
                            <div style="font-size: 9px; color: #0284c7; font-weight: 600; margin-top: 1px;">
                                Warranty Coverage: {{ $item->warranty_days }} Days
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #0f172a;">{{ number_format($item->quantity) }}</td>
                    <td style="text-align: right; color: #334155;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary & Terms Grid -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 12px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <table class="summary-card">
                    <tr>
                        <td>
                            <h3 style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Terms &amp; Conditions</h3>
                            <p style="font-size: 10.5px; color: #475569; line-height: 1.5; margin-bottom: 4px;">
                                &bull; Products can be returned within 7 days in their original, unopened condition.
                            </p>
                            <p style="font-size: 10.5px; color: #475569; line-height: 1.5; margin-bottom: 6px;">
                                &bull; Warranty claims require this original valid invoice for processing.
                            </p>
                            <div style="font-size: 10.5px; color: #475569;">
                                Contact support at <strong style="color: #4f46e5;">{{ $team->phone ?? 'our help desk' }}</strong> for any inquiries.
                            </div>
                            @if($order->notes)
                                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 10.5px; color: #334155;">
                                    <strong style="color: #0f172a;">Note:</strong> {{ $order->notes }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                <table class="summary-card">
                    <tr>
                        <td>
                            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                                <tr>
                                    <td style="padding: 3.5px 0; color: #475569;">Sub Total:</td>
                                    <td style="padding: 3.5px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                @if($order->tax_amount > 0)
                                <tr>
                                    <td style="padding: 3.5px 0; color: #475569;">VAT / Tax:</td>
                                    <td style="padding: 3.5px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->tax_amount, 2) }}</td>
                                </tr>
                                @endif
                                @if($order->shipping_cost > 0)
                                <tr>
                                    <td style="padding: 3.5px 0; color: #475569;">Delivery Charge:</td>
                                    <td style="padding: 3.5px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->shipping_cost, 2) }}</td>
                                </tr>
                                @endif
                                @if($order->discount_amount > 0)
                                <tr>
                                    <td style="padding: 3.5px 0; color: #475569;">Discount:</td>
                                    <td style="padding: 3.5px 0; text-align: right; font-weight: 600; color: #dc2626;">- {{ $team->currency_symbol ?? '৳' }}{{ number_format($order->discount_amount, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 6px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-size: 13px; font-weight: 800; color: #4f46e5;">Grand Total:</td>
                                    <td style="padding: 6px 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: right; font-size: 13px; font-weight: 800; color: #4f46e5;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3.5px 0; color: #475569;">Received / Paid:</td>
                                    <td style="padding: 3.5px 0; text-align: right; font-weight: 600; color: #059669;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3.5px 0; font-weight: 800; color: #dc2626;">Total Due:</td>
                                    <td style="padding: 3.5px 0; text-align: right; font-weight: 800; color: {{ ($order->total_amount - $order->paid_amount) > 0 ? '#dc2626' : '#16a34a' }};">
                                        {{ $team->currency_symbol ?? '৳' }}{{ number_format(max(0, $order->total_amount - $order->paid_amount), 2) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- In Words Card -->
    <table class="in-words-card">
        <tr>
            <td>
                <strong style="color: #4f46e5; margin-right: 6px;">Amount In Words:</strong>
                {{ numberToWords($order->total_amount) }} Taka Only
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <table align="center" style="margin: 0 auto; width: 170px;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; padding-top: 6px; font-size: 10.5px; font-weight: 600; color: #475569; text-align: center;">
                            Customer Signature
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <table align="center" style="margin: 0 auto; width: 170px;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; padding-top: 6px; font-size: 10.5px; font-weight: 600; color: #4f46e5; text-align: center;">
                            Authorized Signature
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer fixed at bottom -->
    <htmlpagefooter name="invoiceFooter">
        <table style="width: 100%; border-top: 1px solid #e2e8f0; padding-top: 6px; font-size: 9px; color: #94a3b8;">
            <tr>
                <td style="text-align: left;">
                    @if($order->creator)
                        Sold By: <strong style="color: #334155;">{{ $order->creator->name }}</strong> &middot;
                    @endif
                    Printed on {{ date('d M Y, h:i A') }}
                </td>
                <td style="text-align: right;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

</body>
</html>
