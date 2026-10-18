<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\TaDaClaim;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TaDaClaimSeeder extends Seeder
{
    /**
     * Run the database seeds for Field TA/DA Claims menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $employees = Employee::where('team_id', $team->id)->get();
        if ($employees->isEmpty()) {
            return;
        }

        $claimsData = [
            [
                'number'         => 'CLM-2026-001',
                'emp_idx'        => 4, // Kevin O'Connor
                'date'           => now()->subDays(16)->toDateString(),
                'destination'    => 'Houston Technology Corridor, TX',
                'purpose'        => 'Client on-site server rack alignment and fiber termination testing.',
                'travel_allow'   => 120.00,
                'daily_allow'    => 80.00,
                'other_exp'      => 45.00,
                'status'         => 'reimbursed',
                'notes'          => 'Client sign-off slip attached. Reimbursed via payroll transfer.',
            ],
            [
                'number'         => 'CLM-2026-002',
                'emp_idx'        => 1, // Rachel Green
                'date'           => now()->subDays(11)->toDateString(),
                'destination'    => 'Austin Semiconductor Park, TX',
                'purpose'        => 'Emergency hardware diagnosis for BlueSky Health server nodes.',
                'travel_allow'   => 150.00,
                'daily_allow'    => 90.00,
                'other_exp'      => 35.00,
                'status'         => 'approved',
                'notes'          => 'Mileage verified and hotel receipt submitted.',
            ],
            [
                'number'         => 'CLM-2026-003',
                'emp_idx'        => 2, // Brandon Murphy
                'date'           => now()->subDays(7)->toDateString(),
                'destination'    => 'Chicago B2B Logistics Expo, IL',
                'purpose'        => 'Quarterly enterprise client acquisition and procurement negotiation meetings.',
                'travel_allow'   => 420.00,
                'daily_allow'    => 240.00,
                'other_exp'      => 95.00,
                'status'         => 'approved',
                'notes'          => 'Flights and expo entry pass approved by management.',
            ],
            [
                'number'         => 'CLM-2026-004',
                'emp_idx'        => 4, // Kevin O'Connor
                'date'           => now()->subDays(3)->toDateString(),
                'destination'    => 'San Antonio Storage Depot, TX',
                'purpose'        => 'Supplier inbound packaging quality audit and pallet check.',
                'travel_allow'   => 110.00,
                'daily_allow'    => 70.00,
                'other_exp'      => 20.00,
                'status'         => 'pending',
                'notes'          => 'Awaiting supervisor approval signature.',
            ],
            [
                'number'         => 'CLM-2026-005',
                'emp_idx'        => 0, // Daniel Cooper
                'date'           => now()->subDays(1)->toDateString(),
                'destination'    => 'Phoenix Regional Distribution Center, AZ',
                'purpose'        => 'Facility annual safety and OSHA compliance audit.',
                'travel_allow'   => 350.00,
                'daily_allow'    => 180.00,
                'other_exp'      => 50.00,
                'status'         => 'pending',
                'notes'          => 'Depot operational review conducted.',
            ],
        ];

        foreach ($claimsData as $c) {
            $emp = $employees[$c['emp_idx'] % $employees->count()];
            $total = $c['travel_allow'] + $c['daily_allow'] + $c['other_exp'];

            TaDaClaim::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'claim_number' => $c['number'],
                ],
                [
                    'employee_id'      => $emp->id,
                    'travel_date'      => $c['date'],
                    'destination'      => $c['destination'],
                    'purpose'          => $c['purpose'],
                    'travel_allowance' => $c['travel_allow'],
                    'daily_allowance'  => $c['daily_allow'],
                    'other_expenses'   => $c['other_exp'],
                    'total_amount'     => $total,
                    'status'           => $c['status'],
                    'approved_by'      => in_array($c['status'], ['approved', 'reimbursed']) ? $team->user_id : null,
                    'approved_at'      => in_array($c['status'], ['approved', 'reimbursed']) ? now()->subDays(2) : null,
                    'notes'            => $c['notes'],
                    'created_by'       => $team->user_id,
                ]
            );
        }
    }
}
