<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $primaryKey = 'brand_id';
    
    protected $fillable = [
        'brand_name',
        'description'
    ];

    public function partNumbers(): HasMany
    {
        return $this->hasMany(PartNumber::class, 'brand_id');
    }
} 