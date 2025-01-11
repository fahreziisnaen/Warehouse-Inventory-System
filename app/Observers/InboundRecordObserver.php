<?php

namespace App\Observers;

use App\Models\InboundRecord;
use App\Models\BatchItem;

class InboundRecordObserver
{
    public function created(InboundRecord $inboundRecord)
    {
        if ($inboundRecord->part_number_id && $inboundRecord->batch_quantity) {
            BatchItem::updateQuantity(
                $inboundRecord->part_number_id,
                $inboundRecord->batch_quantity,
                'inbound',
                $inboundRecord
            );
        }
    }
} 