<?php

namespace Database\Seeders;

use App\Models\InboundRecord;
use App\Models\OutboundRecord;
use App\Models\InboundItem;
use App\Models\OutboundItem;
use App\Models\Item;
use App\Models\BatchItem;
use Illuminate\Database\Seeder;

class CombinedTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $project = \App\Models\Project::first();
        $po = \App\Models\PurchaseOrder::first();
        $customer = \App\Models\Vendor::customers()->first();

        // Ambil semua purpose
        $purposes = \App\Models\Purpose::all();
        $sewaPurpose = $purposes->where('name', 'Sewa')->first();
        $beliPurpose = $purposes->where('name', 'Pembelian')->first();
        $pinjamPurpose = $purposes->where('name', 'Peminjaman')->first();

        // Ambil Items dengan Serial Number untuk sample (30 items)
        $serialItems = Item::where('status', 'baru')->take(30)->get();
        
        // === INBOUND RECORDS (12 records) ===

        // 1. Inbound hanya Serial Items (6 records) - Gudang Jakarta
        for($i = 1; $i <= 6; $i++) {
            $inbound = InboundRecord::create([
                'lpb_number' => sprintf('LPB/SERIAL/2024/JKT/%03d', $i),
                'receive_date' => now()->subDays(60 - $i)->startOfDay(),
                'po_id' => $po->po_id,
                'project_id' => $project->project_id,
                'location' => 'Gudang Jakarta',
            ]);
            foreach($serialItems->slice(($i-1) * 2, 2) as $item) {
                InboundItem::create(['inbound_id' => $inbound->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
            }
        }

        // 2. Inbound hanya Serial Items (6 records) - Gudang Surabaya
        for($i = 1; $i <= 6; $i++) {
            $inbound = InboundRecord::create([
                'lpb_number' => sprintf('LPB/SERIAL/2024/SBY/%03d', $i),
                'receive_date' => now()->subDays(50 - $i)->startOfDay(),
                'po_id' => $po->po_id,
                'project_id' => $project->project_id,
                'location' => 'Gudang Surabaya',
            ]);
            foreach($serialItems->slice(12 + ($i-1) * 2, 2) as $item) {
                InboundItem::create(['inbound_id' => $inbound->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
            }
        }

        // === OUTBOUND RECORDS (8 records) ===

        // 1. Outbound Serial Items (4 records) - Dari Gudang Jakarta
        $purposes = [$sewaPurpose, $beliPurpose, $pinjamPurpose, $sewaPurpose];
        for($i = 0; $i < 4; $i++) {
            $outbound = OutboundRecord::create([
                'lkb_number' => sprintf('LKB/JKT/2024/%03d', $i+1),
                'delivery_date' => now()->subDays(40 - $i * 2)->startOfDay(),
                'vendor_id' => $customer->vendor_id,
                'project_id' => $project->project_id,
                'purpose_id' => $purposes[$i]->purpose_id,
            ]);
            foreach($serialItems->slice($i * 2, 2) as $item) {
                OutboundItem::create(['outbound_id' => $outbound->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
            }
        }

        // 2. Outbound Serial Items (4 records) - Dari Gudang Surabaya
        for($i = 0; $i < 4; $i++) {
            $outbound = OutboundRecord::create([
                'lkb_number' => sprintf('LKB/SBY/2024/%03d', $i+1),
                'delivery_date' => now()->subDays(30 - $i * 2)->startOfDay(),
                'vendor_id' => $customer->vendor_id,
                'project_id' => $project->project_id,
                'purpose_id' => $purposes[$i]->purpose_id,
            ]);
            foreach($serialItems->slice(12 + $i * 2, 2) as $item) {
                OutboundItem::create(['outbound_id' => $outbound->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
            }
        }

        // === BATCH ITEMS TRANSACTIONS ===
        
        // Inbound Batch Items - Gudang Jakarta
        $batchItems = [
            ['part_number' => 'RJ45-CAT6', 'qty' => 1000],
            ['part_number' => 'PATCH-2M', 'qty' => 500],
            ['part_number' => 'SFP-1G', 'qty' => 200],
        ];

        foreach($batchItems as $index => $item) {
            $batchItem = BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', $item['part_number']))->first();
            InboundRecord::create([
                'lpb_number' => sprintf('LPB/BATCH/JKT/2024/%03d', $index + 1),
                'receive_date' => now()->subDays(45 - $index * 5)->startOfDay(),
                'po_id' => $po->po_id,
                'project_id' => $project->project_id,
                'part_number_id' => $batchItem->part_number_id,
                'batch_quantity' => $item['qty'],
                'location' => 'Gudang Jakarta',
            ]);
        }

        // Inbound Batch Items - Gudang Surabaya
        $batchItems = [
            ['part_number' => 'CABLE-UTP', 'qty' => 100],
            ['part_number' => 'PATCH-3M', 'qty' => 300],
            ['part_number' => 'SFP-10G', 'qty' => 100],
            ['part_number' => 'CABLE-FIBER', 'qty' => 50],
        ];

        foreach($batchItems as $index => $item) {
            $batchItem = BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', $item['part_number']))->first();
            InboundRecord::create([
                'lpb_number' => sprintf('LPB/BATCH/SBY/2024/%03d', $index + 1),
                'receive_date' => now()->subDays(40 - $index * 5)->startOfDay(),
                'po_id' => $po->po_id,
                'project_id' => $project->project_id,
                'part_number_id' => $batchItem->part_number_id,
                'batch_quantity' => $item['qty'],
                'location' => 'Gudang Surabaya',
            ]);
        }

        // Outbound Batch Items dengan Purpose berbeda
        $outboundBatch = [
            ['part_number' => 'RJ45-CAT6', 'qty' => 300, 'purpose' => $sewaPurpose],
            ['part_number' => 'CABLE-UTP', 'qty' => 30, 'purpose' => $beliPurpose],
            ['part_number' => 'PATCH-2M', 'qty' => 200, 'purpose' => $pinjamPurpose],
            ['part_number' => 'PATCH-3M', 'qty' => 100, 'purpose' => $sewaPurpose],
        ];

        foreach($outboundBatch as $index => $item) {
            $batchItem = BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', $item['part_number']))->first();
            OutboundRecord::create([
                'lkb_number' => sprintf('LKB/BATCH/2024/%03d', $index + 1),
                'delivery_date' => now()->subDays(20 - $index * 5)->startOfDay(),
                'vendor_id' => $customer->vendor_id,
                'project_id' => $project->project_id,
                'purpose_id' => $item['purpose']->purpose_id,
                'part_number_id' => $batchItem->part_number_id,
                'batch_quantity' => $item['qty'],
            ]);
        }
    }
} 