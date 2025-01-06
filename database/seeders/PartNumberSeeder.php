<?php

namespace Database\Seeders;

use App\Models\PartNumber;
use App\Models\Brand;
use Illuminate\Database\Seeder;

class PartNumberSeeder extends Seeder
{
    public function run(): void
    {
        $networkDevices = [
            'Cisco' => [
                ['part_number' => 'C9200L-24T-4G', 'description' => 'Catalyst 9200L 24-port data, 4 x 1G uplink'],
                ['part_number' => 'C9300-48U-E', 'description' => 'Catalyst 9300 48-port UPOE, Network Essentials'],
                ['part_number' => 'ASA5506-K9', 'description' => 'ASA 5506-X with FirePOWER services'],
            ],
            'Juniper' => [
                ['part_number' => 'EX2300-24T', 'description' => 'EX2300 24-port 10/100/1000BaseT'],
                ['part_number' => 'SRX340-SYS-JB', 'description' => 'SRX340 Services Gateway with 16 GE ports'],
            ],
            'Fortinet' => [
                ['part_number' => 'FG-100F', 'description' => 'FortiGate 100F NGFW Appliance'],
                ['part_number' => 'FG-200F', 'description' => 'FortiGate 200F NGFW Appliance'],
            ],
            'Palo Alto' => [
                ['part_number' => 'PA-820', 'description' => 'PA-820 Next-Gen Firewall'],
                ['part_number' => 'PA-850', 'description' => 'PA-850 Next-Gen Firewall'],
            ],
        ];

        foreach ($networkDevices as $brandName => $devices) {
            $brand = Brand::where('brand_name', $brandName)->first();
            foreach ($devices as $device) {
                PartNumber::create([
                    'brand_id' => $brand->brand_id,
                    'part_number' => $device['part_number'],
                    'description' => $device['description'],
                ]);
            }
        }
    }
} 