<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PartNumber;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    private function generateSerialNumber($brand, $partNumber): string
    {
        $serialFormats = [
            'Cisco' => [
                'prefix' => 'FOC',
                'length' => 11,
                'format' => 'FOC%s%04d'
            ],
            'Juniper' => [
                'prefix' => 'JN',
                'length' => 11,
                'format' => 'JN%s%05d'
            ],
            'Fortinet' => [
                'prefix' => 'FGT',
                'length' => 14,
                'format' => 'FGT%sT%06d'
            ],
            'Palo Alto' => [
                'prefix' => 'PA',
                'length' => 12,
                'format' => 'PA-%s-%05d'
            ],
        ];

        $format = $serialFormats[$brand] ?? [
            'prefix' => 'SN',
            'length' => 12,
            'format' => 'SN%s%06d'
        ];

        $timestamp = strtoupper(substr(md5(microtime()), 0, 4));
        $sequence = rand(1, 99999);

        return sprintf($format['format'], $timestamp, $sequence);
    }

    public function run(): void
    {
        $partNumbers = PartNumber::with('brand')->get();
        $statuses = ['baru', 'bekas', 'diterima', 'terjual', 'dipinjam', 'masa_sewa'];

        foreach ($partNumbers as $partNumber) {
            // Buat 1 item untuk setiap status
            foreach ($statuses as $status) {
                Item::create([
                    'part_number_id' => $partNumber->part_number_id,
                    'serial_number' => $this->generateSerialNumber(
                        $partNumber->brand->brand_name,
                        $partNumber->part_number
                    ),
                    'status' => $status,
                ]);
            }

            // Tambah beberapa item random
            for ($i = 1; $i <= 2; $i++) {
                Item::create([
                    'part_number_id' => $partNumber->part_number_id,
                    'serial_number' => $this->generateSerialNumber(
                        $partNumber->brand->brand_name,
                        $partNumber->part_number
                    ),
                    'status' => $statuses[array_rand($statuses)],
                ]);
            }
        }
    }
} 