<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CommissionTierSeeder::class,
            InitialSuperAdminSeeder::class,
            UnitOfMeasureSeeder::class,
            BrandAndCategorySeeder::class,
            SystemSettingSeeder::class,
            ArticleCategorySeeder::class,
            ProductAndSkuSampleSeeder::class,
        ]);
    }
}
