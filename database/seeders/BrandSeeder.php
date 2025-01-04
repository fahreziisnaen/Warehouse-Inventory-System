<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'brand_name' => 'Schneider Electric',
                'description' => 'Peralatan listrik dan otomasi industri',
            ],
            [
                'brand_name' => 'Siemens',
                'description' => 'Solusi otomasi dan kontrol industri',
            ],
            [
                'brand_name' => 'ABB',
                'description' => 'Teknologi elektrifitasi dan otomasi',
            ],
            [
                'brand_name' => 'Rockwell Automation',
                'description' => 'Solusi otomasi industri dan informasi',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
