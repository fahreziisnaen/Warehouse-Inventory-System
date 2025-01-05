<?php

namespace Database\Seeders;

use App\Models\InboundRecord;
use App\Models\Item;
use App\Models\InboundItem;
use Illuminate\Database\Seeder;

class InboundItemSeeder extends Seeder
{
    public function run(): void
    {
        $inboundRecords = InboundRecord::all();
        $items = Item::whereIn('status', ['baru', 'bekas'])->get();

        foreach ($inboundRecords as $inbound) {
            // Ambil jumlah item yang tersedia atau maksimal 2 item
            $numItems = min(2, $items->count());
            
            if ($numItems > 0) {
                $randomItems = $items->random($numItems);
                foreach ($randomItems as $item) {
                    // Buat InboundItem
                    InboundItem::create([
                        'inbound_id' => $inbound->inbound_id,
                        'item_id' => $item->item_id,
                        'quantity' => 1,
                    ]);

                    // Update status item menjadi 'diterima'
                    $item->update(['status' => 'diterima']);
                    
                    // Hapus item dari koleksi agar tidak dipilih lagi
                    $items = $items->where('item_id', '!=', $item->item_id);
                }
            }
        }
    }
} 