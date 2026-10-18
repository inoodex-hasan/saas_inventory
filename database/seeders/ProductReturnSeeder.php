<?php

namespace Database\Seeders;

use App\Models\ProductReturn;
use App\Models\ProductReturnItem;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductReturnSeeder extends Seeder
{
    /**
     * Run the database seeds for Returns & RMA menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouse = Warehouse::where('team_id', $team->id)->first();
        $salesOrders = SalesOrder::where('team_id', $team->id)->with('items.product')->get();

        if ($salesOrders->isEmpty()) {
            return;
        }

        $returnsData = [
            [
                'number'         => 'RMA-2026-001',
                'order_index'    => 0,
                'status'         => 'approved',
                'refund_type'    => 'replacement',
                'refund_amount'  => 1200.00,
                'reason'         => 'Battery charging failure on unit during first boot after delivery.',
                'condition'      => 'defective',
                'action_taken'   => 'repair',
                'notes'          => 'Authorized by technician supervisor. Sent to workshop for motherboard diagnosis.',
            ],
            [
                'number'         => 'RMA-2026-002',
                'order_index'    => 1,
                'status'         => 'completed',
                'refund_type'    => 'exchange',
                'refund_amount'  => 650.00,
                'reason'         => 'Port 4 physical packet loss detected during enterprise rack mounting.',
                'condition'      => 'defective',
                'action_taken'   => 'restock',
                'notes'          => 'Unit swapped with spare from warehouse stock. Defective unit returned to vendor.',
            ],
            [
                'number'         => 'RMA-2026-003',
                'order_index'    => 3,
                'status'         => 'pending',
                'refund_type'    => 'replacement',
                'refund_amount'  => 1200.00,
                'reason'         => 'Protective casing carton punctured in transit. Inner chassis inspection required.',
                'condition'      => 'damaged',
                'action_taken'   => 'scrap',
                'notes'          => 'Awaiting carrier damage assessment report before approving replacement dispatch.',
            ],
            [
                'number'         => 'RMA-2026-004',
                'order_index'    => 4,
                'status'         => 'approved',
                'refund_type'    => 'credit_note',
                'refund_amount'  => 198.00,
                'reason'         => 'Client customer mistakenly ordered wrong peripheral model variant.',
                'condition'      => 'sellable',
                'action_taken'   => 'restock',
                'notes'          => 'Box seal unopened. Approved for restocking with credit voucher issued.',
            ],
            [
                'number'         => 'RMA-2026-005',
                'order_index'    => 0,
                'status'         => 'rejected',
                'refund_type'    => 'credit_note',
                'refund_amount'  => 0.00,
                'reason'         => 'Liquid spillage on laptop keyboard occurred on client office premises.',
                'condition'      => 'damaged',
                'action_taken'   => 'scrap',
                'notes'          => 'Outside manufacturer warranty scope. Paid repair ticket offered instead.',
            ],
        ];

        foreach ($returnsData as $rma) {
            $order = $salesOrders[$rma['order_index'] % $salesOrders->count()];
            $firstItem = $order->items->first();

            $return = ProductReturn::updateOrCreate(
                [
                    'team_id'       => $team->id,
                    'return_number' => $rma['number'],
                ],
                [
                    'sales_order_id' => $order->id,
                    'customer_id'    => $order->customer_id,
                    'warehouse_id'   => $order->warehouse_id ?? $warehouse?->id,
                    'status'         => $rma['status'],
                    'refund_type'    => $rma['refund_type'],
                    'refund_amount'  => $rma['refund_amount'],
                    'reason'         => $rma['reason'],
                    'notes'          => $rma['notes'],
                    'approved_by'    => $team->user_id,
                    'approved_at'    => now()->subDays(2),
                    'created_by'     => $team->user_id,
                ]
            );

            if ($firstItem) {
                ProductReturnItem::updateOrCreate(
                    [
                        'team_id'           => $team->id,
                        'product_return_id' => $return->id,
                        'product_id'        => $firstItem->product_id,
                    ],
                    [
                        'sales_order_item_id' => $firstItem->id,
                        'serial_number'       => 'SN-' . strtoupper(substr(md5($rma['number']), 0, 8)),
                        'quantity'            => 1,
                        'unit_price'          => $firstItem->unit_price,
                        'subtotal'            => $firstItem->unit_price,
                        'condition'           => $rma['condition'],
                        'action_taken'        => $rma['action_taken'],
                    ]
                );
            }
        }
    }
}
