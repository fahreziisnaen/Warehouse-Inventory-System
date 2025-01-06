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
        
        // Ambil semua batch items
        $batchItems = [
            'RJ45' => BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'RJ45-CAT6'))->first(),
            'UTP' => BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'CABLE-UTP'))->first(),
            'FIBER' => BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'CABLE-FIBER'))->first(),
            'SFP1G' => BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'SFP-1G'))->first(),
            'SFP10G' => BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'SFP-10G'))->first(),
        ];

        // === INBOUND RECORDS (12 records) ===

        // 1. Inbound RJ45 + 4 Serial Items
        $inbound1 = InboundRecord::create([
            'lpb_number' => 'LPB/MIXED/2024/001',
            'receive_date' => now()->subDays(60)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $batchItems['RJ45']->part_number_id,
            'batch_quantity' => 1000
        ]);
        foreach($serialItems->take(4) as $item) {
            InboundItem::create(['inbound_id' => $inbound1->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 2. Inbound UTP Cable + 3 Serial Items
        $inbound2 = InboundRecord::create([
            'lpb_number' => 'LPB/MIXED/2024/002',
            'receive_date' => now()->subDays(55)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $batchItems['UTP']->part_number_id,
            'batch_quantity' => 100
        ]);
        foreach($serialItems->slice(4, 3) as $item) {
            InboundItem::create(['inbound_id' => $inbound2->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 3. Inbound Fiber Cable + 3 Serial Items
        $inbound3 = InboundRecord::create([
            'lpb_number' => 'LPB/MIXED/2024/003',
            'receive_date' => now()->subDays(50)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $batchItems['FIBER']->part_number_id,
            'batch_quantity' => 50
        ]);
        foreach($serialItems->slice(7, 3) as $item) {
            InboundItem::create(['inbound_id' => $inbound3->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 4. Inbound SFP 1G + 3 Serial Items
        $inbound4 = InboundRecord::create([
            'lpb_number' => 'LPB/MIXED/2024/004',
            'receive_date' => now()->subDays(45)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $batchItems['SFP1G']->part_number_id,
            'batch_quantity' => 200
        ]);
        foreach($serialItems->slice(10, 3) as $item) {
            InboundItem::create(['inbound_id' => $inbound4->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 5. Inbound SFP 10G + 3 Serial Items
        $inbound5 = InboundRecord::create([
            'lpb_number' => 'LPB/MIXED/2024/005',
            'receive_date' => now()->subDays(40)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $batchItems['SFP10G']->part_number_id,
            'batch_quantity' => 100
        ]);
        foreach($serialItems->slice(13, 3) as $item) {
            InboundItem::create(['inbound_id' => $inbound5->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 6. Inbound PATCH-2M + 2 Serial Items
        $patch2mBatchItem = BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'PATCH-2M'))->first();
        $inbound6 = InboundRecord::create([
            'lpb_number' => 'LPB/PATCH2M/2024/001',
            'receive_date' => now()->subDays(38)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $patch2mBatchItem->part_number_id,
            'batch_quantity' => 500
        ]);
        foreach($serialItems->slice(13, 2) as $item) {
            InboundItem::create(['inbound_id' => $inbound6->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 7. Inbound PATCH-3M + 2 Serial Items
        $patch3mBatchItem = BatchItem::whereHas('partNumber', fn($q) => $q->where('part_number', 'PATCH-3M'))->first();
        $inbound7 = InboundRecord::create([
            'lpb_number' => 'LPB/PATCH3M/2024/001',
            'receive_date' => now()->subDays(37)->startOfDay(),
            'po_id' => $po->po_id,
            'project_id' => $project->project_id,
            'part_number_id' => $patch3mBatchItem->part_number_id,
            'batch_quantity' => 300
        ]);
        foreach($serialItems->slice(15, 2) as $item) {
            InboundItem::create(['inbound_id' => $inbound7->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 6-12. Inbound hanya Serial Items (7 records)
        for($i = 6; $i <= 12; $i++) {
            $inbound = InboundRecord::create([
                'lpb_number' => sprintf('LPB/SERIAL/2024/%03d', $i),
                'receive_date' => now()->subDays(35 - ($i-6) * 2)->startOfDay(),
                'po_id' => $po->po_id,
                'project_id' => $project->project_id,
            ]);
            foreach($serialItems->slice(15 + ($i-6) * 2, 2) as $item) {
                InboundItem::create(['inbound_id' => $inbound->inbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
            }
        }

        // === OUTBOUND RECORDS (8 records) ===

        // 1. Outbound RJ45 + 2 Serial Items (Sewa)
        $outbound1 = OutboundRecord::create([
            'lkb_number' => 'LKB/SEWA/2024/001',
            'delivery_date' => now()->subDays(30)->startOfDay(),
            'vendor_id' => $customer->vendor_id,
            'project_id' => $project->project_id,
            'purpose_id' => $sewaPurpose->purpose_id,
            'part_number_id' => $batchItems['RJ45']->part_number_id,
            'batch_quantity' => 300
        ]);
        foreach($serialItems->take(2) as $item) {
            OutboundItem::create(['outbound_id' => $outbound1->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 2. Outbound UTP Cable + 2 Serial Items (Beli)
        $outbound2 = OutboundRecord::create([
            'lkb_number' => 'LKB/BELI/2024/001',
            'delivery_date' => now()->subDays(25)->startOfDay(),
            'vendor_id' => $customer->vendor_id,
            'project_id' => $project->project_id,
            'purpose_id' => $beliPurpose->purpose_id,
            'part_number_id' => $batchItems['UTP']->part_number_id,
            'batch_quantity' => 30
        ]);
        foreach($serialItems->slice(4, 2) as $item) {
            OutboundItem::create(['outbound_id' => $outbound2->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 3. Outbound Fiber + 2 Serial Items (Pinjam)
        $outbound3 = OutboundRecord::create([
            'lkb_number' => 'LKB/PINJAM/2024/001',
            'delivery_date' => now()->subDays(20)->startOfDay(),
            'vendor_id' => $customer->vendor_id,
            'project_id' => $project->project_id,
            'purpose_id' => $pinjamPurpose->purpose_id,
            'part_number_id' => $batchItems['FIBER']->part_number_id,
            'batch_quantity' => 15
        ]);
        foreach($serialItems->slice(7, 2) as $item) {
            OutboundItem::create(['outbound_id' => $outbound3->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 4. Outbound SFP 1G + 2 Serial Items (Sewa)
        $outbound4 = OutboundRecord::create([
            'lkb_number' => 'LKB/SEWA/2024/002',
            'delivery_date' => now()->subDays(15)->startOfDay(),
            'vendor_id' => $customer->vendor_id,
            'project_id' => $project->project_id,
            'purpose_id' => $sewaPurpose->purpose_id,
            'part_number_id' => $batchItems['SFP1G']->part_number_id,
            'batch_quantity' => 50
        ]);
        foreach($serialItems->slice(10, 2) as $item) {
            OutboundItem::create(['outbound_id' => $outbound4->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 5. Outbound PATCH-2M + 2 Serial Items (Beli)
        $outbound5 = OutboundRecord::create([
            'lkb_number' => 'LKB/BELI/2024/003',
            'delivery_date' => now()->subDays(12)->startOfDay(),
            'vendor_id' => $customer->vendor_id,
            'project_id' => $project->project_id,
            'purpose_id' => $beliPurpose->purpose_id,
            'part_number_id' => $patch2mBatchItem->part_number_id,
            'batch_quantity' => 200
        ]);
        foreach($serialItems->slice(13, 2) as $item) {
            OutboundItem::create(['outbound_id' => $outbound5->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 6. Outbound PATCH-3M + 2 Serial Items (Sewa)
        $outbound6 = OutboundRecord::create([
            'lkb_number' => 'LKB/SEWA/2024/003',
            'delivery_date' => now()->subDays(11)->startOfDay(),
            'vendor_id' => $customer->vendor_id,
            'project_id' => $project->project_id,
            'purpose_id' => $sewaPurpose->purpose_id,
            'part_number_id' => $patch3mBatchItem->part_number_id,
            'batch_quantity' => 100
        ]);
        foreach($serialItems->slice(15, 2) as $item) {
            OutboundItem::create(['outbound_id' => $outbound6->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
        }

        // 5-8. Outbound hanya Serial Items dengan Purpose berbeda
        $purposes = [$sewaPurpose, $beliPurpose, $pinjamPurpose, $sewaPurpose];
        for($i = 0; $i < 4; $i++) {
            $outbound = OutboundRecord::create([
                'lkb_number' => sprintf('LKB/SERIAL/2024/%03d', $i+1),
                'delivery_date' => now()->subDays(10 - $i * 2)->startOfDay(),
                'vendor_id' => $customer->vendor_id,
                'project_id' => $project->project_id,
                'purpose_id' => $purposes[$i]->purpose_id,
            ]);
            foreach($serialItems->slice(15 + $i * 2, 2) as $item) {
                OutboundItem::create(['outbound_id' => $outbound->outbound_id, 'item_id' => $item->item_id, 'quantity' => 1]);
            }
        }
    }
} 