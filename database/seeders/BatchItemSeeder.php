<?php

namespace Database\Seeders;

use App\Models\BatchItem;
use App\Models\PartNumber;
use App\Models\UnitFormat;
use Illuminate\Database\Seeder;

class BatchItemSeeder extends Seeder
{
    public function run(): void
    {
        $batchPartNumbers = [
            ['part_number' => 'RJ45-CAT6', 'description' => 'RJ45 Connector CAT6', 'format' => 'Piece'],
            ['part_number' => 'PATCH-2M', 'description' => 'Patch Cable 2 Meter', 'format' => 'Unit'],
            ['part_number' => 'PATCH-3M', 'description' => 'Patch Cable 3 Meter', 'format' => 'Unit'],
            ['part_number' => 'SFP-1G', 'description' => 'SFP Module 1G', 'format' => 'Unit'],
            ['part_number' => 'SFP-10G', 'description' => 'SFP Module 10G', 'format' => 'Unit'],
            ['part_number' => 'CABLE-UTP', 'description' => 'UTP Cable CAT6', 'format' => 'Box'],
            ['part_number' => 'CABLE-FIBER', 'description' => 'Fiber Optic Cable', 'format' => 'Roll'],
        ];

        $brand = \App\Models\Brand::firstOrCreate(
            ['brand_name' => 'Generic'],
            ['brand_name' => 'Generic']
        );

        foreach ($batchPartNumbers as $bpn) {
            $partNumber = PartNumber::firstOrCreate(
                ['part_number' => $bpn['part_number']],
                [
                    'brand_id' => $brand->brand_id,
                    'part_number' => $bpn['part_number'],
                    'description' => $bpn['description']
                ]
            );

            $format = UnitFormat::where('name', $bpn['format'])->first();

            BatchItem::firstOrCreate(
                ['part_number_id' => $partNumber->part_number_id],
                [
                    'quantity' => 0,
                    'format_id' => $format->format_id
                ]
            );
        }
    }
} 