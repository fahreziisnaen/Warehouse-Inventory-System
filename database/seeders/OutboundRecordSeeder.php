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
            
            // Buat outbound untuk setiap purpose
            foreach ($purposes as $purpose) {
                // Tambah jumlah project per purpose (dari 3 menjadi 4-5)
                $selectedProjects = $projects->random(min(rand(4, 5), $projects->count()));
                
                foreach ($selectedProjects as $project) {
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
                        'purpose_id' => $purpose->purpose_id,
                    ]);

                    $counter++;
                }
            }
        }
    }
} 