<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Cisco',
            'Fortinet',
            'Generic'
        ];

        foreach ($brands as $brandName) {
            Brand::firstOrCreate(['brand_name' => $brandName]);
        }
    }
}
