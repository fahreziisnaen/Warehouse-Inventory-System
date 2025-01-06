<?php

namespace App\Observers;

use App\Models\OutboundItem;
use App\Models\Item;

class OutboundItemObserver
{
    public function created(OutboundItem $outboundItem): void
    {
        // Update status item menjadi masa_sewa saat keluar
        Item::where('item_id', $outboundItem->item_id)
            ->update(['status' => 'masa_sewa']);
    }

    public function deleted(OutboundItem $outboundItem): void
    {
        // Kembalikan status ke diterima jika outbound dihapus
        Item::where('item_id', $outboundItem->item_id)
            ->update(['status' => 'diterima']);
    }
} 