<?php

namespace App\Services;

use App\Exceptions\InvalidStockOperationException;
use App\Models\ProductSerial;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SerialService
{
    /**
     * Ingest serial numbers into inventory linked to a purchase order receipt.
     *
     * @param PurchaseOrder $order
     * @param PurchaseOrderItem $item
     * @param array $serialNumbers
     * @return array<ProductSerial>
     * @throws InvalidArgumentException
     */
    public function ingestSerialsForPurchase(PurchaseOrder $order, PurchaseOrderItem $item, array $serialNumbers): array
    {
        $cleanSerials = array_values(array_unique(array_filter(array_map('trim', $serialNumbers))));

        if (empty($cleanSerials)) {
            return [];
        }

        $existing = ProductSerial::where('team_id', $order->team_id)
            ->whereIn('serial_number', $cleanSerials)
            ->pluck('serial_number')
            ->toArray();

        if (!empty($existing)) {
            $duplicateList = implode(', ', $existing);
            throw new InvalidArgumentException("Serial number(s) already exist in your inventory: {$duplicateList}");
        }

        return DB::transaction(function () use ($order, $item, $cleanSerials) {
            $created = [];
            $warrantyDays = (int) ($item->product?->warranty_days ?? 0);

            foreach ($cleanSerials as $sn) {
                $created[] = ProductSerial::create([
                    'team_id' => $order->team_id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'serial_number' => $sn,
                    'status' => 'in_stock',
                    'purchase_order_id' => $order->id,
                    'purchase_order_item_id' => $item->id,
                    'warranty_days' => $warrantyDays,
                ]);
            }

            return $created;
        });
    }

    /**
     * Allocate in-stock serial numbers to a customer sales order line.
     *
     * @param SalesOrder $order
     * @param SalesOrderItem $item
     * @param array $serialNumbers
     * @return array<ProductSerial>
     * @throws InvalidArgumentException|InvalidStockOperationException
     */
    public function allocateSerialsForSale(SalesOrder $order, SalesOrderItem $item, array $serialNumbers): array
    {
        $cleanSerials = array_values(array_unique(array_filter(array_map('trim', $serialNumbers))));

        if (empty($cleanSerials)) {
            return [];
        }

        if (count($cleanSerials) != (int) $item->quantity) {
            throw new InvalidArgumentException("Serial count (" . count($cleanSerials) . ") must match order line quantity (" . (int) $item->quantity . ").");
        }

        return DB::transaction(function () use ($order, $item, $cleanSerials) {
            $serials = ProductSerial::where('team_id', $order->team_id)
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->whereIn('serial_number', $cleanSerials)
                ->lockForUpdate()
                ->get();

            if ($serials->count() !== count($cleanSerials)) {
                $found = $serials->pluck('serial_number')->toArray();
                $missing = array_diff($cleanSerials, $found);
                throw new InvalidStockOperationException("The following serials were not found in stock at this warehouse: " . implode(', ', $missing));
            }

            foreach ($serials as $serial) {
                if ($serial->status !== 'in_stock') {
                    throw new InvalidStockOperationException("Serial '{$serial->serial_number}' is currently '{$serial->status}' and cannot be sold.");
                }
            }

            $orderDate = $order->order_date ? Carbon::parse($order->order_date) : Carbon::today();
            $warrantyDays = (int) $item->warranty_days;
            $expiryDate = $warrantyDays > 0 ? $orderDate->copy()->addDays($warrantyDays)->toDateString() : null;

            $updated = [];
            foreach ($serials as $serial) {
                $serial->update([
                    'status' => 'sold',
                    'sales_order_id' => $order->id,
                    'sales_order_item_id' => $item->id,
                    'customer_id' => $order->customer_id,
                    'sold_at' => $orderDate,
                    'warranty_days' => $warrantyDays,
                    'warranty_expires_at' => $expiryDate,
                ]);
                $updated[] = $serial;
            }

            return $updated;
        });
    }

    /**
     * Release allocated serials if an order is cancelled.
     */
    public function releaseSerialsForCancelledSale(SalesOrder $order): void
    {
        DB::transaction(function () use ($order) {
            ProductSerial::where('team_id', $order->team_id)
                ->where('sales_order_id', $order->id)
                ->update([
                    'status' => 'in_stock',
                    'sales_order_id' => null,
                    'sales_order_item_id' => null,
                    'customer_id' => null,
                    'sold_at' => null,
                    'warranty_expires_at' => null,
                ]);
        });
    }

    /**
     * Mark a serial as defective / RMA intake.
     */
    public function markDefective(ProductSerial $serial, string $reason): void
    {
        $serial->update([
            'status' => 'defective',
            'notes' => trim($serial->notes . "\n[" . now()->toDateTimeString() . "] Marked Defective: " . $reason),
        ]);
    }
}
