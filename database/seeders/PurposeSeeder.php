<?php

namespace Database\Seeders;

use App\Models\Purpose;
use Illuminate\Database\Seeder;

class PurposeSeeder extends Seeder
{
    public function run(): void
    {
        $purposes = [
            ['name' => 'Sewa'],
            ['name' => 'Pembelian'],
            ['name' => 'Peminjaman'],
        ];

        foreach ($purposes as $purpose) {
            Purpose::create($purpose);
        }
    }
} 