<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds for Daily Expenses menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $employee = Employee::where('team_id', $team->id)->first();

        // 1. Expense Categories
        $categoriesData = [
            ['name' => 'Facility Rent & Leases', 'code' => 'EXP-CAT-RENT', 'description' => 'Warehouse and showroom rental leases'],
            ['name' => 'Utilities & Internet', 'code' => 'EXP-CAT-UTIL', 'description' => 'Commercial power, water, fiber internet connectivity'],
            ['name' => 'Forklift & Equipment Maintenance', 'code' => 'EXP-CAT-MAINT', 'description' => 'Hydraulic lifts, pallet jacks, and battery chargers'],
            ['name' => 'Vehicle Fuel & Tolls', 'code' => 'EXP-CAT-FUEL', 'description' => 'Delivery fleet diesel, expressway toll tags, and parking'],
            ['name' => 'Packing Supplies & Cartons', 'code' => 'EXP-CAT-PACK', 'description' => 'Bubble wrap, strapping, thermal labels, and pallets'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[] = ExpenseCategory::updateOrCreate(
                ['team_id' => $team->id, 'name' => $c['name']],
                [
                    'code'        => $c['code'],
                    'description' => $c['description'],
                    'is_active'   => true,
                ]
            );
        }

        // 2. 5 Expenses
        $expensesData = [
            [
                'number'     => 'EXP-2026-001',
                'cat_idx'    => 1,
                'title'      => 'Commercial Dedicated Gigabit Fiber Internet',
                'amount'     => 240.00,
                'date'       => now()->subDays(15)->toDateString(),
                'method'     => 'bank_transfer',
                'payee'      => 'AT&T Business Fiber',
                'receipt'    => 'REC-ATT-889102',
                'notes'      => 'Monthly primary connectivity invoice for Dallas Hub.',
            ],
            [
                'number'     => 'EXP-2026-002',
                'cat_idx'    => 2,
                'title'      => 'Toyota Electric Forklift 500-Hour Scheduled Service',
                'amount'     => 480.00,
                'date'       => now()->subDays(12)->toDateString(),
                'method'     => 'credit_card',
                'payee'      => 'Lone Star Material Handling Co.',
                'receipt'    => 'INV-LSMH-4412',
                'notes'      => 'Hydraulic fluid flush, brake inspection, and battery cell balancing.',
            ],
            [
                'number'     => 'EXP-2026-003',
                'cat_idx'    => 1,
                'title'      => 'Warehouse High-Bay LED Electricity & HVAC Power',
                'amount'     => 1150.00,
                'date'       => now()->subDays(8)->toDateString(),
                'method'     => 'bank_transfer',
                'payee'      => 'Oncor Electric Delivery',
                'receipt'    => 'ONCOR-OCT-2026-9',
                'notes'      => 'Facility climate control for electronic components storage.',
            ],
            [
                'number'     => 'EXP-2026-004',
                'cat_idx'    => 3,
                'title'      => 'Delivery Fleet Diesel Card Statement',
                'amount'     => 385.00,
                'date'       => now()->subDays(5)->toDateString(),
                'method'     => 'corporate_card',
                'payee'      => 'ExxonMobil Fleet Solutions',
                'receipt'    => 'EXX-FLT-2026-10',
                'notes'      => 'Bi-weekly fueling for dispatch vans serving Austin and Houston routes.',
            ],
            [
                'number'     => 'EXP-2026-005',
                'cat_idx'    => 4,
                'title'      => 'Thermal Barcode Labels & Heavy Duty Strapping Rolls',
                'amount'     => 195.00,
                'date'       => now()->subDays(2)->toDateString(),
                'method'     => 'cash',
                'payee'      => 'Uline Industrial Shipping Supplies',
                'receipt'    => 'ULINE-9981203',
                'notes'      => 'Replenishing dispatch packaging desk supplies.',
            ],
        ];

        foreach ($expensesData as $exp) {
            $cat = $categories[$exp['cat_idx'] % count($categories)];

            Expense::updateOrCreate(
                [
                    'team_id'        => $team->id,
                    'expense_number' => $exp['number'],
                ],
                [
                    'expense_category_id' => $cat->id,
                    'employee_id'         => $employee?->id,
                    'title'               => $exp['title'],
                    'amount'              => $exp['amount'],
                    'expense_date'        => $exp['date'],
                    'payment_method'      => $exp['method'],
                    'vendor_or_payee'     => $exp['payee'],
                    'receipt_reference'   => $exp['receipt'],
                    'notes'               => $exp['notes'],
                    'created_by'          => $team->user_id,
                ]
            );
        }
    }
}
