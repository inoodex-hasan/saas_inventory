<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Money Receipt - {{ $order->order_number }}</title>
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
        }
        .receipt-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 6px 14px;
            border-radius: 4px;
            text-transform: uppercase;
            text-align: right;
        }
        .box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px;
            background-color: #f8fafc;
            margin-top: 15px;
        }
        .line-item {
            margin-bottom: 10px;
            font-size: 10.5pt;
        }
        .dotted-line {
            border-bottom: 1px dotted #64748b;
            display: inline-block;
            padding-bottom: 1px;
            font-weight: bold;
            color: #0f172a;
        }
        .amount-box {
            background-color: #ecfdf5;
            border: 1.5px solid #059669;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 15px;
        }
        .amount-title {
            font-size: 9pt;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
        }
        .amount-val {
            font-size: 16pt;
            font-weight: 800;
            color: #065f46;
            font-family: monospace;
        }
        .signatures-table {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
            text-align: center;
            font-size: 9pt;
            color: #475569;
            font-weight: 600;
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
            <td style="width: 60%;">
                <div class="company-name">{{ $team->displayName() }}</div>
                @if($team->address ?? false)
                    <div style="font-size: 8.5pt; color: #64748b;">{{ $team->address }}</div>
                @endif
                @if(($team->phone ?? false) || ($team->email ?? false))
                    <div style="font-size: 8.5pt; color: #64748b;">
                        {{ $team->phone ? 'Phone: ' . $team->phone : '' }} 
                        {{ $team->email ? ' | Email: ' . $team->email : '' }}
                    </div>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="receipt-badge">MONEY RECEIPT</div>
                <div style="font-size: 9pt; font-weight: bold; margin-top: 6px; color: #334155;">
                    MR-{{ $order->order_number }}
                </div>
                <div style="font-size: 8.5pt; color: #64748b;">
                    Date: {{ now()->format('d M, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Receipt Details Box -->
    <div class="box">
        <div class="line-item">
            Received with thanks from: 
            <span class="dotted-line" style="min-width: 320px;">
                {{ $order->customer->name ?? 'Walk-in Customer' }}
                @if($order->customer->phone ?? false)
                    ({{ $order->customer->phone }})
                @endif
            </span>
        </div>

        @if($order->customer->address ?? false)
            <div class="line-item">
                Customer Address: 
                <span class="dotted-line" style="min-width: 380px;">
                    {{ $order->customer->address }}
                </span>
            </div>
        @endif

        <div class="line-item">
            Against Invoice Reference: 
            <span class="dotted-line" style="min-width: 180px;">
                {{ $order->order_number }}
            </span>
            &nbsp;&nbsp;&nbsp;&nbsp;
            Payment Status: 
            <span class="dotted-line" style="min-width: 100px; text-transform: uppercase;">
                {{ str_replace('_', ' ', $order->payment_status) }}
            </span>
        </div>

        <div class="line-item">
            Warehouse / Dispatch Hub: 
            <span class="dotted-line" style="min-width: 250px;">
                {{ $order->warehouse->name ?? 'Main Hub' }}
            </span>
        </div>
    </div>

    <!-- Financial Breakdown Cards -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 33%; padding-right: 8px;">
                <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; background-color: #f8fafc;">
                    <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Invoice Grand Total</div>
                    <div style="font-size: 13pt; font-weight: bold; color: #0f172a; font-family: monospace;">
                        {{ $team->currency_symbol ?? '৳' }}{{ number_format($order->total_amount, 2) }}
                    </div>
                </div>
            </td>
            <td style="width: 34%; padding: 0 4px;">
                <div style="border: 1.5px solid #059669; border-radius: 6px; padding: 10px; background-color: #ecfdf5;">
                    <div style="font-size: 8pt; color: #047857; font-weight: bold; text-transform: uppercase;">Amount Paid / Cleared</div>
                    <div style="font-size: 14pt; font-weight: 800; color: #065f46; font-family: monospace;">
                        {{ $team->currency_symbol ?? '৳' }}{{ number_format($order->paid_amount, 2) }}
                    </div>
                </div>
            </td>
            <td style="width: 33%; padding-left: 8px;">
                <div style="border: 1px solid {{ ($order->total_amount - $order->paid_amount) > 0 ? '#fca5a5' : '#cbd5e1' }}; border-radius: 6px; padding: 10px; background-color: {{ ($order->total_amount - $order->paid_amount) > 0 ? '#fef2f2' : '#f8fafc' }};">
                    <div style="font-size: 8pt; color: {{ ($order->total_amount - $order->paid_amount) > 0 ? '#b91c1c' : '#64748b' }}; font-weight: bold; text-transform: uppercase;">Remaining Due Balance</div>
                    <div style="font-size: 13pt; font-weight: bold; color: {{ ($order->total_amount - $order->paid_amount) > 0 ? '#b91c1c' : '#0f172a' }}; font-family: monospace;">
                        {{ $team->currency_symbol ?? '৳' }}{{ number_format(max(0, $order->total_amount - $order->paid_amount), 2) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <td style="width: 40%; text-align: center;">
                <div class="signature-line">Customer Signature</div>
            </td>
            <td style="width: 20%;"></td>
            <td style="width: 40%; text-align: center;">
                <div class="signature-line">Authorized Seal & Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Official Receipt generated by {{ $team->displayName() }} ERP on {{ now()->format('d M, Y H:i') }}. System-authenticated document.
    </div>

</body>
</html>
