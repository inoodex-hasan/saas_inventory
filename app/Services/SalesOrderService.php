<?php

namespace App\Services;

use App\Exceptions\InvalidStockOperationException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        protected StockService $stockService,
        protected ?SerialService $serialService = null
    ) {
        $this->serialService = $serialService ?? app(SerialService::class);
    }

    /**
     * Create a new sales order with line items.
     */
    public function createOrder(array $data, array $items): SalesOrder
    {
        if (empty($items)) {
            throw new InvalidStockOperationException("A sales order must contain at least one line item.");
        }

        return DB::transaction(function () use ($data, $items) {
            $teamId = $data['team_id'] ?? Auth::user()?->current_team_id;

            // Generate unique SO sequence
            $orderNumber = $data['order_number'] ?? $this->generateOrderNumber($teamId);

            $subtotal = 0.00;
            $preparedItems = [];

            $productIds = array_unique(array_filter(array_column($items, 'product_id')));
            $productsQuery = Product::whereIn('id', $productIds);
            if ($teamId) {
                $productsQuery->where('team_id', $teamId);
            }
            $products = $productsQuery->get()->keyBy('id');
            if (count($products) !== count($productIds)) {
                throw new \InvalidArgumentException("One or more products do not belong to the active organization.");
            }

            if (!empty($data['warehouse_id']) && $teamId) {
                $warehouseBelongs = \App\Models\Warehouse::where('team_id', $teamId)->where('id', $data['warehouse_id'])->exists();
                if (!$warehouseBelongs) {
                    throw new \InvalidArgumentException("Selected warehouse does not belong to the active organization.");
                }
            }

            $variantIds = array_unique(array_filter(array_column($items, 'product_variant_id')));
            $variants = !empty($variantIds) ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();

            $customerId = $data['customer_id'] ?? null;
            if ($customerId && $teamId) {
                $customerBelongs = \App\Models\Customer::where('team_id', $teamId)->where('id', $customerId)->exists();
                if (!$customerBelongs) {
                    throw new \InvalidArgumentException("Selected customer does not belong to the active organization.");
                }
            } elseif (!$customerId && !empty($data['new_customer'])) {
                $customer = \App\Models\Customer::create([
                    'team_id' => $teamId,
                    'name' => $data['new_customer']['name'],
                    'phone' => $data['new_customer']['phone'],
                    'address' => $data['new_customer']['address'] ?? null,
                    'email' => $data['new_customer']['email'] ?? null,
                    'is_active' => true,
                ]);
                $customerId = $customer->id;
            }

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) {
                    throw new \InvalidArgumentException("Product not found: {$item['product_id']}");
                }
                $variantId = $item['product_variant_id'] ?? null;
                $variant = $variantId ? $variants->get($variantId) : null;
                
                // Snapshot cost price (COGS) at the moment of order creation
                $unitCost = $variant?->cost_price ?? $product->cost_price;
                $unitPrice = $item['unit_price'] ?? ($variant?->unit_price ?? $product->unit_price);
                $warrantyDays = isset($item['warranty_days']) ? (int) $item['warranty_days'] : (int) ($product->warranty_days ?? 0);
                $itemSubtotal = $item['quantity'] * $unitPrice;

                $subtotal += $itemSubtotal;

                $preparedItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'warranty_days' => $warrantyDays,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $tax = (float) ($data['tax_amount'] ?? 0.00);
            $shipping = (float) ($data['shipping_cost'] ?? 0.00);
            $discount = (float) ($data['discount_amount'] ?? 0.00);
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

            $initialStatus = 'draft';

            $order = SalesOrder::create([
                'team_id' => $teamId,
                'customer_id' => $customerId,
                'warehouse_id' => $data['warehouse_id'],
                'order_number' => $orderNumber,
                'status' => $initialStatus,
                'payment_status' => $paymentStatus,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_cost' => $shipping,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'tendered_amount' => isset($data['tendered_amount']) ? (float) $data['tendered_amount'] : null,
                'change_amount' => isset($data['change_amount']) ? (float) $data['change_amount'] : null,
                'due_amount' => $dueAmount,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($preparedItems as $idx => $preparedItem) {
                $orderItem = SalesOrderItem::create(array_merge($preparedItem, [
                    'team_id' => $teamId,
                    'sales_order_id' => $order->id,
                ]));

                $serials = $items[$idx]['serials'] ?? [];
                if (!empty($serials)) {
                    $this->serialService->allocateSerialsForSale($order, $orderItem, (array) $serials);
                }
            }

            // Auto-confirm if requested
            if (($data['status'] ?? '') === 'confirmed') {
                $this->confirmOrder($order);
            }

            return $order->load(['items', 'serials']);
        });
    }

    /**
     * Confirm order and reserve warehouse stock.
     */
    public function confirmOrder(SalesOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw new InvalidStockOperationException("Only draft sales orders can be confirmed.");
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $this->stockService->reserve(
                    warehouseId: $order->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    variantId: $item->product_variant_id
                );
            }

            $order->update(['status' => 'confirmed']);
        });
    }

    /**
     * Dispatch order goods and execute stock-out deduction.
     */
    public function dispatchOrder(SalesOrder $order): void
    {
        if (!in_array($order->status, ['draft', 'confirmed'])) {
            throw new InvalidStockOperationException("Cannot dispatch an order with status '{$order->status}'.");
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // If previously confirmed, release reservation first
                if ($order->status === 'confirmed') {
                    $this->stockService->release(
                        warehouseId: $order->warehouse_id,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        variantId: $item->product_variant_id
                    );
                }

                // Execute stock deduction via StockService
                $this->stockService->stockOut(
                    warehouseId: $order->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    variantId: $item->product_variant_id,
                    referenceType: SalesOrder::class,
                    referenceId: $order->id,
                    reason: "SO #{$order->order_number} Dispatch"
                );
            }

            $order->update(['status' => 'dispatched']);
        });
    }

    /**
     * Complete sales order.
     */
    public function completeOrder(SalesOrder $order): void
    {
        if ($order->status !== 'dispatched') {
            throw new InvalidStockOperationException("Only dispatched orders can be marked as completed.");
        }

        $order->update(['status' => 'completed']);
    }

    /**
     * Cancel an order and release any reserved stock.
     */
    public function cancelOrder(SalesOrder $order): void
    {
        if (in_array($order->status, ['dispatched', 'completed'])) {
            throw new InvalidStockOperationException("Cannot cancel an order that has already been dispatched. Process a return instead.");
        }

        DB::transaction(function () use ($order) {
            if ($order->status === 'confirmed') {
                foreach ($order->items as $item) {
                    $this->stockService->release(
                        warehouseId: $order->warehouse_id,
                        productId: $item->product_id,
                        quantity: (float) $item->quantity,
                        variantId: $item->product_variant_id
                    );
                }
            }

            $this->serialService->releaseSerialsForCancelledSale($order);

            $order->update(['status' => 'cancelled']);
        });
    }

    /**
     * Record payment against sales order.
     */
    public function recordPayment(SalesOrder $order, float $amount): void
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
     * Generate unique SO sequence SO-YYYYMMDD-0001
     */
    protected function generateOrderNumber(?int $teamId): string
    {
        $prefix = 'SO-' . Carbon::now()->format('Ymd') . '-';

        $lastOrder = SalesOrder::where('team_id', $teamId)
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
