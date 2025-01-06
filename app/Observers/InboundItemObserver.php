<?php

namespace App\Observers;

use App\Models\InboundItem;
use App\Models\Item;

class InboundItemObserver
{
    public function created(InboundItem $inboundItem): void
    {
        \Log::info('InboundItem Created', [
            'inbound_item_id' => $inboundItem->inbound_item_id,
            'item_id' => $inboundItem->item_id,
            'previous_status' => $inboundItem->item->status
        ]);
        
        $item = Item::find($inboundItem->item_id);
        
        // Logika penentuan status baru
        $newStatus = match($item->status) {
            'dipinjam' => 'diterima',  // Item yang dipinjam kembali menjadi diterima
            'masa_sewa' => 'diterima',  // Item yang disewa kembali menjadi diterima
            'sewa_habis' => 'diterima', // Item sewa habis menjadi diterima
            'baru' => 'diterima',       // Item baru menjadi diterima
            'bekas' => 'diterima',      // Item bekas menjadi diterima
            default => $item->status    // Pertahankan status lain
        };

        $item->update(['status' => $newStatus]);
    }

    public function deleted(InboundItem $inboundItem): void
    {
        // Kembalikan ke status sebelumnya
        $previousStatus = $inboundItem->item->inboundItems()
            ->where('inbound_item_id', '<', $inboundItem->inbound_item_id)
            ->latest()
            ->first()?->item->status ?? 'baru';

        Item::where('item_id', $inboundItem->item_id)
            ->update(['status' => $previousStatus]);
    }
} 