<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Invoice - {{ $ticket->ticket_number }}</title>
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
            color: #4338ca;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            margin-bottom: 12px;
        }
        .box-title {
            font-size: 8.5pt;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
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
            letter-spacing: 0.5px;
            padding: 8px 10px;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9pt;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .total-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .total-row td {
            padding: 4px 0;
            font-size: 9pt;
        }
        .grand-total {
            border-top: 2px solid #cbd5e1;
            font-size: 11pt;
            font-weight: 800;
            color: #0f172a;
            padding-top: 6px !important;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .badge-partially_paid {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .signature-table {
            margin-top: 35px;
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
                <div style="font-size: 8.5pt; color: #475569; margin-top: 3px;">
                    @if($team->address_line1){{ $team->address_line1 }}, @endif
                    @if($team->city){{ $team->city }}, @endif
                    @if($team->country){{ $team->country }}@endif
                </div>
                <div style="font-size: 8.5pt; color: #475569;">
                    @if($team->phone)Phone: {{ $team->phone }}@endif
                    @if($team->email) | Email: {{ $team->email }}@endif
                </div>
                @if($team->tax_id)
                <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                    Trade/Tax ID: {{ $team->tax_id }}
                </div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="doc-title">SERVICE INVOICE</div>
                <table style="margin-top: 6px;">
                    <tr>
                        <td class="meta-label">Invoice / Ticket #:</td>
                        <td style="text-align: right; font-weight: 800; font-family: monospace;">{{ $ticket->ticket_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Received Date:</td>
                        <td style="text-align: right; font-weight: 600;">{{ \Carbon\Carbon::parse($ticket->received_date)->format('d M Y') }}</td>
                    </tr>
                    @if($ticket->completed_date)
                    <tr>
                        <td class="meta-label">Completion Date:</td>
                        <td style="text-align: right; font-weight: 600;">{{ \Carbon\Carbon::parse($ticket->completed_date)->format('d M Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="meta-label">Payment Status:</td>
                        <td style="text-align: right;">
                            <span class="badge badge-{{ $ticket->payment_status }}">
                                {{ str_replace('_', ' ', $ticket->payment_status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 15px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <div class="info-box">
                    <div class="box-title">Client Information</div>
                    <div style="font-weight: 800; font-size: 10pt; color: #0f172a;">{{ $ticket->customer->name ?? 'N/A' }}</div>
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">Phone: {{ $ticket->customer->phone ?? 'N/A' }}</div>
                    @if(!empty($ticket->customer->email))
                    <div style="color: #475569; font-size: 8.5pt;">Email: {{ $ticket->customer->email }}</div>
                    @endif
                    @if(!empty($ticket->customer->address))
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">{{ $ticket->customer->address }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <div class="info-box">
                    <div class="box-title">Device & Service Details</div>
                    <div style="font-weight: 800; font-size: 10pt; color: #0f172a;">{{ $ticket->device_name }}</div>
                    @if($ticket->device_brand)
                        <div style="color: #475569; font-size: 8.5pt;">Brand: {{ $ticket->device_brand }}</div>
                    @endif
                    @if($ticket->serial_no)
                        <div style="color: #475569; font-size: 8.5pt; font-family: monospace;">Serial/IMEI: {{ $ticket->serial_no }}</div>
                    @endif
                    @if($ticket->technician)
                        <div style="color: #475569; font-size: 8.5pt;">Technician: {{ $ticket->technician->name }}</div>
                    @endif
                    @if($ticket->warranty_days > 0)
                        <div style="color: #166534; font-size: 8.5pt; font-weight: 700; margin-top: 2px;">
                            Service Warranty: {{ $ticket->warranty_days }} Days
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 50%; text-align: left;">Description</th>
                <th style="width: 12%; text-align: center;">Qty</th>
                <th style="width: 16%; text-align: right;">Unit Price ({{ $team->currency_symbol ?? '৳' }})</th>
                <th style="width: 16%; text-align: right;">Total ({{ $team->currency_symbol ?? '৳' }})</th>
            </tr>
        </thead>
        <tbody>
            @php $line = 1; @endphp
            @if($ticket->service_charge > 0 || $ticket->items->isEmpty())
            <tr>
                <td style="text-align: center;">{{ $line++ }}</td>
                <td>
                    <strong style="color: #0f172a;">Labor & Diagnosis Service Charge</strong>
                    @if($ticket->problem_description)
                        <div style="font-size: 8pt; color: #64748b;">Job: {{ $ticket->problem_description }}</div>
                    @endif
                </td>
                <td style="text-align: center;">1</td>
                <td style="text-align: right;">{{ number_format($ticket->service_charge, 2) }}</td>
                <td style="text-align: right; font-weight: 700;">{{ number_format($ticket->service_charge, 2) }}</td>
            </tr>
            @endif

            @foreach($ticket->items as $item)
            <tr>
                <td style="text-align: center;">{{ $line++ }}</td>
                <td>
                    <strong style="color: #0f172a;">{{ $item->part_name }}</strong>
                    @if($item->notes)
                        <div style="font-size: 8pt; color: #64748b;">{{ $item->notes }}</div>
                    @endif
                </td>
                <td style="text-align: center;">{{ (float) $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right; font-weight: 700;">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 15px;">
                @if($ticket->remarks)
                <div class="info-box">
                    <div class="box-title">Technician Notes & Remarks</div>
                    <div style="font-size: 8.5pt; color: #334155;">{{ $ticket->remarks }}</div>
                </div>
                @endif

                @if($ticket->warranty_days > 0)
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 8px 10px; font-size: 8pt; color: #166534;">
                    <strong>Warranty Clause:</strong> This repair is covered under a <strong>{{ $ticket->warranty_days }}-day limited warranty</strong> from completion date against recurring defects on replaced components. Physical damage or liquid spills void warranty.
                </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <div class="total-box">
                    <table class="total-row">
                        <tr>
                            <td class="meta-label">Service Labor:</td>
                            <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->service_charge, 2) }}</td>
                        </tr>
                        @if($ticket->parts_total > 0)
                        <tr>
                            <td class="meta-label">Spare Parts:</td>
                            <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->parts_total, 2) }}</td>
                        </tr>
                        @endif
                        @if($ticket->discount_amount > 0)
                        <tr>
                            <td class="meta-label" style="color: #dc2626;">Discount:</td>
                            <td style="text-align: right; color: #dc2626; font-weight: 600;">- {{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td>Grand Total:</td>
                            <td style="text-align: right; color: #4338ca;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Paid Amount:</td>
                            <td style="text-align: right; font-weight: 700; color: #16a34a;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->paid_amount, 2) }}</td>
                        </tr>
                        @if($ticket->due_amount > 0)
                        <tr>
                            <td class="meta-label" style="color: #dc2626; font-weight: 700;">Balance Due:</td>
                            <td style="text-align: right; font-weight: 800; color: #dc2626;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($ticket->due_amount, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 45%;">
                <div class="signature-line">Customer Signature & Received in Good Condition</div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="signature-line">Authorized Signatory / Service Center</div>
            </td>
        </tr>
    </table>
</body>
</html>
