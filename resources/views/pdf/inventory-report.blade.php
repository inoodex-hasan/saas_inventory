<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            @if($bgPath = $team->getReportBgAbsolutePath())
                background: url('{{ $bgPath }}') no-repeat 0 0;
                background-image-resize: 6;
            @endif
            margin-top: {{ $team->report_bg_path ? ($team->report_margin_top ?? 42) : 12 }}mm;
            margin-bottom: {{ $team->report_bg_path ? ($team->report_margin_bottom ?? 25) : 12 }}mm;
            margin-left: 12mm;
            margin-right: 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9pt;
            line-height: 1.4;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 15pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: 900;
            color: #4338ca;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-meta {
            font-size: 8pt;
            color: #64748b;
            text-align: right;
            margin-top: 3px;
        }

        /* KPI Grid Table */
        .kpi-table {
            margin-top: 14px;
            margin-bottom: 14px;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: left;
        }
        .kpi-title {
            font-size: 7.5pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-value {
            font-size: 12pt;
            font-weight: 900;
            color: #0f172a;
            margin-top: 3px;
        }
        .kpi-sub {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Data Tables */
        .section-header {
            margin-top: 14px;
            margin-bottom: 6px;
            font-size: 10pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid #4338ca;
            padding-bottom: 3px;
        }
        .data-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            font-weight: bold;
            text-align: left;
        }
        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .total-row td {
            background-color: #f1f5f9 !important;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            font-weight: 800;
            font-size: 8.5pt;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-in { background-color: #dcfce7; color: #15803d; }
        .badge-out { background-color: #fee2e2; color: #b91c1c; }
        .badge-transfer { background-color: #e0e7ff; color: #4338ca; }
        .badge-adjustment { background-color: #fef3c7; color: #b45309; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-slate-500 { color: #64748b; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if(!$team->report_bg_path)
                    <div class="company-name">{{ $team->name }}</div>
                    @if($team->company_address)
                        <div class="company-sub">{{ $team->company_address }}</div>
                    @endif
                    @if($team->company_phone || $team->company_email)
                        <div class="company-sub">
                            {{ $team->company_phone ? 'Phone: ' . $team->company_phone : '' }}
                            {{ $team->company_phone && $team->company_email ? ' • ' : '' }}
                            {{ $team->company_email ? 'Email: ' . $team->company_email : '' }}
                        </div>
                    @endif
                @endif
            </td>
            <td style="width: 45%;">
                <div class="doc-title">{{ $reportTitle }}</div>
                <div class="doc-meta">Generated: {{ $generatedAt }}</div>
                <div class="doc-meta">Active Module: <strong>{{ strtoupper(str_replace('_', ' ', $tab)) }}</strong></div>
            </td>
        </tr>
    </table>

    {{-- TAB 1: STOCK VALUATION --}}
    @if($tab === 'valuation' && $valuation)
        <table class="kpi-table">
            <tr>
                <td style="width: 25%; padding-right: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Total Units On Hand</div>
                        <div class="kpi-value">{{ number_format($valuation['total_units']) }}</div>
                        <div class="kpi-sub">Across all warehouses</div>
                    </div>
                </td>
                <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Asset Cost Value</div>
                        <div class="kpi-value">{{ $currencySymbol }}{{ number_format($valuation['total_cost'], 2) }}</div>
                        <div class="kpi-sub">Total inventory asset basis</div>
                    </div>
                </td>
                <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Retail Asset Value</div>
                        <div class="kpi-value">{{ $currencySymbol }}{{ number_format($valuation['total_retail'], 2) }}</div>
                        <div class="kpi-sub">Expected sales revenue</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Potential Gross Profit</div>
                        <div class="kpi-value" style="color: #15803d;">{{ $currencySymbol }}{{ number_format($valuation['potential_profit'], 2) }}</div>
                        <div class="kpi-sub">{{ number_format($valuation['margin_percent'], 1) }}% potential margin</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Warehouse Breakdown -->
        <div class="section-header">Warehouse Facility Allocation</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Warehouse Facility</th>
                    <th style="width: 15%; text-align: center;">Code</th>
                    <th style="width: 15%;" class="text-right">Units Count</th>
                    <th style="width: 17%;" class="text-right">Cost Value</th>
                    <th style="width: 18%;" class="text-right">Retail Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($valuation['warehouse_breakdown'] as $wh)
                    <tr>
                        <td class="font-bold">{{ $wh->name }}</td>
                        <td class="text-center font-mono">{{ $wh->code }}</td>
                        <td class="text-right font-mono">{{ number_format($wh->units_count) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($wh->cost_value, 2) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($wh->retail_value, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500">No warehouse stock data recorded.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">TOTAL ACROSS ALL FACILITIES</td>
                    <td class="text-right font-mono">{{ number_format($valuation['total_units']) }}</td>
                    <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($valuation['total_cost'], 2) }}</td>
                    <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($valuation['total_retail'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Category Breakdown -->
        <div class="section-header" style="margin-top: 20px;">Product Category Asset Breakdown</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Category Name</th>
                    <th style="width: 15%;" class="text-right">Units In Stock</th>
                    <th style="width: 20%;" class="text-right">Total Cost Value</th>
                    <th style="width: 20%;" class="text-right">Total Retail Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($valuation['category_breakdown'] as $cat)
                    <tr>
                        <td class="font-bold">{{ $cat->category_name }}</td>
                        <td class="text-right font-mono">{{ number_format($cat->units_count) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($cat->cost_value, 2) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($cat->retail_value, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-500">No category breakdown data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- TAB 2: LOW STOCK RADAR --}}
    @if($tab === 'low_stock' && $lowStockItems)
        @php
            $totalDeficit = array_sum(array_column($lowStockItems, 'deficit'));
            $totalRestockCost = array_sum(array_column($lowStockItems, 'estimated_restock_cost'));
        @endphp
        <table class="kpi-table">
            <tr>
                <td style="width: 33.3%; padding-right: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Items Breaching Minimum Threshold</div>
                        <div class="kpi-value" style="color: #b91c1c;">{{ count($lowStockItems) }} Products</div>
                        <div class="kpi-sub">Immediate reorder recommended</div>
                    </div>
                </td>
                <td style="width: 33.3%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Total Units Deficit</div>
                        <div class="kpi-value">{{ number_format($totalDeficit) }} Units</div>
                        <div class="kpi-sub">Units needed to restore safety stock</div>
                    </div>
                </td>
                <td style="width: 33.3%; padding-left: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Est. Procurement Capital Needed</div>
                        <div class="kpi-value">{{ $currencySymbol }}{{ number_format($totalRestockCost, 2) }}</div>
                        <div class="kpi-sub">At current vendor cost basis</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-header">Reorder Alert Schedule</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">SKU</th>
                    <th style="width: 30%;">Product Name</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 10%;" class="text-right">Current Stock</th>
                    <th style="width: 10%;" class="text-right">Reorder Level</th>
                    <th style="width: 10%;" class="text-right">Deficit</th>
                    <th style="width: 10%;" class="text-right">Est. Restock Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lowStockItems as $item)
                    <tr>
                        <td class="font-mono font-bold">{{ $item['product']['sku'] }}</td>
                        <td>{{ $item['product']['name'] }}</td>
                        <td class="text-slate-500">{{ $item['product']['category']['name'] ?? '—' }}</td>
                        <td class="text-right font-mono" style="color: #b91c1c; font-weight: bold;">
                            {{ number_format($item['current_stock']) }} {{ $item['product']['unit'] ?? '' }}
                        </td>
                        <td class="text-right font-mono">{{ number_format($item['reorder_level']) }}</td>
                        <td class="text-right font-mono font-bold text-center">
                            <span class="badge badge-danger">-{{ number_format($item['deficit']) }}</span>
                        </td>
                        <td class="text-right font-mono font-bold">
                            {{ $currencySymbol }}{{ number_format($item['estimated_restock_cost'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">All product inventories are currently above minimum safety thresholds!</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($lowStockItems) > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5">TOTAL DEFICIT & RESTOCK INVESTMENT</td>
                        <td class="text-right font-mono">{{ number_format($totalDeficit) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($totalRestockCost, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    {{-- TAB 3: STOCK MOVEMENTS --}}
    @if($tab === 'movements' && $movements)
        <div class="section-header">Inventory Audit Trail Log (Latest {{ count($movements) }} Records)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 13%;">Timestamp</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 25%;">Product / SKU</th>
                    <th style="width: 15%;">Warehouse</th>
                    <th style="width: 10%;" class="text-right">Qty</th>
                    <th style="width: 13%;">Reference / User</th>
                    <th style="width: 12%;">Reason / Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="font-mono" style="font-size: 7.5pt;">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            @if($m->type === 'purchase_received' || $m->type === 'return_restocked')
                                <span class="badge badge-in">+ IN</span>
                            @elseif($m->type === 'sale_deducted' || $m->type === 'damaged')
                                <span class="badge badge-out">- OUT</span>
                            @elseif($m->type === 'transfer_in' || $m->type === 'transfer_out')
                                <span class="badge badge-transfer">TRANS</span>
                            @else
                                <span class="badge badge-adjustment">{{ strtoupper(substr($m->type, 0, 8)) }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="font-bold">{{ $m->product->name ?? 'Deleted Item' }}</div>
                            <div class="text-slate-500 font-mono" style="font-size: 7pt;">SKU: {{ $m->product->sku ?? 'N/A' }}</div>
                        </td>
                        <td>{{ $m->warehouse->name ?? '—' }}</td>
                        <td class="text-right font-mono font-bold">
                            {{ $m->quantity > 0 ? '+' : '' }}{{ number_format($m->quantity) }}
                        </td>
                        <td style="font-size: 7.5pt;">
                            <div>{{ $m->reference_type ? class_basename($m->reference_type) . ' #' . $m->reference_id : 'Manual Entry' }}</div>
                            <div class="text-slate-500">{{ $m->creator->name ?? 'System' }}</div>
                        </td>
                        <td class="text-slate-500" style="font-size: 7.5pt;">{{ $m->reason ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No stock movements found matching filter criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- TAB 4: BATCH & EXPIRY RADAR --}}
    @if($tab === 'expiry' && $expiryData)
        <table class="kpi-table">
            <tr>
                <td style="width: 25%; padding-right: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #e11d48;">
                        <div class="kpi-title" style="color: #e11d48;">Expired Batches</div>
                        <div class="kpi-value" style="color: #e11d48;">{{ number_format($expiryData['summary']['expired_count']) }}</div>
                        <div class="kpi-sub">Immediate write-off / scrap</div>
                    </div>
                </td>
                <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #ea580c;">
                        <div class="kpi-title" style="color: #ea580c;">Expiring (&lt; 30 Days)</div>
                        <div class="kpi-value" style="color: #ea580c;">{{ number_format($expiryData['summary']['expiring_30_count']) }}</div>
                        <div class="kpi-sub">Priority liquidation / promo</div>
                    </div>
                </td>
                <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #d97706;">
                        <div class="kpi-title" style="color: #d97706;">Warning (&lt; 90 Days)</div>
                        <div class="kpi-value" style="color: #d97706;">{{ number_format($expiryData['summary']['expiring_90_count']) }}</div>
                        <div class="kpi-sub">Monitor inventory turnover</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px;">
                    <div class="kpi-card">
                        <div class="kpi-title">Capital at Risk</div>
                        <div class="kpi-value">{{ $currencySymbol }}{{ number_format($expiryData['summary']['at_risk_cost'], 2) }}</div>
                        <div class="kpi-sub">Cost in expired/urgent items</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-header">Batch Expiry Tracking &amp; Shelf Life Audit</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Batch #</th>
                    <th style="width: 28%;">Product / SKU</th>
                    <th style="width: 16%;">Warehouse</th>
                    <th style="width: 10%; text-align: right;">Quantity</th>
                    <th style="width: 12%; text-align: right;">Unit Cost</th>
                    <th style="width: 10%; text-align: center;">Expiry Date</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expiryData['items'] as $item)
                    <tr>
                        <td class="font-mono font-bold">{{ $item['batch_number'] }}</td>
                        <td>
                            <div class="font-bold">{{ $item['product_name'] }}</div>
                            <div class="text-slate-500 font-mono" style="font-size: 7pt;">SKU: {{ $item['sku'] }}</div>
                        </td>
                        <td>{{ $item['warehouse_name'] }}</td>
                        <td class="text-right font-mono font-bold">{{ number_format($item['quantity']) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($item['unit_cost'], 2) }}</td>
                        <td class="text-center font-mono">{{ $item['expiry_date'] }}</td>
                        <td class="text-center">
                            @if($item['status_key'] === 'expired')
                                <span class="badge badge-danger">EXPIRED</span>
                            @elseif($item['status_key'] === 'expiring_30')
                                <span class="badge badge-adjustment">URGENT</span>
                            @elseif($item['status_key'] === 'expiring_90')
                                <span class="badge badge-transfer">&lt; 90D</span>
                            @else
                                <span class="badge badge-in">OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No batch expiry records found matching filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- TAB 5: STOCK AGING & DEAD STOCK --}}
    @if($tab === 'aging' && $agingData)
        <table class="kpi-table">
            <tr>
                <td style="width: 25%; padding-right: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #16a34a;">
                        <div class="kpi-title" style="color: #16a34a;">Fresh (0-30 Days)</div>
                        <div class="kpi-value" style="color: #16a34a;">{{ number_format($agingData['summary']['fresh_count']) }}</div>
                        <div class="kpi-sub">Active turnover items</div>
                    </div>
                </td>
                <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #0284c7;">
                        <div class="kpi-title" style="color: #0284c7;">Moderate (31-60 Days)</div>
                        <div class="kpi-value" style="color: #0284c7;">{{ number_format($agingData['summary']['moderate_count']) }}</div>
                        <div class="kpi-sub">Normal inventory cycle</div>
                    </div>
                </td>
                <td style="width: 25%; padding-right: 5px; padding-left: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #d97706;">
                        <div class="kpi-title" style="color: #d97706;">Slow-Moving (61-90 Days)</div>
                        <div class="kpi-value" style="color: #d97706;">{{ number_format($agingData['summary']['slow_count']) }}</div>
                        <div class="kpi-sub">Stagnation warning</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #e11d48;">
                        <div class="kpi-title" style="color: #e11d48;">Dead Stock (90+ Days)</div>
                        <div class="kpi-value" style="color: #e11d48;">{{ number_format($agingData['summary']['dead_count']) }}</div>
                        <div class="kpi-sub">Frozen: {{ $currencySymbol }}{{ number_format($agingData['summary']['dead_stock_cost'], 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-header">Inventory Aging &amp; Dormant Capital Analysis</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 14%;">SKU</th>
                    <th style="width: 28%;">Product Name</th>
                    <th style="width: 16%;">Category</th>
                    <th style="width: 10%; text-align: right;">Stock Qty</th>
                    <th style="width: 12%; text-align: right;">Frozen Capital</th>
                    <th style="width: 10%; text-align: center;">Days Inactive</th>
                    <th style="width: 10%; text-align: center;">Aging Bracket</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agingData['items'] as $item)
                    <tr>
                        <td class="font-mono font-bold">{{ $item['sku'] }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['category_name'] }}</td>
                        <td class="text-right font-mono font-bold">{{ number_format($item['total_stock']) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($item['total_cost_value'], 2) }}</td>
                        <td class="text-center font-mono font-bold">{{ $item['days_inactive'] }}d</td>
                        <td class="text-center">
                            @if($item['bracket_key'] === 'dead')
                                <span class="badge badge-danger">DEAD STOCK</span>
                            @elseif($item['bracket_key'] === 'slow')
                                <span class="badge badge-adjustment">SLOW</span>
                            @elseif($item['bracket_key'] === 'moderate')
                                <span class="badge badge-transfer">MODERATE</span>
                            @else
                                <span class="badge badge-in">FRESH</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No stock aging records found matching filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- TAB 6: SALES & PRODUCT PROFITABILITY --}}
    @if($tab === 'profitability' && $profitabilityData)
        <table class="kpi-table">
            <tr>
                <td style="width: 25%; padding-right: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #059669;">
                        <div class="kpi-title" style="color: #059669;">Total Sales Revenue</div>
                        <div class="kpi-value" style="color: #065f46;">{{ $currencySymbol }}{{ number_format($profitabilityData['summary']['total_revenue'], 2) }}</div>
                        <div class="kpi-sub">Across active sales orders</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px; padding-right: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #475569;">
                        <div class="kpi-title">Cost of Goods Sold (COGS)</div>
                        <div class="kpi-value">{{ $currencySymbol }}{{ number_format($profitabilityData['summary']['total_cogs'], 2) }}</div>
                        <div class="kpi-sub">Product acquisition cost basis</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px; padding-right: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #4f46e5;">
                        <div class="kpi-title" style="color: #4f46e5;">Gross Profit Margin</div>
                        <div class="kpi-value" style="color: #3730a3;">{{ $currencySymbol }}{{ number_format($profitabilityData['summary']['total_gross_profit'], 2) }}</div>
                        <div class="kpi-sub">Overall Margin: {{ $profitabilityData['summary']['overall_margin_percent'] }}%</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px;">
                    <div class="kpi-card" style="border-left: 3px solid #0284c7;">
                        <div class="kpi-title">High Margin Catalog (> 40%)</div>
                        <div class="kpi-value">{{ number_format($profitabilityData['summary']['high_count']) }}</div>
                        <div class="kpi-sub">Low/Loss skus: {{ $profitabilityData['summary']['low_count'] + $profitabilityData['summary']['loss_count'] }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-header">Product Sales, COGS &amp; Gross Profit Margin Breakdown</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 13%;">SKU</th>
                    <th style="width: 25%;">Product Name</th>
                    <th style="width: 14%;">Category</th>
                    <th style="width: 10%; text-align: right;">Qty Sold</th>
                    <th style="width: 12%; text-align: right;">Revenue</th>
                    <th style="width: 12%; text-align: right;">COGS</th>
                    <th style="width: 14%; text-align: right;">Gross Profit (Margin)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profitabilityData['items'] as $item)
                    <tr>
                        <td class="font-mono font-bold">{{ $item['sku'] }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td>{{ $item['category_name'] }}</td>
                        <td class="text-right font-mono font-bold">{{ number_format($item['units_sold']) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($item['total_revenue'], 2) }}</td>
                        <td class="text-right font-mono text-slate-500">{{ $currencySymbol }}{{ number_format($item['total_cogs'], 2) }}</td>
                        <td class="text-right font-mono font-bold" style="color: {{ $item['gross_profit'] >= 0 ? '#059669' : '#e11d48' }};">
                            {{ $currencySymbol }}{{ number_format($item['gross_profit'], 2) }}
                            <span style="font-size: 8px; font-weight: normal; color: #64748b;">({{ $item['margin_percent'] }}%)</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No sales transactions found matching filters.</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($profitabilityData['items']) > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4">PORTFOLIO TOTALS</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($profitabilityData['summary']['total_revenue'], 2) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($profitabilityData['summary']['total_cogs'], 2) }}</td>
                        <td class="text-right font-mono">{{ $currencySymbol }}{{ number_format($profitabilityData['summary']['total_gross_profit'], 2) }} ({{ $profitabilityData['summary']['overall_margin_percent'] }}%)</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

</body>
</html>

