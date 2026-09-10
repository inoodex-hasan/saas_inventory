<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for a model.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically apply the global scope
        static::addGlobalScope(new TenantScope());

        // Automatically populate team_id on model creation
        static::creating(function ($model) {
            if (empty($model->team_id) && Auth::check() && Auth::user()->current_team_id) {
                $model->team_id = Auth::user()->current_team_id;
            }
        });
    }

    /**
     * Relationship: The tenant/team this model belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Alias relationship: The tenant this model belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->team();
    }
}
