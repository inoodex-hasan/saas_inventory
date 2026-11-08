<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAgingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?string $bracket;
    protected ?string $categoryId;
    protected ?string $search;

    public function __construct(int $teamId, ?string $bracket = null, ?string $categoryId = null, ?string $search = null)
    {
        $this->teamId = $teamId;
        $this->bracket = $bracket;
        $this->categoryId = $categoryId;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $products = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->join('stock_levels', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.team_id', $this->teamId)
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit', 'products.cost_price', 'products.unit_price', 'products.created_at', 'product_categories.name')
            ->selectRaw("
                products.id,
                products.sku,
                products.name,
                products.unit,
                products.cost_price,
                products.unit_price,
                products.created_at,
                product_categories.name as category_name,
                SUM(stock_levels.quantity) as total_stock,
                (
                    SELECT MAX(sm.created_at)
                    FROM stock_movements sm
                    WHERE sm.product_id = products.id
                      AND sm.team_id = {$this->teamId}
                      AND sm.type = 'out'
                ) as last_sale_at,
                (
                    SELECT MAX(sm2.created_at)
                    FROM stock_movements sm2
                    WHERE sm2.product_id = products.id
                      AND sm2.team_id = {$this->teamId}
                ) as last_movement_at
            ")
            ->havingRaw('SUM(stock_levels.quantity) > 0')
            ->orderBy('products.name');

        if (!empty($this->categoryId)) {
            $products->where('products.category_id', $this->categoryId);
        }

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $products->where(function ($q) use ($term) {
                $q->where('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $items = $products->get();
        $now = now();

        $processed = $items->map(function ($item) use ($now) {
            $referenceDate = $item->last_sale_at
                ? \Carbon\Carbon::parse($item->last_sale_at)
                : ($item->last_movement_at ? \Carbon\Carbon::parse($item->last_movement_at) : \Carbon\Carbon::parse($item->created_at));

            $daysInactive = (int) $referenceDate->diffInDays($now);

            if ($daysInactive <= 30) {
                $bracketKey = 'fresh';
                $bracketLabel = 'Fresh (0-30 days)';
            } elseif ($daysInactive <= 60) {
                $bracketKey = 'moderate';
                $bracketLabel = 'Moderate (31-60 days)';
            } elseif ($daysInactive <= 90) {
                $bracketKey = 'slow';
                $bracketLabel = 'Slow-Moving (61-90 days)';
            } else {
                $bracketKey = 'dead';
                $bracketLabel = 'Dead Stock (90+ days)';
            }

            $qty = (float) $item->total_stock;
            $cost = (float) $item->cost_price;
            $retail = (float) $item->unit_price;

            return (object) [
                'sku' => $item->sku,
                'name' => $item->name,
                'category_name' => $item->category_name ?? 'Uncategorized',
                'unit' => $item->unit ?? 'pcs',
                'total_stock' => $qty,
                'cost_price' => $cost,
                'retail_price' => $retail,
                'total_cost_value' => round($qty * $cost, 2),
                'total_retail_value' => round($qty * $retail, 2),
                'days_inactive' => $daysInactive,
                'last_activity_date' => $referenceDate->format('Y-m-d'),
                'bracket_key' => $bracketKey,
                'bracket_label' => $bracketLabel,
            ];
        });

        if (!empty($this->bracket) && $this->bracket !== 'all') {
            $processed = $processed->filter(fn ($i) => $i->bracket_key === $this->bracket)->values();
        }

        return $processed;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Category',
            'Stock on Hand',
            'Unit Cost',
            'Unit Retail Price',
            'Frozen Capital ($)',
            'Potential Sales ($)',
            'Days Inactive',
            'Last Activity Date',
            'Aging Bracket',
        ];
    }

    /**
     * @param object $row
     */
    public function map($row): array
    {
        return [
            $row->sku,
            $row->name,
            $row->category_name,
            $row->total_stock,
            $row->cost_price,
            $row->retail_price,
            $row->total_cost_value,
            $row->total_retail_value,
            $row->days_inactive,
            $row->last_activity_date,
            $row->bracket_label,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0284C7'], // Sky-600
                ],
            ],
        ];
    }
}

