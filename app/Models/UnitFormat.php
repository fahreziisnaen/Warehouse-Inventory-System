<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitFormat extends Model
{
    protected $primaryKey = 'format_id';
    
    protected $fillable = ['name'];

    public function batchItems(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'format_id');
    }
} 