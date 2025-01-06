<?php

namespace Database\Seeders;

use App\Models\UnitFormat;
use Illuminate\Database\Seeder;

class UnitFormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            'Unit',
            'Lonjor',
            'Pack',
            'Roll',
            'Box',
            'Meter',
            'Set',
            'Piece',
        ];

        foreach ($formats as $format) {
            UnitFormat::firstOrCreate(['name' => $format]);
        }
    }
} 