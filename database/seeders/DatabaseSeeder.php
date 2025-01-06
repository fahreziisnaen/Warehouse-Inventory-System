<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Master Data Dasar
            UserSeeder::class,
            VendorTypeSeeder::class,
            VendorSeeder::class,
            BrandSeeder::class,
            PartNumberSeeder::class,
            ProjectStatusSeeder::class,
            ProjectSeeder::class,
            PurchaseOrderSeeder::class,
            PurposeSeeder::class,

            // 2. Format Unit untuk Batch Items
            UnitFormatSeeder::class,

            // 3. Items dan Batch Items
            ItemSeeder::class,        // Membuat items dengan serial number
            BatchItemSeeder::class,   // Membuat batch items dengan format unit

            // 4. Transaksi Kombinasi
            CombinedTransactionSeeder::class,  // Membuat inbound/outbound dengan kombinasi serial number dan batch
        ]);
    }
}
