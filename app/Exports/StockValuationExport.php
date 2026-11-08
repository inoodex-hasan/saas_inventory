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

class StockValuationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?string $warehouseId;
    protected ?string $categoryId;

    public function __construct(int $teamId, ?string $warehouseId = null, ?string $categoryId = null)
    {
        $this->teamId = $teamId;
        $this->warehouseId = $warehouseId;
        $this->categoryId = $categoryId;
    }

    public function collection(): Collection
    {
        $query = DB::table('stock_levels')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_levels.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('stock_levels.team_id', $this->teamId)
            ->select([
                'products.sku',
                'products.name as product_name',
                'product_categories.name as category_name',
                'warehouses.name as warehouse_name',
                'products.unit',
                'stock_levels.quantity',
                'products.cost_price',
                'products.unit_price',
            ])
            ->orderBy('warehouses.name')
            ->orderBy('products.name');

        if (!empty($this->warehouseId)) {
            $query->where('stock_levels.warehouse_id', $this->warehouseId);
        }

        if (!empty($this->categoryId)) {
            $query->where('products.category_id', $this->categoryId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Category',
            'Warehouse',
            'Unit',
            'Quantity on Hand',
            'Unit Cost',
            'Unit Retail Price',
            'Total Cost Value',
            'Total Retail Value',
            'Potential Profit ($)',
            'Margin (%)',
        ];
    }

    /**
     * @param object $row
     */
    public function map($row): array
    {
        $qty = (float) $row->quantity;
        $cost = (float) $row->cost_price;
        $retail = (float) $row->unit_price;
        $totalCost = $qty * $cost;
        $totalRetail = $qty * $retail;
        $profit = max(0, $totalRetail - $totalCost);
        $marginPct = $totalRetail > 0 ? round(($profit / $totalRetail) * 100, 2) : 0;

        return [
            $row->sku,
            $row->product_name,
            $row->category_name ?? 'Uncategorized',
            $row->warehouse_name,
            $row->unit ?? 'pcs',
            $qty,
            $cost,
            $retail,
            round($totalCost, 2),
            round($totalRetail, 2),
            round($profit, 2),
            $marginPct . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo-600
                ],
            ],
        ];
    }
}

