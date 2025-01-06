<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $supplierType = VendorType::where('type_name', 'Supplier')->first();
        $customerType = VendorType::where('type_name', 'Customer')->first();

        // Sample Suppliers
        Vendor::create([
            'vendor_type_id' => $supplierType->vendor_type_id,
            'vendor_name' => 'PT Supplier Utama',
            'address' => 'Jl. Supplier No. 1, Jakarta Selatan',
        ]);

        Vendor::create([
            'vendor_type_id' => $supplierType->vendor_type_id,
            'vendor_name' => 'CV Maju Jaya',
            'address' => 'Jl. Maju No. 123, Surabaya',
        ]);

        // Sample Customers
        Vendor::create([
            'vendor_type_id' => $customerType->vendor_type_id,
            'vendor_name' => 'PT Customer Sejahtera',
            'address' => 'Jl. Customer No. 45, Bandung',
        ]);

        Vendor::create([
            'vendor_type_id' => $customerType->vendor_type_id,
            'vendor_name' => 'CV Pelanggan Setia',
            'address' => 'Jl. Setia No. 67, Semarang',
        ]);
    }
} 