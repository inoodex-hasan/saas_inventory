<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CostCategory;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds for Projects & Costing menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        // 1. Clients
        $clientsData = [
            ['name' => 'Michael Chang', 'company' => 'AeroDynamics Lab Corp', 'email' => 'mchang@aerodynamicslab.com', 'phone' => '+1 (555) 771-9012'],
            ['name' => 'Sophia Al-Mansoor', 'company' => 'Global Logistics Center', 'email' => 'sophia@glcenter.com', 'phone' => '+1 (555) 882-3144'],
            ['name' => 'Lucas Bernard', 'company' => 'Metropolitan Banking Tech', 'email' => 'lbernard@metrobanktech.com', 'phone' => '+1 (555) 993-8711'],
            ['name' => 'Amanda Foster', 'company' => 'BioHealth Genomics Institute', 'email' => 'afoster@biohealthgenomics.org', 'phone' => '+1 (555) 224-5501'],
            ['name' => 'Vikram Patel', 'company' => 'Apex Smart City Infrastructures', 'email' => 'vpatel@smartcityapex.io', 'phone' => '+1 (555) 335-7722'],
        ];

        $clients = [];
        foreach ($clientsData as $c) {
            $clients[] = Client::updateOrCreate(
                ['team_id' => $team->id, 'email' => $c['email']],
                [
                    'name'         => $c['name'],
                    'company_name' => $c['company'],
                    'phone'        => $c['phone'],
                    'address'      => 'Corporate Park Suite 500, Dallas, TX',
                    'is_active'    => true,
                ]
            );
        }

        // 2. Cost Categories
        $categoriesData = [
            ['name' => 'Subcontractor Labor', 'description' => 'On-site engineering, cable pulling and network installation'],
            ['name' => 'Permits & Municipal Fees', 'description' => 'City building and electrical safety compliance certifications'],
            ['name' => 'Freight & Logistics', 'description' => 'Dedicated crane and heavy equipment hauling transport'],
            ['name' => 'Hardware Consumables', 'description' => 'Patch cords, rack screws, zip ties, and mounting brackets'],
            ['name' => 'Software Licenses & Cloud', 'description' => 'Third-party management licenses and cloud gateway provisioning'],
        ];

        $costCats = [];
        foreach ($categoriesData as $cc) {
            $costCats[] = CostCategory::updateOrCreate(
                ['team_id' => $team->id, 'name' => $cc['name']],
                ['description' => $cc['description'], 'is_active' => true]
            );
        }

        // 3. Projects
        $projectsData = [
            [
                'code'        => 'PRJ-2026-001',
                'name'        => 'Data Center Server Rack Migration & Cabling',
                'client_idx'  => 0,
                'status'      => 'completed',
                'budget'      => 25000.00,
                'items_total' => 12000.00,
                'costs_total' => 4500.00,
                'billed'      => 24500.00,
                'paid'        => 24500.00,
                'due'         => 0.00,
                'pay_status'  => 'paid',
                'start_date'  => now()->subMonths(2)->toDateString(),
                'end_date'    => now()->subDays(10)->toDateString(),
                'desc'        => 'Complete migration of 48 server nodes and Cat6A structured cabling.',
            ],
            [
                'code'        => 'PRJ-2026-002',
                'name'        => 'Warehouse Smart RFID Inventory Tracking System',
                'client_idx'  => 1,
                'status'      => 'in_progress',
                'budget'      => 45000.00,
                'items_total' => 18500.00,
                'costs_total' => 6200.00,
                'billed'      => 35000.00,
                'paid'        => 20000.00,
                'due'         => 15000.00,
                'pay_status'  => 'partially_paid',
                'start_date'  => now()->subMonth()->toDateString(),
                'end_date'    => now()->addMonths(2)->toDateString(),
                'desc'        => 'Installation of overhead RFID sensor gates and handheld mobile scanners.',
            ],
            [
                'code'        => 'PRJ-2026-003',
                'name'        => 'Trading Floor Multi-Display Workstation Setup',
                'client_idx'  => 2,
                'status'      => 'in_progress',
                'budget'      => 32000.00,
                'items_total' => 14000.00,
                'costs_total' => 3800.00,
                'billed'      => 28000.00,
                'paid'        => 14000.00,
                'due'         => 14000.00,
                'pay_status'  => 'partially_paid',
                'start_date'  => now()->subWeeks(3)->toDateString(),
                'end_date'    => now()->addWeeks(3)->toDateString(),
                'desc'        => 'Deploying 30 dual-monitor ergonomic trading desks with backup UPS circuits.',
            ],
            [
                'code'        => 'PRJ-2026-004',
                'name'        => 'Laboratory Cleanroom Wi-Fi 6E Mesh Deployment',
                'client_idx'  => 3,
                'status'      => 'planning',
                'budget'      => 18000.00,
                'items_total' => 0.00,
                'costs_total' => 850.00,
                'billed'      => 5000.00,
                'paid'        => 5000.00,
                'due'         => 0.00,
                'pay_status'  => 'paid',
                'start_date'  => now()->addDays(5)->toDateString(),
                'end_date'    => now()->addMonths(1)->toDateString(),
                'desc'        => 'Antimicrobial sealed wireless access point installations for ISO Class 5 cleanrooms.',
            ],
            [
                'code'        => 'PRJ-2026-005',
                'name'        => 'Downtown Smart Traffic Monitoring Network',
                'client_idx'  => 4,
                'status'      => 'on_hold',
                'budget'      => 75000.00,
                'items_total' => 22000.00,
                'costs_total' => 9500.00,
                'billed'      => 40000.00,
                'paid'        => 15000.00,
                'due'         => 25000.00,
                'pay_status'  => 'partially_paid',
                'start_date'  => now()->subMonths(3)->toDateString(),
                'end_date'    => now()->addMonths(4)->toDateString(),
                'desc'        => 'Outdoor ruggedized PoE industrial switches for city intersection cameras.',
            ],
        ];

        foreach ($projectsData as $p) {
            $client = $clients[$p['client_idx'] % count($clients)];

            $project = Project::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'project_code' => $p['code'],
                ],
                [
                    'client_id'       => $client->id,
                    'name'            => $p['name'],
                    'status'          => $p['status'],
                    'budget'          => $p['budget'],
                    'items_total'     => $p['items_total'],
                    'costs_total'     => $p['costs_total'],
                    'discount_amount' => 0.00,
                    'tax_amount'      => 0.00,
                    'billed_total'    => $p['billed'],
                    'paid_amount'     => $p['paid'],
                    'due_amount'      => $p['due'],
                    'payment_status'  => $p['pay_status'],
                    'start_date'      => $p['start_date'],
                    'end_date'        => $p['end_date'],
                    'description'     => $p['desc'],
                    'created_by'      => $team->user_id,
                ]
            );

            // Add direct project cost entry
            if ($p['costs_total'] > 0) {
                ProjectCost::updateOrCreate(
                    [
                        'team_id'     => $team->id,
                        'project_id'  => $project->id,
                        'description' => 'Project Site Operations & Subcontractor Fee',
                    ],
                    [
                        'cost_category_id' => $costCats[0]->id,
                        'amount'           => $p['costs_total'],
                        'cost_date'        => now()->subDays(10)->toDateString(),
                        'vendor_name'      => 'Apex Field Operations Engineering Group',
                        'notes'            => 'Certified cost slip logged against project milestone 1.',
                    ]
                );
            }
        }
    }
}
