<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartNumber extends Model
{
    protected $primaryKey = 'part_number_id';
    
    protected $fillable = [
        'brand_id',
        'part_number',
        'description'
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'part_number_id');
    }
} 