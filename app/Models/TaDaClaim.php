<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaDaClaim extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'ta_da_claims';

    protected $fillable = [
        'team_id',
        'employee_id',
        'claim_number',
        'travel_date',
        'destination',
        'purpose',
        'travel_allowance',
        'daily_allowance',
        'other_expenses',
        'total_amount',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'travel_date' => 'date:Y-m-d',
        'travel_allowance' => 'decimal:2',
        'daily_allowance' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
