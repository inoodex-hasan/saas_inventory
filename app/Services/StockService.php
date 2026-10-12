<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockOperationException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Record incoming stock (e.g. Purchase order received, initial inventory, return restocked).
     *
     * @throws InvalidStockOperationException
     */
    public function stockIn(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?float $unitCost = null,
        ?int $variantId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $reason = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidStockOperationException("Stock-in quantity must be greater than zero.");
        }

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $unitCost,
            $variantId,
            $referenceType,
            $referenceId,
            $reason
        ) {
            $teamId = Auth::user()?->current_team_id ?? Product::findOrFail($productId)->team_id;

            // 1. Ensure stock level row exists, lock and increment
            $stockLevel = StockLevel::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                ],
                [
                    'team_id' => $teamId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]
            );

            $stockLevel = StockLevel::where('id', $stockLevel->id)->lockForUpdate()->first();
            $stockLevel->quantity += $quantity;
            $stockLevel->save();

            // 2. Create immutable audit ledger record
            return StockMovement::create([
                'team_id' => $teamId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason ?? 'Stock In',
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Record outgoing stock (e.g. Sales order dispatch, damage write-off).
     *
     * @throws InvalidStockOperationException
     * @throws InsufficientStockException
     */
    public function stockOut(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $variantId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $reason = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidStockOperationException("Stock-out quantity must be greater than zero.");
        }

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantity,
            $variantId,
            $referenceType,
            $referenceId,
            $reason
        ) {
            $teamId = Auth::user()?->current_team_id ?? Product::findOrFail($productId)->team_id;

            // 1. Lock stock level row for concurrency safety
            $stockLevel = StockLevel::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $available = $stockLevel ? $stockLevel->available_quantity : 0;

            if (!$stockLevel || $available < $quantity) {
                throw new InsufficientStockException(
                    "Insufficient available stock in warehouse. Required: {$quantity}, Available: {$available}."
                );
            }

            // 2. Decrement stock level
            $stockLevel->quantity -= $quantity;
            $stockLevel->save();

            // 3. Create immutable audit ledger record
            return StockMovement::create([
                'team_id' => $teamId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'out',
                'quantity' => -$quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason ?? 'Stock Out',
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Adjust stock quantity based on physical stock audit.
     *
     * @throws InvalidStockOperationException
     */
    public function adjust(
        int $warehouseId,
        int $productId,
        float $newPhysicalCount,
        string $reason,
        ?int $variantId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        if (empty(trim($reason))) {
            throw new InvalidStockOperationException("A documented reason is required for manual stock adjustments.");
        }

        if ($newPhysicalCount < 0) {
            throw new InvalidStockOperationException("Physical stock count cannot be negative.");
        }

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $newPhysicalCount,
            $reason,
            $variantId,
            $referenceType,
            $referenceId
        ) {
            $teamId = Auth::user()?->current_team_id ?? Product::findOrFail($productId)->team_id;

            $stockLevel = StockLevel::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                ],
                [
                    'team_id' => $teamId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]
            );

            $stockLevel = StockLevel::where('id', $stockLevel->id)->lockForUpdate()->first();
            $currentQty = (float) $stockLevel->quantity;
            $delta = $newPhysicalCount - $currentQty;

            $stockLevel->quantity = $newPhysicalCount;
            $stockLevel->save();

            // Record movement delta (e.g. +5 or -3)
            return StockMovement::create([
                'team_id' => $teamId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'adjustment',
                'quantity' => $delta,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Transfer stock atomically between two warehouses.
     *
     * @throws InvalidStockOperationException
     * @throws InsufficientStockException
     * @return array{out: StockMovement, in: StockMovement}
     */
    public function transfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        float $quantity,
        ?int $variantId = null,
        ?string $reason = null
    ): array {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidStockOperationException("Source and destination warehouses cannot be the same.");
        }

        if ($quantity <= 0) {
            throw new InvalidStockOperationException("Transfer quantity must be greater than zero.");
        }

        return DB::transaction(function () use (
            $fromWarehouseId,
            $toWarehouseId,
            $productId,
            $quantity,
            $variantId,
            $reason
        ) {
            $teamId = Auth::user()?->current_team_id ?? Product::findOrFail($productId)->team_id;

            // 1. Lock source stock level
            $sourceLevel = StockLevel::where('warehouse_id', $fromWarehouseId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $available = $sourceLevel ? $sourceLevel->available_quantity : 0;
            if (!$sourceLevel || $available < $quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock at source warehouse for transfer. Available: {$available}, Requested: {$quantity}."
                );
            }

            // Decrement source
            $sourceLevel->quantity -= $quantity;
            $sourceLevel->save();

            // 2. Ensure / Lock destination stock level
            $destLevel = StockLevel::firstOrCreate(
                [
                    'warehouse_id' => $toWarehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                ],
                [
                    'team_id' => $teamId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]
            );

            $destLevel = StockLevel::where('id', $destLevel->id)->lockForUpdate()->first();
            $destLevel->quantity += $quantity;
            $destLevel->save();

            $transferNote = $reason ?? "Transfer from WH #{$fromWarehouseId} to WH #{$toWarehouseId}";

            // 3. Create paired movements
            $outMovement = StockMovement::create([
                'team_id' => $teamId,
                'warehouse_id' => $fromWarehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'transfer_out',
                'quantity' => -$quantity,
                'reason' => $transferNote,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            $inMovement = StockMovement::create([
                'team_id' => $teamId,
                'warehouse_id' => $toWarehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'reason' => $transferNote,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            return [
                'out' => $outMovement,
                'in' => $inMovement,
            ];
        });
    }

    /**
     * Reserve stock for an active/pending sales order.
     *
     * @throws InsufficientStockException
     */
    public function reserve(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $variantId = null
    ): void {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($warehouseId, $productId, $quantity, $variantId) {
            $stockLevel = StockLevel::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $available = $stockLevel ? $stockLevel->available_quantity : 0;

            if (!$stockLevel || $available < $quantity) {
                throw new InsufficientStockException(
                    "Cannot reserve stock. Available: {$available}, Requested: {$quantity}."
                );
            }

            $stockLevel->reserved_quantity += $quantity;
            $stockLevel->save();
        });
    }

    /**
     * Release reserved stock (e.g. Cancelled order).
     */
    public function release(
        int $warehouseId,
        int $productId,
        float $quantity,
        ?int $variantId = null
    ): void {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($warehouseId, $productId, $quantity, $variantId) {
            $stockLevel = StockLevel::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($stockLevel) {
                $stockLevel->reserved_quantity = max(0, (float) $stockLevel->reserved_quantity - $quantity);
                $stockLevel->save();
            }
        });
    }
}
