<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'warehouse_id',
    ];

    /**
     * The assigned default branch / warehouse.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Resolve the assigned branch/outlet for this user within a specific or active team context.
     */
    public function assignedWarehouse(?Team $team = null): ?Warehouse
    {
        $activeTeam = $team ?: $this->currentTeam;
        if (!$activeTeam) {
            return null;
        }

        // 1. Check team membership pivot
        $membership = Membership::where('user_id', $this->id)->where('team_id', $activeTeam->id)->first();
        if ($membership && $membership->warehouse_id) {
            $wh = Warehouse::where('team_id', $activeTeam->id)->where('is_active', true)->find($membership->warehouse_id);
            if ($wh) return $wh;
        }

        // 2. Direct user warehouse assignment
        if ($this->warehouse_id) {
            $wh = Warehouse::where('team_id', $activeTeam->id)->where('is_active', true)->find($this->warehouse_id);
            if ($wh) return $wh;
        }

        // 3. Fallback to default team branch/warehouse
        return Warehouse::where('team_id', $activeTeam->id)->where('is_active', true)->where('is_default', true)->first()
            ?: Warehouse::where('team_id', $activeTeam->id)->where('is_active', true)->first();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'phone_otp_code',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_otp_expires_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user has verified their phone number via OTP.
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Determine if the user is a platform super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Get the current team of the user's context.
     * Platform Super Admin operates in platform control plane (null) unless actively impersonating a tenant.
     */
    public function currentTeam()
    {
        if ($this->isSuperAdmin()) {
            return $this->belongsTo(\Laravel\Jetstream\Jetstream::teamModel(), 'current_team_id');
        }

        if (is_null($this->current_team_id) && $this->id) {
            $this->switchTeam($this->personalTeam());
        }

        return $this->belongsTo(\Laravel\Jetstream\Jetstream::teamModel(), 'current_team_id');
    }
}
