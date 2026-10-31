<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Core security, roles & platform plans
            RolesAndPermissionsSeeder::class,
            PlanSeeder::class,

            // 2. Base platform users & demo company tenant
            UserAndTenantSeeder::class,

            // 3. Inventory & Operations Menus
            WarehouseSeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
            PurchaseOrderSeeder::class,

            // 4. Sales & CRM Menus
            CustomerSeeder::class,
            QuotationSeeder::class,
            SalesOrderSeeder::class,
            DeliveryChallanSeeder::class,
            ProductReturnSeeder::class,
            ProductSerialSeeder::class,

            // 5. Services & Projects Menus
            ServiceTicketSeeder::class,
            ProjectSeeder::class,

            // 6. HR & Payroll Menus
            EmployeeSeeder::class,
            ExpenseSeeder::class,
            PayrollSeeder::class,

            // 7. Accounting & Finance Menus
            AccountingSeeder::class,
        ]);
    }
}
