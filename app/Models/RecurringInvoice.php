<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringInvoice extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'customer_id',
        'warehouse_id',
        'profile_name',
        'subscription_number',
        'status',
        'frequency',
        'interval_count',
        'start_date',
        'end_date',
        'next_issue_date',
        'last_issued_at',
        'total_cycles_limit',
        'cycles_completed',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_terms_days',
        'auto_send_email',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_issue_date' => 'date',
        'last_issued_at' => 'datetime',
        'interval_count' => 'integer',
        'total_cycles_limit' => 'integer',
        'cycles_completed' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'auto_send_email' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Compute the Monthly Recurring Revenue (MRR) equivalent.
     */
    public function getMonthlyRecurringRevenueAttribute(): float
    {
        if ($this->status !== 'active') {
            return 0.00;
        }

        $amount = (float) $this->total_amount;

        return match ($this->frequency) {
            'weekly' => round($amount * 4.333, 2),
            'monthly' => round($amount, 2),
            'quarterly' => round($amount / 3, 2),
            'semi_annual' => round($amount / 6, 2),
            'yearly' => round($amount / 12, 2),
            default => round($amount, 2),
        };
    }

    /**
     * Calculate the next date following a given date.
     */
    public function calculateNextDate(Carbon|string $fromDate): Carbon
    {
        $date = Carbon::parse($fromDate);
        $interval = max(1, $this->interval_count ?? 1);

        return match ($this->frequency) {
            'weekly' => $date->addWeeks($interval),
            'monthly' => $date->addMonthsNoOverflow($interval),
            'quarterly' => $date->addMonthsNoOverflow(3 * $interval),
            'semi_annual' => $date->addMonthsNoOverflow(6 * $interval),
            'yearly' => $date->addYearsNoOverflow($interval),
            default => $date->addMonthsNoOverflow($interval),
        };
    }
}
