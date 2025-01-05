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
            'item_id' => $inboundItem->item_id
        ]);
        
        Item::where('item_id', $inboundItem->item_id)
            ->update(['status' => 'diterima']);
    }

    public function deleted(InboundItem $inboundItem): void
    {
        // Kembalikan status ke 'baru' jika inbound dihapus
        Item::where('item_id', $inboundItem->item_id)
            ->update(['status' => 'baru']);
    }
} 