<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Vendor;
use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Vendor::where('vendor_type_id', function($query) {
            $query->select('vendor_type_id')
                ->from('vendor_types')
                ->where('type_name', 'Customer')
                ->first();
        })->first();

        $activeStatus = ProjectStatus::where('name', 'Aktif')->first();
        
        $projects = [
            [
                'project_id' => 'PRJ-001',
                'project_name' => 'Network Refresh 2024',
                'vendor_id' => $customer->vendor_id,
                'status_id' => $activeStatus->status_id,
                'description' => 'Pembaruan infrastruktur jaringan'
            ],
            [
                'project_id' => 'PRJ-002',
                'project_name' => 'Data Center Expansion',
                'vendor_id' => $customer->vendor_id,
                'status_id' => $activeStatus->status_id,
                'description' => 'Perluasan kapasitas data center'
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['project_id' => $project['project_id']],
                $project
            );
        }
    }
} 