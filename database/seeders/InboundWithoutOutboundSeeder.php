<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InboundRecord;
use App\Models\InboundItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use Carbon\Carbon;

class InboundWithoutOutboundSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil PO yang sudah ada
        $purchaseOrders = PurchaseOrder::all();
        
        foreach ($purchaseOrders as $po) {
            // Buat Inbound Record
            $inboundRecord = InboundRecord::create([
                'lpb_number' => 'LPB/' . now()->format('Y') . '/' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'receive_date' => Carbon::now()->subDays(rand(1, 30)),
                'po_id' => $po->po_id,
                'project_id' => $po->project_id,
            ]);

            // Ambil item yang belum memiliki inbound dan outbound
            $availableItems = Item::whereDoesntHave('inboundItems')
                ->whereDoesntHave('outboundItems')
                ->whereIn('status', ['baru', 'bekas'])
                ->inRandomOrder()
                ->take(rand(1, 3))
                ->get();

            foreach ($availableItems as $item) {
                InboundItem::create([
                    'inbound_id' => $inboundRecord->inbound_id,
                    'item_id' => $item->item_id,
                    'quantity' => 1,
                ]);
            }
        }
    }
} 