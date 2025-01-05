<?php

namespace Database\Seeders;

use App\Models\OutboundRecord;
use App\Models\Item;
use App\Models\OutboundItem;
use Illuminate\Database\Seeder;

class OutboundItemSeeder extends Seeder
{
    public function run(): void
    {
        $outboundRecords = OutboundRecord::all();
        $items = Item::where('status', 'diterima')->get();

        foreach ($outboundRecords as $outbound) {
            // Ambil 1-2 item yang tersedia
            $numItems = min(rand(1, 2), $items->count());
            
            if ($numItems > 0) {
                $randomItems = $items->random($numItems);
                foreach ($randomItems as $item) {
                    OutboundItem::create([
                        'outbound_id' => $outbound->outbound_id,
                        'item_id' => $item->item_id,
                        'quantity' => 1,
                    ]);

                    // Update status item menjadi masa_sewa
                    $item->update(['status' => 'masa_sewa']);
                    
                    // Hapus item dari koleksi agar tidak dipilih lagi
                    $items = $items->where('item_id', '!=', $item->item_id);
                }
            }
        }
    }
} 