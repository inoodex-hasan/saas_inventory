<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'supplier_id',
        'warehouse_id',
        'order_number',
        'status',
        'order_date',
        'expected_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if all items in this purchase order have been received.
     */
    public function isFullyReceived(): bool
    {
        if ($this->items->isEmpty()) {
            return false;
        }

        foreach ($this->items as $item) {
            if ($item->received_quantity < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any item in this purchase order has been partially received.
     */
    public function isPartiallyReceived(): bool
    {
        $hasSomeReceived = false;
        $allReceived = true;

        foreach ($this->items as $item) {
            if ($item->received_quantity > 0) {
                $hasSomeReceived = true;
            }
            if ($item->received_quantity < $item->quantity) {
                $allReceived = false;
            }
        }

        return $hasSomeReceived && !$allReceived;
    }
}
