<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'customer_name',
        'address',
        'contact_info'
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'customer_id');
    }

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'customer_id');
    }
} 