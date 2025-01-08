<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Independent data
            UserSeeder::class,           // Users untuk login
            UnitFormatSeeder::class,     // Format unit untuk batch items
            VendorTypeSeeder::class,     // Tipe vendor
            ProjectStatusSeeder::class,   // Status project
            PurposeSeeder::class,        // Tujuan outbound
            BrandSeeder::class,          // Brand untuk part number
            
            // Dependent data
            VendorSeeder::class,         // Butuh VendorType
            ProjectSeeder::class,        // Butuh Vendor dan ProjectStatus
            PartNumberSeeder::class,     // Butuh Brand
            PurchaseOrderSeeder::class,  // Butuh Vendor dan Project
            
            // Transactions dengan aturan bisnis lengkap
            CombinedTransactionSeeder::class,  // Menangani Inbound, Items, dan BatchItems
        ]);
    }
}
