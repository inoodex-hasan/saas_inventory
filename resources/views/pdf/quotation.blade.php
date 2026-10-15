<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10pt;
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
            font-size: 18pt;
            font-weight: 800;
            color: #0f172a;
        }
        .quote-badge {
            display: inline-block;
            background-color: #4338ca;
            color: #ffffff;
            font-size: 12pt;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 6px 14px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .info-box {
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #1e1b4b;
            color: #ffffff;
            font-size: 8.5pt;
            text-transform: uppercase;
            padding: 7px 10px;
            font-weight: bold;
        }
        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5pt;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 9.5pt;
        }
        .grand-total {
            background-color: #e0e7ff;
            color: #312e81;
            font-size: 12pt;
            font-weight: 800;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-title">{{ $team->displayName() }}</div>
                @if($team->address ?? false)
                    <div style="font-size: 8.5pt; color: #64748b; margin-top: 2px;">{{ $team->address }}</div>
                @endif
                @if(($team->phone ?? false) || ($team->email ?? false))
                    <div style="font-size: 8.5pt; color: #64748b;">
                        {{ $team->phone ? 'Phone: ' . $team->phone : '' }} 
                        {{ $team->email ? ' | Email: ' . $team->email : '' }}
                    </div>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="quote-badge">FORMAL QUOTATION</div>
                <div style="font-size: 10pt; font-weight: bold; margin-top: 5px; color: #1e1b4b;">
                    {{ $quotation->quotation_number }}
                </div>
                <div style="font-size: 8.5pt; color: #64748b;">
                    Date: {{ $quotation->quotation_date->format('d M, Y') }}
                </div>
                @if($quotation->valid_until)
                    <div style="font-size: 8.5pt; color: #b91c1c; font-weight: bold;">
                        Valid Until: {{ $quotation->valid_until->format('d M, Y') }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Client Info -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 100%;">
                <div class="info-box">
                    <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Quotation Prepared For</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 2px;">
                        {{ $quotation->customer->name ?? 'Valued Client' }}
                    </div>
                    @if($quotation->customer && $quotation->customer->phone)
                        <div style="font-size: 9pt; color: #475569;">Contact: {{ $quotation->customer->phone }}</div>
                    @endif
                    @if($quotation->customer && $quotation->customer->address)
                        <div style="font-size: 9pt; color: #475569;">Address: {{ $quotation->customer->address }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">#</th>
                <th style="width: 52%; text-align: left;">Product Item & Specifications</th>
                <th style="width: 15%; text-align: right;">Unit Rate</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 15%; text-align: right;">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $idx => $item)
                <tr>
                    <td style="text-align: center; color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $item->product->name ?? 'Product' }}</div>
                        <div style="font-family: monospace; font-size: 8pt; color: #6366f1;">SKU: {{ $item->product->sku ?? '—' }}</div>
                    </td>
                    <td style="text-align: right; color: #475569;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($item->quantity) }}</td>
                    <td style="text-align: right; font-weight: bold; color: #0f172a;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Financial Totals & Terms -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                @if($quotation->terms)
                    <div class="info-box" style="margin-right: 15px;">
                        <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Terms & Conditions</div>
                        <div style="font-size: 8.5pt; color: #334155; margin-top: 3px; white-space: pre-line;">{{ $quotation->terms }}</div>
                    </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="totals-table" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <tr>
                        <td style="color: #64748b; font-weight: 600;">Subtotal:</td>
                        <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($quotation->subtotal, 2) }}</td>
                    </tr>
                    @if($quotation->discount_amount > 0)
                        <tr>
                            <td style="color: #64748b; font-weight: 600;">Discount:</td>
                            <td style="text-align: right; color: #dc2626;">-{{ $team->currency_symbol ?? '৳' }}{{ number_format($quotation->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($quotation->tax_amount > 0)
                        <tr>
                            <td style="color: #64748b; font-weight: 600;">Tax / VAT:</td>
                            <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($quotation->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>Total Quoted:</td>
                        <td style="text-align: right;">{{ $team->currency_symbol ?? '৳' }}{{ number_format($quotation->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <table style="width: 100%; margin-top: 50px;">
        <tr>
            <td style="width: 40%; text-align: center;">
                <div style="border-top: 1px solid #94a3b8; padding-top: 5px; font-size: 8.5pt; font-weight: bold; color: #475569;">
                    Client Acceptance (Sign & Date)
                </div>
            </td>
            <td style="width: 20%;"></td>
            <td style="width: 40%; text-align: center;">
                <div style="border-top: 1px solid #94a3b8; padding-top: 5px; font-size: 8.5pt; font-weight: bold; color: #475569;">
                    For {{ $team->displayName() }} (Authorized Signatory)
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by {{ $team->displayName() }} ERP on {{ now()->format('d M, Y H:i') }}.
    </div>

</body>
</html>
