<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds for Warehouses & Hubs menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouses = [
            [
                'name'       => 'Main Central Hub',
                'code'       => 'MCH-01',
                'address'    => '742 Evergreen Industrial Pkwy, Dallas, TX 75001',
                'phone'      => '+1 (555) 019-2834',
                'email'      => 'central-hub@apexlogistics.com',
                'is_default' => true,
                'is_active'  => true,
            ],
            [
                'name'       => 'North Logistics Depot',
                'code'       => 'NLD-02',
                'address'    => '1400 Interstate 90 Logistics Way, Chicago, IL 60607',
                'phone'      => '+1 (555) 019-8812',
                'email'      => 'north-depot@apexlogistics.com',
                'is_default' => false,
                'is_active'  => true,
            ],
            [
                'name'       => 'Downtown Express Hub',
                'code'       => 'DEH-03',
                'address'    => '320 Commerce St, Suite 400, Austin, TX 78701',
                'phone'      => '+1 (555) 019-4491',
                'email'      => 'express-austin@apexlogistics.com',
                'is_default' => false,
                'is_active'  => true,
            ],
            [
                'name'       => 'East Coast Distribution',
                'code'       => 'ECD-04',
                'address'    => '880 Freight Terminal Blvd, Atlanta, GA 30303',
                'phone'      => '+1 (555) 019-7733',
                'email'      => 'east-atlanta@apexlogistics.com',
                'is_default' => false,
                'is_active'  => true,
            ],
            [
                'name'       => 'West Bay Storage Facility',
                'code'       => 'WBS-05',
                'address'    => '510 Desert Sky Logistics Loop, Phoenix, AZ 85001',
                'phone'      => '+1 (555) 019-6120',
                'email'      => 'west-phoenix@apexlogistics.com',
                'is_default' => false,
                'is_active'  => true,
            ],
        ];

        foreach ($warehouses as $data) {
            Warehouse::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'code'    => $data['code'],
                ],
                array_merge($data, ['team_id' => $team->id])
            );
        }
    }
}
