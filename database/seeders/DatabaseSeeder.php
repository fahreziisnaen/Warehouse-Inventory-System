<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            VendorTypeSeeder::class,
            VendorSeeder::class,
            BrandSeeder::class,
            PartNumberSeeder::class,
            ItemSeeder::class,
            ProjectSeeder::class,
            PurchaseOrderSeeder::class,
            InboundRecordSeeder::class,
            InboundItemSeeder::class,
            PurposeSeeder::class,
            OutboundRecordSeeder::class,
            OutboundItemSeeder::class,
        ]);
    }
}
