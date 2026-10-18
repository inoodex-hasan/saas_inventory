<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
        'employee_code',
        'name',
        'email',
        'phone',
        'designation',
        'department',
        'joining_date',
        'basic_salary',
        'bank_name',
        'bank_account_no',
        'mobile_banking_no',
        'status',
        'notes',
    ];

    protected $casts = [
        'joining_date' => 'date:Y-m-d',
        'basic_salary' => 'decimal:2',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function tadaClaims(): HasMany
    {
        return $this->hasMany(TaDaClaim::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
