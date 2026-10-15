<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit and Loss Statement</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9.5pt;
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
            font-size: 16pt;
            font-weight: 900;
            color: #059669;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-header td {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 8.5pt;
            text-transform: uppercase;
            padding: 6px 10px;
        }
        .subtotal-row td {
            background-color: #f8fafc;
            font-weight: 800;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 10px;
        }
        .line-item td {
            padding: 5px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .net-income-box {
            background-color: #ecfdf5;
            border: 2px solid #10b981;
            font-weight: 900;
            font-size: 11pt;
            padding: 8px 12px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $team->displayName() }}</div>
                <div style="font-size: 8.5pt; color: #475569; margin-top: 3px;">
                    @if($team->address_line1){{ $team->address_line1 }}, @endif
                    @if($team->city){{ $team->city }}, @endif
                    @if($team->country){{ $team->country }}@endif
                </div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">PROFIT & LOSS STATEMENT</div>
                <div style="text-align: right; font-size: 8.5pt; color: #64748b; margin-top: 4px;">
                    For Period: <strong>{{ \Carbon\Carbon::parse($report['start_date'])->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($report['end_date'])->format('d M Y') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 15px;">
        {{-- Revenue Section --}}
        <tr class="section-header">
            <td style="width: 75%;">1. OPERATING REVENUE</td>
            <td style="width: 25%; text-align: right;">Amount ({{ $team->currency_symbol ?? '৳' }})</td>
        </tr>
        @forelse($report['revenue_rows'] as $row)
        <tr class="line-item">
            <td style="padding-left: 20px;">{{ $row['account_code'] }} - {{ $row['name'] }}</td>
            <td style="text-align: right; font-weight: 600;">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @empty
        <tr class="line-item">
            <td style="padding-left: 20px; color: #94a3b8; font-style: italic;">No revenue recorded</td>
            <td style="text-align: right; color: #94a3b8;">0.00</td>
        </tr>
        @endforelse
        <tr class="subtotal-row">
            <td>Total Operating Revenue:</td>
            <td style="text-align: right; color: #047857;">{{ number_format($report['total_revenue'], 2) }}</td>
        </tr>

        {{-- COGS Section --}}
        <tr class="section-header" style="margin-top: 10px;">
            <td>2. COST OF GOODS SOLD (COGS)</td>
            <td style="text-align: right;">Amount ({{ $team->currency_symbol ?? '৳' }})</td>
        </tr>
        @forelse($report['cogs_rows'] as $row)
        <tr class="line-item">
            <td style="padding-left: 20px;">{{ $row['account_code'] }} - {{ $row['name'] }}</td>
            <td style="text-align: right; font-weight: 600;">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @empty
        <tr class="line-item">
            <td style="padding-left: 20px; color: #94a3b8; font-style: italic;">No direct inventory cost</td>
            <td style="text-align: right; color: #94a3b8;">0.00</td>
        </tr>
        @endforelse
        <tr class="subtotal-row">
            <td>Total Cost of Goods Sold:</td>
            <td style="text-align: right; color: #dc2626;">- {{ number_format($report['total_cogs'], 2) }}</td>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: 800;">
            <td style="padding: 7px 10px; font-size: 10pt;">GROSS PROFIT:</td>
            <td style="padding: 7px 10px; text-align: right; font-size: 10pt; color: #0f172a;">
                {{ number_format($report['gross_profit'], 2) }}
            </td>
        </tr>

        {{-- Expenses Section --}}
        <tr class="section-header">
            <td>3. OPERATING EXPENSES</td>
            <td style="text-align: right;">Amount ({{ $team->currency_symbol ?? '৳' }})</td>
        </tr>
        @forelse($report['expense_rows'] as $row)
        <tr class="line-item">
            <td style="padding-left: 20px;">{{ $row['account_code'] }} - {{ $row['name'] }}</td>
            <td style="text-align: right; font-weight: 600;">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @empty
        <tr class="line-item">
            <td style="padding-left: 20px; color: #94a3b8; font-style: italic;">No operating expenses recorded</td>
            <td style="text-align: right; color: #94a3b8;">0.00</td>
        </tr>
        @endforelse
        <tr class="subtotal-row">
            <td>Total Operating Expenses:</td>
            <td style="text-align: right; color: #dc2626;">- {{ number_format($report['total_operating_expenses'], 2) }}</td>
        </tr>
    </table>

    <table style="margin-top: 15px;">
        <tr class="net-income-box">
            <td style="width: 75%;">NET OPERATING INCOME / PROFIT:</td>
            <td style="width: 25%; text-align: right; color: #047857;">
                {{ $team->currency_symbol ?? '৳' }} {{ number_format($report['net_income'], 2) }}
            </td>
        </tr>
    </table>
</body>
</html>
