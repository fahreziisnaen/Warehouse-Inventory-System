<?php

namespace Database\Seeders;

use App\Models\VendorType;
use Illuminate\Database\Seeder;

class VendorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['type_name' => 'Supplier'],
            ['type_name' => 'Customer'],
        ];

        foreach ($types as $type) {
            VendorType::firstOrCreate(
                ['type_name' => $type['type_name']]
            );
        }
    }
} 