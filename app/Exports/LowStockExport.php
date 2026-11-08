<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LowStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?string $search;

    public function __construct(int $teamId, ?string $search = null)
    {
        $this->teamId = $teamId;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $query = Product::with(['category', 'stockLevels'])
            ->where('team_id', $this->teamId)
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_levels WHERE stock_levels.product_id = products.id) <= products.reorder_level');

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            });
        }

        return $query->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Category',
            'Unit',
            'Current Stock',
            'Reorder Threshold',
            'Deficit / Shortfall',
            'Unit Cost Price',
            'Estimated Restock Cost',
        ];
    }

    /**
     * @param Product $product
     */
    public function map($product): array
    {
        $currentStock = (float) $product->stockLevels->sum('quantity');
        $reorderLvl = (float) $product->reorder_level;
        $deficit = max(0, $reorderLvl - $currentStock);
        $cost = (float) $product->cost_price;
        $restockCost = $deficit * $cost;

        return [
            $product->sku,
            $product->name,
            $product->category?->name ?? 'Uncategorized',
            $product->unit ?? 'pcs',
            $currentStock,
            $reorderLvl,
            $deficit,
            $cost,
            round($restockCost, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D97706'], // Amber-600
                ],
            ],
        ];
    }
}

