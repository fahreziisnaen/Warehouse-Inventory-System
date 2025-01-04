<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\PartNumber;
use Illuminate\Database\Seeder;

class PartNumberSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::all();

        foreach ($brands as $brand) {
            // Create sample part numbers for each brand
            for ($i = 1; $i <= 3; $i++) {
                PartNumber::create([
                    'brand_id' => $brand->brand_id,
                    'part_number' => $brand->brand_name . '-PN' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'description' => 'Sample ' . $brand->brand_name . ' Part ' . $i,
                    'specifications' => 'Technical specifications for ' . $brand->brand_name . ' part ' . $i,
                ]);
            }
        }
    }
} 