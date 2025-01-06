<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Aktif'],
            ['name' => 'Tidak Aktif'],
        ];

        foreach ($statuses as $status) {
            ProjectStatus::create($status);
        }
    }
} 