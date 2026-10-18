<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\Team;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds for Vendors & Suppliers menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $vendors = [
            [
                'name'         => 'TechSupply Global Inc.',
                'contact_name' => 'Sarah Jenkins',
                'email'        => 'orders@techsupplyglobal.com',
                'phone'        => '+1 (800) 555-0144',
                'tax_number'   => 'TAX-TSG-99881',
                'address'      => '500 Technology Drive, San Jose, CA 95110',
                'is_active'    => true,
            ],
            [
                'name'         => 'Apex Hardware Distributors',
                'contact_name' => 'Robert Miller',
                'email'        => 'sales@apexhardwaredist.com',
                'phone'        => '+1 (800) 555-0182',
                'tax_number'   => 'TAX-AHD-44219',
                'address'      => '120 Industrial Loop, Detroit, MI 48201',
                'is_active'    => true,
            ],
            [
                'name'         => 'Silicon Components Ltd.',
                'contact_name' => 'David Chen',
                'email'        => 'supply@siliconcomponents.com',
                'phone'        => '+1 (800) 555-0199',
                'tax_number'   => 'TAX-SCL-11023',
                'address'      => '88 Semiconductor Blvd, Austin, TX 78728',
                'is_active'    => true,
            ],
            [
                'name'         => 'Prime Packaging Corp',
                'contact_name' => 'Elena Rostova',
                'email'        => 'accounts@primepackagingcorp.com',
                'phone'        => '+1 (800) 555-0163',
                'tax_number'   => 'TAX-PPC-77341',
                'address'      => '340 Corrugated Way, Columbus, OH 43215',
                'is_active'    => true,
            ],
            [
                'name'         => 'Metro Office Essentials',
                'contact_name' => 'Marcus Vance',
                'email'        => 'b2b@metroofficeessentials.com',
                'phone'        => '+1 (800) 555-0128',
                'tax_number'   => 'TAX-MOE-55198',
                'address'      => '910 Commercial Ave, Charlotte, NC 28202',
                'is_active'    => true,
            ],
        ];

        foreach ($vendors as $data) {
            Supplier::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'name'    => $data['name'],
                ],
                array_merge($data, ['team_id' => $team->id])
            );
        }
    }
}
