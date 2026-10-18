<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds for Purchases & Inbound & Vendor Dues menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouse = Warehouse::where('team_id', $team->id)->first();
        $suppliers = Supplier::where('team_id', $team->id)->get();
        $products = Product::where('team_id', $team->id)->get();

        if ($suppliers->isEmpty() || $products->isEmpty() || !$warehouse) {
            return;
        }

        $ordersData = [
            [
                'order_number'    => 'PO-2026-001',
                'supplier_index'  => 0,
                'status'          => 'received',
                'order_date'      => now()->subDays(25)->toDateString(),
                'expected_date'   => now()->subDays(20)->toDateString(),
                'product_index'   => 0, // Dell Laptops
                'quantity'        => 15,
                'unit_cost'       => 850.00,
                'shipping_cost'   => 150.00,
                'tax_amount'      => 637.50,
                'paid_amount'     => 13537.50,
                'due_amount'      => 0.00,
                'payment_status'  => 'paid',
                'notes'           => 'Initial batch of Dell Latitude laptops fully received and verified.',
            ],
            [
                'order_number'    => 'PO-2026-002',
                'supplier_index'  => 1,
                'status'          => 'received',
                'order_date'      => now()->subDays(18)->toDateString(),
                'expected_date'   => now()->subDays(12)->toDateString(),
                'product_index'   => 1, // Cisco Switches
                'quantity'        => 20,
                'unit_cost'       => 420.00,
                'shipping_cost'   => 100.00,
                'tax_amount'      => 420.00,
                'paid_amount'     => 5000.00,
                'due_amount'      => 3920.00,
                'payment_status'  => 'partially_paid',
                'notes'           => 'Cisco switches order. Balance payable within 30 net days.',
            ],
            [
                'order_number'    => 'PO-2026-003',
                'supplier_index'  => 2,
                'status'          => 'ordered',
                'order_date'      => now()->subDays(6)->toDateString(),
                'expected_date'   => now()->addDays(5)->toDateString(),
                'product_index'   => 3, // APC UPS
                'quantity'        => 10,
                'unit_cost'       => 480.00,
                'shipping_cost'   => 120.00,
                'tax_amount'      => 240.00,
                'paid_amount'     => 0.00,
                'due_amount'      => 5160.00,
                'payment_status'  => 'unpaid',
                'notes'           => 'Emergency backup power units dispatched from supplier warehouse.',
            ],
            [
                'order_number'    => 'PO-2026-004',
                'supplier_index'  => 3,
                'status'          => 'partially_received',
                'order_date'      => now()->subDays(10)->toDateString(),
                'expected_date'   => now()->subDays(2)->toDateString(),
                'product_index'   => 2, // Logitech Mice
                'quantity'        => 50,
                'unit_cost'       => 65.00,
                'shipping_cost'   => 50.00,
                'tax_amount'      => 162.50,
                'paid_amount'     => 3462.50,
                'due_amount'      => 0.00,
                'payment_status'  => 'paid',
                'notes'           => '30 units received, remaining 20 units on backorder.',
            ],
            [
                'order_number'    => 'PO-2026-005',
                'supplier_index'  => 4,
                'status'          => 'received',
                'order_date'      => now()->subDays(4)->toDateString(),
                'expected_date'   => now()->subDays(1)->toDateString(),
                'product_index'   => 4, // WD Red HDD
                'quantity'        => 30,
                'unit_cost'       => 210.00,
                'shipping_cost'   => 80.00,
                'tax_amount'      => 315.00,
                'paid_amount'     => 3000.00,
                'due_amount'      => 3695.00,
                'payment_status'  => 'partially_paid',
                'notes'           => 'Enterprise server storage drives. First installment settled via wire.',
            ],
        ];

        foreach ($ordersData as $item) {
            $supplier = $suppliers[$item['supplier_index'] % $suppliers->count()];
            $product = $products[$item['product_index'] % $products->count()];
            $subtotal = $item['quantity'] * $item['unit_cost'];
            $total = $subtotal + $item['shipping_cost'] + $item['tax_amount'];

            $po = PurchaseOrder::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'order_number' => $item['order_number'],
                ],
                [
                    'team_id'         => $team->id,
                    'supplier_id'     => $supplier->id,
                    'warehouse_id'    => $warehouse->id,
                    'status'          => $item['status'],
                    'order_date'      => $item['order_date'],
                    'expected_date'   => $item['expected_date'],
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

            PurchaseOrderItem::updateOrCreate(
                [
                    'team_id'           => $team->id,
                    'purchase_order_id' => $po->id,
                    'product_id'        => $product->id,
                ],
                [
                    'quantity'          => $item['quantity'],
                    'received_quantity' => in_array($item['status'], ['received', 'partially_received']) ? $item['quantity'] : 0,
                    'unit_cost'         => $item['unit_cost'],
                    'subtotal'          => $subtotal,
                    'batch_number'      => 'BATCH-' . date('Ym', strtotime($item['order_date'])) . '-' . $product->id,
                ]
            );
        }
    }
}
