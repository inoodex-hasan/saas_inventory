<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialAccount extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'institution_name',
        'account_number',
        'account_type',
        'branch',
        'routing_number',
        'swift_code',
        'opening_balance',
        'current_balance',
        'is_default',
        'is_active',
        'notes',
        'chart_of_account_id',
        'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class)->latest('id');
    }

    /**
     * Record a deposit / incoming payment into this account.
     */
    public function deposit(float $amount, array $meta = []): FinancialTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($amount, $meta) {
            $this->increment('current_balance', $amount);
            $this->refresh();

            return FinancialTransaction::create([
                'team_id' => $this->team_id,
                'financial_account_id' => $this->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $this->current_balance,
                'transaction_date' => $meta['date'] ?? now()->toDateString(),
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id' => $meta['reference_id'] ?? null,
                'reference_number' => $meta['reference_number'] ?? null,
                'description' => $meta['description'] ?? 'Incoming payment received',
                'created_by' => $meta['created_by'] ?? Auth::id(),
            ]);
        });
    }

    /**
     * Record a withdrawal / outgoing disbursement from this account.
     */
    public function withdraw(float $amount, array $meta = []): FinancialTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be greater than zero.");
        }

        return DB::transaction(function () use ($amount, $meta) {
            $this->decrement('current_balance', $amount);
            $this->refresh();

            return FinancialTransaction::create([
                'team_id' => $this->team_id,
                'financial_account_id' => $this->id,
                'transaction_type' => 'withdrawal',
                'amount' => $amount,
                'balance_after' => $this->current_balance,
                'transaction_date' => $meta['date'] ?? now()->toDateString(),
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id' => $meta['reference_id'] ?? null,
                'reference_number' => $meta['reference_number'] ?? null,
                'description' => $meta['description'] ?? 'Outgoing payment disbursed',
                'created_by' => $meta['created_by'] ?? Auth::id(),
            ]);
        });
    }
}
