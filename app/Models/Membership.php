<?php

namespace App\Models;

use Laravel\Jetstream\Membership as JetstreamMembership;

class Membership extends JetstreamMembership
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the assigned branch / warehouse for this membership.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
