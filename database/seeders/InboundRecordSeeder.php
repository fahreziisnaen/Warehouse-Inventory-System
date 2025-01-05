<?php

namespace Database\Seeders;

use App\Models\InboundRecord;
use App\Models\PurchaseOrder;
use Illuminate\Database\Seeder;

class InboundRecordSeeder extends Seeder
{
    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::all();
        $year = date('Y');
        $counter = 1;

        foreach ($purchaseOrders as $po) {
            $lpbNumber = sprintf(
                "LPB/%s/%s/%04d",
                substr($po->po_number, 3, 3),
                $year,
                $counter
            );
            
            InboundRecord::create([
                'lpb_number' => $lpbNumber,
                'receive_date' => $po->po_date->addDays(rand(1, 14)),
                'po_id' => $po->po_id,
                'project_id' => $po->project_id,
            ]);

            $counter++;
        }
    }
} 