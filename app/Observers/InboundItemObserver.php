<?php

namespace App\Observers;

use App\Models\InboundItem;

class InboundItemObserver
{
    public function created(InboundItem $inboundItem)
    {
        if ($inboundItem->item) {
            $inboundItem->item->updateLatestStatus();
        }
    }
} 