<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purpose extends Model
{
    protected $primaryKey = 'purpose_id';
    
    protected $fillable = ['name'];

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'purpose_id', 'purpose_id');
    }
} 