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
        $marginBottom = $team && $team->invoice_bg_path ? ($team->invoice_margin_bottom ?? 26) : 14;
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
        $orderNoFormatted = strtoupper(substr($orderNo, 0, 4)) === 'INV-' ? '#' . $orderNo : '#INV-' . $orderNo;
        $invoiceDate = $order->order_date ? $order->order_date->format('M d, Y') : ($order->created_at ? $order->created_at->format('M d, Y') : date('M d, Y'));
        $dueDate = $order->order_date ? $order->order_date->addDays(7)->format('M d, Y') : date('M d, Y', strtotime('+7 days'));

        $custName = $customer->name ?? 'Walk-in Customer';
        $custPhone = $customer->phone ?? '—';
        $custAddress = $customer->address ?: ($order->shipping_address ?: 'Direct Counter Delivery');

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
            color: #334155;
            font-size: 11.5px;
            line-height: 1.45;
            background: #ffffff;
        }
    </style>
</head>

<body>

    <!-- Header Table: Logo/Company on Left, Title & Meta on Right -->
    @if(!$hideHeader)
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px;">
        <tr>
            <!-- Left: Company info -->
            <td style="width: 50%; vertical-align: middle;">
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ $team->displayName() }}</div>
                @if($team->formattedAddress())
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">{{ $team->formattedAddress() }}</div>
                @endif
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                    {{ collect([$team->phone, $team->email])->filter()->join(' · ') }}
                </div>
                @if($team->tax_id)
                    <div style="font-size: 9.5px; color: #2563eb; margin-top: 1px;">Tax Reg: <strong>{{ $team->tax_id }}</strong></div>
                @endif
            </td>

            <!-- Right: Title & Meta -->
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <table style="border-collapse: collapse; margin-left: auto; margin-right: 0;">
                    <tr>
                        <td style="font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: 1px; line-height: 1; padding: 0 0 3px 0; text-align: right;">INVOICE</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; font-weight: 700; color: #2563eb; padding: 0 0 8px 0; text-align: right;">{{ $orderNoFormatted }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <table style="border-collapse: collapse; margin-left: auto; margin-right: 0; width: 260px;">
                                <tr>
                                    <td style="width: 100px; padding: 2.5px 0; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; text-align: left; border-bottom: 1px solid #f1f5f9;">Date</td>
                                    <td style="padding: 2.5px 0; font-size: 11px; font-weight: 700; color: #0f172a; text-align: right; border-bottom: 1px solid #f1f5f9;">{{ $invoiceDate }}</td>
                                </tr>
                                <tr>
                                    <td style="width: 100px; padding: 2.5px 0; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; text-align: left; border-bottom: 1px solid #f1f5f9;">Payment Status</td>
                                    <td style="padding: 2.5px 0; font-size: 11px; font-weight: 700; color: {{ $isFullyPaid ? '#059669' : '#dc2626' }}; text-align: right; border-bottom: 1px solid #f1f5f9;">
                                        {{ $isFullyPaid ? 'PAID' : ($received > 0 ? 'PARTIAL' : 'UNPAID') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 100px; padding: 2.5px 0; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; text-align: left;">Warehouse Hub</td>
                                    <td style="padding: 2.5px 0; font-size: 11px; font-weight: 700; color: #0f172a; text-align: right;">{{ $order->warehouse->name ?? 'Main Hub' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @else
    <!-- Pre-printed Pad Header -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div style="font-size: 10px; color: #64748b;">Hub: <strong>{{ $order->warehouse->name ?? 'Main Hub' }}</strong></div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div style="font-size: 20px; font-weight: 900; color: #0f172a;">INVOICE</div>
                <div style="font-size: 12px; font-weight: 700; color: #2563eb;">{{ $orderNoFormatted }}</div>
                <div style="font-size: 10px; color: #64748b;">Date: {{ $invoiceDate }}</div>
            </td>
        </tr>
    </table>
    @endif

    <!-- 2-Column Addresses: Bill To & Ship To -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <!-- Column 1: Bill To -->
            <td style="width: 48%; vertical-align: top;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #2563eb; border-radius: 6px;">
                    <tr>
                        <td style="padding: 9px 12px;">
                            <div style="font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">
                                Bill To / Client
                            </div>
                            <div style="font-size: 12.5px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">{{ $custName }}</div>
                            <div style="font-size: 11px; color: #475569; line-height: 1.4;">
                                <div>{{ $custAddress }}</div>
                                <div style="margin-top: 2px; color: #334155;"><strong>Phone:</strong> {{ $custPhone }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>

            <td style="width: 4%;"></td>

            <!-- Column 2: Order & Delivery Meta -->
            <td style="width: 48%; vertical-align: top;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #64748b; border-radius: 6px;">
                    <tr>
                        <td style="padding: 9px 12px;">
                            <div style="font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">
                                Shipping &amp; Reference
                            </div>
                            <div style="font-size: 11.5px; color: #334155; line-height: 1.45;">
                                <div><strong>Destination:</strong> {{ $order->shipping_address ?: $custAddress }}</div>
                                <div><strong>Due Date:</strong> {{ $dueDate }}</div>
                                <div><strong>Issued By:</strong> {{ $order->creator->name ?? 'Sales Admin' }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Items Table: Minimalist Soft Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px;">
        <thead>
            <tr style="background-color: #f1f5f9; border-top: 1px solid #e2e8f0; border-bottom: 1.5px solid #cbd5e1;">
                <th style="text-align: left; padding: 7px 8px; font-size: 10.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; width: 55%;">Description</th>
                <th style="text-align: right; padding: 7px 8px; font-size: 10.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; width: 12%;">Qty</th>
                <th style="text-align: right; padding: 7px 8px; font-size: 10.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; width: 16%;">Unit price</th>
                <th style="text-align: right; padding: 7px 8px; font-size: 10.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; width: 17%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $pName = $item->product->name ?? ($item->name ?? 'Product');
                    $sku = $item->product->sku ?? ($item->sku ?? null);
                    $qty = $item->quantity ?? ($item->qty ?? 1);
                    $unitPrice = $item->unit_price ?? 0;
                    $totalPrice = $item->subtotal ?? ($unitPrice * $qty);
                @endphp
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 7px 8px; vertical-align: top;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">{{ $pName }}</div>
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
                    <td style="padding: 7px 8px; text-align: right; vertical-align: top; font-size: 11px; color: #334155;">{{ number_format($qty) }}</td>
                    <td style="padding: 7px 8px; text-align: right; vertical-align: top; font-size: 11px; color: #334155;">{{ $currency }}{{ number_format($unitPrice, 2) }}</td>
                    <td style="padding: 7px 8px; text-align: right; vertical-align: top; font-size: 11px; font-weight: 700; color: #0f172a;">{{ $currency }}{{ number_format($totalPrice, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 12px; color: #64748b;">No products listed.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Returned Items Section if any -->
    @if($hasReturns)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
            <thead>
                <tr style="border-bottom: 1.5px solid #f87171;">
                    <th style="text-align: left; padding: 5px 0; font-size: 10.5px; font-weight: 700; color: #b91c1c; width: 60%;">Returned Products (Refund Processed)</th>
                    <th style="text-align: right; padding: 5px 8px; font-size: 10.5px; font-weight: 700; color: #b91c1c; width: 15%;">Qty</th>
                    <th style="text-align: right; padding: 5px 0; font-size: 10.5px; font-weight: 700; color: #b91c1c; width: 25%;">Refund</th>
                </tr>
            </thead>
            <tbody>
                @foreach($completedReturns as $return)
                    @foreach($return->items as $retItem)
                        <tr style="border-bottom: 1px solid #fee2e2;">
                            <td style="padding: 5px 0; font-size: 10.5px; color: #374151;">&bull; {{ $retItem->product->name ?? 'Returned Item' }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 10.5px; color: #64748b;">{{ $retItem->quantity }}</td>
                            <td style="padding: 5px 0; text-align: right; font-size: 10.5px; font-weight: 700; color: #b91c1c;">-{{ $currency }}{{ number_format($retItem->total_price ?? ($retItem->unit_price * $retItem->quantity), 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Amount in Words & Summary Layout -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px;">
        <tr>
            <!-- Left Column: Notes & In Words -->
            <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #2563eb; border-radius: 6px; margin-bottom: 10px;">
                    <tr>
                        <td style="padding: 9px 12px;">
                            <div style="font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 3px;">Notes &amp; Policies</div>
                            <div style="font-size: 10.5px; color: #475569; line-height: 1.45;">
                                @if($order->notes)
                                    <div style="margin-bottom: 3px; color: #0f172a;"><strong>Order Note:</strong> {{ $order->notes }}</div>
                                @endif
                                <div>• Returns must be made in original condition within 7 days.</div>
                                <div>• Warranty requires this physical invoice for authorization.</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #f1f5f9; border: 1px solid #e2e8f0; border-left: 3px solid #2563eb; border-radius: 6px;">
                    <tr>
                        <td style="padding: 8px 12px; font-size: 10.5px; color: #334155;">
                            <strong style="text-transform: uppercase; font-size: 9.5px; letter-spacing: 0.5px; color: #2563eb; margin-right: 4px;">In Words:</strong>
                            <span style="font-weight: 700; color: #0f172a;">{{ $inWords }}</span>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Right Column: Financial Summary Table -->
            <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Subtotal:</td>
                        <td style="padding: 3px 0; text-align: right; color: #0f172a; font-weight: 700;">{{ $currency }}{{ number_format($subtotal, 2) }}</td>
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
                        <td style="padding: 3px 0; text-align: right; color: #dc2626; font-weight: 700;">-{{ $currency }}{{ number_format($discount, 2) }}</td>
                    </tr>
                    @endif
                    @if($hasReturns && $totalRefundAmount > 0)
                    <tr>
                        <td style="padding: 3px 0; color: #b91c1c;">Returned Refund:</td>
                        <td style="padding: 3px 0; text-align: right; color: #b91c1c; font-weight: 700;">-{{ $currency }}{{ number_format($totalRefundAmount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 5px 0; border-top: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 800; color: #0f172a;">Grand Total:</td>
                        <td style="padding: 5px 0; border-top: 1.5px solid #cbd5e1; text-align: right; font-size: 13.5px; font-weight: 800; color: #0f172a;">{{ $currency }}{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                    @if($tendered > 0 && ($tendered > $received || $change > 0))
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Cash Tendered:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 600; color: #0f172a;">{{ $currency }}{{ number_format($tendered, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 3px 0; color: #475569;">Amount Paid:</td>
                        <td style="padding: 3px 0; text-align: right; color: #059669; font-weight: 700;">{{ $currency }}{{ number_format($received, 2) }}</td>
                    </tr>
                    @if($change > 0)
                    <tr>
                        <td style="padding: 3px 0; font-weight: 700; color: #059669;">Change Return:</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: 700; color: #059669;">{{ $currency }}{{ number_format($change, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 4px 0; border-top: 1px solid #e2e8f0; font-size: 12px; font-weight: 700; color: #dc2626;">Due Balance:</td>
                        <td style="padding: 4px 0; border-top: 1px solid #e2e8f0; text-align: right; font-size: 12.5px; font-weight: 800; color: {{ $due > 0 ? '#dc2626' : '#16a34a' }};">
                            {{ $currency }}{{ number_format($due, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Dual Signatures -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 45px; margin-bottom: 6px;">
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
                        <td style="text-align: center; padding-top: 5px; font-size: 10.5px; font-weight: 700; color: #2563eb;">Authorized Signature</td>
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
                    <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $team->displayName() }}</div>
                </td>
                <td style="width: 50%; vertical-align: middle; text-align: right;">
                    <div style="font-size: 11px; font-weight: 700; color: #2563eb;">{{ $orderNoFormatted }}</div>
                    <div style="font-size: 10px; color: #64748b;">Page {PAGENO} of {nbpg}</div>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <!-- Pinned Page Footer for mPDF -->
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
