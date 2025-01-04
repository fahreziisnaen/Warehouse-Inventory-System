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
        $items = Item::all();

        foreach ($inboundRecords as $inbound) {
            // Tambahkan 2-5 item untuk setiap inbound record
            $randomItems = $items->random(rand(2, 5));
            foreach ($randomItems as $item) {
                InboundItem::create([
                    'inbound_id' => $inbound->inbound_id,
                    'item_id' => $item->item_id,
                    'quantity' => rand(1, 5),
                ]);
            }
        }
    }
} 