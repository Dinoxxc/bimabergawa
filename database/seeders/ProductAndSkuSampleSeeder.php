<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductAndSkuSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Map Brands
        $schneider = Brand::where('name', 'Schneider Electric')->first();
        $philips = Brand::where('name', 'Philips Lighting / Signify')->first();
        $abb = Brand::where('name', 'ABB')->first();
        $legrand = Brand::where('name', 'Legrand')->first();

        // Map Categories
        $catMcb = Category::where('name', 'Miniature Circuit Breaker (MCB)')->first();
        $catMccb = Category::where('name', 'Molded Case Circuit Breaker (MCCB)')->first();
        $catVfd = Category::where('name', 'Variable Frequency Drive (Inverter VFD)')->first();
        $catContactor = Category::where('name', 'Kontaktor & Thermal Overload Relay')->first();
        $catHighbay = Category::where('name', 'High-Bay LED Industri')->first();
        $catFloodlight = Category::where('name', 'Lampu Sorot & Floodlight Proyek')->first();
        $catDownlight = Category::where('name', 'Architectural Downlight')->first();
        $catTray = Category::where('name', 'Kabel Tray & Cable Ladder')->first();
        $catPanel = Category::where('name', 'Box Panel Industri IP66')->first();
        $catSocket = Category::where('name', 'Stop Kontak & Steker Industri')->first();

        $sampleProducts = [
            // Schneider Electric (3 SKU)
            [
                'brand_id' => $schneider?->id,
                'category_id' => $catContactor?->id,
                'sku' => 'LC1D25M7',
                'name' => 'TeSys D Contactor 25A 220VAC 3P',
                'technical_summary' => 'Kontaktor 3-pole 25A AC-3 11kW dengan koil 220V 50/60Hz dan auxiliary contact 1NO+1NC.',
                'cost_price' => 420000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Rated Current (AC-3)', 'spec_value' => '25 Ampere', 'sort_order' => 1],
                    ['spec_key' => 'Control Circuit Voltage', 'spec_value' => '220 V AC 50/60 Hz', 'sort_order' => 2],
                    ['spec_key' => 'Motor Power Rating', 'spec_value' => '11 kW at 380/400 V', 'sort_order' => 3],
                    ['spec_key' => 'Poles Description', 'spec_value' => '3P (3 NO)', 'sort_order' => 4],
                ],
            ],
            [
                'brand_id' => $schneider?->id,
                'category_id' => $catVfd?->id,
                'sku' => 'ATV320U55N4B',
                'name' => 'Altivar Machine ATV320 VFD 5.5kW 400V',
                'technical_summary' => 'Inverter industri 5.5kW / 7.5HP 3-phase 380-500V format buku dengan komunikasi Modbus & CANopen.',
                'cost_price' => 7850000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Motor Power', 'spec_value' => '5.5 kW (7.5 HP)', 'sort_order' => 1],
                    ['spec_key' => 'Supply Voltage', 'spec_value' => '380...500 V (-15...+10%)', 'sort_order' => 2],
                    ['spec_key' => 'Output Current', 'spec_value' => '14.3 A at 4 kHz', 'sort_order' => 3],
                    ['spec_key' => 'Protection Rating', 'spec_value' => 'IP20', 'sort_order' => 4],
                ],
            ],
            [
                'brand_id' => $schneider?->id,
                'category_id' => $catMccb?->id,
                'sku' => 'NSX100F-TM80D',
                'name' => 'Compact NSX100F MCCB 80A 3P 36kA',
                'technical_summary' => 'Circuit breaker cetak 80A 3-pole dengan trip unit termal-magnetik TMD dan kapasitas breaking 36kA pada 415V.',
                'cost_price' => 1750000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Rated Current (In)', 'spec_value' => '80 A at 40 °C', 'sort_order' => 1],
                    ['spec_key' => 'Breaking Capacity (Icu)', 'spec_value' => '36 kA at 380/415 V AC', 'sort_order' => 2],
                    ['spec_key' => 'Trip Unit Name', 'spec_value' => 'TM-D Thermal-Magnetic', 'sort_order' => 3],
                    ['spec_key' => 'Number of Poles', 'spec_value' => '3P', 'sort_order' => 4],
                ],
            ],

            // Philips Lighting / Signify (3 SKU)
            [
                'brand_id' => $philips?->id,
                'category_id' => $catHighbay?->id,
                'sku' => 'BY698P-LED200',
                'name' => 'GreenPerform Highbay G4 150W CW',
                'technical_summary' => 'Armatur lampu high-bay industri efisiensi tinggi 20.000 lumen 150W 6500K Cool White IP65 IK08.',
                'cost_price' => 1450000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Power Consumption', 'spec_value' => '150 Watt', 'sort_order' => 1],
                    ['spec_key' => 'Luminous Flux', 'spec_value' => '20,000 Lumen (135 lm/W)', 'sort_order' => 2],
                    ['spec_key' => 'Correlated Color Temp', 'spec_value' => '6500 K (Cool White)', 'sort_order' => 3],
                    ['spec_key' => 'IP / IK Rating', 'spec_value' => 'IP65 / IK08', 'sort_order' => 4],
                ],
            ],
            [
                'brand_id' => $philips?->id,
                'category_id' => $catFloodlight?->id,
                'sku' => 'BVP151-LED100',
                'name' => 'SmartBright G2 Floodlight 100W NW',
                'technical_summary' => 'Lampu sorot LED eksterior kokoh 100W 8500 lumen 4000K Neutral White aluminium die-cast.',
                'cost_price' => 620000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Power Consumption', 'spec_value' => '100 Watt', 'sort_order' => 1],
                    ['spec_key' => 'Luminous Flux', 'spec_value' => '8,500 Lumen', 'sort_order' => 2],
                    ['spec_key' => 'Color Temperature', 'spec_value' => '4000 K (Neutral White)', 'sort_order' => 3],
                    ['spec_key' => 'Ingress Protection', 'spec_value' => 'IP65 Weatherproof', 'sort_order' => 4],
                ],
            ],
            [
                'brand_id' => $philips?->id,
                'category_id' => $catDownlight?->id,
                'sku' => 'DN027B-LED12',
                'name' => 'SmartBright Downlight G2 12W 4000K',
                'technical_summary' => 'Downlight arsitektural plafon recessed 12W 1200 lumen diameter cut-out 150mm.',
                'cost_price' => 95000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'System Power', 'spec_value' => '12 Watt', 'sort_order' => 1],
                    ['spec_key' => 'Cut-out Diameter', 'spec_value' => '150 mm (6 inch)', 'sort_order' => 2],
                    ['spec_key' => 'Color Rendering Index', 'spec_value' => 'CRI > 80', 'sort_order' => 3],
                ],
            ],

            // ABB (3 SKU)
            [
                'brand_id' => $abb?->id,
                'category_id' => $catMcb?->id,
                'sku' => 'S203-C16',
                'name' => 'Miniature Circuit Breaker S200 3P 16A 6kA',
                'technical_summary' => 'MCB proteksi sirkuit 3-pole 16 Ampere Curve C kapasitas pemutus 6kA standar IEC/EN 60898-1.',
                'cost_price' => 265000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Rated Current (In)', 'spec_value' => '16 Ampere', 'sort_order' => 1],
                    ['spec_key' => 'Tripping Characteristic', 'spec_value' => 'Type C', 'sort_order' => 2],
                    ['spec_key' => 'Breaking Capacity', 'spec_value' => '6 kA at 400 V AC', 'sort_order' => 3],
                    ['spec_key' => 'Poles Description', 'spec_value' => '3P', 'sort_order' => 4],
                ],
            ],
            [
                'brand_id' => $abb?->id,
                'category_id' => $catContactor?->id,
                'sku' => 'OT63F3',
                'name' => 'Switch Disconnector 3P 63A Front Operated',
                'technical_summary' => 'Sakelar pemutus beban isolator switch 3-pole 63A base mount dengan poros pegangan standar industri.',
                'cost_price' => 480000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Rated Thermal Current (Ith)', 'spec_value' => '63 Ampere', 'sort_order' => 1],
                    ['spec_key' => 'Operational Voltage', 'spec_value' => 'up to 690 V', 'sort_order' => 2],
                    ['spec_key' => 'Mounting Type', 'spec_value' => 'Base mounting / DIN-rail', 'sort_order' => 3],
                ],
            ],
            [
                'brand_id' => $abb?->id,
                'category_id' => $catContactor?->id,
                'sku' => 'MS116-16',
                'name' => 'Manual Motor Starter MS116 10-16A',
                'technical_summary' => 'Proteksi motor listrik kompak dengan trip termal 10-16A dan proteksi arus pendek magnetik.',
                'cost_price' => 640000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Setting Range', 'spec_value' => '10.0 ... 16.0 Ampere', 'sort_order' => 1],
                    ['spec_key' => 'Rated Operational Power', 'spec_value' => '7.5 kW at 400 V AC', 'sort_order' => 2],
                    ['spec_key' => 'Phase Failure Sensitivity', 'spec_value' => 'Yes (IEC 60947-4-1)', 'sort_order' => 3],
                ],
            ],

            // Legrand (3 SKU)
            [
                'brand_id' => $legrand?->id,
                'category_id' => $catTray?->id,
                'sku' => 'P31-100X50',
                'name' => 'P31+ Cable Tray Perforated 100x50mm',
                'technical_summary' => 'Kabel tray berlubang galvanis hot-dip 100mm x 50mm panjang 3000mm standar IEC 61537.',
                'cost_price' => 185000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Dimensions (WxH)', 'spec_value' => '100 mm x 50 mm', 'sort_order' => 1],
                    ['spec_key' => 'Length', 'spec_value' => '3000 mm (3 Meter)', 'sort_order' => 2],
                    ['spec_key' => 'Material Finish', 'spec_value' => 'Hot-Dip Galvanized (HDG)', 'sort_order' => 3],
                ],
            ],
            [
                'brand_id' => $legrand?->id,
                'category_id' => $catSocket?->id,
                'sku' => 'HYPRA-055533',
                'name' => 'Hypra Industrial Surface Socket 3P+E 16A IP44',
                'technical_summary' => 'Stop kontak industri tempel dinding 16A 3-fasa + netral/earth 380-415V material tahan kimia IP44.',
                'cost_price' => 320000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Rated Current', 'spec_value' => '16 Ampere', 'sort_order' => 1],
                    ['spec_key' => 'Poles Configuration', 'spec_value' => '3P + E', 'sort_order' => 2],
                    ['spec_key' => 'Voltage Range', 'spec_value' => '380 - 415 V AC', 'sort_order' => 3],
                    ['spec_key' => 'Protection Degree', 'spec_value' => 'IP44', 'sort_order' => 4],
                ],
            ],
            [
                'brand_id' => $legrand?->id,
                'category_id' => $catPanel?->id,
                'sku' => 'MARINA-035504',
                'name' => 'Marina Polyester Enclosure IP66 400x300x206',
                'technical_summary' => 'Box panel industri fiberglass-reinforced polyester anti-karat IP66 IK10 cocok untuk area luar ruang korosif.',
                'cost_price' => 1650000.00,
                'stock_status' => 'ready',
                'specs' => [
                    ['spec_key' => 'Dimensions (HxWxD)', 'spec_value' => '400 x 300 x 206 mm', 'sort_order' => 1],
                    ['spec_key' => 'Protection Standards', 'spec_value' => 'IP66 / IK10', 'sort_order' => 2],
                    ['spec_key' => 'Material', 'spec_value' => 'Fiberglass Reinforced Polyester', 'sort_order' => 3],
                    ['spec_key' => 'Door Opening', 'spec_value' => '180 Degrees Reversible', 'sort_order' => 4],
                ],
            ],
        ];

        foreach ($sampleProducts as $item) {
            if (! $item['brand_id'] || ! $item['category_id']) {
                continue;
            }

            $product = Product::firstOrCreate(
                ['model_number' => $item['sku']],
                [
                    'brand_id' => $item['brand_id'],
                    'category_id' => $item['category_id'],
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name'].'-'.$item['sku']),
                    'model_number' => $item['sku'],
                    'description' => $item['technical_summary'],
                    'cost_price' => $item['cost_price'],
                    'currency' => 'IDR',
                    'availability_status' => 'ready_stock',
                    'is_featured' => true,
                    'is_published' => true,
                ]
            );

            // Seed Specs
            foreach ($item['specs'] as $spec) {
                ProductSpecification::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'spec_key' => $spec['spec_key'],
                    ],
                    [
                        'spec_value' => $spec['spec_value'],
                        'sort_order' => $spec['sort_order'],
                    ]
                );
            }

            // Seed Primary Image Placeholder
            ProductImage::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'is_primary' => true,
                ],
                [
                    'image_path' => 'products/'.Str::slug($product->model_number).'.webp',
                    'sort_order' => 1,
                ]
            );
        }
    }
}
