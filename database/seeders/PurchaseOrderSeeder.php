<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Vendor::suppliers()->get();
        $projects = Project::all();
        $year = date('Y');
        $counter = 1;

        foreach ($projects as $project) {
            // Buat 2 PO untuk setiap project
            for ($i = 1; $i <= 2; $i++) {
                $poNumber = sprintf(
                    "PO/%s/%s/%04d",
                    substr($project->project_id, 4, 3), // Mengambil kode customer
                    $year,
                    $counter
                );
                
                PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'po_date' => now()->subDays(rand(1, 30)),
                    'vendor_id' => $suppliers->random()->vendor_id,
                    'project_id' => $project->project_id,
                ]);

                $counter++;
            }
        }
    }
} 