<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Statement - {{ $project->project_code }}</title>
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
            font-size: 17pt;
            font-weight: 900;
            color: #0369a1;
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
        .section-title {
            font-size: 10pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 15px;
            margin-bottom: 6px;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 3px;
        }
        .items-table {
            margin-bottom: 14px;
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
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8.5pt;
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
            padding: 3.5px 0;
            font-size: 8.5pt;
        }
        .grand-total {
            border-top: 2px solid #cbd5e1;
            font-size: 10.5pt;
            font-weight: 800;
            color: #0f172a;
            padding-top: 5px !important;
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
            margin-top: 30px;
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
                    Tax/Trade ID: {{ $team->tax_id }}
                </div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="doc-title">PROJECT STATEMENT & BILL</div>
                <table style="margin-top: 6px;">
                    <tr>
                        <td class="meta-label">Project Code:</td>
                        <td style="text-align: right; font-weight: 800; font-family: monospace;">{{ $project->project_code }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Statement Date:</td>
                        <td style="text-align: right; font-weight: 600;">{{ now()->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Project Status:</td>
                        <td style="text-align: right; font-weight: 700; text-transform: uppercase;">{{ str_replace('_', ' ', $project->status) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Billing Status:</td>
                        <td style="text-align: right;">
                            <span class="badge badge-{{ $project->payment_status }}">
                                {{ str_replace('_', ' ', $project->payment_status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <div class="info-box">
                    <div class="box-title">Client Details</div>
                    <div style="font-weight: 800; font-size: 10pt; color: #0f172a;">{{ $project->client->name ?? 'N/A' }}</div>
                    @if($project->client && $project->client->company_name)
                    <div style="color: #475569; font-size: 8.5pt; font-weight: 600;">{{ $project->client->company_name }}</div>
                    @endif
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">Phone: {{ $project->client->phone ?? 'N/A' }}</div>
                    @if($project->client && $project->client->email)
                    <div style="color: #475569; font-size: 8.5pt;">Email: {{ $project->client->email }}</div>
                    @endif
                    @if($project->client && $project->client->address)
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">{{ $project->client->address }}</div>
                    @endif
                    @if($project->client && $project->client->tax_number)
                    <div style="color: #64748b; font-size: 8pt; margin-top: 2px;">Tax ID: {{ $project->client->tax_number }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <div class="info-box">
                    <div class="box-title">Project Scope & Timeline</div>
                    <div style="font-weight: 800; font-size: 10pt; color: #0f172a;">{{ $project->name }}</div>
                    @if($project->start_date)
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">
                        Timeline: {{ \Carbon\Carbon::parse($project->start_date)->format('d M Y') }}
                        @if($project->end_date) - {{ \Carbon\Carbon::parse($project->end_date)->format('d M Y') }} @endif
                    </div>
                    @endif
                    @if($project->creator)
                    <div style="color: #475569; font-size: 8.5pt;">Manager / Assigned: {{ $project->creator->name }}</div>
                    @endif
                    @if($project->description)
                    <div style="color: #64748b; font-size: 8pt; margin-top: 3px;">{{ $project->description }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Section 1: Materials & Inventory --}}
    @if($project->items->isNotEmpty())
    <div class="section-title">1. Bill of Materials & Equipment Issued</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 48%; text-align: left;">Item & Description</th>
                <th style="width: 16%; text-align: center;">Warehouse</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 10%; text-align: right;">Rate</th>
                <th style="width: 10%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->items as $idx => $item)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td>
                    <strong style="color: #0f172a;">{{ $item->item_name }}</strong>
                    @if($item->notes)<div style="font-size: 8pt; color: #64748b;">{{ $item->notes }}</div>@endif
                </td>
                <td style="text-align: center; color: #64748b;">{{ $item->warehouse->name ?? '-' }}</td>
                <td style="text-align: center;">{{ (float) $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right; font-weight: 700;">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Section 2: Direct Costs --}}
    @if($project->costs->isNotEmpty())
    <div class="section-title">2. Direct Expenses & Subcontractor Costs</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 14%; text-align: center;">Date</th>
                <th style="width: 40%; text-align: left;">Description</th>
                <th style="width: 18%; text-align: left;">Category</th>
                <th style="width: 12%; text-align: left;">Vendor</th>
                <th style="width: 10%; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->costs as $idx => $cost)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="text-align: center;">{{ \Carbon\Carbon::parse($cost->cost_date)->format('d M Y') }}</td>
                <td>
                    <strong style="color: #0f172a;">{{ $cost->description }}</strong>
                    @if($cost->receipt_reference)<div style="font-size: 8pt; color: #64748b;">Ref: {{ $cost->receipt_reference }}</div>@endif
                </td>
                <td>{{ $cost->category->name ?? 'General' }}</td>
                <td>{{ $cost->vendor_name ?? '-' }}</td>
                <td style="text-align: right; font-weight: 700;">{{ number_format($cost->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Section 3: Milestone Bills --}}
    @if($project->bills->isNotEmpty())
    <div class="section-title">3. Milestone Invoices & Claims</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 20%; text-align: left;">Invoice / Bill #</th>
                <th style="width: 44%; text-align: left;">Milestone Title</th>
                <th style="width: 15%; text-align: center;">Due Date</th>
                <th style="width: 15%; text-align: right;">Claim Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->bills as $idx => $bill)
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="font-family: monospace; font-weight: 700;">{{ $bill->bill_number }}</td>
                <td>{{ $bill->title }}</td>
                <td style="text-align: center;">{{ $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('d M Y') : '-' }}</td>
                <td style="text-align: right; font-weight: 700;">{{ number_format($bill->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Financial Totals Box --}}
    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 15px;">
                <div class="info-box">
                    <div class="box-title">Payment Terms & Instructions</div>
                    <div style="font-size: 8.5pt; color: #475569;">
                        Payments should be made according to project agreement milestones. Checks/transfers should be addressed to <strong>{{ $team->displayName() }}</strong>.
                    </div>
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <div class="total-box">
                    <table class="total-row">
                        @if($project->budget > 0)
                        <tr>
                            <td class="meta-label">Contract Budget:</td>
                            <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($project->budget, 2) }}</td>
                        </tr>
                        @endif
                        @if($project->items_total > 0)
                        <tr>
                            <td class="meta-label">Materials Total:</td>
                            <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($project->items_total, 2) }}</td>
                        </tr>
                        @endif
                        @if($project->costs_total > 0)
                        <tr>
                            <td class="meta-label">Direct Costs Total:</td>
                            <td style="text-align: right; font-weight: 600;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($project->costs_total, 2) }}</td>
                        </tr>
                        @endif
                        @if($project->discount_amount > 0)
                        <tr>
                            <td class="meta-label" style="color: #dc2626;">Discount:</td>
                            <td style="text-align: right; color: #dc2626; font-weight: 600;">- {{ $team->currency_symbol ?? '৳' }} {{ number_format($project->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($project->tax_amount > 0)
                        <tr>
                            <td class="meta-label">Tax / VAT:</td>
                            <td style="text-align: right; font-weight: 600;">+ {{ $team->currency_symbol ?? '৳' }} {{ number_format($project->tax_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td>Total Billed Amount:</td>
                            <td style="text-align: right; color: #0369a1;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($project->billed_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Received Amount:</td>
                            <td style="text-align: right; font-weight: 700; color: #16a34a;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($project->paid_amount, 2) }}</td>
                        </tr>
                        @if($project->due_amount > 0)
                        <tr>
                            <td class="meta-label" style="color: #dc2626; font-weight: 700;">Outstanding Balance:</td>
                            <td style="text-align: right; font-weight: 800; color: #dc2626;">{{ $team->currency_symbol ?? '৳' }} {{ number_format($project->due_amount, 2) }}</td>
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
                <div class="signature-line">Client Representative Signature & Acceptance</div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="signature-line">Authorized Signatory / Project Lead</div>
            </td>
        </tr>
    </table>
</body>
</html>
