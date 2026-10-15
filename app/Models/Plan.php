<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_bdt',
        'annual_price_bdt',
        'max_products',
        'max_warehouses',
        'max_users',
        'features',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_bdt' => 'decimal:2',
        'annual_price_bdt' => 'decimal:2',
        'max_products' => 'integer',
        'max_warehouses' => 'integer',
        'max_users' => 'integer',
        'features' => 'array',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isUnlimitedProducts(): bool
    {
        return $this->max_products === -1;
    }

    public function isUnlimitedWarehouses(): bool
    {
        return $this->max_warehouses === -1;
    }

    public function isUnlimitedUsers(): bool
    {
        return $this->max_users === -1;
    }
}
