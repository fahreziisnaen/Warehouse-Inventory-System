<?php

namespace Database\Seeders;

use App\Models\InboundItem;
use App\Models\InboundRecord;
use App\Models\Item;
use Illuminate\Database\Seeder;

class InboundItemSeeder extends Seeder
{
    public function run(): void
    {
        $inboundRecords = InboundRecord::all();
        $items = Item::where('status', 'diterima')->get();

        foreach ($inboundRecords as $inbound) {
            // Tambahkan 2-3 item untuk setiap inbound record
            $randomItems = $items->random(rand(2, 3));
            foreach ($randomItems as $item) {
                InboundItem::create([
                    'inbound_id' => $inbound->inbound_id,
                    'item_id' => $item->item_id,
                    'quantity' => 1,  // Selalu 1 karena serial number unique
                ]);
            }
        }
    }
} 