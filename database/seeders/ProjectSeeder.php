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
        $statuses = ['planning', 'ongoing', 'completed', 'on-hold'];

        foreach ($customers as $customer) {
            // Buat 2 proyek untuk setiap customer
            for ($i = 1; $i <= 2; $i++) {
                $startDate = now()->subDays(rand(30, 180));
                $projectId = 'PRJ' . str_pad($customer->vendor_id . $i, 5, '0', STR_PAD_LEFT);
                
                Project::create([
                    'project_id' => $projectId,
                    'project_name' => "Project {$customer->vendor_name} {$i}",
                    'vendor_id' => $customer->vendor_id,
                    'start_date' => $startDate,
                    'end_date' => $startDate->copy()->addDays(rand(30, 90)),
                    'status' => $statuses[array_rand($statuses)],
                    'description' => "Sample project {$i} for {$customer->vendor_name}",
                ]);
            }
        }
    }
} 