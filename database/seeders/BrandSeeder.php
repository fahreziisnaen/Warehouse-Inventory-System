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
            'Juniper',
            'Fortinet',
            'Palo Alto',
            'Arista',
            'HPE',
            'Mikrotik',
            'Ubiquiti',
        ];

        foreach ($brands as $brandName) {
            Brand::create(['brand_name' => $brandName]);
        }
    }
}
