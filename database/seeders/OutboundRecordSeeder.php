<?php

namespace Database\Seeders;

use App\Models\OutboundRecord;
use App\Models\Project;
use Illuminate\Database\Seeder;

class OutboundRecordSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();
        $purposes = ['installation', 'replacement', 'maintenance'];

        foreach ($projects as $project) {
            // Buat 2 outbound record untuk setiap project
            for ($i = 1; $i <= 2; $i++) {
                OutboundRecord::create([
                    'lkb_number' => 'LKB-' . date('Ym') . str_pad($project->project_id . $i, 4, '0', STR_PAD_LEFT),
                    'delivery_note_number' => 'DN-' . date('Ym') . str_pad($project->project_id . $i, 4, '0', STR_PAD_LEFT),
                    'outbound_date' => now()->subDays(rand(1, 30)),
                    'vendor_id' => $project->vendor_id,
                    'project_id' => $project->project_id,
                    'purpose' => $purposes[array_rand($purposes)],
                ]);
            }
        }
    }
} 