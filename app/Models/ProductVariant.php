<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'product_id',
        'name',
        'sku',
        'barcode',
        'attributes',
        'cost_price',
        'unit_price',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'total_stock',
        'selling_price',
    ];

    public function getSellingPriceAttribute(): float
    {
        return (float) ($this->attributes['unit_price'] ?? 0.00);
    }

    public function setSellingPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = $value;
    }

    public function getTotalStockAttribute(): float
    {
        if (array_key_exists('total_stock', $this->attributes)) {
            return (float) $this->attributes['total_stock'];
        }

        if ($this->relationLoaded('stockLevels')) {
            return (float) $this->stockLevels->sum('quantity');
        }

        return (float) $this->stockLevels()->sum('quantity');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'product_variant_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id');
    }
}
