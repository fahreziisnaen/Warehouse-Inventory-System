<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundItem extends Model
{
    protected $primaryKey = 'outbound_item_id';
    
    protected $fillable = [
        'outbound_id',
        'item_id',
        'quantity',
        'purpose_id'
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function outboundRecord(): BelongsTo
    {
        return $this->belongsTo(OutboundRecord::class, 'outbound_id', 'outbound_id');
    }

    public function inboundItem()
    {
        return $this->belongsTo(InboundItem::class, 'item_id', 'item_id');
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Purpose::class, 'purpose_id', 'purpose_id');
    }
} 