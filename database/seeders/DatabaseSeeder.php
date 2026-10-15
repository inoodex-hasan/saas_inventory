<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed base roles & permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Create the Platform Super-Admin (SaaS Vendor / Operator)
        // Strictly manages client organizations and platform operations at /admin
        $superAdmin = User::create([
            'name'           => 'Inoodex Platform Admin',
            'email'          => 'hello@inoodex.com',
            'password'       => Hash::make('hello@inoodex.com'),
            'is_super_admin' => true,
        ]);

        // 3. Create a Demo Client Company Owner (Customer / Tenant Owner)
        // Operates their own inventory business at /dashboard
        $clientOwner = User::create([
            'name'           => 'Demo Company Owner',
            'email'          => 'admin@inoodex.com',
            'password'       => Hash::make('admin12345'),
            'is_super_admin' => false,
        ]);

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

        $clientOwner->switchTeam($clientTeam);

        // Seed roles for client team & assign owner role
        RolesAndPermissionsSeeder::seedRolesForTeam($clientTeam->id);
        setPermissionsTeamId($clientTeam->id);
        $clientOwner->assignRole('owner');

        // 4. Seed initial business operations data for Demo Client
        $warehouse = Warehouse::create([
            'team_id'    => $clientTeam->id,
            'name'       => 'Main Central Hub',
            'code'       => 'MCH-01',
            'address'    => '742 Evergreen Industrial Pkwy, Dallas, TX 75001',
            'phone'      => '+1 (555) 019-2834',
            'email'      => 'hub@apexlogistics.com',
            'is_default' => true,
            'is_active'  => true,
        ]);

        $category = ProductCategory::create([
            'team_id'     => $clientTeam->id,
            'name'        => 'Electronics',
            'description' => 'Computer and workspace hardware',
        ]);

    }
}
