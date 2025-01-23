<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PartNumber extends Model
{
    use LogsActivity;

    protected $primaryKey = 'part_number_id';
    
    protected $fillable = [
        'brand_id',
        'part_number',
        'description',
        'is_equipment'
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'part_number_id');
    }

    public function batchItems(): HasMany
    {
        return $this->hasMany(BatchItem::class, 'part_number_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['part_number', 'description', 'brand_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'membuat Part Number baru',
                'updated' => 'mengubah data Part Number',
                'deleted' => 'menghapus Part Number',
                default => $eventName
            });
    }
} 