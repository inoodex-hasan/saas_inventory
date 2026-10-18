<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    /**
     * Run the database seeds for Sales & Dispatches & Customer Dues menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouse = Warehouse::where('team_id', $team->id)->first();
        $customers = Customer::where('team_id', $team->id)->get();
        $products = Product::where('team_id', $team->id)->get();

        if ($customers->isEmpty() || $products->isEmpty() || !$warehouse) {
            return;
        }

        $salesData = [
            [
                'order_number'   => 'SO-2026-001',
                'customer_index' => 0, // Apex Cybernetics
                'status'         => 'completed',
                'order_date'     => now()->subDays(18)->toDateString(),
                'product_index'  => 0, // Dell Laptop
                'quantity'       => 5,
                'unit_price'     => 1200.00,
                'tax_amount'     => 300.00,
                'shipping_cost'  => 50.00,
                'paid_amount'    => 6350.00,
                'due_amount'     => 0.00,
                'payment_status' => 'paid',
                'notes'          => 'Laptops delivered and tested by client IT team. Full payment received via ACH.',
            ],
            [
                'order_number'   => 'SO-2026-002',
                'customer_index' => 1, // Horizon Media Group
                'status'         => 'dispatched',
                'order_date'     => now()->subDays(10)->toDateString(),
                'product_index'  => 1, // Cisco Switch
                'quantity'       => 6,
                'unit_price'     => 650.00,
                'tax_amount'     => 195.00,
                'shipping_cost'  => 80.00,
                'paid_amount'    => 2000.00,
                'due_amount'     => 2175.00,
                'payment_status' => 'partially_paid',
                'notes'          => 'Switches in transit with Delivery Challan. Remaining due on Net 15 days.',
            ],
            [
                'order_number'   => 'SO-2026-003',
                'customer_index' => 2, // Summit Engineering
                'status'         => 'confirmed',
                'order_date'     => now()->subDays(5)->toDateString(),
                'product_index'  => 3, // APC UPS
                'quantity'       => 4,
                'unit_price'     => 720.00,
                'tax_amount'     => 144.00,
                'shipping_cost'  => 60.00,
                'paid_amount'    => 0.00,
                'due_amount'     => 3084.00,
                'payment_status' => 'unpaid',
                'notes'          => 'Confirmed purchase order. Awaiting warehouse pack and dispatch.',
            ],
            [
                'order_number'   => 'SO-2026-004',
                'customer_index' => 3, // BlueSky Health
                'status'         => 'completed',
                'order_date'     => now()->subDays(22)->toDateString(),
                'product_index'  => 0, // Dell Laptop
                'quantity'       => 8,
                'unit_price'     => 1200.00,
                'tax_amount'     => 480.00,
                'shipping_cost'  => 100.00,
                'paid_amount'    => 10180.00,
                'due_amount'     => 0.00,
                'payment_status' => 'paid',
                'notes'          => 'Hospital ward mobile computer fleet installation completed.',
            ],
            [
                'order_number'   => 'SO-2026-005',
                'customer_index' => 4, // Nexus Digital
                'status'         => 'dispatched',
                'order_date'     => now()->subDays(2)->toDateString(),
                'product_index'  => 2, // Logitech Mice
                'quantity'       => 20,
                'unit_price'     => 99.00,
                'tax_amount'     => 99.00,
                'shipping_cost'  => 30.00,
                'paid_amount'    => 1000.00,
                'due_amount'     => 1109.00,
                'payment_status' => 'partially_paid',
                'notes'          => 'Office accessories order. Delivery scheduled for end of week.',
            ],
        ];

        foreach ($salesData as $item) {
            $customer = $customers[$item['customer_index'] % $customers->count()];
            $product = $products[$item['product_index'] % $products->count()];
            $subtotal = $item['quantity'] * $item['unit_price'];
            $total = $subtotal + $item['shipping_cost'] + $item['tax_amount'];

            $so = SalesOrder::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'order_number' => $item['order_number'],
                ],
                [
                    'customer_id'     => $customer->id,
                    'warehouse_id'    => $warehouse->id,
                    'status'          => $item['status'],
                    'order_date'      => $item['order_date'],
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $item['tax_amount'],
                    'shipping_cost'   => $item['shipping_cost'],
                    'discount_amount' => 0.00,
                    'total_amount'    => $total,
                    'paid_amount'     => $item['paid_amount'],
                    'due_amount'      => $item['due_amount'],
                    'payment_status'  => $item['payment_status'],
                    'notes'           => $item['notes'],
                    'created_by'      => $team->user_id,
                ]
            );

            SalesOrderItem::updateOrCreate(
                [
                    'team_id'        => $team->id,
                    'sales_order_id' => $so->id,
                    'product_id'     => $product->id,
                ],
                [
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'unit_cost'     => $product->cost_price,
                    'warranty_days' => $product->warranty_days ?? 365,
                    'subtotal'      => $subtotal,
                ]
            );
        }
    }
}
