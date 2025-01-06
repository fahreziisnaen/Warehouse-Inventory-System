<?php

namespace Database\Seeders;

use App\Models\InboundRecord;
use App\Models\PurchaseOrder;
use App\Models\Project;
use Illuminate\Database\Seeder;

class InboundRecordSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();
        $purchaseOrders = PurchaseOrder::all();
        $counter = 1;

        foreach ($purchaseOrders as $po) {
            $project = $projects->random();
            
            InboundRecord::create([
                'lpb_number' => sprintf(
                    "LPB/%s/%s/%04d",
                    substr($project->project_id, 4, 3),
                    date('Y'),
                    $counter
                ),
                'receive_date' => now()->subDays(rand(1, 30)),
                'po_id' => $po->po_id,
                'project_id' => $project->project_id,
            ]);

            $counter++;
        }
    }
} 