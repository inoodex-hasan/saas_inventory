<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Invoice #{{ $order->order_number ?? ($sales->order_no ?? 'INV') }}</title>
    @php
        $order = $order ?? $sales;
        $sales = $order;
        $team = $team ?? ($order->team ?? auth()->user()?->currentTeam);
        $customer = $customer ?? $order->customer;
        $items = $items ?? $order->items;
        $returns = $returns ?? ($order->relationLoaded('returns') ? $order->returns : ($order->returns()->with('items.product')->get() ?? collect()));

        $bgPath = $team ? $team->getInvoiceBgAbsolutePath() : null;
        $marginTop = $team && $team->invoice_bg_path ? ($team->invoice_margin_top ?? 38) : 14;
        $marginBottom = $team && $team->invoice_bg_path ? ($team->invoice_margin_bottom ?? 25) : 14;
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

        if (function_exists('numberToWords')) {
            $inWords = numberToWords($grandTotal) . ' ' . (($team->currency_code ?? 'BDT') === 'BDT' ? 'Taka' : ($team->currency_code ?? '')) . ' Only';
        } elseif (class_exists('\NumberFormatter')) {
            $f = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $inWords = ucwords($f->format($grandTotal)) . ' Only';
        } else {
            $inWords = number_format($grandTotal, 2) . ' Only';
        }

        $orderNo = $order->order_number ?? ($sales->order_no ?? 'INV-' . $order->id);
        $orderDate = $order->order_date ? $order->order_date->format('d M Y') : ($order->created_at ? $order->created_at->format('d M Y') : date('d M Y'));

        $completedReturns = isset($returns) ? $returns->where('status', 'completed') : collect();
        $totalRefundAmount = $completedReturns->sum(function($ret) {
            return $ret->refund_amount ?? $ret->items->sum('total_price');
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
            margin-footer: 6mm;
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
            font-family: Helvetica, Arial, sans-serif;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            width: 100%;
            margin-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .customer-card {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .customer-card td {
            padding: 10px 14px;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
        }

        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            padding: 9px 12px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 8px 12px;
            font-size: 11px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            padding: 2.5px 7px;
            border-radius: 4px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .badge-paid { color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; }
        .badge-partial { color: #d97706; background: #fffbeb; border: 1px solid #fde68a; }
        .badge-unpaid { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
    </style>
</head>

<body>

    <!-- Header Title & Order Meta -->
    @if(!$hideHeader)
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <h1 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 3px;">{{ $team->displayName() }}</h1>
                @if($team->formattedAddress())
                    <div style="font-size: 10.5px; color: #475569; line-height: 1.4;">{{ $team->formattedAddress() }}</div>
                @endif
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                    {{ collect([$team->phone, $team->email])->filter()->join(' · ') }}
                </div>
                @if($team->tax_id)
                    <div style="font-size: 10px; color: #4f46e5; margin-top: 2px;">Tax / VAT Reg: <strong>{{ $team->tax_id }}</strong></div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 3px; letter-spacing: 1px;">INVOICE</h1>
                <div style="font-size: 13px; font-weight: 700; color: #4f46e5;">Invoice No: #{{ $orderNo }}</div>
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Invoice Date: {{ $orderDate }}</div>
                <div style="margin-top: 4px;">
                    @if($order->payment_status === 'paid' || $due <= 0)
                        <span class="badge badge-paid">&#10003; PAID</span>
                    @elseif($order->payment_status === 'partially_paid' || ($received > 0 && $due > 0))
                        <span class="badge badge-partial">PARTIALLY PAID</span>
                    @else
                        <span class="badge badge-unpaid">UNPAID DUE</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>
    @else
    <!-- Pre-Printed Letterhead Mode: Clean Header -->
    <table class="header-table" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div style="font-size: 10px; color: #64748b; font-weight: 600;">
                    Hub / Branch: {{ $order->warehouse->name ?? 'Main Hub' }}
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <h1 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">INVOICE</h1>
                <div style="font-size: 12.5px; font-weight: 700; color: #4f46e5;">#{{ $orderNo }}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 1px;">Date: {{ $orderDate }}</div>
                <div style="margin-top: 3px;">
                    @if($order->payment_status === 'paid' || $due <= 0)
                        <span class="badge badge-paid">PAID</span>
                    @elseif($order->payment_status === 'partially_paid' || ($received > 0 && $due > 0))
                        <span class="badge badge-partial">PARTIAL</span>
                    @else
                        <span class="badge badge-unpaid">UNPAID</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>
    @endif

    <!-- Customer Info Card -->
    <table class="customer-card">
        <tr>
            <td style="width: 33.33%;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">CUSTOMER / CLIENT</div>
                <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;">{{ $customer->name ?? 'Walk-in Customer' }}</div>
                @if(!empty($customer->company_name))
                    <div style="font-size: 10px; color: #64748b; margin-top: 1px;">{{ $customer->company_name }}</div>
                @endif
            </td>
            <td style="width: 33.33%; border-left: 1px solid #cbd5e1;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">PHONE NUMBER</div>
                <div style="font-size: 12px; font-weight: 600; color: #0f172a; font-family: monospace;">{{ $customer->phone ?? '—' }}</div>
                @if(!empty($customer->email))
                    <div style="font-size: 10px; color: #64748b; margin-top: 1px;">{{ $customer->email }}</div>
                @endif
            </td>
            <td style="width: 33.33%; border-left: 1px solid #cbd5e1;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">ADDRESS / LOCATION</div>
                <div style="font-size: 11px; color: #334155; line-height: 1.35;">
                    {{ $customer->address ?: ($order->shipping_address ?: 'Counter Sale Delivery') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 48%; text-align: left;">Item Description &amp; Details</th>
                <th style="width: 12%; text-align: center;">Qty</th>
                <th style="width: 17%; text-align: right;">Unit Price</th>
                <th style="width: 17%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $item)
                @php
                    $productName = $item->product->name ?? ($item->name ?? 'Product Item');
                    $sku = $item->product->sku ?? ($item->sku ?? null);
                    $qty = $item->quantity ?? ($item->qty ?? 1);
                    $unitPrice = $item->unit_price ?? 0;
                    $itemSubtotal = $item->subtotal ?? ($unitPrice * $qty);
                @endphp
                <tr style="background-color: {{ $idx % 2 === 1 ? '#f8fafc' : '#ffffff' }};">
                    <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $productName }}</strong>
                        @if($sku)
                            <div style="font-size: 9.5px; color: #64748b; font-family: monospace; margin-top: 1px;">
                                SKU: {{ $sku }}
                            </div>
                        @endif
                        @if(!empty($item->warranty_days) && $item->warranty_days > 0)
                            <div style="font-size: 9px; color: #0284c7; font-weight: 600; margin-top: 1px;">
                                Warranty: {{ $item->warranty_days }} Days
                            </div>
                        @endif
                        @if(isset($item->serials) && count($item->serials) > 0)
                            <div style="font-size: 9px; color: #475569; font-family: monospace; margin-top: 1px;">
                                S/N: {{ collect($item->serials)->pluck('serial_number')->join(', ') }}
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #0f172a;">{{ number_format($qty) }}</td>
                    <td style="text-align: right; color: #334155;">{{ $currency }}{{ number_format($unitPrice, 2) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a;">{{ $currency }}{{ number_format($itemSubtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Returned Items Section if any -->
    @if($hasReturns)
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #fff5f5; border: 1px dashed #f87171; border-radius: 10px; margin-bottom: 18px;">
        <tr>
            <td style="padding: 10px 14px;">
                <h4 style="color: #dc2626; font-size: 11px; font-weight: 700; margin-bottom: 6px;">Returned Items (Refund Processed)</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #fecaca;">
                            <th style="text-align: left; padding: 4px; color: #991b1b;">Product</th>
                            <th style="text-align: center; padding: 4px; color: #991b1b;">Qty</th>
                            <th style="text-align: right; padding: 4px; color: #991b1b;">Refund</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completedReturns as $return)
                            @foreach($return->items as $returnItem)
                                <tr>
                                    <td style="padding: 4px; color: #374151;">&bull; {{ $returnItem->product->name ?? 'Returned Item' }}</td>
                                    <td style="padding: 4px; text-align: center; color: #4b5563;">{{ $returnItem->quantity }}</td>
                                    <td style="padding: 4px; text-align: right; font-weight: 700; color: #dc2626;">-{{ $currency }}{{ number_format($returnItem->total_price ?? ($returnItem->unit_price * $returnItem->quantity), 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    @endif

    <!-- Financial Summary & Notes -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <!-- Notes Column -->
            <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #64748b; border-radius: 8px; margin-bottom: 12px;">
                    <tr>
                        <td style="padding: 10px 12px;">
                            <div style="font-weight: 700; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.7px; color: #64748b; margin-bottom: 4px;">Terms &amp; Notes</div>
                            <div style="font-size: 10.5px; color: #475569; line-height: 1.45;">
                                @if($order->notes)
                                    <div style="margin-bottom: 4px; color: #1e293b;"><strong>Note:</strong> {{ $order->notes }}</div>
                                @endif
                                <div>• Warranty claims require this original invoice.</div>
                                <div>• Goods once sold can be returned as per company policy.</div>
                                @if($team->phone)
                                    <div style="margin-top: 4px;">Support Helpline: <strong style="color: #4f46e5;">{{ $team->phone }}</strong></div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Amount in Words -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f1f5f9; border: 1px solid #e2e8f0; border-left: 3px solid #4f46e5; border-radius: 8px;">
                    <tr>
                        <td style="padding: 8px 12px; font-size: 10.5px; color: #334155;">
                            <strong style="text-transform: uppercase; font-size: 9.5px; letter-spacing: 0.5px; color: #4f46e5; margin-right: 4px;">In Words:</strong>
                            <span style="font-weight: 700; color: #0f172a;">{{ $inWords }}</span>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Financial Summary Table -->
            <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Subtotal:</td>
                        <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 600;">{{ $currency }}{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if($vat > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">VAT / Tax:</td>
                        <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 600;">{{ $currency }}{{ number_format($vat, 2) }}</td>
                    </tr>
                    @endif
                    @if($delivery > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Delivery / Shipping:</td>
                        <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 600;">{{ $currency }}{{ number_format($delivery, 2) }}</td>
                    </tr>
                    @endif
                    @if(!empty($additionalCharges))
                        @foreach($additionalCharges as $charge)
                            @if(($charge['amount'] ?? 0) > 0)
                            <tr>
                                <td style="padding: 3px 0; color: #475569;">{{ $charge['name'] }}:</td>
                                <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 600;">{{ $currency }}{{ number_format($charge['amount'], 2) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    @endif
                    @if($discount > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #64748b;">Discount:</td>
                        <td style="padding: 3px 0; text-align: right; color: #dc2626; font-weight: 600;">- {{ $currency }}{{ number_format($discount, 2) }}</td>
                    </tr>
                    @endif
                    @if($hasReturns && $totalRefundAmount > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #dc2626;">Returned Refund:</td>
                        <td style="padding: 3px 0; text-align: right; color: #dc2626; font-weight: 600;">- {{ $currency }}{{ number_format($totalRefundAmount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 5px 0; border-top: 1.5px solid #cbd5e1; border-bottom: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 800; color: #4f46e5;">Grand Total:</td>
                        <td style="padding: 5px 0; border-top: 1.5px solid #cbd5e1; border-bottom: 1.5px solid #cbd5e1; text-align: right; font-size: 13px; font-weight: 800; color: #4f46e5;">{{ $currency }}{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                    @if($tendered > 0 && ($tendered > $received || $change > 0))
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Cash Tendered:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ $currency }}{{ number_format($tendered, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Paid / Received:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #059669;">{{ $currency }}{{ number_format($received, 2) }}</td>
                    </tr>
                    @if($change > 0)
                    <tr>
                        <td style="padding: 3px 0; font-weight: 700; color: #059669;">Change Return:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #059669;">{{ $currency }}{{ number_format($change, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 4px 0; border-top: 1px solid #e2e8f0; font-size: 12px; font-weight: 700; color: #dc2626;">Due Amount:</td>
                        <td style="padding: 4px 0; border-top: 1px solid #e2e8f0; text-align: right; font-size: 12px; font-weight: 800; color: {{ $due > 0 ? '#dc2626' : '#16a34a' }};">
                            {{ $currency }}{{ number_format($due, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Dual Signatures -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 40px;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <table align="center" style="width: 170px; margin: 0 auto; border-collapse: collapse;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; padding-top: 5px; font-size: 10px; font-weight: 600; color: #475569; text-align: center;">
                            Customer Signature
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <table align="center" style="width: 170px; margin: 0 auto; border-collapse: collapse;">
                    <tr>
                        <td style="border-top: 1.5px solid #475569; padding-top: 5px; font-size: 10px; font-weight: 600; color: #4f46e5; text-align: center;">
                            Authorized Signature
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Pinned Header for Page 2+ in mPDF -->
    <htmlpageheader name="pageHeader">
        <table style="width: 100%; border-collapse: collapse; padding-bottom: 5px; border-bottom: 1.5px solid #cbd5e1;">
            <tr>
                <td style="width: 50%; vertical-align: middle;">
                    <strong style="font-size: 13px; color: #0f172a;">{{ $team->displayName() }}</strong>
                </td>
                <td style="width: 50%; vertical-align: middle; text-align: right;">
                    <span style="font-size: 11px; font-weight: 700; color: #4f46e5;">#{{ $orderNo }}</span>
                    <span style="font-size: 10px; color: #64748b;">&nbsp;|&nbsp; Page {PAGENO} of {nbpg}</span>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <!-- Pinned Footer for mPDF -->
    <htmlpagefooter name="invoiceFooter">
        <table style="width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; font-size: 8.5px; color: #94a3b8;">
            <tr>
                <td style="text-align: left;">
                    @if(!$hideHeader && $team->formattedAddress())
                        {{ $team->formattedAddress() }} &middot;
                    @endif
                    Printed on {{ date('d M Y, h:i A') }}
                </td>
                <td style="text-align: right;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>

</body>
</html>
