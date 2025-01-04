<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $primaryKey = 'vendor_id';
    
    protected $fillable = [
        'vendor_type_id',
        'vendor_name',
        'address'
    ];

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorType::class, 'vendor_type_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'vendor_id');
    }

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'vendor_id');
    }

    public function scopeSuppliers($query)
    {
        return $query->whereHas('vendorType', function($q) {
            $q->where('type_name', 'Supplier');
        });
    }

    public function scopeCustomers($query)
    {
        return $query->whereHas('vendorType', function($q) {
            $q->where('type_name', 'Customer');
        });
    }
} 