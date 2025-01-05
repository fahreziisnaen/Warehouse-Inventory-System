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
        $initialStatuses = ['baru', 'bekas'];

        foreach ($partNumbers as $partNumber) {
            // Tambah jumlah item per part number (dari 8-12 menjadi 15-20)
            $numItems = rand(15, 20);
            
            for ($i = 0; $i < $numItems; $i++) {
                Item::create([
                    'part_number_id' => $partNumber->part_number_id,
                    'serial_number' => $this->generateSerialNumber(
                        $partNumber->brand->brand_name,
                        $partNumber->part_number
                    ),
                    'status' => $initialStatuses[array_rand($initialStatuses)],
                ]);
            }
        }
    }
} 