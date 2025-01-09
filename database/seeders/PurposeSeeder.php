<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purpose;

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