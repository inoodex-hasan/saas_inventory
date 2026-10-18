<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;

    public function __construct(int $teamId)
    {
        $this->teamId = $teamId;
    }

    public function query()
    {
        return Product::query()
            ->where('team_id', $this->teamId)
            ->with(['category', 'brand'])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Category',
            'Brand',
            'Barcode',
            'Unit',
            'Cost Price',
            'Selling Price',
            'Reorder Level',
            'Warranty Days',
            'Total Stock',
            'Status',
        ];
    }

    /**
     * @param Product $product
     */
    public function map($product): array
    {
        return [
            $product->sku,
            $product->name,
            $product->category?->name ?? 'Uncategorized',
            $product->brand?->name ?? '—',
            $product->barcode ?? '—',
            $product->unit ?? 'pcs',
            (float) $product->cost_price,
            (float) $product->selling_price,
            $product->reorder_level ?? 0,
            $product->warranty_days ?? 0,
            (float) $product->total_stock,
            $product->is_active ? 'Active' : 'Inactive',
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
