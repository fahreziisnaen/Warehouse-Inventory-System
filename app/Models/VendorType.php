<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorType extends Model
{
    protected $primaryKey = 'vendor_type_id';
    
    protected $fillable = [
        'type_name'
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'vendor_type_id');
    }
} 