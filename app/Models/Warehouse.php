<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'code',
        'address',
        'phone',
        'email',
        'is_default',
        'is_active',
    ];

    public function isOutlet(): bool
    {
        return ($this->type ?? 'outlet') === 'outlet';
    }

    public function isWarehouse(): bool
    {
        return $this->type === 'warehouse';
    }

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function getShortCodeAttribute(): string
    {
        if (empty($this->code)) {
            return '';
        }

        preg_match('/\d+/', $this->code, $matches);
        if (!empty($matches[0])) {
            return str_pad(substr($matches[0], -2), 2, '0', STR_PAD_LEFT);
        }

        return strtoupper(substr($this->code, 0, 2));
    }
}
