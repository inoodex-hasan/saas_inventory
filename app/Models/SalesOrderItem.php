<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'team_id',
        'sales_order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'warranty_days',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'warranty_days' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function serials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    /**
     * Gross profit margin on this line item.
     */
    public function getGrossProfitAttribute(): float
    {
        return ((float) $this->unit_price - (float) $this->unit_cost) * (float) $this->quantity;
    }
}
