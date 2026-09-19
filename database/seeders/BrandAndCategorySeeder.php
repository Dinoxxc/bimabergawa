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
        // 1. 4 Official Global Principals
        $brands = [
            [
                'name' => 'Schneider Electric',
                'description' => 'Distribusi tenaga listrik, MCB, MCCB, ACB, kontaktor TeSys, inverter Altivar, sensor telemecanique, dan relay otomasi.',
                'logo_path' => 'brands/schneider-electric.webp',
                'is_active' => true,
            ],
            [
                'name' => 'Philips Lighting / Signify',
                'description' => 'Pencahayaan industri komersial, high-bay LED, lampu sorot stadion/proyek, downlight architectural, dan armatur tahan ledakan.',
                'logo_path' => 'brands/philips-lighting.webp',
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
