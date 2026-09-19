<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'code' => 'pcs',
                'name' => 'Pieces (Biji / Buah)',
                'description' => 'Satuan hitung umum untuk komponen elektrikal, mekanikal, dan perkakas.',
                'sort_order' => 1,
            ],
            [
                'code' => 'unit',
                'name' => 'Unit (Perangkat / Mesin)',
                'description' => 'Satuan untuk mesin, pompa, inverter, panel listrik, dan peralatan mandiri.',
                'sort_order' => 2,
            ],
            [
                'code' => 'set',
                'name' => 'Set Lengkap (Kit / Rakitan)',
                'description' => 'Satuan untuk rakitan plug-socket industri, baut panel set, atau perangkat berpasangan.',
                'sort_order' => 3,
            ],
            [
                'code' => 'meter',
                'name' => 'Meter Lari (m)',
                'description' => 'Satuan panjang untuk kabel listrik, selang hidrolik, dan pemipaan.',
                'sort_order' => 4,
            ],
            [
                'code' => 'batang',
                'name' => 'Batang (2.4m / 3m / 6m)',
                'description' => 'Satuan panjang kaku untuk kabel tray Legrand P31, pipa baja, dan profil konstruksi.',
                'sort_order' => 5,
            ],
            [
                'code' => 'roll',
                'name' => 'Roll / Gulung',
                'description' => 'Satuan gulungan untuk kawat, kabel fleksibel, dan isolasi listrik industri.',
                'sort_order' => 6,
            ],
            [
                'code' => 'box',
                'name' => 'Box / Dus Karton',
                'description' => 'Satuan kemasan kotak untuk lampu proyek, saklar, dan relay.',
                'sort_order' => 7,
            ],
            [
                'code' => 'pack',
                'name' => 'Pack / Bungkus',
                'description' => 'Satuan kemasan bungkus untuk kabel ties, terminal block, fuse link, dan skun kabel.',
                'sort_order' => 8,
            ],
            [
                'code' => 'pail',
                'name' => 'Pail (18 - 20 Liter)',
                'description' => 'Satuan ember industri untuk cat heavy-duty, grease pelumas, dan bahan kimia lantai.',
                'sort_order' => 9,
            ],
            [
                'code' => 'drum',
                'name' => 'Drum (200 - 209 Liter)',
                'description' => 'Satuan drum baja untuk oli mesin industri, minyak trafo, dan pelarut kimia.',
                'sort_order' => 10,
            ],
            [
                'code' => 'liter',
                'name' => 'Liter (L)',
                'description' => 'Satuan volume cairan kimia pembersih industri dan cairan penetran.',
                'sort_order' => 11,
            ],
            [
                'code' => 'kg',
                'name' => 'Kilogram (kg)',
                'description' => 'Satuan berat untuk timah solder, elektroda las, baut curah, dan serbuk abrasif.',
                'sort_order' => 12,
            ],
            [
                'code' => 'pasang',
                'name' => 'Pasang / Pair',
                'description' => 'Satuan alat pelindung diri K3 (sepatu safety, sarung tangan mekanik, ear plug).',
                'sort_order' => 13,
            ],
            [
                'code' => 'can',
                'name' => 'Can / Kaleng Aerosol',
                'description' => 'Satuan kaleng semprot untuk pelumas WD-40, kontak cleaner elektrikal, dan cat semprot.',
                'sort_order' => 14,
            ],
            [
                'code' => 'lot',
                'name' => 'Lot / Paket Proyek',
                'description' => 'Satuan borongan paket untuk pengadaan instalasi atau paket material proyek gedung.',
                'sort_order' => 15,
            ],
        ];

        foreach ($units as $unit) {
            UnitOfMeasure::updateOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'description' => $unit['description'],
                    'is_active' => true,
                    'sort_order' => $unit['sort_order'],
                ]
            );
        }
    }
}
