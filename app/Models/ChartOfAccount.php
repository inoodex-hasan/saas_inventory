<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'team_id',
        'parent_id',
        'account_code',
        'name',
        'type',
        'subtype',
        'description',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    /**
     * Compute current net balance for this account based on debits and credits.
     * Normal debit balance: Assets, Expenses
     * Normal credit balance: Liabilities, Equity, Revenue
     */
    public function getBalanceAttribute(): float
    {
        $debits = (float) $this->journalLines()->sum('debit');
        $credits = (float) $this->journalLines()->sum('credit');

        if (in_array($this->type, ['asset', 'expense'])) {
            return round($debits - $credits, 2);
        } else {
            return round($credits - $debits, 2);
        }
    }
}
