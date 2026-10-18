<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Team;
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
                'name'           => 'Inoodex Platform Admin',
                'password'       => Hash::make('hello@inoodex.com'),
                'is_super_admin' => true,
            ]
        );

        // 2. Create or update the Demo Client Company Owner (Customer / Tenant Owner)
        $clientOwner = User::firstOrCreate(
            ['email' => 'admin@inoodex.com'],
            [
                'name'           => 'Demo Company Owner',
                'password'       => Hash::make('admin12345'),
                'is_super_admin' => false,
            ]
        );

        // 3. Create or update the Demo Tenant Organization
        $clientTeam = Team::where('user_id', $clientOwner->id)->first();
        if (!$clientTeam) {
            $clientTeam = Team::forceCreate([
                'user_id'         => $clientOwner->id,
                'name'            => 'Apex Logistics & Retail',
                'legal_name'      => 'Apex Logistics Global Ltd.',
                'email'           => 'contact@apexlogistics.com',
                'phone'           => '+1 (555) 019-2834',
                'tax_id'          => 'US-EIN-987654321',
                'address_line1'   => '742 Evergreen Industrial Pkwy',
                'city'            => 'Dallas',
                'state'           => 'TX',
                'postal_code'     => '75001',
                'country'         => 'United States',
                'currency_code'   => 'USD',
                'currency_symbol' => '$',
                'invoice_prefix'  => 'INV-',
                'po_prefix'       => 'PO-',
                'status'          => 'active',
                'personal_team'   => true,
            ]);
        }

        $clientOwner->switchTeam($clientTeam);

        // Seed roles for client team & assign owner role
        RolesAndPermissionsSeeder::seedRolesForTeam($clientTeam->id);
        setPermissionsTeamId($clientTeam->id);
        if (!$clientOwner->hasRole('owner')) {
            $clientOwner->assignRole('owner');
        }

        // Attach tenant subscription to Professional plan
        $proPlan = Plan::where('slug', 'professional')->first() ?? Plan::first();
        if ($proPlan) {
            TenantSubscription::firstOrCreate(
                ['team_id' => $clientTeam->id],
                [
                    'plan_id'        => $proPlan->id,
                    'status'         => 'active',
                    'billing_cycle'  => 'monthly',
                    'trial_ends_at'  => null,
                    'starts_at'      => now(),
                    'ends_at'        => now()->addYear(),
                ]
            );
        }
    }
}
