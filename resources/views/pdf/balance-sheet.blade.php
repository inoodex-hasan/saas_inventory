<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Balance Sheet Statement</title>
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
            color: #1d4ed8;
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
        .grand-total-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-weight: 900;
            font-size: 10pt;
            padding: 8px 10px;
            background-color: #eff6ff;
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
                <div class="doc-title">BALANCE SHEET</div>
                <div style="text-align: right; font-size: 8.5pt; color: #64748b; margin-top: 4px;">
                    Statement as of: <strong>{{ \Carbon\Carbon::parse($report['as_of_date'])->format('d M Y') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 15px;">
        {{-- Assets --}}
        <tr class="section-header">
            <td style="width: 75%;">1. ASSETS</td>
            <td style="width: 25%; text-align: right;">Amount ({{ $team->currency_symbol ?? '৳' }})</td>
        </tr>
        @forelse($report['asset_rows'] as $row)
        <tr class="line-item">
            <td style="padding-left: 20px;">{{ $row['account_code'] }} - {{ $row['name'] }}</td>
            <td style="text-align: right; font-weight: 600;">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @empty
        <tr class="line-item">
            <td style="padding-left: 20px; color: #94a3b8; font-style: italic;">No asset balances</td>
            <td style="text-align: right; color: #94a3b8;">0.00</td>
        </tr>
        @endforelse
        <tr class="grand-total-row">
            <td>TOTAL ASSETS:</td>
            <td style="text-align: right; color: #1d4ed8;">{{ number_format($report['total_assets'], 2) }}</td>
        </tr>

        {{-- Liabilities --}}
        <tr class="section-header" style="margin-top: 15px;">
            <td>2. LIABILITIES</td>
            <td style="text-align: right;">Amount ({{ $team->currency_symbol ?? '৳' }})</td>
        </tr>
        @forelse($report['liability_rows'] as $row)
        <tr class="line-item">
            <td style="padding-left: 20px;">{{ $row['account_code'] }} - {{ $row['name'] }}</td>
            <td style="text-align: right; font-weight: 600;">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @empty
        <tr class="line-item">
            <td style="padding-left: 20px; color: #94a3b8; font-style: italic;">No liability obligations</td>
            <td style="text-align: right; color: #94a3b8;">0.00</td>
        </tr>
        @endforelse
        <tr class="subtotal-row">
            <td>Total Liabilities:</td>
            <td style="text-align: right; color: #0f172a;">{{ number_format($report['total_liabilities'], 2) }}</td>
        </tr>

        {{-- Equity --}}
        <tr class="section-header">
            <td>3. EQUITY & RETAINED EARNINGS</td>
            <td style="text-align: right;">Amount ({{ $team->currency_symbol ?? '৳' }})</td>
        </tr>
        @foreach($report['equity_rows'] as $row)
        <tr class="line-item">
            <td style="padding-left: 20px;">{{ $row['account_code'] }} - {{ $row['name'] }}</td>
            <td style="text-align: right; font-weight: 600;">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @endforeach
        <tr class="line-item">
            <td style="padding-left: 20px;">Current Period Net Income (Retained)</td>
            <td style="text-align: right; font-weight: 600; color: {{ $report['net_income_to_date'] >= 0 ? '#047857' : '#dc2626' }};">
                {{ number_format($report['net_income_to_date'], 2) }}
            </td>
        </tr>
        <tr class="subtotal-row">
            <td>Total Equity:</td>
            <td style="text-align: right; color: #0f172a;">{{ number_format($report['total_equity'], 2) }}</td>
        </tr>

        <tr class="grand-total-row">
            <td>TOTAL LIABILITIES & EQUITY:</td>
            <td style="text-align: right; color: #1d4ed8;">{{ number_format($report['total_liabilities_and_equity'], 2) }}</td>
        </tr>
    </table>
</body>
</html>
