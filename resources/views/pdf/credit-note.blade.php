<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Note - {{ $return->return_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10pt;
            line-height: 1.4;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 16pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 18pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-align: right;
            text-transform: uppercase;
        }
        .meta-label {
            color: #64748b;
            font-size: 9pt;
            font-weight: 600;
            text-align: right;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5pt;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 10pt;
        }
        .grand-total {
            background-color: #fef2f2;
            color: #dc2626;
            font-size: 12pt;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }
        .signatures-table {
            margin-top: 45px;
            width: 100%;
        }
        .signature-box {
            width: 33.3%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            width: 80%;
            margin: 0 auto 5px auto;
            border-top: 1px solid #64748b;
        }
        .signature-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
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
                <div class="company-name">{{ $team->displayName() }}</div>
                <div style="font-size: 8.5pt; color: #64748b; margin-top: 3px;">
                    @if($team->address_line1) {{ $team->address_line1 }}<br> @endif
                    @if($team->phone || $team->email)
                        {{ collect([$team->phone, $team->email])->filter()->join(' | ') }}
                    @endif
                </div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="doc-title">CREDIT NOTE</div>
                <div style="font-family: monospace; font-size: 11pt; font-weight: bold; color: #dc2626; margin-top: 2px;">
                    {{ $return->return_number }}
                </div>
                <div style="font-size: 8.5pt; color: #64748b; margin-top: 2px;">
                    Date: {{ $return->created_at->format('d M, Y') }} | Status: <strong style="text-transform: uppercase; color: #0f172a;">{{ $return->status }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px;"></div>

    <!-- Customer & Invoice Info Boxes -->
    <table>
        <tr>
            <td style="width: 50%; padding-right: 8px; vertical-align: top;">
                <div class="info-box">
                    <div style="font-size: 8.5pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Customer / Billed To</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a;">
                        {{ $return->customer->name ?? $return->salesOrder->customer_name ?? 'Counter Customer' }}
                    </div>
                    @if($return->customer?->phone || $return->salesOrder?->customer_phone)
                        <div style="font-size: 8.5pt; color: #475569;">Phone: {{ $return->customer->phone ?? $return->salesOrder->customer_phone }}</div>
                    @endif
                    @if($return->customer?->address || $return->salesOrder?->customer_address)
                        <div style="font-size: 8.5pt; color: #475569;">Address: {{ $return->customer->address ?? $return->salesOrder->customer_address }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px; vertical-align: top;">
                <div class="info-box">
                    <div style="font-size: 8.5pt; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Return & Settlement Details</div>
                    <div style="font-size: 9pt; color: #475569;">Original Invoice #: <strong style="font-family: monospace; color: #0f172a;">{{ $return->salesOrder->order_number ?? '—' }}</strong></div>
                    <div style="font-size: 9pt; color: #475569; margin-top: 2px;">Return Destination: <strong>{{ $return->warehouse->name ?? 'Main Hub' }}</strong></div>
                    <div style="font-size: 9pt; color: #475569; margin-top: 2px;">Settlement Mode: <strong style="text-transform: uppercase; color: #2563eb;">{{ str_replace('_', ' ', $return->refund_type) }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    @if(!empty($return->reason))
        <div style="margin-top: 10px; background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 6px 10px; font-size: 9pt; color: #92400e;">
            <strong>Reason for Return:</strong> {{ $return->reason }}
        </div>
    @endif

    <!-- Returned Line Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 45%; text-align: left;">Product Description</th>
                <th style="width: 15%; text-align: center;">Condition</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 12%; text-align: right;">Rate (৳)</th>
                <th style="width: 13%; text-align: right;">Credit (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->items as $index => $item)
                <tr>
                    <td style="text-align: center; font-family: monospace;">{{ $index + 1 }}</td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $item->product->name ?? 'Catalog Item' }}</div>
                        <div style="font-size: 8pt; color: #64748b; font-family: monospace;">
                            SKU: {{ $item->product->sku ?? '—' }}
                            @if($item->serial_number)
                                | S/N: <strong style="color: #2563eb;">{{ $item->serial_number }}</strong>
                            @endif
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span style="font-size: 8.5pt; font-weight: bold; text-transform: uppercase; color: {{ $item->condition === 'sellable' ? '#166534' : '#dc2626' }};">
                            {{ $item->condition }}
                        </span>
                    </td>
                    <td style="text-align: center; font-weight: bold; font-family: monospace;">{{ number_format($item->quantity, 0) }}</td>
                    <td style="text-align: right; font-family: monospace;">৳{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold;">৳{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Table -->
    <table class="totals-table">
        <tr class="grand-total">
            <td>Total Refund / Credit:</td>
            <td style="text-align: right; font-family: monospace;">৳{{ number_format($return->refund_amount, 2) }}</td>
        </tr>
    </table>

    @if(!empty($return->notes))
        <div style="margin-top: 10px; font-size: 8.5pt; color: #64748b;">
            <strong>Inspection Notes:</strong> {{ $return->notes }}
        </div>
    @endif

    <!-- Authorized Signatures -->
    <table class="signatures-table">
        <tr>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Customer Signature</div>
            </td>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Receiving Officer</div>
            </td>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Approval</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Official Credit Note Voucher. Powered by {{ config('app.name', 'SaaS Inventory') }}.
    </div>

</body>
</html>
