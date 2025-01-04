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
        'quantity'
    ];

    public function outboundRecord(): BelongsTo
    {
        return $this->belongsTo(OutboundRecord::class, 'outbound_id', 'outbound_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
} 