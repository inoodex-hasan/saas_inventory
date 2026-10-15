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
