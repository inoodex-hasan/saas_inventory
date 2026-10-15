<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trial Balance - {{ $report['as_of_date'] }}</title>
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
            color: #4338ca;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            padding: 7px 10px;
            font-weight: bold;
        }
        .items-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8.5pt;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .total-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-weight: 800;
            font-size: 9.5pt;
            padding: 8px 10px;
            background-color: #f1f5f9;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-balanced {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .badge-unbalanced {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
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
                <div style="font-size: 8.5pt; color: #475569;">
                    @if($team->phone)Phone: {{ $team->phone }}@endif
                    @if($team->email) | Email: {{ $team->email }}@endif
                </div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">TRIAL BALANCE</div>
                <table style="margin-top: 6px;">
                    <tr>
                        <td style="text-align: right; color: #64748b; font-size: 8.5pt;">As of Date:</td>
                        <td style="text-align: right; font-weight: 700;">{{ \Carbon\Carbon::parse($report['as_of_date'])->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; color: #64748b; font-size: 8.5pt;">Status:</td>
                        <td style="text-align: right;">
                            <span class="badge {{ $report['is_balanced'] ? 'badge-balanced' : 'badge-unbalanced' }}">
                                {{ $report['is_balanced'] ? 'Balanced' : 'Unbalanced' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%; text-align: left;">Code</th>
                <th style="width: 45%; text-align: left;">Account Title</th>
                <th style="width: 15%; text-align: center;">Category</th>
                <th style="width: 12.5%; text-align: right;">Debit ({{ $team->currency_symbol ?? '৳' }})</th>
                <th style="width: 12.5%; text-align: right;">Credit ({{ $team->currency_symbol ?? '৳' }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
            <tr>
                <td style="font-family: monospace; font-weight: 700;">{{ $row['account_code'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td style="text-align: center; text-transform: uppercase; font-size: 7.5pt; color: #64748b;">{{ $row['type'] }}</td>
                <td style="text-align: right; font-weight: 600;">
                    {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}
                </td>
                <td style="text-align: right; font-weight: 600;">
                    {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}
                </td>
            </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="3" style="text-align: right;">GRAND TOTAL:</td>
                <td style="text-align: right; color: #4338ca;">
                    {{ number_format($report['total_debit'], 2) }}
                </td>
                <td style="text-align: right; color: #4338ca;">
                    {{ number_format($report['total_credit'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
