<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Distribusi Daya & Panel',
                'description' => 'Artikel teknis seputar sistem proteksi tenaga, transformator, ACB, dan MCCB industri.',
            ],
            [
                'name' => 'Otomasi & Efisiensi Energi',
                'description' => 'Panduan implementasi inverter Altivar, motor drive, PLC, dan instrumentasi pabrik.',
            ],
            [
                'name' => 'Pencahayaan Cerdas & LED',
                'description' => 'Studi kasus pencahayaan gedung komersial, high-bay hemat energi, dan standar lux industri.',
            ],
            [
                'name' => 'Regulasi & Standar Industri',
                'description' => 'Ulasan kepatuhan PUIL 2020, SNI, standar IEC, dan pembaruan regulasi keselamatan kerja.',
            ],
        ];

        foreach ($categories as $cat) {
            ArticleCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'slug' => Str::slug($cat['name']),
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
