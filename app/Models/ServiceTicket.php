<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTicket extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'team_id',
        'customer_id',
        'ticket_number',
        'device_name',
        'device_brand',
        'serial_no',
        'accessories_received',
        'problem_description',
        'initial_diagnosis',
        'technician_id',
        'status',
        'priority',
        'service_charge',
        'parts_total',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'warranty_days',
        'received_date',
        'estimated_completion_date',
        'completed_date',
        'delivered_date',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date:Y-m-d',
        'estimated_completion_date' => 'date:Y-m-d',
        'completed_date' => 'date:Y-m-d',
        'delivered_date' => 'date:Y-m-d',
        'service_charge' => 'decimal:2',
        'parts_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'warranty_days' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceTicketItem::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ServiceTicketItem::class);
    }
}
