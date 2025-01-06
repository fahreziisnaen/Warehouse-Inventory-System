<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundItem extends Model
{
    protected $primaryKey = 'inbound_item_id';
    
    protected $fillable = [
        'inbound_id',
        'item_id',
        'quantity'
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(InboundRecord::class, 'inbound_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
} 