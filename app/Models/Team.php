<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
        'status',
        'subscription_plan',
        'subscription_expires_at',
        'legal_name',
        'email',
        'phone',
        'tax_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'currency_symbol',
        'currency_code',
        'invoice_prefix',
        'po_prefix',
        'invoice_bg_path',
        'report_bg_path',
        'invoice_margin_top',
        'invoice_margin_bottom',
        'report_margin_top',
        'report_margin_bottom',
    ];

    /**
     * Get single-line or multi-line formatted company address.
     */
    public function formattedAddress(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->join(', ');
    }

    /**
     * Get display business name (legal name or team name fallback).
     */
    public function displayName(): string
    {
        return $this->legal_name ?: $this->name;
    }

    /**
     * Determine if tenant account is currently suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Determine if tenant account is active.
     */
     public function isActive(): bool
     {
         return $this->status === 'active' || $this->status === 'trial';
     }

    /**
     * Get absolute filesystem path to invoice background image.
     */
    public function getInvoiceBgAbsolutePath(): ?string
    {
        if ($this->invoice_bg_path) {
            $path = storage_path('app/public/' . $this->invoice_bg_path);
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Get absolute filesystem path to report background image.
     */
    public function getReportBgAbsolutePath(): ?string
    {
        if ($this->report_bg_path) {
            $path = storage_path('app/public/' . $this->report_bg_path);
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Get web URL for invoice background preview.
     */
    public function getInvoiceBgUrl(): ?string
    {
        return $this->invoice_bg_path ? asset('storage/' . $this->invoice_bg_path) : null;
    }

    /**
     * Get web URL for report background preview.
     */
    public function getReportBgUrl(): ?string
    {
        return $this->report_bg_path ? asset('storage/' . $this->report_bg_path) : null;
    }

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    public function subscription()
    {
        return $this->hasOne(TenantSubscription::class);
    }

    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Get all of the team's users including assigned branch / warehouse.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, Membership::class)
            ->withPivot(['role', 'warehouse_id'])
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'subscription_expires_at' => 'datetime',
        ];
    }
}
