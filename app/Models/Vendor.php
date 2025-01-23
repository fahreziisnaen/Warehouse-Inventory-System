<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model
{
    use LogsActivity;

    protected $primaryKey = 'vendor_id';
    
    protected $fillable = [
        'vendor_name',
        'address'
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'vendor_id', 'vendor_id');
    }

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'vendor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['vendor_name', 'address'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'membuat Vendor baru',
                'updated' => 'mengubah data Vendor',
                'deleted' => 'menghapus Vendor',
                default => $eventName
            });
    }
} 