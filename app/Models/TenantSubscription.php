<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'plan_id',
        'status',
        'billing_cycle',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        if ($this->status === 'trialing') {
            return $this->trial_ends_at ? $this->trial_ends_at->isFuture() : true;
        }

        if ($this->status === 'active') {
            return $this->ends_at ? $this->ends_at->isFuture() : true;
        }

        return false;
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }

    public function isExpired(): bool
    {
        return !$this->isActive();
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status === 'trialing' && $this->trial_ends_at) {
            return max(0, (int) round(now()->diffInHours($this->trial_ends_at, false) / 24));
        }

        if ($this->status === 'active' && $this->ends_at) {
            return max(0, (int) round(now()->diffInHours($this->ends_at, false) / 24));
        }

        return 0;
    }
}
