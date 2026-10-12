<?php

namespace App\Services;

use App\Exceptions\InvalidStockOperationException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create a new purchase order with line items.
     */
    public function createOrder(array $data, array $items): PurchaseOrder
    {
        if (empty($items)) {
            throw new InvalidStockOperationException("A purchase order must contain at least one line item.");
        }

        return DB::transaction(function () use ($data, $items) {
            $teamId = Auth::user()?->current_team_id;

            // Generate order number if not provided
            $orderNumber = $data['order_number'] ?? $this->generateOrderNumber($teamId);

            // Compute subtotal from line items
            $subtotal = 0.00;
            foreach ($items as $item) {
                $subtotal += ($item['quantity'] * $item['unit_cost']);
            }

            $tax = $data['tax_amount'] ?? 0.00;
            $shipping = $data['shipping_cost'] ?? 0.00;
            $discount = $data['discount_amount'] ?? 0.00;
            $totalAmount = max(0, $subtotal + $tax + $shipping - $discount);

            $order = PurchaseOrder::create([
                'team_id' => $teamId,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_number' => $orderNumber,
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_cost' => $shipping,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_cost'];
                PurchaseOrderItem::create([
                    'team_id' => $teamId,
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $itemSubtotal,
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Mark purchase order as ordered/sent to supplier.
     */
    public function markAsOrdered(PurchaseOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw new InvalidStockOperationException("Only draft purchase orders can be marked as ordered.");
        }

        $order->update(['status' => 'ordered']);
    }

    /**
     * Receive goods for a purchase order (triggers stock-in).
     *
     * @param array<int, array{item_id: int, quantity: float}> $receivedItems
     */
    public function receiveItems(PurchaseOrder $order, array $receivedItems): void
    {
        if (in_array($order->status, ['draft', 'cancelled', 'received'])) {
            throw new InvalidStockOperationException("Cannot receive items for an order with status '{$order->status}'.");
        }

        DB::transaction(function () use ($order, $receivedItems) {
            foreach ($receivedItems as $receipt) {
                $itemId = $receipt['item_id'];
                $quantityToReceive = (float) $receipt['quantity'];

                if ($quantityToReceive <= 0) {
                    continue;
                }

                $orderItem = PurchaseOrderItem::where('purchase_order_id', $order->id)
                    ->where('id', $itemId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $remaining = $orderItem->remaining_quantity;

                if ($quantityToReceive > $remaining) {
                    throw new InvalidStockOperationException(
                        "Cannot receive {$quantityToReceive} units for product #{$orderItem->product_id}. Only {$remaining} units remaining on order."
                    );
                }

                // Increment received quantity on item
                $orderItem->received_quantity += $quantityToReceive;
                $orderItem->save();

                // Trigger StockService stock-in
                $this->stockService->stockIn(
                    warehouseId: $order->warehouse_id,
                    productId: $orderItem->product_id,
                    quantity: $quantityToReceive,
                    unitCost: (float) $orderItem->unit_cost,
                    variantId: $orderItem->product_variant_id,
                    referenceType: PurchaseOrder::class,
                    referenceId: $order->id,
                    reason: "PO #{$order->order_number} Stock Receipt"
                );
            }

            // Update order status based on remaining quantities
            $order->refresh();
            if ($order->isFullyReceived()) {
                $order->update(['status' => 'received']);
            } elseif ($order->isPartiallyReceived()) {
                $order->update(['status' => 'partially_received']);
            }
        });
    }

    /**
     * Cancel an unreceived purchase order.
     */
    public function cancelOrder(PurchaseOrder $order): void
    {
        if ($order->status === 'received' || $order->status === 'partially_received') {
            throw new InvalidStockOperationException("Cannot cancel a purchase order that has already received stock.");
        }

        $order->update(['status' => 'cancelled']);
    }

    /**
     * Generate unique PO sequence PO-YYYYMMDD-0001
     */
    protected function generateOrderNumber(?int $teamId): string
    {
        $prefix = 'PO-' . Carbon::now()->format('Ymd') . '-';
        
        $lastOrder = PurchaseOrder::where('team_id', $teamId)
            ->where('order_number', 'LIKE', $prefix . '%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }
}
