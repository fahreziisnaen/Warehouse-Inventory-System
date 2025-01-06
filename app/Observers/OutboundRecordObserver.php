<?php

namespace App\Observers;

use App\Models\OutboundRecord;
use App\Models\BatchItem;

class OutboundRecordObserver
{
    public function created(OutboundRecord $outboundRecord)
    {
        if ($outboundRecord->part_number_id && $outboundRecord->batch_quantity) {
            BatchItem::updateQuantity(
                $outboundRecord->part_number_id,
                $outboundRecord->batch_quantity,
                'outbound',
                $outboundRecord
            );
        }
    }
} 