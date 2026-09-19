<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandAndCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 18 Official Global Partner Principals across all industrial divisions
        $brands = [
            // Elektrikal & Tenaga Listrik
            [
                'name' => 'Schneider Electric',
                'description' => 'Distribusi tenaga listrik, MCB, MCCB, ACB, kontaktor TeSys, inverter Altivar, sensor telemecanique, dan relay otomasi.',
                'logo_path' => 'brands/schneider-electric.webp',
                'is_active' => true,
            ],
            [
                'name' => 'ABB',
                'description' => 'Sakelar pemutus beban, isolator switch, motor starter, miniature circuit breaker, dan sistem proteksi kelistrikan industri berat.',
                'logo_path' => 'brands/abb.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Legrand',
                'description' => 'Sistem pengkabelan terstruktur, kabel tray, stop kontak industri komersial, box panel industri, dan modul otomasi gedung.',
                'logo_path' => 'brands/legrand.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Chint',
                'description' => 'Peralatan distribusi tenaga listrik, modular DIN-rail MCB/MCCB, kontaktor AC, kapasitor bank, dan panel kontrol industri berstandar internasional.',
                'logo_path' => 'brands/chint.webp',
                'is_active' => true,
            ],

            // Pencahayaan Industri & Komersial
            [
                'name' => 'Philips Lighting / Signify',
                'description' => 'Pencahayaan industri komersial, high-bay LED, lampu sorot stadion/proyek, downlight architectural, dan armatur tahan ledakan.',
                'logo_path' => 'brands/philips-lighting.webp',
                'is_active' => true,
            ],

            // Mekanikal, Pompa, & Pemipaan
            [
                'name' => 'Kitz',
                'description' => 'Katup industri berkualitas tinggi, ball valve, butterfly valve, gate valve, globe valve, dan check valve berbahan stainless steel, carbon steel, dan ductile iron.',
                'logo_path' => 'brands/kitz.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Grundfos',
                'description' => 'Pompa sentrifugal industri, pompa submersible, booster pump set, dan solusi sirkulasi air efisiensi tinggi untuk manufaktur dan fasilitas komersial.',
                'logo_path' => 'brands/grundfos.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Torishima',
                'description' => 'Pompa industri berat, end suction pump, high-pressure boiler feed pump, dan sistem pemompaan pembangkit listrik dan instalasi pabrik.',
                'logo_path' => 'brands/torishima.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Rucika',
                'description' => 'Sistem perpipaan industri lengkap, pipa PVC-O, HDPE, PPR, pipa baja saluran, serta fitting, flange, dan gasket bertekanan tinggi.',
                'logo_path' => 'brands/rucika.webp',
                'is_active' => true,
            ],

            // Otomasi Pabrik, Kontrol, & Sensor
            [
                'name' => 'Omron',
                'description' => 'Komponen otomasi pabrik, sensor fotolistrik, proximity sensor, limit switch, PLC (Sysmac), relay industri, dan pengatur suhu digital.',
                'logo_path' => 'brands/omron.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Mitsubishi Electric',
                'description' => 'Sistem kendali otomasi pabrik, inverter frekuensi (MELSERVO / FREQROL VFD), PLC seri FX/iQ-R, dan modul I/O cerdas.',
                'logo_path' => 'brands/mitsubishi-electric.webp',
                'is_active' => true,
            ],

            // Peralatan Keselamatan Kerja, Safety & K3 (HSE)
            [
                'name' => '3M',
                'description' => 'Peralatan keselamatan kerja personal (APD), masker respirator N95/FFP2, kacamata pelindung safety goggles, earplug peredam bising, dan harness keselamatan.',
                'logo_path' => 'brands/3m.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Cheetah',
                'description' => 'Sepatu keselamatan kerja industri (Safety Shoes) premium berstandar SNI dan EN ISO dengan toe-cap baja dan sol tahan minyak serta antistatis.',
                'logo_path' => 'brands/cheetah.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Krisbow Safety',
                'description' => 'Peralatan keselamatan kerja, helm proyek standar ANSI, rompi safety visibilitas tinggi, APAR powder & CO2, dan rambu K3 lapangan.',
                'logo_path' => 'brands/krisbow-safety.webp',
                'is_active' => true,
            ],

            // Perkakas Teknik & MRO Pabrik
            [
                'name' => 'Bosch',
                'description' => 'Perkakas listrik profesional (Power Tools), mesin bor rotary, gerinda tangan, impact wrench, dan aksesori mata teknik industri pemeliharaan pabrik.',
                'logo_path' => 'brands/bosch.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Tekiro',
                'description' => 'Perkakas tangan teknis (Hand Tools), kunci pas ring, soket mekanik, tang kombinasi, obeng industri, dan toolbox mekanik bengkel manufaktur.',
                'logo_path' => 'brands/tekiro.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Loctite',
                'description' => 'Bahan perekat teknik industri, threadlocker baut mesin, retaining compound, lem struktural, dan cairan sealant gasket pencegah kebocoran pabrik.',
                'logo_path' => 'brands/loctite.webp',
                'is_active' => true,
            ],
            [
                'name' => 'WD-40',
                'description' => 'Cairan pelumas serbaguna, pelindung anti-karat, pembersih kontak elektronik, dan penetran pembebas baut macet untuk pemeliharaan mesin MRO.',
                'logo_path' => 'brands/wd-40.webp',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand['name']],
                array_merge($brand, ['slug' => Str::slug($brand['name'])])
            );
        }

        // 2. Hierarchical Categories
        $taxonomy = [
            'Distribusi Daya Listrik' => [
                'Miniature Circuit Breaker (MCB)',
                'Molded Case Circuit Breaker (MCCB)',
                'Air Circuit Breaker (ACB)',
                'Kapasitor Bank & Power Quality',
            ],
            'Otomasi & Kendali Industri' => [
                'Variable Frequency Drive (Inverter VFD)',
                'Kontaktor & Thermal Overload Relay',
                'Programmable Logic Controller (PLC)',
                'Sensor & Limit Switch Industri',
            ],
            'Pencahayaan Industri Komersial' => [
                'High-Bay LED Industri',
                'Lampu Sorot & Floodlight Proyek',
                'Architectural Downlight',
                'Armatur Tahan Ledakan (Explosion-Proof)',
            ],
            'Infrastruktur Pengkabelan & Panel' => [
                'Kabel Tray & Cable Ladder',
                'Box Panel Industri IP66',
                'Stop Kontak & Steker Industri',
            ],
            'Mekanikal & Pemipaan Industri' => [
                'Industrial Valves (Ball, Butterfly, Gate, Globe)',
                'Pompa Sentrifugal & Booster',
                'Pipa Baja Seamless & Fitting Flange',
            ],
            'Keselamatan Kerja & K3 (HSE)' => [
                'Sepatu Safety & Boot K3',
                'Helm Proyek & Pelindung Kepala',
                'Sarung Tangan Proteksi Industri',
                'Alat Pemadam Api Ringan (APAR)',
            ],
            'Perkakas Teknik & MRO Pabrik' => [
                'Hand Tools & Kunci Mekanik',
                'Power Tools & Mesin Bor/Gerinda',
                'Pelumas, Grease, & Aerosol Industri',
            ],
        ];

        foreach ($taxonomy as $parentName => $subCategories) {
            $parent = Category::firstOrCreate(
                ['name' => $parentName],
                [
                    'slug' => Str::slug($parentName),
                    'parent_id' => null,
                    'description' => "Kategori induk perlengkapan {$parentName}.",
                ]
            );

            foreach ($subCategories as $subName) {
                Category::firstOrCreate(
                    ['name' => $subName],
                    [
                        'slug' => Str::slug($subName),
                        'parent_id' => $parent->id,
                        'description' => "Sub-kategori teknis {$subName}.",
                    ]
                );
            }
        }
    }
}
