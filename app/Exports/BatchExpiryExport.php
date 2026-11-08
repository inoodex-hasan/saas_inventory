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

class BatchExpiryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $teamId;
    protected ?string $status;
    protected ?string $warehouseId;
    protected ?string $search;

    public function __construct(int $teamId, ?string $status = null, ?string $warehouseId = null, ?string $search = null)
    {
        $this->teamId = $teamId;
        $this->status = $status;
        $this->warehouseId = $warehouseId;
        $this->search = $search;
    }

    public function collection(): Collection
    {
        $query = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
            ->leftJoin('warehouses', 'purchase_orders.warehouse_id', '=', 'warehouses.id')
            ->where('purchase_orders.team_id', $this->teamId)
            ->whereNotNull('purchase_order_items.expiry_date')
            ->select([
                'purchase_order_items.batch_number',
                'products.sku',
                'products.name as product_name',
                'warehouses.name as warehouse_name',
                DB::raw('COALESCE(purchase_order_items.received_quantity, purchase_order_items.quantity, 0) as quantity_received'),
                'purchase_order_items.unit_cost',
                'purchase_order_items.manufacture_date',
                'purchase_order_items.expiry_date',
            ])
            ->orderBy('purchase_order_items.expiry_date');

        if (!empty($this->warehouseId)) {
            $query->where('purchase_orders.warehouse_id', $this->warehouseId);
        }

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_order_items.batch_number', 'like', $term)
                  ->orWhere('products.name', 'like', $term)
                  ->orWhere('products.sku', 'like', $term);
            });
        }

        $now = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in90Days = now()->addDays(90)->toDateString();

        if ($this->status === 'expired') {
            $query->where('purchase_order_items.expiry_date', '<', $now);
        } elseif ($this->status === 'expiring_30') {
            $query->whereBetween('purchase_order_items.expiry_date', [$now, $in30Days]);
        } elseif ($this->status === 'expiring_90') {
            $query->whereBetween('purchase_order_items.expiry_date', [$now, $in90Days]);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Batch #',
            'SKU',
            'Product Name',
            'Warehouse',
            'Qty Received',
            'Unit Cost',
            'Total Value',
            'Mfg Date',
            'Expiry Date',
            'Days Remaining',
            'Expiry Status',
        ];
    }

    /**
     * @param object $row
     */
    public function map($row): array
    {
        $expiry = \Carbon\Carbon::parse($row->expiry_date)->startOfDay();
        $today = now()->startOfDay();
        $diff = (int) $today->diffInDays($expiry, false);

        if ($diff < 0) {
            $statusLabel = 'EXPIRED';
            $daysText = abs($diff) . ' days ago';
        } elseif ($diff <= 30) {
            $statusLabel = 'CRITICAL (< 30 days)';
            $daysText = $diff . ' days left';
        } elseif ($diff <= 90) {
            $statusLabel = 'WARNING (< 90 days)';
            $daysText = $diff . ' days left';
        } else {
            $statusLabel = 'HEALTHY';
            $daysText = $diff . ' days left';
        }

        $qty = (float) $row->quantity_received;
        $cost = (float) $row->unit_cost;

        return [
            $row->batch_number ?? '—',
            $row->sku,
            $row->product_name,
            $row->warehouse_name ?? '—',
            $qty,
            $cost,
            round($qty * $cost, 2),
            $row->manufacture_date ?? '—',
            $row->expiry_date,
            $daysText,
            $statusLabel,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E11D48'], // Rose-600
                ],
            ],
        ];
    }
}
