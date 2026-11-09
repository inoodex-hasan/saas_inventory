<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or update the Platform Super-Admin (SaaS Vendor / Operator)
        $superAdmin = User::firstOrCreate(
            ['email' => 'hello@inoodex.com'],
            [
                'name'     => 'Inoodex',
                'password' => Hash::make('hello@inoodex.com'),
            ]
        );
        $superAdmin->forceFill(['is_super_admin' => true])->save();

        // 2. Demo Client / Tenant Owner
        $clientOwner = User::firstOrCreate(
            ['email' => 'admin@inoodex.com'],
            [
                'name'     => 'Demo',
                'password' => Hash::make('admin12345'),
            ]
        );
        $clientOwner->forceFill(['is_super_admin' => false])->save();

    }
}

