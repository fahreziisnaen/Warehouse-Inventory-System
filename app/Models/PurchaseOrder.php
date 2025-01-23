<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use LogsActivity;

    protected $primaryKey = 'po_id';
    
    protected $fillable = [
        'po_number',
        'po_date',
        'vendor_id',
        'project_id'
    ];

    protected $casts = [
        'po_date' => 'date'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function inboundRecords(): HasMany
    {
        return $this->hasMany(InboundRecord::class, 'po_id', 'po_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['po_number', 'po_date', 'vendor_id', 'project_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'membuat PO baru',
                'updated' => 'mengubah data PO',
                'deleted' => 'menghapus PO',
                default => $eventName
            });
    }
} 