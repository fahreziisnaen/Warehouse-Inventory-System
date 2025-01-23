<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use LogsActivity;

    protected $primaryKey = 'project_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'project_id',
        'project_name',
        'vendor_id'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'project_id');
    }

    public function inboundRecords(): HasMany
    {
        return $this->hasMany(InboundRecord::class, 'project_id');
    }

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'project_id');
    }

    public function getRouteKeyName()
    {
        return 'project_id';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['project_name', 'project_id', 'vendor_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'membuat Project baru',
                'updated' => 'mengubah data Project',
                'deleted' => 'menghapus Project',
                default => $eventName
            });
    }
} 