<?php

namespace Database\Seeders;

use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delegate to SubscriptionService as single source of truth
        app(SubscriptionService::class)->ensureDefaultPlans();
    }
}
