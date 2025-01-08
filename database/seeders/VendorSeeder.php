<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Buat vendor types terlebih dahulu
        $supplierType = VendorType::firstOrCreate(['type_name' => 'Supplier']);
        $customerType = VendorType::firstOrCreate(['type_name' => 'Customer']);

        $vendors = [
            [
                'vendor_name' => 'PT Lintas Data Prima',
                'vendor_type_id' => $supplierType->vendor_type_id,
                'address' => 'Jakarta Selatan'
            ],
            [
                'vendor_name' => 'PT Global Network Solution',
                'vendor_type_id' => $customerType->vendor_type_id,
                'address' => 'Jakarta Pusat'
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::firstOrCreate(
                ['vendor_name' => $vendor['vendor_name']],
                $vendor
            );
        }
    }
} 