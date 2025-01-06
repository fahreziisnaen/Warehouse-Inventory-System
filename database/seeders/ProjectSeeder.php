<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ProjectStatus::all();
        $customers = Vendor::customers()->get();
        $counter = 1;

        foreach ($customers as $customer) {
            // Buat 1-2 project untuk setiap customer
            for ($i = 1; $i <= 2; $i++) {
                Project::create([
                    'project_id' => sprintf('PRJ-%03d', $counter),
                    'project_name' => "Project {$customer->vendor_name} {$i}",
                    'vendor_id' => $customer->vendor_id,
                    'status_id' => $statuses->random()->status_id,
                    'description' => "Sample project {$i} for {$customer->vendor_name}",
                ]);

                $counter++;
            }
        }
    }
} 