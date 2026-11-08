<?php

namespace App\Exports;

use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?string $type;
    protected ?string $warehouseId;
    protected ?string $search;
    protected $startDate;
    protected $endDate;

    public function __construct(
        int $teamId,
        ?string $type = null,
        ?string $warehouseId = null,
        ?string $search = null,
        $startDate = null,
        $endDate = null
    ) {
        $this->teamId = $teamId;
        $this->type = $type;
        $this->warehouseId = $warehouseId;
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): Collection
    {
        $query = StockMovement::with(['product', 'warehouse', 'creator'])
            ->where('team_id', $this->teamId)
            ->orderByDesc('created_at');

        if (!empty($this->type)) {
            $query->where('type', $this->type);
        }

        if (!empty($this->warehouseId)) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', $term)
                  ->orWhere('reference_type', 'like', $term)
                  ->orWhereHas('product', function ($pq) use ($term) {
                      $pq->where('name', 'like', $term)
                         ->orWhere('sku', 'like', $term);
                  });
            });
        }

        return $query->limit(1000)->get();
    }

    public function headings(): array
    {
        return [
            'Date & Time',
            'Movement Type',
            'SKU',
            'Product Name',
            'Warehouse',
            'Quantity Changed',
            'Reference Type',
            'Reason / Notes',
            'Recorded By',
        ];
    }

    /**
     * @param StockMovement $movement
     */
    public function map($movement): array
    {
        return [
            $movement->created_at ? $movement->created_at->format('Y-m-d H:i') : '—',
            strtoupper($movement->type),
            $movement->product?->sku ?? '—',
            $movement->product?->name ?? '—',
            $movement->warehouse?->name ?? '—',
            (float) $movement->quantity,
            $movement->reference_type ?? 'Manual',
            $movement->reason ?? '—',
            $movement->creator?->name ?? 'System',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F172A'], // Slate-900
                ],
            ],
        ];
    }
}

