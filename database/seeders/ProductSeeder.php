<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds for Products & Catalog menu.
     */
    public function run(): void
    {
        $team = Team::first();
        if (!$team) {
            return;
        }

        $defaultWarehouse = Warehouse::where('team_id', $team->id)->where('is_default', true)->first()
            ?? Warehouse::where('team_id', $team->id)->first();

        // 1. Categories
        $categoriesData = [
            ['name' => 'Enterprise Laptops', 'description' => 'Commercial-grade laptops and portable workstations'],
            ['name' => 'Networking Gear', 'description' => 'Enterprise switches, routers, and APs'],
            ['name' => 'Office Peripherals', 'description' => 'Keyboards, mice, webcams, and docking stations'],
            ['name' => 'Power & Protection', 'description' => 'Online UPS, surge protectors, and battery units'],
            ['name' => 'Storage & Server', 'description' => 'Enterprise NAS drives, NVMe arrays, and server parts'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['name']] = ProductCategory::updateOrCreate(
                ['team_id' => $team->id, 'name' => $cat['name']],
                ['team_id' => $team->id, 'name' => $cat['name'], 'description' => $cat['description']]
            );
        }

        // 2. Brands
        $brandsData = [
            ['name' => 'Dell Enterprise', 'description' => 'Commercial laptops, servers and enterprise hardware'],
            ['name' => 'Cisco Systems', 'description' => 'Enterprise routing, switching and wireless hardware'],
            ['name' => 'Logitech', 'description' => 'Commercial workspace peripherals and collaboration tools'],
            ['name' => 'APC Schneider', 'description' => 'Uninterruptible power supplies and rack infrastructure'],
            ['name' => 'Western Digital', 'description' => 'Enterprise storage and NAS hard drives'],
        ];

        $brands = [];
        foreach ($brandsData as $b) {
            $brands[$b['name']] = Brand::updateOrCreate(
                ['team_id' => $team->id, 'name' => $b['name']],
                [
                    'team_id'     => $team->id,
                    'name'        => $b['name'],
                    'slug'        => \Illuminate\Support\Str::slug($b['name']),
                    'description' => $b['description'],
                ]
            );
        }

        // 3. 5 Demo Products
        $products = [
            [
                'name'          => 'Dell Latitude 5540 15.6" i7-1365U',
                'sku'           => 'PROD-DELL-5540',
                'barcode'       => '884116421092',
                'category_id'   => $categories['Enterprise Laptops']->id,
                'brand_id'      => $brands['Dell Enterprise']->id,
                'unit'          => 'pcs',
                'cost_price'    => 850.00,
                'unit_price'    => 1200.00,
                'reorder_level' => 10,
                'warranty_days' => 365,
                'is_serialized' => true,
                'has_variants'  => false,
                'description'   => '15.6" FHD, Core i7-1365U, 16GB DDR5 RAM, 512GB PCIe NVMe SSD, Windows 11 Pro.',
                'stock_qty'     => 45,
            ],
            [
                'name'          => 'Cisco Catalyst 1000 24-Port Gigabit PoE Switch',
                'sku'           => 'PROD-CISCO-C1000',
                'barcode'       => '889291029102',
                'category_id'   => $categories['Networking Gear']->id,
                'brand_id'      => $brands['Cisco Systems']->id,
                'unit'          => 'pcs',
                'cost_price'    => 420.00,
                'unit_price'    => 650.00,
                'reorder_level' => 5,
                'warranty_days' => 730,
                'is_serialized' => true,
                'has_variants'  => false,
                'description'   => '24-Port 10/100/1000 Ethernet with 4x 1G SFP uplinks, managed layer 2 switch.',
                'stock_qty'     => 28,
            ],
            [
                'name'          => 'Logitech MX Master 3S Wireless Performance Mouse',
                'sku'           => 'PROD-LOGI-MX3S',
                'barcode'       => '097855174628',
                'category_id'   => $categories['Office Peripherals']->id,
                'brand_id'      => $brands['Logitech']->id,
                'unit'          => 'pcs',
                'cost_price'    => 65.00,
                'unit_price'    => 99.00,
                'reorder_level' => 25,
                'warranty_days' => 365,
                'is_serialized' => false,
                'has_variants'  => false,
                'description'   => 'Quiet clicks, 8K DPI sensor on glass, MagSpeed scrolling, Bluetooth & Logi Bolt.',
                'stock_qty'     => 120,
            ],
            [
                'name'          => 'APC Smart-UPS 1500VA LCD Tower 230V (SMT1500I)',
                'sku'           => 'PROD-APC-SMT1500',
                'barcode'       => '731304268680',
                'category_id'   => $categories['Power & Protection']->id,
                'brand_id'      => $brands['APC Schneider']->id,
                'unit'          => 'pcs',
                'cost_price'    => 480.00,
                'unit_price'    => 720.00,
                'reorder_level' => 6,
                'warranty_days' => 730,
                'is_serialized' => true,
                'has_variants'  => false,
                'description'   => 'Intelligent and efficient network power protection from entry level to scaleable runtime.',
                'stock_qty'     => 18,
            ],
            [
                'name'          => 'Western Digital Red Pro 14TB SATA 7200 RPM NAS HDD',
                'sku'           => 'PROD-WD-14TB',
                'barcode'       => '718037871239',
                'category_id'   => $categories['Storage & Server']->id,
                'brand_id'      => $brands['Western Digital']->id,
                'unit'          => 'pcs',
                'cost_price'    => 210.00,
                'unit_price'    => 320.00,
                'reorder_level' => 15,
                'warranty_days' => 1825,
                'is_serialized' => true,
                'has_variants'  => false,
                'description'   => 'Designed specifically for medium to large scale business NAS environments up to 24 bays.',
                'stock_qty'     => 64,
            ],
        ];

        foreach ($products as $prodData) {
            $stockQty = $prodData['stock_qty'];
            unset($prodData['stock_qty']);

            $product = Product::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'sku'     => $prodData['sku'],
                ],
                array_merge($prodData, [
                    'team_id'   => $team->id,
                    'is_active' => true,
                ])
            );

            // Populate initial warehouse stock level
            if ($defaultWarehouse) {
                StockLevel::updateOrCreate(
                    [
                        'team_id'      => $team->id,
                        'warehouse_id' => $defaultWarehouse->id,
                        'product_id'   => $product->id,
                    ],
                    [
                        'quantity'          => $stockQty,
                        'reserved_quantity' => 0,
                    ]
                );
            }
        }
    }
}
