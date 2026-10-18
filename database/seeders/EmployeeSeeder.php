<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Team;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds for Staff & Employees menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $employees = [
            [
                'code'              => 'EMP-001',
                'name'              => 'Daniel Cooper',
                'email'             => 'd.cooper@apexlogistics.com',
                'phone'             => '+1 (555) 334-1190',
                'designation'       => 'Director of Logistics & Hub Operations',
                'department'        => 'Logistics & Warehousing',
                'joining_date'      => '2024-01-15',
                'basic_salary'      => 4500.00,
                'bank_name'         => 'Chase Bank Commercial',
                'bank_account_no'   => 'CHK-881920391',
                'mobile_banking_no' => '+15553341190',
                'status'            => 'active',
                'notes'             => 'Oversees multi-warehouse inventory distribution and facility security.',
            ],
            [
                'code'              => 'EMP-002',
                'name'              => 'Rachel Green',
                'email'             => 'r.green@apexlogistics.com',
                'phone'             => '+1 (555) 445-2281',
                'designation'       => 'Lead Hardware Systems Engineer',
                'department'        => 'Services & Workshop',
                'joining_date'      => '2024-03-01',
                'basic_salary'      => 3200.00,
                'bank_name'         => 'Bank of America Business',
                'bank_account_no'   => 'CHK-339102931',
                'mobile_banking_no' => '+15554452281',
                'status'            => 'active',
                'notes'             => 'In charge of electronic component diagnosis, warranty claims, and repairs.',
            ],
            [
                'code'              => 'EMP-003',
                'name'              => 'Brandon Murphy',
                'email'             => 'b.murphy@apexlogistics.com',
                'phone'             => '+1 (555) 556-3372',
                'designation'       => 'Senior Enterprise Account Executive',
                'department'        => 'Sales & Business Development',
                'joining_date'      => '2024-06-10',
                'basic_salary'      => 3800.00,
                'bank_name'         => 'Wells Fargo Corporate',
                'bank_account_no'   => 'CHK-449102830',
                'mobile_banking_no' => '+15555563372',
                'status'            => 'active',
                'notes'             => 'Manages B2B corporate contracts, quotations, and wholesale enterprise clients.',
            ],
            [
                'code'              => 'EMP-004',
                'name'              => 'Priya Sharma',
                'email'             => 'p.sharma@apexlogistics.com',
                'phone'             => '+1 (555) 667-4463',
                'designation'       => 'Senior Financial Controller',
                'department'        => 'Finance & Accounting',
                'joining_date'      => '2023-11-20',
                'basic_salary'      => 4200.00,
                'bank_name'         => 'Citibank Commercial',
                'bank_account_no'   => 'CHK-551920381',
                'mobile_banking_no' => '+15556674463',
                'status'            => 'active',
                'notes'             => 'Supervises double-entry journals, bank reconciliations, and tax compliance.',
            ],
            [
                'code'              => 'EMP-005',
                'name'              => 'Kevin O\'Connor',
                'email'             => 'k.oconnor@apexlogistics.com',
                'phone'             => '+1 (555) 778-5554',
                'designation'       => 'Field Logistics & Inspection Specialist',
                'department'        => 'Operations & Field Quality',
                'joining_date'      => '2025-02-01',
                'basic_salary'      => 2800.00,
                'bank_name'         => 'PNC Bank Regional',
                'bank_account_no'   => 'CHK-991820471',
                'mobile_banking_no' => '+15557785554',
                'status'            => 'active',
                'notes'             => 'Conducts site visits, project equipment deliveries, and supplier audits.',
            ],
        ];

        foreach ($employees as $data) {
            Employee::updateOrCreate(
                [
                    'team_id'       => $team->id,
                    'employee_code' => $data['code'],
                ],
                [
                    'name'              => $data['name'],
                    'email'             => $data['email'],
                    'phone'             => $data['phone'],
                    'designation'       => $data['designation'],
                    'department'        => $data['department'],
                    'joining_date'      => $data['joining_date'],
                    'basic_salary'      => $data['basic_salary'],
                    'bank_name'         => $data['bank_name'],
                    'bank_account_no'   => $data['bank_account_no'],
                    'mobile_banking_no' => $data['mobile_banking_no'],
                    'status'            => $data['status'],
                    'notes'             => $data['notes'],
                ]
            );
        }
    }
}
