<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes, LogsActivity;

    protected $fillable = [
        'team_id',
        'category_id',
        'brand_id',
        'name',
        'sku',
        'barcode',
        'description',
        'unit',
        'cost_price',
        'unit_price',
        'selling_price',
        'reorder_level',
        'reorder_quantity',
        'warranty_days',
        'has_variants',
        'is_serialized',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'warranty_days' => 'integer',
        'has_variants' => 'boolean',
        'is_serialized' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    /**
     * Total stock across all warehouses for this product.
     */
    public function getTotalStockAttribute(): float
    {
        return (float) $this->stockLevels()->sum('quantity');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
