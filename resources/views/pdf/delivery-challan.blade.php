<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Challan - {{ $challan->challan_number }}</title>
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
        .challan-badge {
            display: inline-block;
            background-color: #047857;
            color: #ffffff;
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 6px 14px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .info-box {
            padding: 9px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #064e3b;
            color: #ffffff;
            font-size: 8.5pt;
            text-transform: uppercase;
            padding: 7px 10px;
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
        .signatures-table {
            margin-top: 60px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #475569;
            text-align: center;
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

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
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
            <td style="width: 45%; text-align: right;">
                <div class="challan-badge">DELIVERY CHALLAN</div>
                <div style="font-size: 11pt; font-weight: bold; margin-top: 5px; color: #064e3b; font-family: monospace;">
                    {{ $challan->challan_number }}
                </div>
                <div style="font-size: 8.5pt; color: #64748b;">
                    Date: {{ $challan->dispatch_date->format('d M, Y') }}
                </div>
                <div style="font-size: 8.5pt; color: #334155; font-weight: 600;">
                    Order Ref: {{ $challan->salesOrder->order_number ?? '—' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Destination & Transport Info -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 50%; padding-right: 6px;">
                <div class="info-box">
                    <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Consignee / Deliver To</div>
                    <div style="font-size: 11pt; font-weight: bold; color: #0f172a; margin-top: 2px;">
                        {{ $challan->customer->name ?? 'Consignee Client' }}
                    </div>
                    @if($challan->customer && $challan->customer->phone)
                        <div style="font-size: 8.5pt; color: #475569;">Contact: {{ $challan->customer->phone }}</div>
                    @endif
                    <div style="font-size: 8.5pt; color: #475569; margin-top: 2px;">
                        <strong>Address:</strong> {{ $challan->delivery_address ?: ($challan->customer->address ?? 'Standard Delivery') }}
                    </div>
                </div>
            </td>
            <td style="width: 50%; padding-left: 6px;">
                <div class="info-box">
                    <div style="font-size: 8pt; font-weight: bold; color: #64748b; text-transform: uppercase;">Transport & Dispatch Hub</div>
                    <div style="font-size: 9.5pt; font-weight: bold; color: #0f172a;">
                        Hub: {{ $challan->warehouse->name ?? 'Main Hub' }}
                    </div>
                    <div style="font-size: 8.5pt; color: #475569;">
                        Carrier: <strong>{{ $challan->transport_carrier ?: 'Self Transport' }}</strong>
                    </div>
                    @if($challan->vehicle_no)
                        <div style="font-size: 8.5pt; color: #475569;">Vehicle: <strong>{{ $challan->vehicle_no }}</strong></div>
                    @endif
                    @if($challan->driver_name)
                        <div style="font-size: 8.5pt; color: #475569;">
                            Driver: <strong>{{ $challan->driver_name }}</strong> ({{ $challan->driver_phone ?? '—' }})
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Listing Table (NO PRICING) -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">#</th>
                <th style="width: 55%; text-align: left;">Product Item Description & SKU</th>
                <th style="width: 15%; text-align: center;">Quantity</th>
                <th style="width: 22%; text-align: left;">Packaging / Packages</th>
            </tr>
        </thead>
        <tbody>
            @foreach($challan->items as $idx => $item)
                <tr>
                    <td style="text-align: center; color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td>
                        <div style="font-weight: bold; color: #0f172a;">{{ $item->product->name ?? 'Product' }}</div>
                        <div style="font-family: monospace; font-size: 8pt; color: #047857;">SKU: {{ $item->product->sku ?? '—' }}</div>
                    </td>
                    <td style="text-align: center; font-size: 11pt; font-weight: 800; color: #0f172a;">
                        {{ number_format($item->quantity) }} {{ $item->product->unit ?? 'pcs' }}
                    </td>
                    <td style="color: #475569; font-size: 9pt;">
                        {{ $item->packaging_description ?: 'Standard Box / Carton' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="font-size: 8.5pt; color: #64748b; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px;">
        <strong>Total Package Units:</strong> {{ number_format($challan->items->sum('quantity')) }} pieces / packages. 
        Please inspect all carton seals and packaging conditions before signing acknowledgement below.
    </div>

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <td style="width: 28%; text-align: center;">
                <div class="signature-line">Prepared & Dispatched By</div>
            </td>
            <td style="width: 8%;"></td>
            <td style="width: 28%; text-align: center;">
                <div class="signature-line">Driver / Carrier Signature</div>
            </td>
            <td style="width: 8%;"></td>
            <td style="width: 28%; text-align: center;">
                <div class="signature-line">Received in Good Condition (Client Seal & Sign)</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Official Transport Consignment Document generated by {{ $team->displayName() }} ERP on {{ now()->format('d M, Y H:i') }}.
    </div>

</body>
</html>
