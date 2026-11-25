<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Sales Invoice #{{ $order->order_number ?? ($sales->order_no ?? 'INV') }}</title>
    @php
        $order = $order ?? $sales;
        $sales = $order;
        $team = $team ?? ($order->team ?? auth()->user()?->currentTeam);
        $customer = $customer ?? $order->customer;
        $items = $items ?? $order->items;
        $returns = $returns ?? ($order->relationLoaded('returns') ? $order->returns : ($order->returns()->with('items.product')->get() ?? collect()));

        $bgPath = $team ? $team->getInvoiceBgAbsolutePath() : null;
        $marginTop = $team && $team->invoice_bg_path ? ($team->invoice_margin_top ?? 38) : 14;
        $marginBottom = $team && $team->invoice_bg_path ? ($team->invoice_margin_bottom ?? 24) : 14;
        $hideHeader = $team && $team->invoice_bg_path && $team->hide_header_on_pad;
        $currency = $team->currency_symbol ?? '৳';

        $subtotal = (float)($order->subtotal ?? 0);
        $discount = (float)($order->discount_amount ?? 0);
        $vat = (float)($order->tax_amount ?? 0);
        $delivery = (float)($order->shipping_cost ?? 0);
        $additionalCharges = !empty($order->additional_charges) && is_array($order->additional_charges) ? $order->additional_charges : [];
        $grandTotal = (float)($order->total_amount ?? 0);
        $received = (float)($order->paid_amount ?? 0);
        $due = (float)max(0, $grandTotal - $received);
        $tendered = $order->tendered_amount !== null ? (float)$order->tendered_amount : $received;
        $change = $order->change_amount !== null ? (float)$order->change_amount : max(0, $tendered - $received);
        $isFullyPaid = ($due <= 0);

        $orderNo = $order->order_number ?? ($sales->order_no ?? 'INV-' . $order->id);
        $orderNo = strtoupper(substr($orderNo, 0, 4)) === 'INV-' ? '#' . $orderNo : '#INV-' . $orderNo;
        $invoiceDate = $order->order_date ? $order->order_date->format('d M, Y') : ($order->created_at ? $order->created_at->format('d M, Y') : date('d M, Y'));

        $custName = $customer->name ?? 'Walk-in Customer';
        $custPhone = $customer->phone ?? '—';
        $custAddress = $customer->address ?: ($order->shipping_address ?: 'Counter Sale Delivery');

        // Amount in words
        if (function_exists('numberToWords')) {
            $inWords = numberToWords($grandTotal) . ' ' . (($team->currency_code ?? 'BDT') === 'BDT' ? 'Taka' : ($team->currency_code ?? '')) . ' Only';
        } elseif (class_exists('\NumberFormatter')) {
            $f = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $inWords = ucwords($f->format($grandTotal)) . ' Only';
        } else {
            $inWords = number_format($grandTotal, 2) . ' Only';
        }

        $completedReturns = isset($returns) ? $returns->where('status', 'completed') : collect();
        $totalRefundAmount = $completedReturns->sum(function ($return) {
            return $return->refund_amount ?? $return->items->sum('total_price');
        });
        $hasReturns = $completedReturns->isNotEmpty();
    @endphp
    <style>
        @page {
            size: 210mm 297mm;
            @if($bgPath)
            background-image: url('{{ $bgPath }}');
            background-image-resize: 6;
            @endif
            margin-top: {{ $marginTop }}mm;
            margin-bottom: {{ $marginBottom }}mm;
            margin-left: 14mm;
            margin-right: 14mm;
            margin-header: 8mm;
            margin-footer: 5mm;
            header: html_pageHeader;
            footer: html_invoiceFooter;
        }

        @page :first {
            margin-top: {{ $marginTop }}mm;
            header: _blank;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        body {
            color: #1e293b;
            font-size: 11px;
            line-height: 1.45;
            background: #ffffff;
        }

        /* Top Header */
        table.header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-title {
            font-size: 26px;
            font-weight: 900;
            color: #0a1c38;
            letter-spacing: 1px;
            line-height: 1;
        }

        .invoice-subtitle {
            font-size: 9.5px;
            font-weight: 800;
            color: #b8860b;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 3px;
            margin-bottom: 4px;
        }

        /* Two-box Info Section */
        table.info-section {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 14px;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-top: 3px solid #0a1c38;
            border-radius: 6px;
            padding: 9px 12px;
            background-color: #f8fafc;
            vertical-align: top;
        }

        .card-heading {
            font-size: 10px;
            font-weight: 800;
            color: #0a1c38;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        table.kv-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.kv-table td {
            padding: 2px 0;
            font-size: 10.5px;
            vertical-align: top;
        }

        table.kv-table td.lbl {
            width: 80px;
            font-weight: 700;
            color: #64748b;
        }

        table.kv-table td.val {
            color: #1e293b;
        }

        /* Items Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }

        table.items-table th {
            background-color: #0a1c38;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 8px 10px;
            border-bottom: 2px solid #b8860b;
        }

        table.items-table td {
            padding: 7px 10px;
            font-size: 11px;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        /* Signatures Section */
        table.signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }
    </style>
</head>

<body>

    <!-- Header Section -->
    @if(!$hideHeader)
    <table class="header-table" style="margin-bottom: 16px;">
        <tr>
            <!-- Left: Company Branding -->
            <td style="width: 55%; vertical-align: middle;">
                <div style="font-size: 20px; font-weight: 900; color: #0a1c38; line-height: 1.1;">{{ $team->displayName() }}</div>
                @if($team->formattedAddress())
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">{{ $team->formattedAddress() }}</div>
                @endif
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                    {{ collect([$team->phone, $team->email])->filter()->join(' · ') }}
                </div>
                @if($team->tax_id)
                    <div style="font-size: 9.5px; color: #b8860b; font-weight: 700; margin-top: 1px;">VAT / Tax Reg: {{ $team->tax_id }}</div>
                @endif
            </td>

            <!-- Right: Invoice Title & Meta -->
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-subtitle">Official Commercial Document</div>
                <div style="font-size: 12.5px; font-weight: 800; color: #0a1c38; margin-top: 2px;">{{ $orderNo }}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 1px;">Issue Date: <strong>{{ $invoiceDate }}</strong></div>
            </td>
        </tr>
    </table>
    @else
    <!-- Pre-printed Pad Header -->
    <table class="header-table" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div style="font-size: 10px; color: #64748b;">Warehouse: <strong>{{ $order->warehouse->name ?? 'Main Logistics Hub' }}</strong></div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div class="invoice-title" style="font-size: 20px;">INVOICE</div>
                <div style="font-size: 12px; font-weight: 800; color: #0a1c38;">{{ $orderNo }}</div>
                <div style="font-size: 10px; color: #64748b;">Date: {{ $invoiceDate }}</div>
            </td>
        </tr>
    </table>
    @endif

    <!-- Two-box Info Section: Customer & Order Meta -->
    <table class="info-section">
        <tr>
            <!-- Left Box: Bill To -->
            <td class="info-card" style="width: 49%;">
                <div class="card-heading">Billed To (Customer Details)</div>
                <table class="kv-table">
                    <tr>
                        <td class="lbl">Client Name:</td>
                        <td class="val"><strong>{{ $custName }}</strong></td>
                    </tr>
                    @if(!empty($customer->company_name))
                    <tr>
                        <td class="lbl">Company:</td>
                        <td class="val">{{ $customer->company_name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="lbl">Phone:</td>
                        <td class="val" style="font-family: monospace;">{{ $custPhone }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Address:</td>
                        <td class="val">{{ $custAddress }}</td>
                    </tr>
                </table>
            </td>

            <td style="width: 2%;"></td>

            <!-- Right Box: Order Information -->
            <td class="info-card" style="width: 49%;">
                <div class="card-heading">Transaction &amp; Logistics</div>
                <table class="kv-table">
                    <tr>
                        <td class="lbl">Invoice #:</td>
                        <td class="val" style="font-weight: 800; color: #0a1c38;">{{ $orderNo }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Issue Date:</td>
                        <td class="val">{{ $invoiceDate }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Branch / Hub:</td>
                        <td class="val">{{ $order->warehouse->name ?? 'Primary Center' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Status:</td>
                        <td class="val">
                            <span style="font-weight: 800; color: {{ $isFullyPaid ? '#059669' : '#dc2626' }};">
                                {{ $isFullyPaid ? 'PAID IN FULL' : ($received > 0 ? 'PARTIAL BALANCE' : 'UNPAID / DUE') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 50%; text-align: left;">Product Item &amp; Details</th>
                <th style="width: 12%; text-align: center;">Qty</th>
                <th style="width: 16%; text-align: right;">Unit Price</th>
                <th style="width: 17%; text-align: right;">Sub-Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $item)
                @php
                    $pName = $item->product->name ?? ($item->name ?? 'Product Item');
                    $sku = $item->product->sku ?? ($item->sku ?? null);
                    $qty = $item->quantity ?? ($item->qty ?? 1);
                    $unitPrice = $item->unit_price ?? 0;
                    $itemTotal = $item->subtotal ?? ($unitPrice * $qty);
                @endphp
                <tr style="background-color: {{ $idx % 2 === 1 ? '#f8fafc' : '#ffffff' }};">
                    <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $pName }}</strong>
                        @if($sku)
                            <div style="font-size: 9.5px; color: #64748b; font-family: monospace; margin-top: 1px;">SKU: {{ $sku }}</div>
                        @endif
                        @if(!empty($item->warranty_days) && $item->warranty_days > 0)
                            <div style="font-size: 9px; color: #0284c7; font-weight: 600; margin-top: 1px;">Warranty: {{ $item->warranty_days }} Days</div>
                        @endif
                        @if(isset($item->serials) && count($item->serials) > 0)
                            <div style="font-size: 9px; color: #475569; font-family: monospace; margin-top: 1px;">S/N: {{ collect($item->serials)->pluck('serial_number')->join(', ') }}</div>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #0f172a;">{{ number_format($qty) }}</td>
                    <td style="text-align: right; color: #334155;">{{ $currency }}{{ number_format($unitPrice, 2) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a;">{{ $currency }}{{ number_format($itemTotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 12px; color: #64748b;">No items recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Returned Items Section if any -->
    @if($hasReturns)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; border: 1px dashed #f87171; background: #fff5f5; border-radius: 6px;">
            <thead>
                <tr style="border-bottom: 1px solid #fecaca;">
                    <th style="text-align: left; padding: 5px 8px; font-size: 10px; font-weight: 700; color: #991b1b;">Returned Item (Refund)</th>
                    <th style="text-align: center; padding: 5px 8px; font-size: 10px; font-weight: 700; color: #991b1b; width: 15%;">Qty</th>
                    <th style="text-align: right; padding: 5px 8px; font-size: 10px; font-weight: 700; color: #991b1b; width: 25%;">Refund Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($completedReturns as $return)
                    @foreach($return->items as $retItem)
                        <tr>
                            <td style="padding: 4px 8px; font-size: 10.5px; color: #374151;">&bull; {{ $retItem->product->name ?? 'Returned Item' }}</td>
                            <td style="padding: 4px 8px; text-align: center; font-size: 10.5px; color: #64748b;">{{ $retItem->quantity }}</td>
                            <td style="padding: 4px 8px; text-align: right; font-size: 10.5px; font-weight: 700; color: #b91c1c;">-{{ $currency }}{{ number_format($retItem->total_price ?? ($retItem->unit_price * $retItem->quantity), 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Financial Summary & Notes -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <!-- Left: Notes & In-Words -->
            <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #0a1c38; border-radius: 6px; margin-bottom: 10px;">
                    <tr>
                        <td style="padding: 9px 12px;">
                            <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #0a1c38; margin-bottom: 3px;">Important Notes</div>
                            <div style="font-size: 10.5px; color: #475569; line-height: 1.45;">
                                @if($order->notes)
                                    <div style="margin-bottom: 3px; color: #0f172a;"><strong>Order Note:</strong> {{ $order->notes }}</div>
                                @endif
                                <div>• Thank you for choosing {{ $team->displayName() }}.</div>
                                <div>• Please preserve this original receipt for warranty claims.</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #b8860b; border-radius: 6px;">
                    <tr>
                        <td style="padding: 8px 12px; font-size: 10.5px; color: #334155;">
                            <strong style="text-transform: uppercase; font-size: 9.5px; letter-spacing: 0.5px; color: #b8860b; margin-right: 4px;">In Words:</strong>
                            <span style="font-weight: 700; color: #0f172a;">{{ $inWords }}</span>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Right: Breakdown Table -->
            <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Sub-Total:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #0f172a;">{{ $currency }}{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if($vat > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">VAT / Tax:</td>
                        <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 700;">{{ $currency }}{{ number_format($vat, 2) }}</td>
                    </tr>
                    @endif
                    @if($delivery > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Delivery Charge:</td>
                        <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 700;">{{ $currency }}{{ number_format($delivery, 2) }}</td>
                    </tr>
                    @endif
                    @if(!empty($additionalCharges))
                        @foreach($additionalCharges as $charge)
                            @if(($charge['amount'] ?? 0) > 0)
                            <tr>
                                <td style="padding: 3px 0; color: #475569;">{{ $charge['name'] }}:</td>
                                <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 700;">{{ $currency }}{{ number_format($charge['amount'], 2) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    @endif
                    @if($discount > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #64748b;">Discount:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #dc2626;">-{{ $currency }}{{ number_format($discount, 2) }}</td>
                    </tr>
                    @endif
                    @if($hasReturns && $totalRefundAmount > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #b91c1c;">Returned Refund:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #b91c1c;">-{{ $currency }}{{ number_format($totalRefundAmount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 5px 0; border-top: 1.5px solid #cbd5e1; border-bottom: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 800; color: #0a1c38;">Grand Total:</td>
                        <td style="padding: 5px 0; border-top: 1.5px solid #cbd5e1; border-bottom: 1.5px solid #cbd5e1; text-align: right; font-size: 13.5px; font-weight: 800; color: #0a1c38;">{{ $currency }}{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Received / Paid:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #059669;">{{ $currency }}{{ number_format($received, 2) }}</td>
                    </tr>
                    @if($change > 0)
                    <tr>
                        <td style="padding: 3px 0; font-weight: 700; color: #059669;">Change Return:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #059669;">{{ $currency }}{{ number_format($change, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 4px 0; border-top: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: #dc2626;">Due Amount:</td>
                        <td style="padding: 4px 0; border-top: 1px solid #e2e8f0; text-align: right; font-size: 12.5px; font-weight: 800; color: {{ $due > 0 ? '#dc2626' : '#16a34a' }};">
                            {{ $currency }}{{ number_format($due, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Dual Signatures -->
    <table class="signatures-table">
        <tr>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <table align="center" style="width: 170px; border-collapse: collapse; margin-left: auto; margin-right: auto;">
                    <tr>
                        <td style="border-bottom: 1.5px solid #475569; height: 1px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding-top: 5px; font-size: 10.5px; font-weight: 700; color: #1e293b;">Customer Signature</td>
                    </tr>
                </table>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <table align="center" style="width: 170px; border-collapse: collapse; margin-left: auto; margin-right: auto;">
                    <tr>
                        <td style="border-bottom: 1.5px solid #475569; height: 1px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding-top: 5px; font-size: 10.5px; font-weight: 700; color: #0a1c38;">Authorized Signature</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Pinned Page Header for Page 2+ in mPDF -->
    <htmlpageheader name="pageHeader">
        <table style="width: 100%; border-collapse: collapse; padding-bottom: 5px; border-bottom: 1.5px solid #cbd5e1;">
            <tr>
                <td style="width: 50%; vertical-align: middle;">
                    <div style="font-size: 13px; font-weight: 800; color: #0a1c38;">{{ $team->displayName() }}</div>
                </td>
                <td style="width: 50%; vertical-align: middle; text-align: right;">
                    <div style="font-size: 11px; font-weight: 700; color: #0a1c38;">{{ $orderNo }}</div>
                    <div style="font-size: 10px; color: #64748b;">Page {PAGENO} of {nbpg}</div>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <!-- HTML Page Footer for mPDF -->
    <htmlpagefooter name="invoiceFooter">
        <div style="border-top: 1px solid #cbd5e1; padding-top: 5px; text-align: center; font-size: 10px; color: #64748b;">
            @if(!$hideHeader)
                <div>{{ $team->formattedAddress() }} &middot; Phone: {{ $team->phone ?? '—' }} &middot; Email: {{ $team->email ?? '—' }}</div>
            @endif
            <div style="margin-top: 1px; font-size: 9px; color: #94a3b8;">Printed on {{ date('d M Y, h:i A') }} &middot; Page {PAGENO} of {nbpg}</div>
        </div>
    </htmlpagefooter>

</body>

</html>
