<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $primaryKey = 'item_id';
    
    protected $fillable = [
        'part_number_id',
        'serial_number',
        'status',
        'manufacture_date'
    ];

    protected $casts = [
        'manufacture_date' => 'date'
    ];

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    public function inboundItems(): HasMany
    {
        return $this->hasMany(InboundItem::class, 'item_id');
    }

    public function outboundItems(): HasMany
    {
        return $this->hasMany(OutboundItem::class, 'item_id');
    }
} 