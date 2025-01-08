<?php

namespace Database\Seeders;

use App\Models\UnitFormat;
use Illuminate\Database\Seeder;

class UnitFormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            'PCS',
            'BOX',
            'SET',
            'UNIT',
            'ROLL',
            'PACK',
            'METER',
            'LOT',
            'BUNDLE',
            'KG'
        ];

        foreach ($formats as $format) {
            UnitFormat::firstOrCreate(['name' => $format]);
        }
    }
} 