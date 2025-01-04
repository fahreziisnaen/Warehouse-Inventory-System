<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_name' => 'PT Supplier Utama',
                'address' => 'Jl. Supplier No. 1, Jakarta',
                'contact_info' => '021-1234567',
            ],
            [
                'supplier_name' => 'CV Maju Jaya',
                'address' => 'Jl. Maju No. 2, Bandung',
                'contact_info' => '022-7654321',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
