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
        // Self-healing dependency check jika seeder dieksekusi mandiri
        if (Brand::count() === 0 || Category::count() === 0) {
            $this->call(BrandAndCategorySeeder::class);
        }

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
                'uom' => 'unit',
                'moq' => 1,
                'warranty_period' => '18 Bulan Resmi Schneider',
                'specs' => [
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Rated Current (AC-3)', 'spec_value' => '25', 'spec_unit' => 'A', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Control Circuit Voltage', 'spec_value' => '220', 'spec_unit' => 'V AC', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Kapasitas Beban', 'spec_key' => 'Motor Power Rating', 'spec_value' => '11', 'spec_unit' => 'kW', 'is_highlight' => true, 'sort_order' => 3],
                    ['spec_group' => 'Konfigurasi Fisik', 'spec_key' => 'Poles Description', 'spec_value' => '3P (3 NO)', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                'uom' => 'unit',
                'moq' => 1,
                'warranty_period' => '18 Bulan Resmi Schneider',
                'specs' => [
                    ['spec_group' => 'Kapasitas Daya', 'spec_key' => 'Motor Power', 'spec_value' => '5.5', 'spec_unit' => 'kW', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Supply Voltage', 'spec_value' => '380...500', 'spec_unit' => 'V AC', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Output Current', 'spec_value' => '14.3', 'spec_unit' => 'A', 'is_highlight' => false, 'sort_order' => 3],
                    ['spec_group' => 'Proteksi Lingkungan', 'spec_key' => 'Protection Rating', 'spec_value' => 'IP20', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                'uom' => 'unit',
                'moq' => 1,
                'warranty_period' => '18 Bulan Resmi Schneider',
                'specs' => [
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Rated Current (In)', 'spec_value' => '80', 'spec_unit' => 'A', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Breaking Capacity (Icu)', 'spec_value' => '36', 'spec_unit' => 'kA', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Mekanikal Proteksi', 'spec_key' => 'Trip Unit Name', 'spec_value' => 'TM-D Thermal-Magnetic', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 3],
                    ['spec_group' => 'Konfigurasi Fisik', 'spec_key' => 'Number of Poles', 'spec_value' => '3P', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                'uom' => 'unit',
                'moq' => 2,
                'warranty_period' => '36 Bulan Resmi Signify/Philips',
                'specs' => [
                    ['spec_group' => 'Kelistrikan & Daya', 'spec_key' => 'Power Consumption', 'spec_value' => '150', 'spec_unit' => 'W', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Optikal & Pencahayaan', 'spec_key' => 'Luminous Flux', 'spec_value' => '20,000', 'spec_unit' => 'Lumen', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Optikal & Pencahayaan', 'spec_key' => 'Correlated Color Temp', 'spec_value' => '6500', 'spec_unit' => 'K', 'is_highlight' => false, 'sort_order' => 3],
                    ['spec_group' => 'Proteksi Fisik', 'spec_key' => 'IP / IK Rating', 'spec_value' => 'IP65 / IK08', 'spec_unit' => null, 'is_highlight' => true, 'sort_order' => 4],
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
                'uom' => 'unit',
                'moq' => 2,
                'warranty_period' => '24 Bulan Resmi Signify/Philips',
                'specs' => [
                    ['spec_group' => 'Kelistrikan & Daya', 'spec_key' => 'Power Consumption', 'spec_value' => '100', 'spec_unit' => 'W', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Optikal & Pencahayaan', 'spec_key' => 'Luminous Flux', 'spec_value' => '8,500', 'spec_unit' => 'Lumen', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Optikal & Pencahayaan', 'spec_key' => 'Color Temperature', 'spec_value' => '4000', 'spec_unit' => 'K', 'is_highlight' => false, 'sort_order' => 3],
                    ['spec_group' => 'Proteksi Fisik', 'spec_key' => 'Ingress Protection', 'spec_value' => 'IP65', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                'uom' => 'pcs',
                'moq' => 10,
                'warranty_period' => '24 Bulan Resmi Signify/Philips',
                'specs' => [
                    ['spec_group' => 'Kelistrikan & Daya', 'spec_key' => 'System Power', 'spec_value' => '12', 'spec_unit' => 'W', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Dimensi Mekanikal', 'spec_key' => 'Cut-out Diameter', 'spec_value' => '150', 'spec_unit' => 'mm', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Optikal & Pencahayaan', 'spec_key' => 'Color Rendering Index', 'spec_value' => '> 80', 'spec_unit' => 'Ra', 'is_highlight' => false, 'sort_order' => 3],
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
                'uom' => 'pcs',
                'moq' => 5,
                'warranty_period' => '12 Bulan Resmi ABB',
                'specs' => [
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Rated Current (In)', 'spec_value' => '16', 'spec_unit' => 'A', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Karakteristik Trip', 'spec_key' => 'Tripping Characteristic', 'spec_value' => 'Type C', 'spec_unit' => null, 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Kapasitas Pemutus', 'spec_key' => 'Breaking Capacity', 'spec_value' => '6', 'spec_unit' => 'kA', 'is_highlight' => true, 'sort_order' => 3],
                    ['spec_group' => 'Konfigurasi Fisik', 'spec_key' => 'Poles Description', 'spec_value' => '3P', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                'uom' => 'unit',
                'moq' => 1,
                'warranty_period' => '12 Bulan Resmi ABB',
                'specs' => [
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Rated Thermal Current (Ith)', 'spec_value' => '63', 'spec_unit' => 'A', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Operational Voltage', 'spec_value' => '690', 'spec_unit' => 'V AC', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Mekanikal & Mounting', 'spec_key' => 'Mounting Type', 'spec_value' => 'Base mounting / DIN-rail', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 3],
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
                'uom' => 'unit',
                'moq' => 1,
                'warranty_period' => '12 Bulan Resmi ABB',
                'specs' => [
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Setting Range', 'spec_value' => '10.0 ... 16.0', 'spec_unit' => 'A', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Kapasitas Daya', 'spec_key' => 'Rated Operational Power', 'spec_value' => '7.5', 'spec_unit' => 'kW', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Proteksi Mekanikal', 'spec_key' => 'Phase Failure Sensitivity', 'spec_value' => 'Yes (IEC 60947-4-1)', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 3],
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
                'uom' => 'batang',
                'moq' => 4,
                'warranty_period' => '24 Bulan Garansi Material',
                'specs' => [
                    ['spec_group' => 'Dimensi Mekanikal', 'spec_key' => 'Dimensions (WxH)', 'spec_value' => '100 x 50', 'spec_unit' => 'mm', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Dimensi Mekanikal', 'spec_key' => 'Length', 'spec_value' => '3000', 'spec_unit' => 'mm', 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Material & Proteksi', 'spec_key' => 'Material Finish', 'spec_value' => 'Hot-Dip Galvanized (HDG)', 'spec_unit' => null, 'is_highlight' => true, 'sort_order' => 3],
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
                'uom' => 'pcs',
                'moq' => 5,
                'warranty_period' => '12 Bulan Resmi Legrand',
                'specs' => [
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Rated Current', 'spec_value' => '16', 'spec_unit' => 'A', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Konfigurasi Fisik', 'spec_key' => 'Poles Configuration', 'spec_value' => '3P + E', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 2],
                    ['spec_group' => 'Kelistrikan', 'spec_key' => 'Voltage Range', 'spec_value' => '380 - 415', 'spec_unit' => 'V AC', 'is_highlight' => true, 'sort_order' => 3],
                    ['spec_group' => 'Proteksi Lingkungan', 'spec_key' => 'Protection Degree', 'spec_value' => 'IP44', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                'uom' => 'unit',
                'moq' => 1,
                'warranty_period' => '24 Bulan Resmi Legrand',
                'specs' => [
                    ['spec_group' => 'Dimensi Fisik', 'spec_key' => 'Dimensions (HxWxD)', 'spec_value' => '400 x 300 x 206', 'spec_unit' => 'mm', 'is_highlight' => true, 'sort_order' => 1],
                    ['spec_group' => 'Proteksi Lingkungan', 'spec_key' => 'Protection Standards', 'spec_value' => 'IP66 / IK10', 'spec_unit' => null, 'is_highlight' => true, 'sort_order' => 2],
                    ['spec_group' => 'Material Box', 'spec_key' => 'Material', 'spec_value' => 'Fiberglass Reinforced Polyester', 'spec_unit' => null, 'is_highlight' => true, 'sort_order' => 3],
                    ['spec_group' => 'Mekanikal Pintu', 'spec_key' => 'Door Opening', 'spec_value' => '180 Degrees Reversible', 'spec_unit' => null, 'is_highlight' => false, 'sort_order' => 4],
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
                    'uom' => $item['uom'] ?? 'pcs',
                    'moq' => $item['moq'] ?? 1,
                    'warranty_period' => $item['warranty_period'] ?? '12 Bulan Garansi Resmi',
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
                        'spec_group' => $spec['spec_group'] ?? 'Spesifikasi Teknis',
                        'spec_value' => $spec['spec_value'],
                        'spec_unit' => $spec['spec_unit'] ?? null,
                        'is_highlight' => $spec['is_highlight'] ?? false,
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
