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
        protected StockService $stockService,
        protected ?SerialService $serialService = null
    ) {
        $this->serialService = $serialService ?? app(SerialService::class);
    }

    /**
     * Create a new purchase order with line items.
     */
    public function createOrder(array $data, array $items): PurchaseOrder
    {
        if (empty($items)) {
            throw new InvalidStockOperationException("A purchase order must contain at least one line item.");
        }

        return DB::transaction(function () use ($data, $items) {
            $teamId = $data['team_id'] ?? Auth::user()?->current_team_id;

            if ($teamId) {
                $warehouseBelongs = \App\Models\Warehouse::where('team_id', $teamId)->where('id', $data['warehouse_id'])->exists();
                if (!$warehouseBelongs) {
                    throw new \InvalidArgumentException("Selected warehouse does not belong to the active organization.");
                }

                $productIds = array_unique(array_filter(array_column($items, 'product_id')));
                $validProductCount = \App\Models\Product::where('team_id', $teamId)->whereIn('id', $productIds)->count();
                if ($validProductCount !== count($productIds)) {
                    throw new \InvalidArgumentException("One or more products do not belong to the active organization.");
                }
            }

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

            $paidAmount = (float) ($data['paid_amount'] ?? 0.00);
            $dueAmount = max(0, $totalAmount - $paidAmount);
            if (isset($data['payment_status'])) {
                $paymentStatus = $data['payment_status'];
            } else {
                $paymentStatus = ($paidAmount >= $totalAmount && $totalAmount > 0)
                    ? 'paid'
                    : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');
            }

            $supplierId = $data['supplier_id'] ?? null;
            if ($supplierId && $teamId && !\App\Models\Supplier::where('team_id', $teamId)->where('id', $supplierId)->exists()) {
                throw new \InvalidArgumentException("Selected supplier does not belong to the active organization.");
            }
            $newVendorData = $data['new_supplier'] ?? $data['new_vendor'] ?? null;
            if (!$supplierId && !empty($newVendorData)) {
                $supplier = \App\Models\Supplier::create([
                    'team_id' => $teamId,
                    'name' => $newVendorData['name'],
                    'phone' => $newVendorData['phone'],
                    'address' => $newVendorData['address'] ?? null,
                    'contact_name' => $newVendorData['contact_name'] ?? null,
                    'email' => $newVendorData['email'] ?? null,
                    'tax_number' => $newVendorData['tax_number'] ?? null,
                    'is_active' => true,
                ]);
                $supplierId = $supplier->id;
            }

            $order = PurchaseOrder::create([
                'team_id' => $teamId,
                'supplier_id' => $supplierId,
                'warehouse_id' => $data['warehouse_id'],
                'order_number' => $orderNumber,
                'status' => $data['status'] ?? 'draft',
                'payment_status' => $paymentStatus,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_cost' => $shipping,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
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
                    'batch_number' => $item['batch_number'] ?? null,
                    'manufacture_date' => $item['manufacture_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
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
     * Update an existing purchase order with new line items and details.
     */
    public function updateOrder(PurchaseOrder $order, array $data, array $items): PurchaseOrder
    {
        if (in_array($order->status, ['received', 'cancelled'])) {
            throw new InvalidStockOperationException("Cannot update an order that is already '{$order->status}'.");
        }

        if (empty($items)) {
            throw new InvalidStockOperationException("A purchase order must contain at least one line item.");
        }

        return DB::transaction(function () use ($order, $data, $items) {
            $teamId = $order->team_id;

            if (!empty($data['warehouse_id'])) {
                $warehouseBelongs = \App\Models\Warehouse::where('team_id', $teamId)->where('id', $data['warehouse_id'])->exists();
                if (!$warehouseBelongs) {
                    throw new \InvalidArgumentException("Selected warehouse does not belong to the active organization.");
                }
            }

            if (!empty($data['supplier_id'])) {
                $supplierBelongs = \App\Models\Supplier::where('team_id', $teamId)->where('id', $data['supplier_id'])->exists();
                if (!$supplierBelongs) {
                    throw new \InvalidArgumentException("Selected supplier does not belong to the active organization.");
                }
            }

            $productIds = array_unique(array_filter(array_column($items, 'product_id')));
            $validProductCount = \App\Models\Product::where('team_id', $teamId)->whereIn('id', $productIds)->count();
            if ($validProductCount !== count($productIds)) {
                throw new \InvalidArgumentException("One or more products do not belong to the active organization.");
            }

            // Compute subtotal from line items
            $subtotal = 0.00;
            foreach ($items as $item) {
                $subtotal += ($item['quantity'] * $item['unit_cost']);
            }

            $tax = (float) ($data['tax_amount'] ?? $order->tax_amount ?? 0.00);
            $shipping = (float) ($data['shipping_cost'] ?? $order->shipping_cost ?? 0.00);
            $discount = (float) ($data['discount_amount'] ?? $order->discount_amount ?? 0.00);
            $totalAmount = max(0, $subtotal + $tax + $shipping - $discount);

            $paidAmount = (float) $order->paid_amount;
            $dueAmount = max(0, $totalAmount - $paidAmount);
            $paymentStatus = ($paidAmount >= $totalAmount && $totalAmount > 0)
                ? 'paid'
                : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');

            $order->update([
                'supplier_id' => $data['supplier_id'] ?? $order->supplier_id,
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_date' => $data['expected_date'] ?? $order->expected_date,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_cost' => $shipping,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            // Sync items: find existing items
            $existingItems = $order->items()->get()->keyBy('id');
            $keptItemIds = [];

            foreach ($items as $itemData) {
                $itemId = $itemData['id'] ?? null;
                $quantity = (float) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $itemSubtotal = $quantity * $unitCost;

                if ($itemId && isset($existingItems[$itemId])) {
                    $existingItem = $existingItems[$itemId];
                    if ($quantity < $existingItem->received_quantity) {
                        throw new InvalidStockOperationException(
                            "Cannot reduce ordered quantity below already received quantity ({$existingItem->received_quantity})."
                        );
                    }

                    $existingItem->update([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'batch_number' => $itemData['batch_number'] ?? $existingItem->batch_number,
                        'manufacture_date' => $itemData['manufacture_date'] ?? $existingItem->manufacture_date,
                        'expiry_date' => $itemData['expiry_date'] ?? $existingItem->expiry_date,
                        'subtotal' => $itemSubtotal,
                    ]);
                    $keptItemIds[] = $itemId;
                } else {
                    $newItem = PurchaseOrderItem::create([
                        'team_id' => $teamId,
                        'purchase_order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'product_variant_id' => $itemData['product_variant_id'] ?? null,
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'manufacture_date' => $itemData['manufacture_date'] ?? null,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                        'quantity' => $quantity,
                        'received_quantity' => 0,
                        'unit_cost' => $unitCost,
                        'subtotal' => $itemSubtotal,
                    ]);
                    $keptItemIds[] = $newItem->id;
                }
            }

            // Delete removed items (ensure they haven't received items)
            foreach ($existingItems as $oldId => $oldItem) {
                if (!in_array($oldId, $keptItemIds)) {
                    if ($oldItem->received_quantity > 0) {
                        throw new InvalidStockOperationException(
                            "Cannot remove an item that has already been partially received."
                        );
                    }
                    $oldItem->delete();
                }
            }

            return $order->fresh(['items.product', 'supplier', 'warehouse']);
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

                // Auto-update latest product purchase price on PO receive
                if ($orderItem->product) {
                    $orderItem->product->update(['cost_price' => (float) $orderItem->unit_cost]);
                }

                // Ingest serial numbers if provided
                if (!empty($receipt['serials'])) {
                    $this->serialService->ingestSerialsForPurchase($order, $orderItem, (array) $receipt['serials']);
                }
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
     * Record payment made to vendor/supplier against purchase order.
     */
    public function recordPayment(PurchaseOrder $order, float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidStockOperationException("Payment amount must be greater than zero.");
        }

        DB::transaction(function () use ($order, $amount) {
            $order->paid_amount += $amount;
            $order->due_amount = max(0, (float) $order->total_amount - (float) $order->paid_amount);

            if ($order->paid_amount >= $order->total_amount) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'partially_paid';
            }

            $order->save();
        });
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
