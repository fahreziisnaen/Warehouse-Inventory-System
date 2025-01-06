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
        
        // Update status menjadi 'diterima' saat item di-inbound
        $item->update(['status' => 'diterima']);
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