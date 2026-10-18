<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds for Customers directory.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $customers = [
            [
                'name'         => 'Alexander Wright',
                'company_name' => 'Apex Cybernetics Corp',
                'email'        => 'procurement@apexcybernetics.com',
                'phone'        => '+1 (555) 201-9081',
                'tax_number'   => 'TAX-CUST-10029',
                'address'      => '100 Cyber Way, Suite 800, Austin, TX 78701',
                'is_active'    => true,
            ],
            [
                'name'         => 'Jessica Taylor',
                'company_name' => 'Horizon Media Group LLC',
                'email'        => 'billing@horizonmediagroup.com',
                'phone'        => '+1 (555) 302-8872',
                'tax_number'   => 'TAX-CUST-39012',
                'address'      => '450 Creative Plaza, Floor 12, Chicago, IL 60611',
                'is_active'    => true,
            ],
            [
                'name'         => 'Liam Davies',
                'company_name' => 'Summit Engineering & Design',
                'email'        => 'orders@summiteng.com',
                'phone'        => '+1 (555) 403-1123',
                'tax_number'   => 'TAX-CUST-88129',
                'address'      => '720 Industrial Blvd, Houston, TX 77002',
                'is_active'    => true,
            ],
            [
                'name'         => 'Dr. Catherine Morales',
                'company_name' => 'BlueSky Health Systems',
                'email'        => 'it-purchasing@blueskyhealth.org',
                'phone'        => '+1 (555) 504-7744',
                'tax_number'   => 'TAX-CUST-44109',
                'address'      => '1200 Medical Center Drive, Dallas, TX 75201',
                'is_active'    => true,
            ],
            [
                'name'         => 'Ethan Ramirez',
                'company_name' => 'Nexus Digital Commerce Ltd.',
                'email'        => 'accounts@nexusdigital.io',
                'phone'        => '+1 (555) 605-3399',
                'tax_number'   => 'TAX-CUST-66281',
                'address'      => '250 Silicon Pier, San Francisco, CA 94105',
                'is_active'    => true,
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'email'   => $data['email'],
                ],
                array_merge($data, ['team_id' => $team->id])
            );
        }
    }
}
