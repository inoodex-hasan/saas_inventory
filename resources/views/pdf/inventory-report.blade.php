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

</body>
</html>

