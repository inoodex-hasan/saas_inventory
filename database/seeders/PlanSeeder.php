<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'             => 'Starter',
                'slug'             => 'starter',
                'description'      => 'Essential inventory management for small local shops & boutique businesses.',
                'price_bdt'        => 1499.00,
                'annual_price_bdt' => 14990.00,
                'max_products'     => 150,
                'max_warehouses'   => 1,
                'max_users'        => 2,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 1,
                'features'         => [
                    'Standard Catalog Management',
                    '1 Central Warehouse',
                    'Sales & Invoicing',
                    'Basic Customer Due Ledger',
                    'Standard Reports',
                ],
            ],
            [
                'name'             => 'Growth',
                'slug'             => 'growth',
                'description'      => 'Perfect for growing wholesale, retail, and regional distributors.',
                'price_bdt'        => 3499.00,
                'annual_price_bdt' => 34990.00,
                'max_products'     => 1000,
                'max_warehouses'   => 3,
                'max_users'        => 5,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 2,
                'features'         => [
                    'Up to 3 Multi-Warehouses',
                    'Delivery Challans & Dispatches',
                    'Purchase Orders & Inbound',
                    'Serial & Warranty Tracking',
                    'Vendor & Customer Due Ledgers',
                    'Daily Expense Tracking',
                ],
            ],
            [
                'name'             => 'Professional',
                'slug'             => 'professional',
                'description'      => 'Full ERP suite with HR, Workshop & Double-entry Accounting.',
                'price_bdt'        => 6999.00,
                'annual_price_bdt' => 69990.00,
                'max_products'     => 5000,
                'max_warehouses'   => 10,
                'max_users'        => 15,
                'trial_days'       => 14,
                'is_active'        => true,
                'sort_order'       => 3,
                'features'         => [
                    'Up to 10 Warehouses & Hubs',
                    'Services & Workshop Job Cards',
                    'Project Costing & Client Billing',
                    'HR, Staff Payroll & TA/DA Claims',
                    'Double-Entry Accounting & Ledger',
                    'Bank Reconciliation & P&L',
                    'Priority Technical Support',
                ],
            ],
            [
                'name'             => 'Enterprise',
                'slug'             => 'enterprise',
                'description'      => 'Unlimited capacity and dedicated support for high-volume enterprise operations.',
                'price_bdt'        => 12999.00,
                'annual_price_bdt' => 129990.00,
                'max_products'     => -1, // Unlimited
                'max_warehouses'   => -1, // Unlimited
                'max_users'        => -1, // Unlimited
                'trial_days'       => 30,
                'is_active'        => true,
                'sort_order'       => 4,
                'features'         => [
                    'Unlimited Products & SKUs',
                    'Unlimited Warehouses & Outlets',
                    'Unlimited User Seats & Roles',
                    'Dedicated Account Manager',
                    'Custom ERP Integration & API Access',
                    '99.9% Uptime SLA',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
