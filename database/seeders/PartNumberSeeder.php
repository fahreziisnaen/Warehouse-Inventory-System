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
            // Perangkat Network
            'Cisco' => [
                ['part_number' => 'C9200L-24T-4G', 'description' => 'Catalyst 9200L 24-port data, 4 x 1G uplink'],
                ['part_number' => 'C9200L-48T-4G', 'description' => 'Catalyst 9200L 48-port data, 4 x 1G uplink'],
                ['part_number' => 'C9300-48U-E', 'description' => 'Catalyst 9300 48-port UPOE, Network Essentials'],
                ['part_number' => 'WS-C2960X-48TS-L', 'description' => 'Catalyst 2960-X 48 GigE, 4 x 1G SFP, LAN Base'],
            ],
            // Perangkat Security
            'Fortinet' => [
                ['part_number' => 'FG-60F', 'description' => 'FortiGate 60F Hardware plus 1 Year 24x7 FortiCare'],
                ['part_number' => 'FG-100F', 'description' => 'FortiGate 100F Hardware plus 1 Year 24x7 FortiCare'],
                ['part_number' => 'FG-200F', 'description' => 'FortiGate 200F Hardware plus 1 Year 24x7 FortiCare'],
            ],
            // Server
            'HPE' => [
                ['part_number' => 'DL380-G10', 'description' => 'HPE ProLiant DL380 Gen10 Server'],
                ['part_number' => 'DL360-G10', 'description' => 'HPE ProLiant DL360 Gen10 Server'],
            ],
            // Perangkat Network
            'Juniper' => [
                ['part_number' => 'EX2300-48T', 'description' => 'Juniper EX2300 48-port Gigabit'],
                ['part_number' => 'EX3400-48T', 'description' => 'Juniper EX3400 48-port Gigabit'],
            ],
            // Perlengkapan Kabel
            'AMP' => [
                ['part_number' => 'PC-C6-05M', 'description' => 'Patch Cord CAT6 0.5M'],
                ['part_number' => 'PC-C6-1M', 'description' => 'Patch Cord CAT6 1M'],
                ['part_number' => 'PC-C6-2M', 'description' => 'Patch Cord CAT6 2M'],
                ['part_number' => 'PC-C6-3M', 'description' => 'Patch Cord CAT6 3M'],
                ['part_number' => 'PC-C6-5M', 'description' => 'Patch Cord CAT6 5M'],
                ['part_number' => 'UTP-C6-305M', 'description' => 'UTP Cable CAT6 305M'],
            ],
            // Perlengkapan Rack
            'Panduit' => [
                ['part_number' => 'PDU-15A', 'description' => 'Power Distribution Unit 15A'],
                ['part_number' => 'CABLE-MGR-1U', 'description' => 'Cable Manager 1U'],
                ['part_number' => 'CABLE-MGR-2U', 'description' => 'Cable Manager 2U'],
                ['part_number' => 'BLANK-1U', 'description' => 'Blank Panel 1U'],
                ['part_number' => 'BLANK-2U', 'description' => 'Blank Panel 2U'],
            ],
        ];

        foreach ($partNumbersData as $brandName => $partNumbers) {
            $brand = Brand::where('brand_name', $brandName)->first();

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