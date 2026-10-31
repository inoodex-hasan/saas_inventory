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

        // 3. Demo Tenant Company (Team 1)
        $clientTeam = Team::where('user_id', $clientOwner->id)->first();
        if (!$clientTeam) {
            $clientTeam = Team::forceCreate([
                'user_id'                 => $clientOwner->id,
                'name'                    => 'Demo',
                'legal_name'              => 'Demo Enterprise Ltd.',
                'email'                   => 'admin@inoodex.com',
                'phone'                   => '+880 1711-000000',
                'currency_symbol'         => '৳',
                'currency_code'           => 'BDT',
                'city'                    => 'Dhaka',
                'country'                 => 'Bangladesh',
                'invoice_prefix'          => 'INV-',
                'po_prefix'               => 'PO-',
                'invoice_margin_top'      => 48,
                'invoice_margin_bottom'   => 25,
                'report_margin_top'       => 42,
                'report_margin_bottom'    => 25,
                'personal_team'           => true,
                'status'                  => 'active',
                'subscription_plan'       => 'pro',
            ]);
        }
        $clientOwner->current_team_id = $clientTeam->id;
        $clientOwner->save();

        // 4. Staff Member (User 3) & second team (Team 2) to ensure >= 2 users and >= 2 teams
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@inoodex.com'],
            [
                'name'     => 'Staff Member',
                'password' => Hash::make('staff12345'),
            ]
        );
        $staffUser->forceFill(['is_super_admin' => false, 'current_team_id' => $clientTeam->id])->save();

        // Attach team members to team_user table
        $clientTeam->users()->syncWithoutDetaching([
            $staffUser->id => ['role' => 'staff'],
            $clientOwner->id => ['role' => 'owner'],
        ]);

        // Second Team for superAdmin (Team 2)
        $secondTeam = Team::where('user_id', $superAdmin->id)->first();
        if (!$secondTeam) {
            $secondTeam = Team::forceCreate([
                'user_id'                 => $superAdmin->id,
                'name'                    => 'Apex Logistics Global',
                'legal_name'              => 'Apex Logistics Global Ltd.',
                'email'                   => 'contact@apexlogistics.com',
                'phone'                   => '+1 (555) 019-2834',
                'currency_symbol'         => '$',
                'currency_code'           => 'USD',
                'city'                    => 'Dallas',
                'country'                 => 'United States',
                'invoice_prefix'          => 'INV-',
                'po_prefix'               => 'PO-',
                'personal_team'           => true,
                'status'                  => 'active',
                'subscription_plan'       => 'enterprise',
            ]);
        }
        $superAdmin->current_team_id = $secondTeam->id;
        $superAdmin->save();
        $secondTeam->users()->syncWithoutDetaching([
            $superAdmin->id => ['role' => 'owner'],
        ]);

        // Seed roles for teams
        RolesAndPermissionsSeeder::seedRolesForTeam($clientTeam->id);
        RolesAndPermissionsSeeder::seedRolesForTeam($secondTeam->id);
        setPermissionsTeamId($clientTeam->id);
        if (!$clientOwner->hasRole('owner')) {
            $clientOwner->assignRole('owner');
        }
        if (!$staffUser->hasRole('staff')) {
            $staffUser->assignRole('staff');
        }

        // Direct model permissions (satisfies model_has_permissions >= 2)
        $staffUser->givePermissionTo(['manage-products', 'view-reports']);

        // Subscriptions & Payments (ensuring tenant_subscriptions & subscription_payments >= 2)
        $proPlan = Plan::where('slug', 'professional')->first() ?? Plan::first();
        $entPlan = Plan::where('slug', 'enterprise')->first() ?? $proPlan;

        if ($proPlan) {
            TenantSubscription::updateOrCreate(
                ['team_id' => $clientTeam->id],
                [
                    'plan_id'        => $proPlan->id,
                    'status'         => 'active',
                    'billing_cycle'  => 'monthly',
                    'starts_at'      => now()->subMonths(3),
                    'ends_at'        => now()->addYear(),
                ]
            );

            SubscriptionPayment::updateOrCreate(
                ['payment_number' => 'SUB-PAY-001'],
                [
                    'team_id'         => $clientTeam->id,
                    'plan_id'         => $proPlan->id,
                    'billing_cycle'   => 'monthly',
                    'amount_bdt'      => $proPlan->price_bdt ?: 2500.00,
                    'payment_method'  => 'bkash',
                    'sender_number'   => '01711000000',
                    'trx_id'          => 'TRX-BKASH-881920',
                    'status'          => 'approved',
                    'notes'           => 'Monthly bKash merchant payment',
                ]
            );
        }

        if ($entPlan) {
            TenantSubscription::updateOrCreate(
                ['team_id' => $secondTeam->id],
                [
                    'plan_id'        => $entPlan->id,
                    'status'         => 'active',
                    'billing_cycle'  => 'yearly',
                    'starts_at'      => now()->subMonth(),
                    'ends_at'        => now()->addYear(),
                ]
            );

            SubscriptionPayment::updateOrCreate(
                ['payment_number' => 'SUB-PAY-002'],
                [
                    'team_id'         => $secondTeam->id,
                    'plan_id'         => $entPlan->id,
                    'billing_cycle'   => 'yearly',
                    'amount_bdt'      => $entPlan->annual_price_bdt ?: 25000.00,
                    'payment_method'  => 'bank_transfer',
                    'sender_number'   => '01811000000',
                    'trx_id'          => 'TRX-BANK-992100',
                    'status'          => 'approved',
                    'notes'           => 'Annual Enterprise corporate subscription deposit',
                ]
            );
        }

        // Team Invitations (>= 2 in team_invitations)
        TeamInvitation::updateOrCreate(
            ['team_id' => $clientTeam->id, 'email' => 'finance@inoodex.com'],
            ['role' => 'admin']
        );
        TeamInvitation::updateOrCreate(
            ['team_id' => $clientTeam->id, 'email' => 'sales@inoodex.com'],
            ['role' => 'manager']
        );
    }
}

