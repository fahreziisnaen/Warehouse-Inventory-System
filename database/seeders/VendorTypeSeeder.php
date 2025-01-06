<?php

namespace Database\Seeders;

use App\Models\VendorType;
use Illuminate\Database\Seeder;

class VendorTypeSeeder extends Seeder
{
    public function run(): void
    {
        VendorType::firstOrCreate(['type_name' => 'Supplier']);
        VendorType::firstOrCreate(['type_name' => 'Customer']);
    }
} 