<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PartNumber;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $partNumbers = PartNumber::all();

        foreach ($partNumbers as $partNumber) {
            // Buat 5 item untuk setiap part number
            for ($i = 1; $i <= 5; $i++) {
                Item::create([
                    'part_number_id' => $partNumber->part_number_id,
                    'serial_number' => $partNumber->part_number . '-SN' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'status' => 'available',
                    'manufacture_date' => now()->subDays(rand(1, 365)),
                ]);
            }
        }
    }
} 