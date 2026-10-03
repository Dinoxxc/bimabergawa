<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schneiderBrand = Brand::where('name', 'like', '%Schneider%')->first();
        $abbBrand = Brand::where('name', 'like', '%ABB%')->first();
        $kitzBrand = Brand::where('name', 'like', '%Kitz%')->first();
        $omronBrand = Brand::where('name', 'like', '%Omron%')->first();

        $suppliers = [
            [
                'name' => 'PT. Schneider Prima Distributor Indonesia',
                'code' => 'SUP-SCH-001',
                'brand_id' => $schneiderBrand?->id,
                'contact_person' => 'Hendra Wijaya',
                'phone' => '08119876543',
                'email' => 'sales.order@schneider-dist.co.id',
                'address' => 'Kawasan Industri MM2100 Blok C-4, Cikarang Barat, Bekasi',
                'payment_terms' => 'Net 30',
                'discount_structure' => 'Pricelist Resmi - 35% (Net-Net)',
                'notes' => 'Distributor resmi Tier-1 komponen elektrikal & otomasi industri Schneider Electric.',
                'is_active' => true,
            ],
            [
                'name' => 'PT. Mega Daya Otomasi ABB',
                'code' => 'SUP-ABB-001',
                'brand_id' => $abbBrand?->id,
                'contact_person' => 'Bambang Priyono',
                'phone' => '08128889990',
                'email' => 'distribution@abb-megadaya.co.id',
                'address' => 'Jl. Daan Mogot Km. 14 No. 88, Kalideres, Jakarta Barat',
                'payment_terms' => 'Net 30',
                'discount_structure' => 'Pricelist ABB - 30%',
                'notes' => 'Mitra penyedia resmi breaker, kontaktor, dan inverter ABB Low Voltage.',
                'is_active' => true,
            ],
            [
                'name' => 'PT. Kitz Valve Trading Nusantara',
                'code' => 'SUP-KTZ-001',
                'brand_id' => $kitzBrand?->id,
                'contact_person' => 'Dedi Kurniawan',
                'phone' => '08561234567',
                'email' => 'order@kitzvalve-trading.co.id',
                'address' => 'Kawasan Pergudangan Pantai Indah Dadap Blok G-12, Tangerang',
                'payment_terms' => 'Net 14',
                'discount_structure' => 'Diskon Standar 28% + Diskon Proyek 5%',
                'notes' => 'Distributor katup industri Kitz (Ball Valve, Gate Valve, Check Valve ANSI/JIS).',
                'is_active' => true,
            ],
            [
                'name' => 'PT. Sensor & Otomasi Unggul (Omron)',
                'code' => 'SUP-OMR-001',
                'brand_id' => $omronBrand?->id,
                'contact_person' => 'Siska Maharani',
                'phone' => '08137776655',
                'email' => 'sales@otomasi-unggul.com',
                'address' => 'Ruko Gading Kirana Timur Blok A-11 No. 5, Kelapa Gading, Jakarta Utara',
                'payment_terms' => 'Net 30',
                'discount_structure' => 'Pricelist Omron - 32%',
                'notes' => 'Pemasok resmi sensor industri, timer, counter, dan PLC Omron Automation.',
                'is_active' => true,
            ],
            [
                'name' => 'CV. Mitra Perkakas & MRO Teknik',
                'code' => 'SUP-MRO-001',
                'brand_id' => null,
                'contact_person' => 'Agus Setiawan',
                'phone' => '087811223344',
                'email' => 'purchasing@mitra-mroteknik.co.id',
                'address' => 'Lindeteves Trade Center (LTC) Glodok Lt. GF2 Blok C-10, Jakarta Barat',
                'payment_terms' => 'COD',
                'discount_structure' => 'Harga Grosir Khusus B2B Toko',
                'notes' => 'Vendor pengadaan barang fast-moving MRO, baut fastener, dan chemical pemeliharaan pabrik.',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $data) {
            $supplier = Supplier::updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            // Jika supplier terhubung ke brand, hubungkan sample produk yang sesuai jika primary_supplier_id masih kosong
            if ($supplier->brand_id) {
                Product::where('brand_id', $supplier->brand_id)
                    ->whereNull('primary_supplier_id')
                    ->update(['primary_supplier_id' => $supplier->id]);
            }
        }
    }
}
