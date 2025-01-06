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
        
        // Hanya ambil item dengan status awal (baru/bekas)
        $items = Item::whereIn('status', ['baru', 'bekas'])->get();

        foreach ($inboundRecords as $inbound) {
            // Tambah jumlah item per inbound (dari 2-3 menjadi 3-5)
            $numItems = min(rand(3, 5), $items->count());
            
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