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

    public function inboundRecord(): BelongsTo
    {
        return $this->belongsTo(InboundRecord::class, 'inbound_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id')
            ->whereNotNull('serial_number')
            ->whereNotNull('part_number_id')
            ->whereHas('partNumber', function ($query) {
                $query->whereHas('brand');
            });
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->item_id)) {
                return false; // Prevent creation if item_id is empty
            }
        });
    }
} 