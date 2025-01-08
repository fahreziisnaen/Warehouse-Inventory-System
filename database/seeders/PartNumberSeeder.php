<?php

namespace Database\Seeders;

use App\Models\PartNumber;
use App\Models\Brand;
use Illuminate\Database\Seeder;

class PartNumberSeeder extends Seeder
{
    public function run(): void
    {
        $partNumbersData = [
            'Cisco' => [
                ['part_number' => 'C9200L-24T-4G', 'description' => 'Catalyst 9200L 24-port data, 4 x 1G uplink'],
                ['part_number' => 'C9200L-48T-4G', 'description' => 'Catalyst 9200L 48-port data, 4 x 1G uplink'],
                ['part_number' => 'C9300-48U-E', 'description' => 'Catalyst 9300 48-port UPOE, Network Essentials'],
            ],
            'Fortinet' => [
                ['part_number' => 'FG-60F', 'description' => 'FortiGate 60F Hardware plus 1 Year 24x7 FortiCare'],
                ['part_number' => 'FG-100F', 'description' => 'FortiGate 100F Hardware plus 1 Year 24x7 FortiCare'],
            ],
            'Generic' => [
                ['part_number' => 'CAB-CONSOLE-USB', 'description' => 'Console Cable 6ft with USB Type-A and mini-USB'],
                ['part_number' => 'CAB-ETH-S-RJ45', 'description' => 'Ethernet Patch Cable CAT6 5M'],
                ['part_number' => 'SFP-10G-AOC3M', 'description' => 'SFP+ Active Optical Cable 3M'],
                ['part_number' => 'CAB-AC-250V', 'description' => 'AC Power Cord 250V'],
                ['part_number' => 'STACK-T1-50CM', 'description' => 'Stacking Cable 50CM'],
                ['part_number' => 'SP-FG60F', 'description' => 'Power Supply for FortiGate 60F'],
                ['part_number' => 'SP-FG100F', 'description' => 'Power Supply for FortiGate 100F'],
                ['part_number' => 'SP-CABLE-USB', 'description' => 'USB Console Cable'],
                ['part_number' => 'SP-RACKMOUNT', 'description' => 'Rack Mount Tray Kit'],
                ['part_number' => 'PC-C6-5M-BL', 'description' => 'Patch Cord CAT6 5M Blue'],
                ['part_number' => 'PC-C6-3M-BL', 'description' => 'Patch Cord CAT6 3M Blue'],
                ['part_number' => 'PC-C6-1M-BL', 'description' => 'Patch Cord CAT6 1M Blue'],
                ['part_number' => 'DAC-10G-3M', 'description' => 'Direct Attach Cable 10G SFP+ 3M'],
                ['part_number' => 'DAC-10G-5M', 'description' => 'Direct Attach Cable 10G SFP+ 5M'],
                ['part_number' => 'SFP-10G-SR', 'description' => '10G SFP+ SR Transceiver'],
                ['part_number' => 'SFP-1G-T', 'description' => '1G SFP Copper RJ45 Transceiver'],
            ],
        ];

        foreach ($partNumbersData as $brandName => $partNumbers) {
            $brand = Brand::firstOrCreate(['brand_name' => $brandName]);

            foreach ($partNumbers as $pn) {
                PartNumber::firstOrCreate(
                    ['part_number' => $pn['part_number']],
                    [
                        'brand_id' => $brand->brand_id,
                        'part_number' => $pn['part_number'],
                        'description' => $pn['description']
                    ]
                );
            }
        }
    }
} 