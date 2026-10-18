<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Team;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds for Quotations & Estimates menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $customers = Customer::where('team_id', $team->id)->get();
        $products = Product::where('team_id', $team->id)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $quotesData = [
            [
                'number'         => 'QT-2026-001',
                'customer_index' => 0,
                'status'         => 'accepted',
                'date'           => now()->subDays(15)->toDateString(),
                'valid_until'    => now()->addDays(15)->toDateString(),
                'product_index'  => 0, // Dell Laptops
                'quantity'       => 5,
                'unit_price'     => 1200.00,
                'tax_rate'       => 0.05,
                'terms'          => 'Payment 50% upfront, balance on delivery. 1 year comprehensive warranty included.',
                'notes'          => 'Client accepted price proposal for the workstation refresh.',
            ],
            [
                'number'         => 'QT-2026-002',
                'customer_index' => 1,
                'status'         => 'sent',
                'date'           => now()->subDays(10)->toDateString(),
                'valid_until'    => now()->addDays(20)->toDateString(),
                'product_index'  => 1, // Cisco Switches
                'quantity'       => 3,
                'unit_price'     => 650.00,
                'tax_rate'       => 0.05,
                'terms'          => 'Quotation valid for 30 calendar days. Delivery within 3 business days of PO.',
                'notes'          => 'Sent to IT director for quarterly networking upgrade review.',
            ],
            [
                'number'         => 'QT-2026-003',
                'customer_index' => 2,
                'status'         => 'draft',
                'date'           => now()->subDays(3)->toDateString(),
                'valid_until'    => now()->addDays(27)->toDateString(),
                'product_index'  => 3, // APC UPS
                'quantity'       => 4,
                'unit_price'     => 720.00,
                'tax_rate'       => 0.05,
                'terms'          => 'Standard commercial terms apply. On-site installation available on request.',
                'notes'          => 'Drafting bundle proposal including power surge protection.',
            ],
            [
                'number'         => 'QT-2026-004',
                'customer_index' => 3,
                'status'         => 'converted',
                'date'           => now()->subDays(20)->toDateString(),
                'valid_until'    => now()->subDays(5)->toDateString(),
                'product_index'  => 4, // WD Red HDD
                'quantity'       => 8,
                'unit_price'     => 320.00,
                'tax_rate'       => 0.05,
                'terms'          => 'Net 30 days after final sign-off. Enterprise hardware SLA.',
                'notes'          => 'Quotation converted successfully into Sales Order SO-2026-001.',
            ],
            [
                'number'         => 'QT-2026-005',
                'customer_index' => 4,
                'status'         => 'rejected',
                'date'           => now()->subDays(12)->toDateString(),
                'valid_until'    => now()->subDays(2)->toDateString(),
                'product_index'  => 2, // Logitech Mice
                'quantity'       => 20,
                'unit_price'     => 99.00,
                'tax_rate'       => 0.05,
                'terms'          => 'Full payment prior to dispatch.',
                'notes'          => 'Client opted for a different supplier with extended credit line.',
            ],
        ];

        foreach ($quotesData as $q) {
            $customer = $customers[$q['customer_index'] % $customers->count()];
            $product = $products[$q['product_index'] % $products->count()];
            $subtotal = $q['quantity'] * $q['unit_price'];
            $tax = $subtotal * $q['tax_rate'];
            $total = $subtotal + $tax;

            $quotation = Quotation::updateOrCreate(
                [
                    'team_id'          => $team->id,
                    'quotation_number' => $q['number'],
                ],
                [
                    'customer_id'     => $customer->id,
                    'status'          => $q['status'],
                    'quotation_date'  => $q['date'],
                    'valid_until'     => $q['valid_until'],
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0.00,
                    'tax_amount'      => $tax,
                    'total_amount'    => $total,
                    'terms'           => $q['terms'],
                    'notes'           => $q['notes'],
                    'created_by'      => $team->user_id,
                ]
            );

            QuotationItem::updateOrCreate(
                [
                    'team_id'      => $team->id,
                    'quotation_id' => $quotation->id,
                    'product_id'   => $product->id,
                ],
                [
                    'quantity'   => $q['quantity'],
                    'unit_price' => $q['unit_price'],
                    'subtotal'   => $subtotal,
                    'notes'      => 'Item line for ' . $product->name,
                ]
            );
        }
    }
}
