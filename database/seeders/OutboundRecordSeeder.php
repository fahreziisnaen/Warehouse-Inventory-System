<?php

namespace Database\Seeders;

use App\Models\OutboundRecord;
use App\Models\Project;
use App\Models\Vendor;
use App\Models\Item;
use App\Models\Purpose;
use Illuminate\Database\Seeder;

class OutboundRecordSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Vendor::customers()->get();
        $projects = Project::all();
        $purposes = Purpose::all();
        
        // Pastikan hanya mengambil item dengan status 'diterima'
        $availableItems = Item::where('status', 'diterima')->count();
        
        // Hanya jalankan seeder jika ada item yang tersedia
        if ($availableItems > 0) {
            $counter = 1;

            foreach ($projects as $project) {
                // Buat 1-2 outbound untuk setiap project
                $numOutbounds = rand(1, 2);
                for ($i = 1; $i <= $numOutbounds; $i++) {
                    $lkbNumber = sprintf(
                        "LKB/%s/%s/%04d",
                        substr($project->project_id, 4, 3),
                        date('Y'),
                        $counter
                    );
                    
                    OutboundRecord::create([
                        'lkb_number' => $lkbNumber,
                        'delivery_date' => now()->subDays(rand(1, 30)),
                        'vendor_id' => $customers->random()->vendor_id,
                        'project_id' => $project->project_id,
                        'purpose_id' => $purposes->random()->purpose_id,
                    ]);

                    $counter++;
                }
            }
        }
    }
} 