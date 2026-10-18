<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketItem;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ServiceTicketSeeder extends Seeder
{
    /**
     * Run the database seeds for Services & Workshop menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $warehouse = Warehouse::where('team_id', $team->id)->first();
        $customers = Customer::where('team_id', $team->id)->get();

        if ($customers->isEmpty()) {
            return;
        }

        $ticketsData = [
            [
                'number'         => 'SRV-2026-001',
                'cust_idx'       => 0,
                'device'         => 'Dell XPS 15 9520 Workstation',
                'brand'          => 'Dell',
                'serial'         => 'DL-XPS-9921',
                'accessories'    => 'Power Adapter 130W, Sleeve Case',
                'problem'        => 'Loud fan grinding noise and thermal throttling during CAD render runs.',
                'diagnosis'      => 'Dust clogging and worn bearing in left cooling fan assembly.',
                'status'         => 'delivered',
                'priority'       => 'high',
                'service_charge' => 80.00,
                'part_name'      => 'Dual High-Speed CPU/GPU Cooling Fan Kit',
                'part_qty'       => 1,
                'part_cost'      => 30.00,
                'part_price'     => 55.00,
                'paid_amount'    => 135.00,
                'due_amount'     => 0.00,
                'payment_status' => 'paid',
                'received_date'  => now()->subDays(14)->toDateString(),
                'completed_date' => now()->subDays(11)->toDateString(),
                'delivered_date' => now()->subDays(10)->toDateString(),
                'warranty_days'  => 90,
            ],
            [
                'number'         => 'SRV-2026-002',
                'cust_idx'       => 1,
                'device'         => 'MacBook Pro 16" M2 Max',
                'brand'          => 'Apple',
                'serial'         => 'C02G9012MD6',
                'accessories'    => 'Original MagSafe Cable',
                'problem'        => 'Battery health message service recommended, battery swollen pushing trackpad.',
                'diagnosis'      => 'Swollen battery pack requiring replacement and chassis recalibration.',
                'status'         => 'repaired',
                'priority'       => 'normal',
                'service_charge' => 120.00,
                'part_name'      => 'OEM 99.6Wh Lithium Polymer Internal Battery',
                'part_qty'       => 1,
                'part_cost'      => 110.00,
                'part_price'     => 195.00,
                'paid_amount'    => 315.00,
                'due_amount'     => 0.00,
                'payment_status' => 'paid',
                'received_date'  => now()->subDays(8)->toDateString(),
                'completed_date' => now()->subDays(2)->toDateString(),
                'delivered_date' => null,
                'warranty_days'  => 180,
            ],
            [
                'number'         => 'SRV-2026-003',
                'cust_idx'       => 2,
                'device'         => 'HP Z4 G4 Tower Workstation',
                'brand'          => 'HP',
                'serial'         => '2UA89102KL',
                'accessories'    => 'Power cord',
                'problem'        => 'No display signal on boot. Motherboard post beeps 3 long 2 short.',
                'diagnosis'      => 'GPU PCIe contact oxidation and CMOS battery depleted.',
                'status'         => 'diagnosing',
                'priority'       => 'urgent',
                'service_charge' => 60.00,
                'part_name'      => 'Bench Diagnostics & Contact Deoxidation',
                'part_qty'       => 1,
                'part_cost'      => 5.00,
                'part_price'     => 15.00,
                'paid_amount'    => 0.00,
                'due_amount'     => 75.00,
                'payment_status' => 'unpaid',
                'received_date'  => now()->subDays(2)->toDateString(),
                'completed_date' => null,
                'delivered_date' => null,
                'warranty_days'  => 30,
            ],
            [
                'number'         => 'SRV-2026-004',
                'cust_idx'       => 3,
                'device'         => 'Lenovo ThinkPad T14 Gen 3',
                'brand'          => 'Lenovo',
                'serial'         => 'PF-289011',
                'accessories'    => 'Charger 65W Type-C',
                'problem'        => 'Coffee spill over keyboard; spacebar and enter keys unresponsive.',
                'diagnosis'      => 'Liquid damage isolated to keyboard top assembly; board clean.',
                'status'         => 'waiting_parts',
                'priority'       => 'normal',
                'service_charge' => 90.00,
                'part_name'      => 'Backlit US English Keyboard Assembly',
                'part_qty'       => 1,
                'part_cost'      => 45.00,
                'part_price'     => 85.00,
                'paid_amount'    => 100.00,
                'due_amount'     => 75.00,
                'payment_status' => 'partially_paid',
                'received_date'  => now()->subDays(5)->toDateString(),
                'completed_date' => null,
                'delivered_date' => null,
                'warranty_days'  => 90,
            ],
            [
                'number'         => 'SRV-2026-005',
                'cust_idx'       => 4,
                'device'         => 'Cisco Catalyst 2960-X 48-Port Switch',
                'brand'          => 'Cisco',
                'serial'         => 'FCW2109A01',
                'accessories'    => 'Rack ears and power cord',
                'problem'        => 'Power supply fan failing, unit overheating and shutting down randomly.',
                'diagnosis'      => 'Internal secondary switching power supply fan failure.',
                'status'         => 'received',
                'priority'       => 'normal',
                'service_charge' => 150.00,
                'part_name'      => 'Internal Delta 40mm Server Fan Module',
                'part_qty'       => 2,
                'part_cost'      => 20.00,
                'part_price'     => 45.00,
                'paid_amount'    => 0.00,
                'due_amount'     => 195.00,
                'payment_status' => 'unpaid',
                'received_date'  => now()->subDays(1)->toDateString(),
                'completed_date' => null,
                'delivered_date' => null,
                'warranty_days'  => 90,
            ],
        ];

        foreach ($ticketsData as $t) {
            $customer = $customers[$t['cust_idx'] % $customers->count()];
            $partsTotal = $t['part_qty'] * $t['part_price'];
            $totalAmount = $t['service_charge'] + $partsTotal;

            $ticket = ServiceTicket::updateOrCreate(
                [
                    'team_id'       => $team->id,
                    'ticket_number' => $t['number'],
                ],
                [
                    'customer_id'               => $customer->id,
                    'device_name'               => $t['device'],
                    'device_brand'              => $t['brand'],
                    'serial_no'                 => $t['serial'],
                    'accessories_received'      => $t['accessories'],
                    'problem_description'       => $t['problem'],
                    'initial_diagnosis'         => $t['diagnosis'],
                    'status'                    => $t['status'],
                    'priority'                  => $t['priority'],
                    'service_charge'            => $t['service_charge'],
                    'parts_total'               => $partsTotal,
                    'discount_amount'           => 0.00,
                    'total_amount'              => $totalAmount,
                    'paid_amount'               => $t['paid_amount'],
                    'due_amount'                => $t['due_amount'],
                    'payment_status'            => $t['payment_status'],
                    'warranty_days'             => $t['warranty_days'],
                    'received_date'             => $t['received_date'],
                    'estimated_completion_date' => now()->addDays(3)->toDateString(),
                    'completed_date'            => $t['completed_date'],
                    'delivered_date'            => $t['delivered_date'],
                    'remarks'                   => 'Customer notified via SMS & Email notification.',
                    'created_by'                => $team->user_id,
                ]
            );

            ServiceTicketItem::updateOrCreate(
                [
                    'team_id'           => $team->id,
                    'service_ticket_id' => $ticket->id,
                    'part_name'         => $t['part_name'],
                ],
                [
                    'warehouse_id' => $warehouse?->id,
                    'quantity'     => $t['part_qty'],
                    'unit_cost'    => $t['part_cost'],
                    'unit_price'   => $t['part_price'],
                    'subtotal'     => $partsTotal,
                    'notes'        => 'Item used in workshop repair job.',
                ]
            );
        }
    }
}
