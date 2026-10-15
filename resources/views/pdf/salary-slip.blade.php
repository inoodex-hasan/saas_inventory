<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - {{ $payroll->payroll_number }}</title>
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
            color: #047857;
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
            margin-top: 10px;
            margin-bottom: 12px;
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
        .badge-draft {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
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
        .net-salary-box {
            background-color: #ecfdf5;
            border: 2px solid #a7f3d0;
            border-radius: 6px;
            padding: 12px 16px;
            text-align: right;
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
                <div class="doc-title">SALARY DISBURSEMENT SLIP</div>
                <table style="margin-top: 6px;">
                    <tr>
                        <td class="meta-label">Slip / Payroll #:</td>
                        <td style="text-align: right; font-weight: 800; font-family: monospace;">{{ $payroll->payroll_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Pay Period:</td>
                        <td style="text-align: right; font-weight: 700;">
                            {{ \Carbon\Carbon::parse($payroll->month_year . '-01')->format('F Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Disbursement Date:</td>
                        <td style="text-align: right; font-weight: 600;">
                            {{ $payroll->payment_date ? \Carbon\Carbon::parse($payroll->payment_date)->format('d M Y') : 'Pending' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Disbursement Status:</td>
                        <td style="text-align: right;">
                            <span class="badge badge-{{ $payroll->status }}">
                                {{ $payroll->status }}
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
                    <div class="box-title">Employee Profile</div>
                    <div style="font-weight: 800; font-size: 10pt; color: #0f172a;">{{ $payroll->employee->name }}</div>
                    <div style="color: #475569; font-size: 8.5pt; font-weight: 600; margin-top: 1px;">
                        {{ $payroll->employee->designation }}
                        @if($payroll->employee->department) | {{ $payroll->employee->department }} @endif
                    </div>
                    <div style="color: #64748b; font-size: 8.5pt; font-family: monospace; margin-top: 2px;">
                        Employee ID: {{ $payroll->employee->employee_code }}
                    </div>
                    @if($payroll->employee->phone)
                    <div style="color: #64748b; font-size: 8.5pt;">Phone: {{ $payroll->employee->phone }}</div>
                    @endif
                    @if($payroll->employee->joining_date)
                    <div style="color: #64748b; font-size: 8pt; margin-top: 2px;">
                        Joined: {{ \Carbon\Carbon::parse($payroll->employee->joining_date)->format('d M Y') }}
                    </div>
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <div class="info-box">
                    <div class="box-title">Banking & Disbursement Info</div>
                    @if($payroll->employee->bank_name)
                    <div style="font-weight: 700; font-size: 9pt; color: #0f172a;">{{ $payroll->employee->bank_name }}</div>
                    <div style="color: #475569; font-size: 8.5pt; font-family: monospace;">A/C: {{ $payroll->employee->bank_account_no ?? 'N/A' }}</div>
                    @endif
                    @if($payroll->employee->mobile_banking_no)
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">
                        Mobile Banking: {{ $payroll->employee->mobile_banking_no }}
                    </div>
                    @endif
                    @if($payroll->payment_method)
                    <div style="color: #475569; font-size: 8.5pt; margin-top: 2px;">
                        Disbursed via: <strong style="text-transform: capitalize;">{{ str_replace('_', ' ', $payroll->payment_method) }}</strong>
                    </div>
                    @endif
                    @if($payroll->payment_reference)
                    <div style="color: #64748b; font-size: 8pt; font-family: monospace;">
                        Txn / Ref: {{ $payroll->payment_reference }}
                    </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Earnings & Deductions Breakdown --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%; text-align: left;">Earnings & Allowances</th>
                <th style="width: 50%; text-align: left;">Deductions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="vertical-align: top; padding: 0;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">Basic Salary:</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700;">
                                {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->basic_salary, 2) }}
                            </td>
                        </tr>
                        @if($payroll->tada_allowance > 0)
                        <tr>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">Field TA/DA Travel Reimbursement:</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700; color: #047857;">
                                + {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->tada_allowance, 2) }}
                            </td>
                        </tr>
                        @endif
                        @if($payroll->other_allowances > 0)
                        <tr>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">Other Allowances / Bonus:</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700; color: #047857;">
                                + {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->other_allowances, 2) }}
                            </td>
                        </tr>
                        @endif
                        <tr style="background-color: #f8fafc;">
                            <td style="padding: 8px 10px; font-weight: 800;">Total Gross Earnings:</td>
                            <td style="padding: 8px 10px; text-align: right; font-weight: 800; color: #0f172a;">
                                {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->basic_salary + $payroll->tada_allowance + $payroll->other_allowances, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>

                <td style="vertical-align: top; padding: 0; border-left: 1px solid #e2e8f0;">
                    <table style="width: 100%;">
                        @if($payroll->deductions_total > 0)
                        <tr>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">Advances, Unpaid Leave & Deductions:</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700; color: #dc2626;">
                                - {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->deductions_total, 2) }}
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">No deductions applied</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; color: #64748b;">0.00</td>
                        </tr>
                        @endif
                        <tr style="background-color: #f8fafc;">
                            <td style="padding: 8px 10px; font-weight: 800;">Total Deductions:</td>
                            <td style="padding: 8px 10px; text-align: right; font-weight: 800; color: #dc2626;">
                                - {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->deductions_total, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Net Salary Callout Box --}}
    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                <div style="font-size: 8pt; color: #64748b;">
                    This is a computer-generated salary slip and does not require a physical seal if verified digitally.
                </div>
            </td>
            <td style="width: 50%;">
                <div class="net-salary-box">
                    <div style="font-size: 9pt; font-weight: 800; color: #065f46; text-transform: uppercase;">NET SALARY PAYABLE</div>
                    <div style="font-size: 16pt; font-weight: 900; color: #047857; margin-top: 2px;">
                        {{ $team->currency_symbol ?? '৳' }} {{ number_format($payroll->net_salary, 2) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 45%;">
                <div class="signature-line">Employee Signature & Acknowledgement</div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;">
                <div class="signature-line">Authorized Signatory / Accounts Manager</div>
            </td>
        </tr>
    </table>
</body>
</html>
