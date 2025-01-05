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
        $statuses = ['baru', 'bekas', 'diterima', 'terjual', 'dipinjam', 'masa_sewa'];

        foreach ($partNumbers as $partNumber) {
            // Buat 1 item untuk setiap status
            foreach ($statuses as $status) {
                Item::create([
                    'part_number_id' => $partNumber->part_number_id,
                    'serial_number' => $partNumber->part_number . '-' . strtoupper($status) . '-' . uniqid(),
                    'status' => $status,
                ]);
            }

            // Tambah beberapa item random jika diperlukan
            for ($i = 1; $i <= 2; $i++) {
                Item::create([
                    'part_number_id' => $partNumber->part_number_id,
                    'serial_number' => $partNumber->part_number . '-EXTRA-' . uniqid(),
                    'status' => $statuses[array_rand($statuses)],
                ]);
            }
        }
    }
} 