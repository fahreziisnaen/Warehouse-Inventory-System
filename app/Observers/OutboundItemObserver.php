<?php

namespace App\Observers;

use App\Models\OutboundItem;

class OutboundItemObserver
{
    public function created(OutboundItem $outboundItem)
    {
        if ($outboundItem->item) {
            $newStatus = match($outboundItem->purpose->name) {
                'Sewa' => 'masa_sewa',
                'Non Sewa' => 'non_sewa',
                'Peminjaman' => 'dipinjam',
                default => $outboundItem->item->status
            };
            
            $outboundItem->item->update(['status' => $newStatus]);
        }
    }

    public function deleted(OutboundItem $outboundItem)
    {
        if ($outboundItem->item) {
            $outboundItem->item->update(['status' => 'unknown']);
        }
    }
} 