<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Vendor::customers()->get();

        foreach ($customers as $customer) {
            // Buat 2 proyek untuk setiap customer
            for ($i = 1; $i <= 2; $i++) {
                $projectId = 'PRJ' . str_pad($customer->vendor_id . $i, 5, '0', STR_PAD_LEFT);
                
                Project::create([
                    'project_id' => $projectId,
                    'project_name' => "Project {$customer->vendor_name} {$i}",
                    'vendor_id' => $customer->vendor_id,
                    'description' => "Sample project {$i} for {$customer->vendor_name}",
                ]);
            }
        }
    }
} 