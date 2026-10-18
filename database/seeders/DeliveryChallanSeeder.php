<?php

namespace Database\Seeders;

use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DeliveryChallanSeeder extends Seeder
{
    /**
     * Run the database seeds for Delivery Challans menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouse = Warehouse::where('team_id', $team->id)->first();
        $salesOrders = SalesOrder::where('team_id', $team->id)->with('items.product')->get();

        if ($salesOrders->isEmpty() || !$warehouse) {
            return;
        }

        $challansData = [
            [
                'number'            => 'DC-2026-001',
                'order_index'       => 0,
                'dispatch_date'     => now()->subDays(17)->toDateString(),
                'carrier'           => 'FedEx Freight Ground',
                'vehicle_no'        => 'TX-789-FED',
                'driver_name'       => 'Jack Morrison',
                'driver_phone'      => '+1 (555) 819-2033',
                'address'           => '100 Cyber Way, Suite 800, Austin, TX 78701',
                'status'            => 'delivered',
                'packaging'         => '5x Foam Padded Master Cartons',
                'notes'             => 'Gate security verified and goods signed by receiving dock manager.',
            ],
            [
                'number'            => 'DC-2026-002',
                'order_index'       => 1,
                'dispatch_date'     => now()->subDays(9)->toDateString(),
                'carrier'           => 'Apex Dedicated Fleet',
                'vehicle_no'        => 'DAL-401-APX',
                'driver_name'       => 'Carlos Mendez',
                'driver_phone'      => '+1 (555) 712-4491',
                'address'           => '450 Creative Plaza, Floor 12, Chicago, IL 60611',
                'status'            => 'in_transit',
                'packaging'         => '2x Heavy Duty Strapped Pallets',
                'notes'             => 'En route via I-35 North corridor. ETA 24 hours.',
            ],
            [
                'number'            => 'DC-2026-003',
                'order_index'       => 3,
                'dispatch_date'     => now()->subDays(21)->toDateString(),
                'carrier'           => 'DHL Express Medical Division',
                'vehicle_no'        => 'TX-552-DHL',
                'driver_name'       => 'Samantha Reed',
                'driver_phone'      => '+1 (555) 601-8842',
                'address'           => '1200 Medical Center Drive, Dallas, TX 75201',
                'status'            => 'delivered',
                'packaging'         => '8x Anti-static Sealed Containers',
                'notes'             => 'Direct room delivery to Hospital IT Department. Received by Lead Admin.',
            ],
            [
                'number'            => 'DC-2026-004',
                'order_index'       => 4,
                'dispatch_date'     => now()->subDays(1)->toDateString(),
                'carrier'           => 'UPS Ground Direct',
                'vehicle_no'        => 'UPS-901-DAL',
                'driver_name'       => 'Kevin Vance',
                'driver_phone'      => '+1 (555) 902-1133',
                'address'           => '250 Silicon Pier, San Francisco, CA 94105',
                'status'            => 'dispatched',
                'packaging'         => '1x Bulk Peripheral Box with Tamper-evident Seal',
                'notes'             => 'Handed over to UPS evening pickup hub.',
            ],
            [
                'number'            => 'DC-2026-005',
                'order_index'       => 2,
                'dispatch_date'     => now()->subDays(3)->toDateString(),
                'carrier'           => 'Metro Express Freight',
                'vehicle_no'        => 'TX-331-MEF',
                'driver_name'       => 'David Lawson',
                'driver_phone'      => '+1 (555) 441-2877',
                'address'           => '720 Industrial Blvd, Houston, TX 77002',
                'status'            => 'in_transit',
                'packaging'         => '4x Wooden Crated UPS Units',
                'notes'             => 'Heavy cargo liftgate truck dispatched.',
            ],
        ];

        foreach ($challansData as $ch) {
            $order = $salesOrders[$ch['order_index'] % $salesOrders->count()];

            $challan = DeliveryChallan::updateOrCreate(
                [
                    'team_id'        => $team->id,
                    'challan_number' => $ch['number'],
                ],
                [
                    'sales_order_id'    => $order->id,
                    'warehouse_id'      => $order->warehouse_id ?? $warehouse->id,
                    'customer_id'       => $order->customer_id,
                    'dispatch_date'     => $ch['dispatch_date'],
                    'transport_carrier' => $ch['carrier'],
                    'vehicle_no'        => $ch['vehicle_no'],
                    'driver_name'       => $ch['driver_name'],
                    'driver_phone'      => $ch['driver_phone'],
                    'delivery_address'  => $ch['address'],
                    'status'            => $ch['status'],
                    'notes'             => $ch['notes'],
                    'created_by'        => $team->user_id,
                ]
            );

            // Add items from the sales order
            foreach ($order->items as $item) {
                DeliveryChallanItem::updateOrCreate(
                    [
                        'team_id'             => $team->id,
                        'delivery_challan_id' => $challan->id,
                        'product_id'          => $item->product_id,
                    ],
                    [
                        'quantity'              => $item->quantity,
                        'packaging_description' => $ch['packaging'],
                        'notes'                 => 'Challan verified quantity: ' . $item->quantity,
                    ]
                );
            }
        }
    }
}
