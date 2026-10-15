<?php

namespace App\Services;

use App\Exceptions\InvalidStockOperationException;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ProductReturnItem;
use App\Models\ProductSerial;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create a new return/RMA request.
     */
    public function createReturn(array $data, array $items): ProductReturn
    {
        if (empty($items)) {
            throw new InvalidStockOperationException("Return request must contain at least one line item.");
        }

        return DB::transaction(function () use ($data, $items) {
            $teamId = Auth::user()?->current_team_id;

            $salesOrder = SalesOrder::where('team_id', $teamId)->findOrFail($data['sales_order_id']);

            $returnNumber = $data['return_number'] ?? $this->generateReturnNumber($teamId);

            $refundTotal = 0.00;
            foreach ($items as $item) {
                $refundTotal += ((float) $item['quantity'] * (float) $item['unit_price']);
            }

            $return = ProductReturn::create([
                'team_id' => $teamId,
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'warehouse_id' => $data['warehouse_id'] ?? $salesOrder->warehouse_id,
                'return_number' => $returnNumber,
                'status' => 'pending',
                'refund_type' => $data['refund_type'] ?? 'credit_note',
                'refund_amount' => $data['refund_amount'] ?? $refundTotal,
                'reason' => $data['reason'] ?? 'Customer Return',
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $subtotal = $qty * $price;

                ProductReturnItem::create([
                    'team_id' => $teamId,
                    'product_return_id' => $return->id,
                    'sales_order_item_id' => $item['sales_order_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'condition' => $item['condition'] ?? 'sellable',
                    'action_taken' => $item['action_taken'] ?? 'restock',
                ]);
            }

            return $return;
        });
    }

    /**
     * Manager approves the return request.
     */
    public function approveReturn(ProductReturn $return, ?int $userId = null): void
    {
        if ($return->status !== 'pending') {
            throw new InvalidStockOperationException("Only pending returns can be approved.");
        }

        $return->update([
            'status' => 'approved',
            'approved_by' => $userId ?? Auth::id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Complete the return: restore inventory, update serials, issue refund/credit.
     */
    public function completeReturn(ProductReturn $return, ?int $userId = null, ?int $warehouseId = null): void
    {
        if (in_array($return->status, ['completed', 'rejected'])) {
            throw new InvalidStockOperationException("Return is already in '{$return->status}' state.");
        }

        DB::transaction(function () use ($return, $userId, $warehouseId) {
            $effectiveWarehouseId = $warehouseId ?? $return->warehouse_id ?? $return->salesOrder?->warehouse_id;

            $return->load(['items.product', 'salesOrder']);

            foreach ($return->items as $item) {
                // Restock sellable items into warehouse
                if ($item->action_taken === 'restock' && $item->condition === 'sellable' && $effectiveWarehouseId) {
                    $unitCost = (float) ($item->product?->cost_price ?? 0);
                    $this->stockService->stockIn(
                        warehouseId: $effectiveWarehouseId,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        unitCost: $unitCost,
                        variantId: $item->product_variant_id,
                        referenceType: ProductReturn::class,
                        referenceId: $return->id,
                        reason: "Return #{$return->return_number} Restock"
                    );
                }

                // Handle serialized items
                if (!empty($item->serial_number)) {
                    $serial = ProductSerial::where('team_id', $return->team_id)
                        ->where('serial_number', $item->serial_number)
                        ->first();

                    if ($serial) {
                        if ($item->condition === 'sellable') {
                            $serial->update([
                                'status' => 'in_stock',
                                'warehouse_id' => $effectiveWarehouseId ?? $serial->warehouse_id,
                                'sales_order_id' => null,
                                'sales_order_item_id' => null,
                                'sold_at' => null,
                                'notes' => "Restocked from Return #{$return->return_number} on " . now()->toFormattedDateString(),
                            ]);
                        } else {
                            $serial->update([
                                'status' => 'defective',
                                'notes' => "Marked defective from Return #{$return->return_number} on " . now()->toFormattedDateString(),
                            ]);
                        }
                    }
                }
            }

            // Adjust sales order due/payment if applicable
            $salesOrder = $return->salesOrder;
            if ($salesOrder && (float) $salesOrder->due_amount > 0) {
                $newDue = max(0, (float) $salesOrder->due_amount - (float) $return->refund_amount);
                $salesOrder->update(['due_amount' => $newDue]);
            }

            $return->update([
                'status' => 'completed',
                'warehouse_id' => $effectiveWarehouseId,
                'processed_by' => $userId ?? Auth::id(),
                'processed_at' => now(),
            ]);
        });
    }

    /**
     * Reject return request.
     */
    public function rejectReturn(ProductReturn $return, ?int $userId = null, ?string $reason = null): void
    {
        if ($return->status === 'completed') {
            throw new InvalidStockOperationException("Cannot reject a return that has already been completed.");
        }

        $existingNotes = $return->notes;
        $rejectNote = $reason ? "Rejection Reason: {$reason}" : null;
        $combinedNotes = trim(($existingNotes ? $existingNotes . "\n" : '') . $rejectNote);

        $return->update([
            'status' => 'rejected',
            'notes' => $combinedNotes ?: null,
            'processed_by' => $userId ?? Auth::id(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Generate sequential return number.
     */
    private function generateReturnNumber(int $teamId): string
    {
        $prefix = 'RET-' . date('Ym') . '-';
        $count = ProductReturn::where('team_id', $teamId)
            ->where('return_number', 'like', "{$prefix}%")
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
