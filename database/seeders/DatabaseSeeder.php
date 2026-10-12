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
            'name'        => 'Electronics & Peripherals',
            'description' => 'Computer and workspace hardware',
        ]);

        Supplier::create([
            'team_id'      => $clientTeam->id,
            'name'         => 'Global Tech Supplies Co.',
            'contact_name' => 'Alice Johnson',
            'email'        => 'sales@globaltechsupplies.com',
            'phone'        => '+1 (555) 443-2211',
            'address'      => '100 Silicon Way, San Jose, CA',
            'is_active'    => true,
        ]);

        Customer::create([
            'team_id'          => $clientTeam->id,
            'name'             => 'Acme Corporation',
            'email'            => 'procurement@acme.com',
            'phone'            => '+1 (555) 887-9922',
            'address'          => '500 Corporate Blvd, Austin, TX 78701',
            'is_active'        => true,
        ]);

        $productsData = [
            [
                'name'             => 'Ergonomic Wireless Mouse',
                'sku'              => 'EWM-100',
                'barcode'          => '8901234567890',
                'unit'             => 'pcs',
                'cost_price'       => 22.50,
                'unit_price'       => 49.99,
                'selling_price'    => 49.99,
                'reorder_level'    => 15,
                'reorder_quantity' => 50,
                'initial_stock'    => 85,
            ],
            [
                'name'             => 'Mechanical Backlit Keyboard',
                'sku'              => 'MBK-200',
                'barcode'          => '8901234567891',
                'unit'             => 'pcs',
                'cost_price'       => 45.00,
                'unit_price'       => 99.00,
                'selling_price'    => 99.00,
                'reorder_level'    => 10,
                'reorder_quantity' => 30,
                'initial_stock'    => 40,
            ],
            [
                'name'             => 'USB-C Dual 4K Docking Station',
                'sku'              => 'UDS-300',
                'barcode'          => '8901234567892',
                'unit'             => 'pcs',
                'cost_price'       => 65.00,
                'unit_price'       => 149.99,
                'selling_price'    => 149.99,
                'reorder_level'    => 8,
                'reorder_quantity' => 20,
                'initial_stock'    => 25,
            ],
            [
                'name'             => 'Aluminum Laptop Stand',
                'sku'              => 'ALS-400',
                'barcode'          => '8901234567893',
                'unit'             => 'pcs',
                'cost_price'       => 14.00,
                'unit_price'       => 34.50,
                'selling_price'    => 34.50,
                'reorder_level'    => 20,
                'reorder_quantity' => 60,
                'initial_stock'    => 12, // Lower than reorder_level (20) to demonstrate low-stock alert!
            ],
        ];

        foreach ($productsData as $data) {
            $initialStock = $data['initial_stock'];
            unset($data['initial_stock']);

            $product = Product::create(array_merge($data, [
                'team_id'     => $clientTeam->id,
                'category_id' => $category->id,
                'is_active'   => true,
            ]));

            StockLevel::create([
                'team_id'           => $clientTeam->id,
                'warehouse_id'      => $warehouse->id,
                'product_id'        => $product->id,
                'quantity'          => $initialStock,
                'reserved_quantity' => 0,
            ]);
        }
    }
}
