<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductProfitabilityExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?string $performance;
    protected ?string $categoryId;
    protected ?string $search;
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;

    public function __construct(
        int $teamId,
        ?string $performance = null,
        ?string $categoryId = null,
        ?string $search = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ) {
        $this->teamId = $teamId;
        $this->performance = $performance;
        $this->categoryId = $categoryId;
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): Collection
    {
        $query = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('sales_orders.team_id', $this->teamId)
            ->whereNotIn('sales_orders.status', ['cancelled', 'draft'])
            ->groupBy('products.id', 'products.sku', 'products.name', 'product_categories.name')
            ->selectRaw("
                products.id,
                products.sku,
                products.name as product_name,
                COALESCE(product_categories.name, 'Uncategorized') as category_name,
                SUM(sales_order_items.quantity) as units_sold,
                SUM(sales_order_items.subtotal) as total_revenue,
                SUM(sales_order_items.quantity * COALESCE(sales_order_items.unit_cost, products.cost_price, 0)) as total_cogs
            ")
            ->orderByDesc('total_revenue');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('sales_orders.order_date', [
                $this->startDate->toDateString(),
                $this->endDate->toDateString(),
            ]);
        }

        if (!empty($this->categoryId)) {
            $query->where('products.category_id', $this->categoryId);
        }

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $records = $query->get();

        $processed = $records->map(function ($row) {
            $revenue = (float) $row->total_revenue;
            $cogs = (float) $row->total_cogs;
            $profit = round($revenue - $cogs, 2);
            $marginPercent = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

            if ($marginPercent >= 40) {
                $perfKey = 'high';
                $perfLabel = 'High Margin (>= 40%)';
            } elseif ($marginPercent >= 20) {
                $perfKey = 'healthy';
                $perfLabel = 'Healthy (20-39%)';
            } elseif ($marginPercent > 0) {
                $perfKey = 'low';
                $perfLabel = 'Low Margin (1-19%)';
            } else {
                $perfKey = 'loss';
                $perfLabel = 'Loss Making (<= 0%)';
            }

            $row->gross_profit = $profit;
            $row->margin_percent = $marginPercent;
            $row->perf_key = $perfKey;
            $row->perf_label = $perfLabel;

            return $row;
        });

        if (!empty($this->performance) && $this->performance !== 'all') {
            $processed = $processed->filter(fn ($item) => $item->perf_key === $this->performance)->values();
        }

        return $processed;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Category',
            'Units Sold',
            'Total Revenue',
            'Total COGS',
            'Gross Profit',
            'Gross Margin %',
            'Performance Status',
        ];
    }

    /**
     * @param object $row
     */
    public function map($row): array
    {
        return [
            $row->sku,
            $row->product_name,
            $row->category_name,
            (float) $row->units_sold,
            (float) $row->total_revenue,
            (float) $row->total_cogs,
            $row->gross_profit,
            $row->margin_percent . '%',
            $row->perf_label,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'], // Emerald-600
                ],
            ],
        ];
    }
}

