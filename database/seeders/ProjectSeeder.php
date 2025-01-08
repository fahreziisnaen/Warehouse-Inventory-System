<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Vendor::whereHas('vendorType', fn($q) => 
            $q->where('type_name', 'Customer')
        )->get();

        $projectsData = [
            [
                'project_id' => 'PRJ-001',
                'project_name' => 'Network Infrastructure Upgrade - Jakarta',
                'vendor_id' => $customers->random()->vendor_id,
            ],
            [
                'project_id' => 'PRJ-002',
                'project_name' => 'Data Center Migration - Surabaya',
                'vendor_id' => $customers->random()->vendor_id,
            ],
            [
                'project_id' => 'PRJ-003',
                'project_name' => 'Security System Implementation',
                'vendor_id' => $customers->random()->vendor_id,
            ],
            [
                'project_id' => 'PRJ-004',
                'project_name' => 'Branch Office Connectivity',
                'vendor_id' => $customers->random()->vendor_id,
            ],
            [
                'project_id' => 'PRJ-005',
                'project_name' => 'Cloud Infrastructure Setup',
                'vendor_id' => $customers->random()->vendor_id,
            ],
        ];

        foreach ($projectsData as $project) {
            Project::firstOrCreate(
                ['project_id' => $project['project_id']],
                $project
            );
        }
    }
} 