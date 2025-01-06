<?php

namespace App\Observers;

use App\Models\OutboundItem;

class OutboundItemObserver
{
    public function created(OutboundItem $outboundItem)
    {
        $outboundItem->item->updateLatestStatus();
    }
} 