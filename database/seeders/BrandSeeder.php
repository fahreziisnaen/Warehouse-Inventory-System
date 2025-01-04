<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'brand_name' => 'Brand A',
                'description' => 'Description for Brand A',
            ],
            [
                'brand_name' => 'Brand B',
                'description' => 'Description for Brand B',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
