<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Token - {{ $ticket->ticket_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9pt;
            line-height: 1.35;
            padding: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 14pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 15pt;
            font-weight: 900;
            color: #4338ca;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .token-box {
            background-color: #eef2ff;
            border: 2px dashed #6366f1;
            border-radius: 8px;
            padding: 8px 12px;
            text-align: center;
            margin: 10px 0;
        }
        .token-number {
            font-size: 16pt;
            font-weight: 900;
            color: #3730a3;
            letter-spacing: 1px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .box-title {
            font-size: 8pt;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
        }
        .data-label {
            color: #64748b;
            font-weight: 600;
            width: 32%;
        }
        .data-value {
            color: #0f172a;
            font-weight: 700;
        }
        .terms-box {
            font-size: 7pt;
            color: #64748b;
            line-height: 1.3;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 8px;
            margin-top: 10px;
        }
        .signature-table {
            margin-top: 25px;
        }
        .signature-line {
            border-top: 1px dashed #94a3b8;
            padding-top: 4px;
            font-size: 8pt;
            text-align: center;
            color: #475569;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $team->displayName() }}</div>
                <div style="font-size: 8pt; color: #475569; margin-top: 2px;">
                    @if($team->address_line1){{ $team->address_line1 }}, @endif
                    @if($team->city){{ $team->city }}, @endif
                    @if($team->country){{ $team->country }}@endif
                </div>
                <div style="font-size: 8pt; color: #475569;">
                    @if($team->phone)Phone: {{ $team->phone }}@endif
                    @if($team->email) | Email: {{ $team->email }}@endif
                </div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="doc-title">JOB INTAKE TOKEN</div>
                <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                    Date: <strong>{{ \Carbon\Carbon::parse($ticket->received_date)->format('d M Y') }}</strong>
                </div>
                <div style="font-size: 8pt; color: #64748b;">
                    Priority: <strong style="text-transform: uppercase; color: {{ $ticket->priority === 'urgent' ? '#dc2626' : ($ticket->priority === 'high' ? '#ea580c' : '#4338ca') }};">{{ $ticket->priority }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="token-box">
        <div style="font-size: 8pt; color: #4338ca; font-weight: 700; text-transform: uppercase;">Customer Claim Token #</div>
        <div class="token-number">{{ $ticket->ticket_number }}</div>
        <div style="font-size: 7.5pt; color: #6366f1;">Please present this token when collecting your repaired equipment.</div>
    </div>

    <table>
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                <div class="info-box">
                    <div class="box-title">Customer Information</div>
                    <table>
                        <tr>
                            <td class="data-label">Name:</td>
                            <td class="data-value">{{ $ticket->customer->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="data-label">Phone:</td>
                            <td class="data-value">{{ $ticket->customer->phone ?? 'N/A' }}</td>
                        </tr>
                        @if(!empty($ticket->customer->address))
                        <tr>
                            <td class="data-label">Address:</td>
                            <td class="data-value" style="font-size: 8pt;">{{ $ticket->customer->address }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                <div class="info-box">
                    <div class="box-title">Device & Job Profile</div>
                    <table>
                        <tr>
                            <td class="data-label">Device:</td>
                            <td class="data-value">{{ $ticket->device_name }}</td>
                        </tr>
                        @if($ticket->device_brand)
                        <tr>
                            <td class="data-label">Brand:</td>
                            <td class="data-value">{{ $ticket->device_brand }}</td>
                        </tr>
                        @endif
                        @if($ticket->serial_no)
                        <tr>
                            <td class="data-label">S/N / IMEI:</td>
                            <td class="data-value" style="font-family: monospace;">{{ $ticket->serial_no }}</td>
                        </tr>
                        @endif
                        @if($ticket->estimated_completion_date)
                        <tr>
                            <td class="data-label">Est. Ready:</td>
                            <td class="data-value">{{ \Carbon\Carbon::parse($ticket->estimated_completion_date)->format('d M Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="info-box">
        <div class="box-title">Issue Description & Accessories</div>
        <div style="margin-bottom: 4px;"><strong>Reported Problem:</strong> {{ $ticket->problem_description ?: 'General servicing & diagnosis' }}</div>
        @if($ticket->initial_diagnosis)
            <div style="margin-bottom: 4px; color: #334155;"><strong>Initial Diagnosis:</strong> {{ $ticket->initial_diagnosis }}</div>
        @endif
        @if($ticket->accessories_received)
            <div style="color: #475569;"><strong>Accessories Received:</strong> {{ $ticket->accessories_received }}</div>
        @endif
    </div>

    @if($ticket->paid_amount > 0)
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 6px 10px; margin-bottom: 8px;">
        <span style="color: #166534; font-weight: 700;">Advance Payment Received:</span>
        <strong style="color: #15803d;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->paid_amount, 2) }}</strong>
        <span style="color: #4b5563; font-size: 8pt; margin-left: 10px;">(Estimated Due: {{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->due_amount, 2) }})</span>
    </div>
    @endif

    <div class="terms-box">
        <strong>Terms of Service & Claim Policy:</strong><br>
        1. Customers must present this original token for equipment release.<br>
        2. Devices not collected within 30 days of completion notification may incur storage charges.<br>
        3. The workshop is not responsible for data loss. Customers are advised to backup data before drop-off.
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 45%;">
                <div class="signature-line">Customer Signature</div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="signature-line">Technician / Authorized Receiver</div>
            </td>
        </tr>
    </table>
</body>
</html>
