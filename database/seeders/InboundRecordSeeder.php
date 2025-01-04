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

        foreach ($purchaseOrders as $po) {
            InboundRecord::create([
                'lpb_number' => 'LPB-' . date('Ym') . str_pad($po->po_id, 4, '0', STR_PAD_LEFT),
                'receive_date' => $po->po_date->addDays(rand(1, 14)),
                'po_id' => $po->po_id,
                'status' => 'received',
                'project_id' => $po->project_id,
            ]);
        }
    }
} 