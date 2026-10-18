<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSerial extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'product_id',
        'warehouse_id',
        'serial_number',
        'status',
        'purchase_order_id',
        'purchase_order_item_id',
        'sales_order_id',
        'sales_order_item_id',
        'customer_id',
        'sold_at',
        'warranty_days',
        'warranty_expires_at',
        'notes',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'warranty_expires_at' => 'date:Y-m-d',
        'warranty_days' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Determine warranty status: active, expiring_soon, expired, in_stock.
     */
    public function getWarrantyStatusAttribute(): string
    {
        if ($this->status !== 'sold' || !$this->warranty_expires_at) {
            return $this->status;
        }

        $today = Carbon::today();
        $expiry = Carbon::parse($this->warranty_expires_at);

        if ($today->gt($expiry)) {
            return 'expired';
        }

        if ($today->diffInDays($expiry) <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Remaining warranty days (0 if expired or not sold).
     */
    public function getRemainingDaysAttribute(): int
    {
        if ($this->status !== 'sold' || !$this->warranty_expires_at) {
            return 0;
        }

        $today = Carbon::today();
        $expiry = Carbon::parse($this->warranty_expires_at);

        if ($today->gt($expiry)) {
            return 0;
        }

        return (int) $today->diffInDays($expiry);
    }
}
