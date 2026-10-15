<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'team_id',
        'plan_id',
        'billing_cycle',
        'amount_bdt',
        'payment_method',
        'sender_number',
        'trx_id',
        'bank_account_info',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'gateway_payload',
    ];

    protected $casts = [
        'amount_bdt' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
