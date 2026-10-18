<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductSerialSeeder extends Seeder
{
    /**
     * Run the database seeds for Serials & Warranties menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouse = Warehouse::where('team_id', $team->id)->first();
        $products = Product::where('team_id', $team->id)->where('is_serialized', true)->get();
        $customers = Customer::where('team_id', $team->id)->get();
        $salesOrder = SalesOrder::where('team_id', $team->id)->first();

        if ($products->isEmpty() || !$warehouse) {
            return;
        }

        $serialsData = [
            [
                'serial'       => 'SN-DELL-882190-A',
                'prod_index'   => 0,
                'status'       => 'in_stock',
                'sold_at'      => null,
                'customer_id'  => null,
                'so_id'        => null,
                'warranty_days'=> 365,
                'expires_at'   => null,
                'notes'        => 'Factory fresh unit on Shelf A-04 in Main Central Hub.',
            ],
            [
                'serial'       => 'SN-DELL-882191-B',
                'prod_index'   => 0,
                'status'       => 'sold',
                'sold_at'      => now()->subDays(18),
                'customer_id'  => $customers->first()?->id,
                'so_id'        => $salesOrder?->id,
                'warranty_days'=> 365,
                'expires_at'   => now()->subDays(18)->addDays(365)->toDateString(),
                'notes'        => 'Sold to Apex Cybernetics under Workstation Deployment Contract.',
            ],
            [
                'serial'       => 'SN-CISCO-77102-C',
                'prod_index'   => 1,
                'status'       => 'sold',
                'sold_at'      => now()->subDays(10),
                'customer_id'  => $customers->skip(1)->first()?->id ?? $customers->first()?->id,
                'so_id'        => $salesOrder?->id,
                'warranty_days'=> 730,
                'expires_at'   => now()->subDays(10)->addDays(730)->toDateString(),
                'notes'        => 'Core layer 2 switch installed at client server rack.',
            ],
            [
                'serial'       => 'SN-APC-55019-D',
                'prod_index'   => 2,
                'status'       => 'in_stock',
                'sold_at'      => null,
                'customer_id'  => null,
                'so_id'        => null,
                'warranty_days'=> 730,
                'expires_at'   => null,
                'notes'        => 'Battery self-test passed during inbound QA verification.',
            ],
            [
                'serial'       => 'SN-WD-99441-E',
                'prod_index'   => 3,
                'status'       => 'defective',
                'sold_at'      => null,
                'customer_id'  => null,
                'so_id'        => null,
                'warranty_days'=> 1825,
                'expires_at'   => null,
                'notes'        => 'SMART bad sector count above threshold. Tagged for vendor RMA.',
            ],
        ];

        foreach ($serialsData as $item) {
            $product = $products[$item['prod_index'] % $products->count()];

            ProductSerial::updateOrCreate(
                [
                    'team_id'       => $team->id,
                    'serial_number' => $item['serial'],
                ],
                [
                    'product_id'          => $product->id,
                    'warehouse_id'        => $warehouse->id,
                    'status'              => $item['status'],
                    'sales_order_id'      => $item['so_id'],
                    'customer_id'         => $item['customer_id'],
                    'sold_at'             => $item['sold_at'],
                    'warranty_days'       => $item['warranty_days'],
                    'warranty_expires_at' => $item['expires_at'],
                    'notes'               => $item['notes'],
                ]
            );
        }
    }
}
