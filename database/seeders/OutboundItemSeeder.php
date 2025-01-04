<?php

namespace Database\Seeders;

use App\Models\OutboundItem;
use App\Models\OutboundRecord;
use App\Models\Item;
use Illuminate\Database\Seeder;

class OutboundItemSeeder extends Seeder
{
    public function run(): void
    {
        $outboundRecords = OutboundRecord::all();
        $items = Item::all();

        foreach ($outboundRecords as $outbound) {
            // Tambahkan 2-5 item untuk setiap outbound record
            $randomItems = $items->random(rand(2, 5));
            foreach ($randomItems as $item) {
                OutboundItem::create([
                    'outbound_id' => $outbound->outbound_id,
                    'item_id' => $item->item_id,
                    'quantity' => rand(1, 3),
                ]);
            }
        }
    }
} 